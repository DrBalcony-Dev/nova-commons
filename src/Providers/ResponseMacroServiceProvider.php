<?php

namespace DrBalcony\NovaCommon\Providers;

use Illuminate\Http\Response;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\ServiceProvider;

class ResponseMacroServiceProvider extends ServiceProvider
{
    /**
     * Boot the response macro services for the application.
     *
     * @return void
     */
    public function register(): void
    {
        Response::macro('success', function ($data = null, $message = null) {
            return response()->json([
                'success' => true,
                'data' => $data,
                'message' => $message,
            ], Response::HTTP_OK);
        });

        Response::macro('withoutData', function ($message) {
            return response()->json([
                'success' => true,
                'message' => $message,
            ], Response::HTTP_OK);
        });

        Response::macro('paginate', function (LengthAwarePaginator $paginate, $data, $message = '') {
            return response()->json([
                'success' => true,
                'data' => $data,
                'links' => [
                    'first' => $paginate->url(1),
                    'last' => $paginate->url($paginate->lastPage()),
                    'prev' => $paginate->previousPageUrl(),
                    'next' => $paginate->nextPageUrl(),
                ],
                'meta' => [
                    'current_page' => $paginate->currentPage(),
                    'last_page' => $paginate->lastPage(),
                    'path' => $paginate->path(),
                    'per_page' => $paginate->perPage(),
                    'total' => $paginate->total(),
                ],
                'message' => $message,
            ], Response::HTTP_OK);
        });

        Response::macro('created', function ($data, $message = 'Created successfully') {
            return response()->json([
                'success' => true,
                'data' => $data,
                'message' => $message,
            ], Response::HTTP_CREATED);
        });

        Response::macro('badRequest', function ($message = 'Bad request') {
            return response()->json([
                'success' => false,
                'message' => $message,
            ], Response::HTTP_BAD_REQUEST);
        });

        Response::macro('unAuthorized', function ($message = 'Unauthorized. You need to login') {
            return response()->json([
                'success' => false,
                'message' => $message,
            ], Response::HTTP_UNAUTHORIZED);
        });

        Response::macro('forbidden', function ($message = 'This action is forbidden') {
            return response()->json([
                'success' => false,
                'message' => $message,
            ], Response::HTTP_FORBIDDEN);
        });

        Response::macro('notFound', function ($message = 'Not found') {
            return response()->json([
                'success' => false,
                'message' => $message,
            ], Response::HTTP_NOT_FOUND);
        });

        Response::macro('validationError', function ($errors, $message = 'Validation error') {
            return response()->json([
                'success' => false,
                'message' => $message,
                'errors' => $errors,
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        });

        Response::macro('error', function ($message, $code = Response::HTTP_INTERNAL_SERVER_ERROR) {
            return response()->json([
                'success' => false,
                'message' => $message,
            ], $code);
        });
    }
}
