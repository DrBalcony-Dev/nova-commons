<?php

declare(strict_types=1);

namespace DrBalcony\NovaCommon\Services\Notification\Contracts;

use DrBalcony\NovaCommon\DTO\NotificationRequestDTO;
use Exception;

/**
 * Interface for notification delivery strategies
 *
 * This interface defines the contract for all notification delivery mechanisms.
 * Each strategy represents a different communication channel (email, SMS, call, etc.)
 */
interface NotificationDeliveryStrategyInterface
{
    /**
     * Send a notification via the specific channel
     *
     * @param NotificationRequestDTO $requestDTO The notification request data transfer object
     * @return bool Success status of the delivery attempt
     *
     * @throws Exception If the delivery mechanism fails
     */
    public function send(NotificationRequestDTO $requestDTO): bool;
}