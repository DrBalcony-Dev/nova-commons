<?php


declare(strict_types=1);

namespace DrBalcony\NovaCommon\Services\Notification\Strategies;

use DrBalcony\NovaCommon\DTO\NotificationMetadataDTO;
use DrBalcony\NovaCommon\DTO\NotificationRequestDTO;
use DrBalcony\NovaCommon\Enums\Priority;
use DrBalcony\NovaCommon\Exceptions\NotificationValidationException;
use DrBalcony\NovaCommon\Services\Notification\Contracts\NotificationDeliveryStrategyInterface;
use DrBalcony\NovaCommon\Services\Notification\Generators\NotificationPayloadGenerator;
use DrBalcony\NovaCommon\Traits\RabbitMQPublisher;
use Exception;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Email notification delivery strategy
 *
 * Handles email notification delivery via RabbitMQ queue system.
 */
final class EmailNotificationStrategy implements NotificationDeliveryStrategyInterface
{
    use RabbitMQPublisher;

    private const string EMAIL_QUEUE_NAME = 'pulse_email_events';
    private const string SENDER_NAME = 'DrBalcony';
    private const string DEFAULT_EMAIL_SUBJECT = 'DrBalcony Notification';

    private LoggerInterface $logger;

    public function __construct(?LoggerInterface $logger = null)
    {
        $this->logger = $logger ?? new NullLogger();
    }

    /**
     * Send an email notification using either template or content approach
     *
     * @param NotificationRequestDTO $requestDTO The notification request data
     * @return bool Success status of the delivery attempt
     *
     * @throws NotificationValidationException If email address is invalid
     * @throws Exception If RabbitMQ publishing fails
     */
    public function send(NotificationRequestDTO $requestDTO): bool
    {
        $this->logger->info('Attempting to send email notification', [
            'recipient' => $requestDTO->recipient,
            'account_uuid' => $requestDTO->getAccountUuid(),
            'is_template_based' => $requestDTO->isTemplateBased(),
            'template_slug' => $requestDTO->templateSlug,
        ]);

        try {
            // Generate payload with email-specific metadata
            $emailMetadata = new NotificationMetadataDTO(
                data: [],
                senderName: self::SENDER_NAME,
                subject: self::DEFAULT_EMAIL_SUBJECT,
            );

            $payload = NotificationPayloadGenerator::generate($requestDTO, $emailMetadata);

            // Validate email address
            $this->validateEmailAddress($payload->recipient);

            // Send to RabbitMQ
            $this->pushRawToRabbitMQ(
                $payload->toArray(),
                self::EMAIL_QUEUE_NAME,
                properties: [
                    'priority' => Priority::Urgent->value,
                ]
            );

            $this->logger->info('Email notification sent successfully', [
                'recipient' => $payload->recipient,
                'account_id' => $payload->accountId,
                'queue' => self::EMAIL_QUEUE_NAME,
            ]);

            return true;
        } catch (NotificationValidationException $e) {
            $this->logger->error('Email validation failed', [
                'recipient' => $requestDTO->recipient,
                'account_uuid' => $requestDTO->getAccountUuid(),
                'error' => $e->getMessage(),
            ]);
            throw $e;
        } catch (Exception $e) {
            $this->logger->error('Failed to send email notification', [
                'recipient' => $requestDTO->recipient,
                'account_uuid' => $requestDTO->getAccountUuid(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    /**
     * Validate email address format
     *
     * @param string $email The email address to validate
     * @return void
     *
     * @throws NotificationValidationException If email format is invalid
     */
    private function validateEmailAddress(string $email): void
    {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new NotificationValidationException(
                'email',
                $email,
                'Email address must be in valid format'
            );
        }
    }
}
