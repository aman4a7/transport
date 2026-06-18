<?php

namespace App\Domain\Shared\Traits;

use Illuminate\Http\JsonResponse;

trait HasApiResponse
{
    protected function respond(mixed $data = null, string $message = '', int $code = 200, array $extra = []): JsonResponse
    {
        $response = [
            'success' => $code >= 200 && $code < 300,
            'data' => $data,
            'message' => $message,
        ];

        if (isset($extra['meta'])) {
            $response['meta'] = $extra['meta'];
        }

        return response()->json($response, $code);
    }

    protected function respondCreated(mixed $data = null, string $message = 'Created successfully'): JsonResponse
    {
        return $this->respond($data, $message, 201);
    }

    protected function respondUpdated(mixed $data = null, string $message = 'Updated successfully'): JsonResponse
    {
        return $this->respond($data, $message, 200);
    }

    protected function respondDeleted(string $message = 'Deleted successfully'): JsonResponse
    {
        return $this->respond(null, $message, 200);
    }

    protected function respondError(string $message, int $code = 400, array $errors = []): JsonResponse
    {
        $response = [
            'success' => false,
            'message' => $message,
        ];

        if (! empty($errors)) {
            $response['errors'] = $errors;
        }

        return response()->json($response, $code);
    }

    protected function respondNotFound(string $message = 'Resource not found'): JsonResponse
    {
        return $this->respondError($message, 404);
    }

    protected function respondUnauthorized(string $message = 'Unauthorized'): JsonResponse
    {
        return $this->respondError($message, 401);
    }

    protected function respondForbidden(string $message = 'Forbidden'): JsonResponse
    {
        return $this->respondError($message, 403);
    }
}
