<?php

namespace DrBalcony\NovaCommon\Handlers;

use Illuminate\Contracts\Debug\ExceptionHandler as ExceptionHandlerInterface;
use DrBalcony\NovaCommon\Services\RabbitMQLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;
use Laravel\Prompts\Output\ConsoleOutput;
use Psr\Log\LogLevel;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Symfony\Component\HttpFoundation\Response as ResponseAlias;

class ExceptionHandler implements ExceptionHandlerInterface
{
    public function report(Throwable $e)
    {
        if (!method_exists($e, 'shouldReport') || $e->shouldReport())
            RabbitMQLogger::log(
                'Exception occurred: ' . $e->getMessage(), // Message
                [
                    'timestamp' => now()->timestamp,
                    'level' => method_exists($e, 'getLevel') ? $e->getLevel() : LogLevel::ERROR,
                    'service' => config('app.name'),
                    'message' => $e->getMessage(),
                    'details' => [
                        'code' => method_exists($e, 'getCode') ? $e->getCode() : null,
                        'file' => $e->getFile(),
                        'line' => $e->getLine(),
                        'trace' => $e->getTraceAsString(),
                    ]
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
                $e instanceof ModelNotFoundException => Response::notFound('Record not found.'),
            $e instanceof ValidationException => Response::validationError($e->errors()),
            $e instanceof AuthenticationException => Response::unAuthorized(),
            $e instanceof AuthorizationException => Response::forbidden(),
            default => Response::error(
                message: $this->getErrorMessage($e),
                code: (method_exists($e, 'getCode') && $e->getCode() > 0) ?
                    $e->getCode()
                    : ResponseAlias::HTTP_BAD_REQUEST
            ),
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
     * @param OutputInterface $output
     * @param Throwable $e
     * @return void
     */
    public function renderForConsole($output, Throwable $e)
    {
        $output->writeln('<error>' . $e->getMessage() . '</error>');
        $output->writeln('<comment>' . $e->getTraceAsString() . '</comment>');
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
