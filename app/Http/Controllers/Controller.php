<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;

abstract class Controller
{
    public function sendSuccess($data = [], $code = 200, $message = null): JsonResponse
    {
        $responseBody = array_merge(
            array_filter(['message' => $message]),
            ['data' => $data]
        );

        return response()->json($responseBody, $code);
    }
}
