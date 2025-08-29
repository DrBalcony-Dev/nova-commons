<?php


declare(strict_types=1);

namespace DrBalcony\NovaCommon\Services\Message\Strategies;

use DrBalcony\NovaCommon\DTO\MessageMetadataDTO;
use DrBalcony\NovaCommon\DTO\MessageRequestDTO;
use DrBalcony\NovaCommon\Enums\Priority;
use DrBalcony\NovaCommon\Exceptions\MessageValidationException;
use DrBalcony\NovaCommon\Services\Message\Contracts\MessageDeliveryStrategyInterface;
use DrBalcony\NovaCommon\Services\Message\Generators\MessagePayloadGenerator;
use DrBalcony\NovaCommon\Traits\RabbitMQPublisher;
use Exception;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Email message delivery strategy
 *
 * Handles email message delivery via RabbitMQ queue system.
 */
final class EmailMessageStrategy implements MessageDeliveryStrategyInterface
{
    use RabbitMQPublisher;

    private const  EMAIL_QUEUE_NAME = 'pulse_email_events';
    private const  SENDER_NAME = 'DrBalcony';
    private const  DEFAULT_EMAIL_SUBJECT = 'DrBalcony message';

    private LoggerInterface $logger;

    public function __construct(?LoggerInterface $logger = null)
    {
        $this->logger = $logger ?? new NullLogger();
    }

    /**
     * Send an email message using either template or content approach
     *
     * @param MessageRequestDTO $requestDTO The message request data
     * @return bool Success status of the delivery attempt
     *
     * @throws MessageValidationException If email address is invalid
     * @throws Exception If RabbitMQ publishing fails
     */
    public function send(MessageRequestDTO $requestDTO): bool
    {
        $this->logger->info('Attempting to send email message', [
            'recipient' => $requestDTO->recipient,
            'account_uuid' => $requestDTO->getAccountUuid(),
            'is_template_based' => $requestDTO->isTemplateBased(),
            'template_slug' => $requestDTO->templateSlug,
        ]);

        try {
            // Generate payload with email-specific metadata
            $emailMetadata = new MessageMetadataDTO(
                data: [],
                senderName: self::SENDER_NAME,
                subject: self::DEFAULT_EMAIL_SUBJECT,
            );

            $payload = MessagePayloadGenerator::generate($requestDTO, $emailMetadata);

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

            $this->logger->info('Email message sent successfully', [
                'recipient' => $payload->recipient,
                'account_id' => $payload->accountId,
                'queue' => self::EMAIL_QUEUE_NAME,
            ]);

            return true;
        } catch (MessageValidationException $e) {
            $this->logger->error('Email validation failed', [
                'recipient' => $requestDTO->recipient,
                'account_uuid' => $requestDTO->getAccountUuid(),
                'error' => $e->getMessage(),
            ]);
            throw $e;
        } catch (Exception $e) {
            $this->logger->error('Failed to send email message', [
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
     * @throws MessageValidationException If email format is invalid
     */
    private function validateEmailAddress(string $email): void
    {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new MessageValidationException(
                'email',
                $email,
                'Email address must be in valid format'
            );
        }
    }
}
