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

    public function sendError($message = '', $code = 400): JsonResponse
    {
        $errors = [];

        if (!empty($message)) {
            $errors = ['message' => $message];
        }

        return response()->json($errors, $code);
    }

    public function sendSuccessNoContent($code = 204): JsonResponse
    {
        return response()->json([], $code);
    }
}
