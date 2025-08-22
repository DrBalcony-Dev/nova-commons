<?php

declare(strict_types=1);

namespace DrBalcony\NovaCommon\Services\Message\Strategies;

use DrBalcony\NovaCommon\DTO\MessageRequestDTO;
use DrBalcony\NovaCommon\Services\Message\Contracts\MessageDeliveryStrategyInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Call message delivery strategy
 *
 * Placeholder implementation for call-based messages.
 * This strategy is currently not implemented but provides the interface
 * for future call message functionality.
 */
final class CallMessageStrategy implements MessageDeliveryStrategyInterface
{
    private LoggerInterface $logger;

    public function __construct(?LoggerInterface $logger = null)
    {
        $this->logger = $logger ?? new NullLogger();
    }

    /**
     * Send a call message
     *
     * @param MessageRequestDTO $requestDTO The message request data
     * @return bool Always returns false as call messages are not yet implemented
     */
    public function send(MessageRequestDTO $requestDTO): bool
    {
        $this->logger->warning('Call message attempted but not implemented', [
            'recipient' => $requestDTO->recipient,
            'account_uuid' => $requestDTO->getAccountUuid(),
            'is_template_based' => $requestDTO->isTemplateBased(),
        ]);

        // TODO: Implement call message logic
        // This would typically involve:
        // 1. Parse and validate phone number
        // 2. Generate voice content from template or direct content
        // 3. Send to voice service provider
        // 4. Handle response and logging

        return false;
    }
}