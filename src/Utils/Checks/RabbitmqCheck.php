<?php

namespace DrBalcony\NovaCommon\Utils\Checks;

use Exception;
use Illuminate\Support\Facades\Queue;
use Spatie\Health\Checks\Check;
use Spatie\Health\Checks\Result;

class RabbitmqCheck extends Check
{
    /**
     * Execute the RabbitMQ health check
     *
     * @return Result
     */
    public function run(): Result
    {
        $result = Result::make()->shortSummary('Connected to RabbitMQ');

        try {
            $isConnected = $this->checkRabbit();

            if (!$isConnected) {
                $result->shortSummary('RabbitMQ is not connected');
                $result->meta = ['error' => 'RabbitMQ is not connected'];
                $result->failed();
            }
        } catch (Exception $exception) {
            $result->shortSummary('RabbitMQ connection error');
            $result->meta = [
                'error' => $exception->getMessage(),
                'exception' => get_class($exception),
            ];
            $result->failed();
        }

        return $result;
    }

    /**
     * Check if RabbitMQ is connected
     *
     * @return bool
     */
    protected function checkRabbit(): bool
    {
        $connection = Queue::connection('rabbitmq');

        try {
            $connection->size();
            return true;
        } catch (Exception) {
            return false;
        }
    }
}