<?php

declare(strict_types=1);

namespace DrBalcony\NovaCommon\Providers;

use DrBalcony\NovaCommon\Traits\JsonResponseTrait;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\ServiceProvider;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

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

        Response::macro('success', function (
            mixed $data = null,
            ?string $message = null
        ) use ($traitInstance) {
            return $traitInstance->sendResponse(
                result: $data,
                message: $message
            );
        });

        Response::macro('withoutData', function (
            string $message
        ) use ($traitInstance) {
            return $traitInstance->sendResponse(
                result: [],
                message: $message
            );
        });

        Response::macro('paginate', function (
            LengthAwarePaginator|ResourceCollection $paginate ,
            ?string $message
        ) use ($traitInstance) {
            return $traitInstance->sendPaginatedResponse(
                data: $paginate,
                message: $message
            );
        });

        Response::macro('created', function (
            mixed $data,
            string $message = 'Created successfully'
        )  use ($traitInstance) {
            return $traitInstance->sendResponse(
                result: $data,
                message: $message,
                code: SymfonyResponse::HTTP_CREATED
            );
        });

        Response::macro('badRequest', function (
            string $message = 'Bad request'
        ) use ($traitInstance) {
            return $traitInstance->sendError(
                error: $message,
                code: SymfonyResponse::HTTP_BAD_REQUEST
            );
        });

        Response::macro('unAuthorized', function (
            string $message = 'Unauthorized. You need to login'
        ) use ($traitInstance) {
            return $traitInstance->sendError(
                error: $message,
                code: SymfonyResponse::HTTP_UNAUTHORIZED);
        });

        Response::macro('forbidden', function (
            string $message = 'This action is forbidden'
        ) use ($traitInstance) {
            return $traitInstance->sendError(
                error: $message,
                code: SymfonyResponse::HTTP_FORBIDDEN
            );
        });

        Response::macro('notFound', function (
            string $message = 'Not found'
        ) use ($traitInstance) {
            return $traitInstance->sendError(
                error: $message,
                code: SymfonyResponse::HTTP_NOT_FOUND
            );
        });

        Response::macro('validationError', function (
            array $errors,
            string $message = 'Validation error'
        ) use ($traitInstance) {
            return $traitInstance->sendError(
                error: $message,
                errorMessages: $errors,
                code: SymfonyResponse::HTTP_UNPROCESSABLE_ENTITY
            );
        });

        Response::macro('error', function (
            string $message,
            int $code = SymfonyResponse::HTTP_INTERNAL_SERVER_ERROR
        ) use ($traitInstance) {
            return $traitInstance->sendError(
                error: $message,
                code: $code
            );
        });

        Response::macro('methodNotAllowed', function (
            string $message = 'Method not allowed'
        ) use ($traitInstance) {
            return $traitInstance->sendError(
                error: $message,
                code: SymfonyResponse::HTTP_METHOD_NOT_ALLOWED);
        });

        Response::macro('tooManyAttempts', function (
            string $message = 'Too many requests. Please try again later.'
        ) use ($traitInstance) {
            return $traitInstance->sendError(
                error: $message,
                code: SymfonyResponse::HTTP_TOO_MANY_REQUESTS);
        });
    }
}
