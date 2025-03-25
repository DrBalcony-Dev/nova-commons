<?php

namespace DrBalcony\NovaCommon\Handlers;

use DrBalcony\NovaCommon\Services\RabbitMQLogger;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Contracts\Debug\ExceptionHandler as ExceptionHandlerInterface;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Config;
use Illuminate\Validation\ValidationException;
use Psr\Log\LogLevel;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpFoundation\Response as ResponseAlias;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Throwable;

class ExceptionHandler implements ExceptionHandlerInterface
{
    /**
     * Report or log an exception.
     *
     * @param Throwable $e
     * @return void
     * @throws Throwable
     */
    public function report(Throwable $e)
    {
        if (!$this->shouldReport($e)) {
            return;
        }

        // Report to RabbitMQ if enabled
        $this->reportToRabbitMQ($e);

        // Report to Sentry if enabled
        $this->reportToSentry($e);
    }

    /**
     * Report exception to RabbitMQ.
     *
     * @param Throwable $e
     * @return void
     */
    protected function reportToRabbitMQ(Throwable $e): void
    {
        // Check if RabbitMQ reporting is enabled
        $rabbitEnabled = Config::get('nova-common.reporting.rabbitmq.enabled', true);

        if (!$rabbitEnabled) {
            return;
        }

        $queueName = Config::get('nova-common.rabbitmq.queues.exception', 'exceptions');

        RabbitMQLogger::log(
            'Exception occurred: ' . $e->getMessage(),
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
            ],
            $queueName
        );
    }

    /**
     * Report exception to Sentry.
     *
     * @param Throwable $e
     * @return void
     */
    protected function reportToSentry(Throwable $e): void
    {
        // Check if Sentry reporting is enabled
        $sentryEnabled = Config::get('nova-common.reporting.sentry.enabled', false);

        if (!$sentryEnabled || !class_exists('\Sentry\SentrySdk')) {
            return;
        }

        try {
            // Capture exception in Sentry
            \Sentry\captureException($e);

            // Add additional context if needed
            if (Config::get('nova-common.reporting.sentry.include_context', true)) {
                \Sentry\configureScope(function (\Sentry\State\Scope $scope) use ($e): void {
                    $scope->setExtra('file', $e->getFile());
                    $scope->setExtra('line', $e->getLine());
                    $scope->setExtra('service', config('app.name'));

                    // Add user context if authenticated
                    if (auth()->check()) {
                        $scope->setUser([
                            'id' => auth()->id(),
                            'email' => auth()->user()->email ?? null,
                        ]);
                    }
                });
            }
        } catch (\Exception $sentryException) {
            // Prevent a Sentry error from breaking the application
            // Only log this internally, do not create a circular reporting loop
            if (app()->bound('log')) {
                app('log')->error('Sentry reporting failed: ' . $sentryException->getMessage());
            }
        }
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @param Request $request
     * @param Throwable $e
     * @return Response|JsonResponse
     */
    public function render($request, Throwable $e)
    {
        return match (true) {
            $e instanceof RouteNotFoundException,
                $e instanceof NotFoundHttpException => Response::notFound('Route not found.'),

            $e instanceof ModelNotFoundException => Response::notFound('Record not found.'),

            $e instanceof ValidationException => Response::validationError($e->errors()),

            $e instanceof AuthenticationException => Response::unAuthorized(),

            $e instanceof AuthorizationException => Response::forbidden(),

            $e instanceof MethodNotAllowedHttpException => Response::methodNotAllowed(),

            $e instanceof ThrottleRequestsException => Response::toManyAttempts(),

            default => Response::error(
                message: $this->getErrorMessage($e),
                code:
                (method_exists($e, 'getCode') && $e->getCode() > 0) ? $e->getCode() :
                    ((method_exists($e, 'getStatusCode') && $e->getStatusCode()) ? $e->getStatusCode() :
                        ResponseAlias::HTTP_INTERNAL_SERVER_ERROR)
            )
        };
    }

    /**
     * Get the error message for the exception.
     *
     * @param Throwable $e
     * @return string
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

    /**
     * Determine if the exception should be reported.
     *
     * @param Throwable $e
     * @return bool
     */
    public function shouldReport(Throwable $e)
    {
        // Define exceptions that should not be reported
        $dontReport = Config::get('nova-common.reporting.dont_report', [
            ValidationException::class,
        ]);

        // Check if this exception type should be skipped
        foreach ($dontReport as $exceptionType) {
            if ($e instanceof $exceptionType) {
                return false;
            }
        }

        return true;
    }
}