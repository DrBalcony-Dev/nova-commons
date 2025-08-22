<?php

declare(strict_types=1);

namespace DrBalcony\NovaCommon\Services\Message\Contracts;

use DrBalcony\NovaCommon\DTO\MessageRequestDTO;
use Exception;

/**
 * Interface for message delivery strategies
 *
 * This interface defines the contract for all message delivery mechanisms.
 * Each strategy represents a different communication channel (email, SMS, call, etc.)
 */
interface MessageDeliveryStrategyInterface
{
    /**
     * Send a message via the specific channel
     *
     * @param MessageRequestDTO $requestDTO The message request data transfer object
     * @return bool Success status of the delivery attempt
     *
     * @throws Exception If the delivery mechanism fails
     */
    public function send(MessageRequestDTO $requestDTO): bool;
}