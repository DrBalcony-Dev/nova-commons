<?php

namespace DrBalcony\NovaCommon\Handlers;

use Illuminate\Contracts\Debug\ExceptionHandler as ExceptionHandlerInterface;
use DrBalcony\NovaCommon\Services\RabbitMQLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;
use Throwable;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;

class ExceptionHandler implements ExceptionHandlerInterface
{
    public function report(Throwable $exception)
    {
        RabbitMQLogger::log(
            'Exception occurred: ' . $exception->getMessage(), // Message
            [
                'exception' => $exception,  // Include full exception details
                'trace' => $exception->getTraceAsString(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
            ], config('nova-common.rabbitmq.queues.exception')
        );
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @param Request $request
     * @return Response|JsonResponse
     */
    public function render($request, Throwable $e)
    {
        return match (true) {
            $e instanceof NotFoundHttpException,
                $e instanceof RouteNotFoundException,
                $e instanceof ModelNotFoundException  => Response::notFound('Record not found.'),

            $e instanceof ValidationException => Response::validationError($e->errors()),

            $e instanceof AuthenticationException => Response::unAuthorized(),

            $e instanceof AuthorizationException => Response::forbidden(),

            default => Response::error(message: $this->getErrorMessage($e)),
        };
    }

    /**
     * Get the error message for the exception.
     */
    protected function getErrorMessage(Throwable $e): string
    {
        $message = $e->getMessage() ?: 'An error occurred.';
        return !app()->environment('production') ? $message : 'An error occurred.';
    }

    /**
     * Render the exception as a response suitable for the console.
     *
     * @param Throwable|ConsoleOutput $output
     * @param Throwable $exception
     * @return void
     */
    public function renderForConsole($output, Throwable $exception)
    {
        $output->writeln('<error>' . $exception->getMessage() . '</error>');
        $output->writeln('<comment>' . $exception->getTraceAsString() . '</comment>');
    }

    public function shouldReport(Throwable $e)
    {
        return match (true) {
            $e instanceof ValidationException => false,
            $e instanceof \Exception => true,
            default => true,
        };
    }
}
