<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Str;

class SnakeCaseRequest
{
    /**
     * Handle an incoming request.
     *
     * @param \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response) $next
     */
    public function handle($request, Closure $next)
    {
        $requestData = $request->all();
        $snakeCaseData = $this::convertKeysToSnakeCase($requestData);
        $request->replace($snakeCaseData);
        return $next($request);
    }

    public static function convertKeysToSnakeCase($data, $CurrentArrayKey = null)
    {
        if (is_array($data)) {
            $snakeCaseData = [];

            foreach ($data as $key => $value) {
                $snakeCaseKey = Str::snake($key);

                if (is_array($value)) {
                    $snakeCaseData[$snakeCaseKey] = self::convertKeysToSnakeCase($value, $snakeCaseKey);
                } else {
                    $snakeCaseData[$snakeCaseKey] = $CurrentArrayKey == 'sort' && is_string($value) ? Str::snake($value) : $value;
                }
            }

            return $snakeCaseData;
        }

        return $data;
    }
}
