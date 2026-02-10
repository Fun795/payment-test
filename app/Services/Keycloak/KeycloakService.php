<?php

namespace App\Services\Keycloak;

abstract class KeycloakService
{
    protected string $baseUrl;

    public function __construct()
    {
        $this->baseUrl = $this->getBaseUrl();
    }

    public static function getBaseUrl(): string
    {
        $scheme = config('keycloak.use_https') ? 'https' : 'http';
        $host = config('keycloak.host');

        return "{$scheme}://{$host}";
    }
}
