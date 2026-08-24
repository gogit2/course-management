<?php

namespace App\Traits;

trait ApiResponseTrait
{
    protected function successResponse(
        mixed $data = null,
        string $message = 'Success',
        int $status = 200
    ) {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
            'errors' => [],
        ], $status);
    }

    protected function errorResponse(
        string $message,
        int $status = 400,
        mixed $data = null,
        array $errors = []
    ) {
        return response()->json([
            'success' => false,
            'message' => $message,
            'data' => $data,
            'errors' => [],
        ], $status);
    }

    protected function notFoundResponse(
        string $resource,
        int|string $id
    ) {
        return $this->errorResponse(
            "{$resource} not found with ID {$id}",
            404
        );
    }
}
