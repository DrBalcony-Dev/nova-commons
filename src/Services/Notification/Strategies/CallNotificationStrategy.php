<?php

declare(strict_types=1);

namespace DrBalcony\NovaCommon\Services\Notification\Strategies;

use DrBalcony\NovaCommon\DTO\NotificationRequestDTO;
use DrBalcony\NovaCommon\Services\Notification\Contracts\NotificationDeliveryStrategyInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Call notification delivery strategy
 *
 * Placeholder implementation for call-based notifications.
 * This strategy is currently not implemented but provides the interface
 * for future call notification functionality.
 */
final class CallNotificationStrategy implements NotificationDeliveryStrategyInterface
{
    private LoggerInterface $logger;

    public function __construct(?LoggerInterface $logger = null)
    {
        $this->logger = $logger ?? new NullLogger();
    }

    /**
     * Send a call notification
     *
     * @param NotificationRequestDTO $requestDTO The notification request data
     * @return bool Always returns false as call notifications are not yet implemented
     */
    public function send(NotificationRequestDTO $requestDTO): bool
    {
        $this->logger->warning('Call notification attempted but not implemented', [
            'recipient' => $requestDTO->recipient,
            'account_uuid' => $requestDTO->getAccountUuid(),
            'is_template_based' => $requestDTO->isTemplateBased(),
        ]);

        // TODO: Implement call notification logic
        // This would typically involve:
        // 1. Parse and validate phone number
        // 2. Generate voice content from template or direct content
        // 3. Send to voice service provider
        // 4. Handle response and logging

        return false;
    }
}