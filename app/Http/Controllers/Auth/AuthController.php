<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\BackChannelLogoutRequest;
use App\Http\Requests\Auth\CallbackRequest;
use App\Http\Requests\Auth\GetAuthUrlRequest;
use App\Http\Requests\Auth\GetProfileUrlRequest;
use App\Http\Requests\Auth\GetTokenRequest;
use App\Http\Requests\Auth\LogoutRequest;
use App\Http\Requests\Auth\RefreshTokenRequest;
use App\Services\Keycloak\KeycloakService;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use KeycloakGuard\Token;

class AuthController extends Controller
{
    public function test(Request $request)
    {
        return response()->json('this is protected auth route');
    }

    public function getLoginUrl(GetAuthUrlRequest $request): \Illuminate\Http\JsonResponse
    {
        $redirectUri = $request->input('redirect_uri');
        $state = Str::uuid()->toString();
        $nonce = Str::uuid()->toString();
        $codeVerifier = $this->base64url(random_bytes(32));
        $codeChallenge = $this->base64url(hash('sha256', $codeVerifier, true));

        // Храним данные на 10 минут
        Cache::put("oidc:$state", [
            'code_verifier' => $codeVerifier,
            'nonce' => $nonce,
            'redirect_uri' => $redirectUri,
        ], 600);

        $tokenEndpoint = KeycloakService::getBaseUrl()
            . '/realms/' . config('keycloak.realm_name')
            . '/protocol/openid-connect/auth?';

        $authUrl = $tokenEndpoint . http_build_query([
                'client_id' => 'lk-resident',
                'redirect_uri' => config('app.url') . '/api/auth/callback',
                'response_type' => 'code',
                'response_mode' => 'query',
                'scope' => 'openid',
                'state' => $state,
                'nonce' => $nonce,
                'code_challenge' => $codeChallenge,
                'code_challenge_method' => 'S256',
            ]);

        return response()->json([
            'login_url' => $authUrl,
        ]);
    }

    public function callback(CallbackRequest $request)
    {
        $code = $request->query('code');
        $state = $request->query('state');

        $cacheKey = "oidc:$state";
        $oidcData = Cache::pull($cacheKey); // Удаляем после одного использования
        $redirectUri = $oidcData['redirect_uri'];

        try {
            if (!$oidcData) {
                throw new \RuntimeException('invalid_state');
            }

            $tokenEndpoint = KeycloakService::getBaseUrl()
                . '/realms/' . config('keycloak.realm_name')
                . '/protocol/openid-connect/token';

            $response = Http::asForm()->post($tokenEndpoint, [
                'grant_type' => 'authorization_code',
                'code' => $code,
                'redirect_uri' => config('app.url') . '/api/auth/callback',
                'client_id' => 'lk-resident',
                'client_secret' => Config::get('keycloak.client_secret_key'),
                'code_verifier' => $oidcData['code_verifier'],
            ]);

            if ($response->failed()) {
                throw new \RuntimeException('token_exchange_failed');
            }

            $responseJson = $response->json();
            $idToken = $responseJson['id_token'] ?? null;

            if (!$idToken) {
                throw new \RuntimeException('Missing id_token');
            }

            //Проверяем nonce
            try {
                $decoded = Token::decode($idToken, config('keycloak.realm_public_key'));
            } catch (\Exception $e) {
                throw new \RuntimeException('invalid_nonce  ');
            }

            if (($decoded->nonce ?? null) !== $oidcData['nonce']) {
                throw new \RuntimeException('invalid_nonce');
            }

            // Храним данные на 10 минут
            $code = Str::uuid()->toString();
            Cache::put("oidc:$code", $response->json(), 600);
        } catch (\Throwable $e) {
            return redirect()->away(
                $redirectUri . '?' . http_build_query([
                    'error' => 'auth_failed',
                    'message' => $e->getMessage(),
                ])
            );
        }

        return redirect()->away($redirectUri . '?code=' . $code);
    }

    public function getToken(GetTokenRequest $request): JsonResponse
    {
        $code = $request->input('code');
        $platform = $request->input('platform');

        $cacheKey = "oidc:$code";
        $oidcData = Cache::pull($cacheKey); // Удаляем после одного использования

        if (!$oidcData || !isset($oidcData['access_token'])) {
            throw new AuthenticationException('Invalid or expired token key');
        }

        if ($platform === 'mobile') {
            return $this->getMobileTokenResponse($oidcData);
        }

        return $this->getWebTokenResponse($oidcData, $this->getSecondLevelDomain($request->getHost()));
    }

    /**
     * @throws AuthenticationException
     * @throws ConnectionException
     */
    public function refreshToken(RefreshTokenRequest $request)
    {
        $platform = $request->input('platform');
        $refreshToken = $platform === 'mobile' ?
            $request->input('refresh_token') :
            $request->cookie('refresh_token');

        if (!$refreshToken) {
            throw new AuthenticationException('No refresh token');
        }

        $tokenEndpoint = KeycloakService::getBaseUrl() . '/realms/' . config('keycloak.realm_name') . '/protocol/openid-connect/token';
        $response = Http::asForm()->post($tokenEndpoint, [
            'grant_type' => 'refresh_token',
            'client_id' => 'lk-resident',
            'client_secret' => Config::get('keycloak.client_secret_key'),
            'refresh_token' => $refreshToken,
        ]);

        if ($response->failed()) {
            throw new AuthenticationException('Не удалось обновить токен');
        }

        $responseJson = $response->json();

        if ($platform === 'mobile') {
            return $this->getMobileTokenResponse($responseJson);
        }

        return $this->getWebTokenResponse($responseJson, $this->getSecondLevelDomain($request->getHost()));
    }

    /**
     * @throws AuthenticationException
     * @throws ConnectionException
     */
    public function logout(LogoutRequest $request)
    {
        $platform = $request->input('platform');
        $refreshToken = $platform === 'mobile' ?
            $request->input('refresh_token') :
            $request->cookie('refresh_token');

        if (!$refreshToken) {
            throw new AuthenticationException('No refresh token');
        }

        $tokenEndpoint = KeycloakService::getBaseUrl() . '/realms/' . config('keycloak.realm_name') . '/protocol/openid-connect/logout';
        $response = Http::asForm()->post($tokenEndpoint, [
            'client_id' => 'lk-resident',
            'client_secret' => Config::get('keycloak.client_secret_key'),
            'refresh_token' => $refreshToken,
        ]);

        if ($response->failed()) {
            return response()->json(['message' => 'Logout failed', 'details' => $response->body()], 400);
        }

        if ($platform === 'mobile') {
            return response()->json(['message' => 'Logged out successfully']);
        }

        return response()->json(['message' => 'Logged out successfully'])
            ->withoutCookie(
                'refresh_token',
                '/',
                '.' . $this->getSecondLevelDomain($request->getHost())
            );
    }

    public function backChannelLogout(BackChannelLogoutRequest $request)
    {
        $logoutToken = $request->input('logout_token');
        $publicKey = config('keycloak.realm_public_key');

        try {
            $decoded = Token::decode($logoutToken, $publicKey);
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage(), 401);
        }

        // Проверка iss
        if ($decoded->iss !== KeycloakService::getBaseUrl() . '/realms/' . config('keycloak.realm_name')) {
            return $this->sendError('Invalid issuer');
        }

        // Проверка aud
        if (!in_array($decoded->aud, explode(',', config('keycloak.client_id')))) {
            return $this->sendError('Invalid audience');
        }

        // Проверка, что это logout-токен
        if (($decoded->typ ?? null) !== 'Logout') {
            return $this->sendError('Not a logout token');
        }

        // Проверка события logout
        if (!isset($decoded->events->{'http://schemas.openid.net/event/backchannel-logout'})) {
            return $this->sendError('Missing logout event');
        }

        // Получаем session id или user id
        $sid = $decoded->sid ?? null;

        Cache::add("revoked_session:$sid", true, config('keycloak.access_token_ttl', 600)); //храним блокированную сессию 10 минут

        return $this->sendSuccessNoContent();
    }

    public function getProfileUrl(GetProfileUrlRequest $request): \Illuminate\Http\JsonResponse
    {
        $redirectUri = $request->input('redirect_uri');
        $tokenEndpoint = KeycloakUrlService::getBaseUrl() . '/realms/' . config('keycloak.realm_name') . '/account?';
        $authUrl = $tokenEndpoint . http_build_query([
                'referrer' => 'lk-resident',
                'redirect_uri' => $redirectUri,
            ]);

        return response()->json([
            'profile_url' => $authUrl,
        ]);
    }

    private function base64url($data)
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    function getSecondLevelDomain($host): string
    {
        // Если это IP или localhost — просто возвращаем как есть
        if (filter_var($host, FILTER_VALIDATE_IP) || $host === 'localhost') {
            return $host;
        }

        $parts = explode('.', $host);

        // Убедимся, что домен достаточно длинный
        if (count($parts) < 2) {
            return $host;
        }

        return $parts[count($parts) - 2] . '.' . $parts[count($parts) - 1];
    }

    function getWebTokenResponse(array $tokens, $cookieDomain): JsonResponse
    {
        return response()->json([
            'access_token' => $tokens['access_token'],
            'expires_in' => $tokens['expires_in'],
            'token_type' => $tokens['token_type'],
            'id_token' => $tokens['id_token'] ?? null,
        ])->withCookie(cookie('refresh_token',
            $tokens['refresh_token'],
            $tokens['refresh_expires_in'] / 60,
            '/lk-resident',
            '.' . $cookieDomain,
            true,
            true,
            false,
            'None'));
    }

    function getMobileTokenResponse(array $tokens): JsonResponse
    {
        return response()->json([
            'access_token' => $tokens['access_token'],
            'expires_in' => $tokens['expires_in'],
            'token_type' => $tokens['token_type'],
            'id_token' => $tokens['id_token'] ?? null,
            'refresh_token' => $tokens['refresh_token'] ?? null,
            'refresh_expires_in' => $tokens['refresh_expires_in'],
        ]);
    }
}
