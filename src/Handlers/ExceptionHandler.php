<?php

namespace DrBalcony\NovaCommon\Handlers;

use Illuminate\Contracts\Debug\ExceptionHandler as ExceptionHandlerInterface;
use DrBalcony\NovaCommon\Services\RabbitMQLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;
use Throwable;

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
        // Default JSON response format
        $statusCode = $this->getStatusCode($e);
        $errorMessage = $this->getErrorMessage($e);
        return response()->json([
            'success' => false,
            'message' => $errorMessage,
        ], $statusCode);
    }

    /**
     * Get the HTTP status code for the exception.
     */
    protected function getStatusCode(Throwable $e): int
    {
        return method_exists($e, 'getStatusCode')
            ? $e->getStatusCode()
            : 500; // Default to 500 if no status code exists
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
