<?php

namespace DrBalcony\NovaCommon\Traits;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Pagination\LengthAwarePaginator;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\Response;

trait JsonResponseTrait
{
    /**
     * Send a standardized JSON response
     *
     * @param mixed $result
     * @param string $message
     * @param int $code
     * @param array<string,mixed> $meta
     */
    public function sendResponse(
        mixed $result,
        string $message = '',
        int $code = Response::HTTP_OK,
        array $meta = []
    ): JsonResponse {
        $response = [
            'success' => true,
            'message' => $message,
            'data' => $this->transformData($result),
        ];

        if (!empty($meta)) {
            $response['meta'] = $meta;
        }

        return response()->json($response, $code);
    }

    /**
     * Send a paginated JSON response
     *
     * @param LengthAwarePaginator|ResourceCollection $data Paginated data
     * @param string $message
     * @param array<string,mixed> $additionalMeta
     */
    public function sendPaginatedResponse(
        mixed $data,
        string $message = '',
        array $additionalMeta = []
    ): JsonResponse {
        $paginator = $this->resolvePaginator($data);
        $transformedData = $this->transformData($data);

        $meta = array_merge([
            'current_page' => $paginator->currentPage(),
            'total_items' => $paginator->total(),
            'per_page' => $paginator->perPage(),
            'total_pages' => $paginator->lastPage(),
        ], $additionalMeta);

        return $this->sendResponse($transformedData, $message, Response::HTTP_OK, $meta);
    }

    /**
     * Send an error response
     *
     * @param string $error
     * @param array<string,mixed> $errorMessages
     * @param int $code
     */
    public function sendError(
        string $error,
        array $errorMessages = [],
        int $code = Response::HTTP_BAD_REQUEST
    ): JsonResponse {
        $response = [
            'success' => false,
            'message' => $error,
        ];

        if (!empty($errorMessages)) {
            $response['errors'] = $errorMessages;
        }

        return response()->json($response, $code);
    }

    /**
     * Transform data using resources when available
     */
    private function transformData(mixed $data): mixed
    {
        return match (true) {
            $data instanceof ResourceCollection => $data->toArray(request()),
            $data instanceof LengthAwarePaginator => $data->items(),
            default => $data
        };
    }

    /**
     * Resolve the underlying paginator instance
     *
     * @throws InvalidArgumentException
     */
    private function resolvePaginator(mixed $data): LengthAwarePaginator
    {
        return match (true) {
            $data instanceof LengthAwarePaginator => $data,
            $data instanceof ResourceCollection && $data->resource instanceof LengthAwarePaginator => $data->resource,
            default => throw new InvalidArgumentException('Invalid paginated data type'),
        };
    }
}
