<?php

namespace DrBalcony\NovaCommon\Providers;

use DrBalcony\NovaCommon\Traits\JsonResponseTrait;
use Illuminate\Http\Response;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\ServiceProvider;
use Symfony\Component\HttpFoundation\Response as ResponseAlias;

class ResponseMacroServiceProvider extends ServiceProvider
{
    use JsonResponseTrait;
    /**
     * Boot the response macro services for the application.
     *
     * @return void
     */
    public function register(): void
    {
        Response::macro('success', function ($data = null, $message = null) {
            return $this->sendResponse($data , $message);
        });

        Response::macro('withoutData', function ($message) {
            return $this->sendResponse([] , $message);
        });

        Response::macro('paginate', function (LengthAwarePaginator $paginate , $message = '') {
            return $this->sendPaginatedResponse($paginate , $message);
        });

        Response::macro('created', function ($data, $message = 'Created successfully') {
            return $this->sendResponse($data , $message , ResponseAlias::HTTP_CREATED);
        });

        Response::macro('badRequest', function ($message = 'Bad request') {
            return $this->sendError($message , [] , ResponseAlias::HTTP_BAD_REQUEST);
        });

        Response::macro('unAuthorized', function ($message = 'Unauthorized. You need to login') {
            return $this->sendError($message , [] , ResponseAlias::HTTP_UNAUTHORIZED);
        });

        Response::macro('forbidden', function ($message = 'This action is forbidden') {
            return $this->sendError($message , [] , ResponseAlias::HTTP_FORBIDDEN);
        });

        Response::macro('notFound', function ($message = 'Not found') {
            return $this->sendError($message , [] , ResponseAlias::HTTP_NOT_FOUND);
        });

        Response::macro('validationError', function ($errors, $message = 'Validation error') {
            return $this->sendError($message , $errors , ResponseAlias::HTTP_UNPROCESSABLE_ENTITY);
        });

        Response::macro('error', function ($message, $code = ResponseAlias::HTTP_INTERNAL_SERVER_ERROR) {
            return $this->sendError($message , [] , $code);
        });
    }
}
