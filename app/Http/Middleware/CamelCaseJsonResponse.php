<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Collection;

class CamelCaseJsonResponse
{
    /**
     * Handle an incoming request.
     *
     * @param \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response) $next
     */
    public function handle($request, Closure $next)
    {
        $response = $next($request);

        if ($response instanceof JsonResponse) {
            $responseData = $response->getData();

            if ($responseData instanceof Collection) {
                $responseData = $responseData->toArray();
            } elseif (is_object($responseData)) {
                $responseData = (array)$responseData;
            }

            $transformedData = $this->convertKeysToCamelCase($responseData);

            $response->setData($transformedData);
        }

        return $response;
    }

    private function convertKeysToCamelCase($data)
    {
        if (is_array($data)) {
            $result = [];

            foreach ($data as $key => $value) {
                $camelCaseKey = lcfirst(str_replace(' ', '', ucwords(str_replace('_', ' ', $key))));
                $result[$camelCaseKey] = is_array($value) ? $this->convertKeysToCamelCase($value) : (is_object($value) ? $this->convertKeysToCamelCase((array)$value) : $value);
            }

            return $result;
        } else {
            return $data;
        }
    }
}
