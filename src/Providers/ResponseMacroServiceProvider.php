<?php

namespace DrBalcony\NovaCommon\Providers;

use DrBalcony\NovaCommon\Traits\JsonResponseTrait;
use Illuminate\Http\Resources\Json\ResourceCollection;
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
        $traitInstance = $this;

        Response::macro('success', function ($data = null, $message = null) use ($traitInstance) {
            return $traitInstance->sendResponse($data , $message);
        });

        Response::macro('withoutData', function ($message) use ($traitInstance) {
            return $traitInstance->sendResponse([] , $message);
        });

        Response::macro('paginate', function (LengthAwarePaginator|ResourceCollection $paginate , $message = '') use ($traitInstance) {
            return $traitInstance->sendPaginatedResponse($paginate , $message);
        });

        Response::macro('created', function ($data, $message = 'Created successfully')  use ($traitInstance) {
            return $traitInstance->sendResponse($data , $message , ResponseAlias::HTTP_CREATED);
        });

        Response::macro('badRequest', function ($message = 'Bad request') use ($traitInstance) {
            return $traitInstance->sendError($message , [] , ResponseAlias::HTTP_BAD_REQUEST);
        });

        Response::macro('unAuthorized', function ($message = 'Unauthorized. You need to login') use ($traitInstance) {
            return $traitInstance->sendError($message , [] , ResponseAlias::HTTP_UNAUTHORIZED);
        });

        Response::macro('forbidden', function ($message = 'This action is forbidden') use ($traitInstance) {
            return $traitInstance->sendError($message , [] , ResponseAlias::HTTP_FORBIDDEN);
        });

        Response::macro('notFound', function ($message = 'Not found') use ($traitInstance) {
            return $traitInstance->sendError($message , [] , ResponseAlias::HTTP_NOT_FOUND);
        });

        Response::macro('validationError', function ($errors, $message = 'Validation error') use ($traitInstance) {
            return $traitInstance->sendError($message , $errors , ResponseAlias::HTTP_UNPROCESSABLE_ENTITY);
        });

        Response::macro('error', function ($message, $code = ResponseAlias::HTTP_BAD_REQUEST) use ($traitInstance) {
            return $traitInstance->sendError($message , [] , $code);
        });
    }
}
