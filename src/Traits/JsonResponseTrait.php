<?php

namespace DrBalcony\NovaCommon\Traits;

use Illuminate\Http\JsonResponse;
use Illuminate\Pagination\LengthAwarePaginator;

trait JsonResponseTrait
{
    public function sendResponse(mixed $result, string $message = '', int $code = 200, array $meta = []): JsonResponse
    {
        $response = [
            'success' => true,
            'message' => $message,
            'data' => $result,
        ];

        if (!empty($meta)) {
            $response['meta'] = $meta;
        }

        return response()->json($response, $code);
    }

    public function sendPaginatedResponse(LengthAwarePaginator $paginator, string $message = ''): JsonResponse
    {
        $meta = [
            'current_page' => $paginator->currentPage(),
            'total_items' => $paginator->total(),
            'per_page' => $paginator->perPage(),
            'total_pages' => $paginator->lastPage(),
        ];

        return $this->sendResponse($paginator->items(), $message, 200, $meta);
    }

    public function sendError(string $error, array $errorMessages = [], int $code = 400): JsonResponse
    {
        $response = [
            'success' => false,
            'message' => $error,
        ];

        if (!empty($errorMessages)) {
            $response['errors'] = $errorMessages;
        }

        return response()->json($response, $code);
    }
}