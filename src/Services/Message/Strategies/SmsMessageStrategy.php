<?php

declare(strict_types=1);

namespace DrBalcony\NovaCommon\Services\Message\Strategies;

use DrBalcony\NovaCommon\DTO\MessageMetadataDTO;
use DrBalcony\NovaCommon\DTO\MessagePayloadDTO;
use DrBalcony\NovaCommon\DTO\MessageRequestDTO;
use DrBalcony\NovaCommon\Enums\Priority;
use DrBalcony\NovaCommon\Exceptions\MessageValidationException;
use DrBalcony\NovaCommon\Services\Message\Contracts\MessageDeliveryStrategyInterface;
use DrBalcony\NovaCommon\Services\Message\Generators\MessagePayloadGenerator;
use DrBalcony\NovaCommon\Services\PhoneNumberService;
use DrBalcony\NovaCommon\Traits\RabbitMQPublisher;
use Exception;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * SMS message delivery strategy
 *
 * Handles SMS message delivery via RabbitMQ queue system with phone number validation.
 */
final class SmsMessageStrategy implements MessageDeliveryStrategyInterface
{
    use RabbitMQPublisher;

    private const  SMS_QUEUE_NAME = 'pulse_sms_events';
    private const  SENDER_NAME = 'DrBalcony';

    private LoggerInterface $logger;
    private PhoneNumberService $phoneNumberService;

    public function __construct(?LoggerInterface $logger = null, ?PhoneNumberService $phoneNumberService = null)
    {
        $this->logger = $logger ?? new NullLogger();
        $this->phoneNumberService = $phoneNumberService ?? app(PhoneNumberService::class);
    }

    /**
     * Send an SMS message using either template or content approach
     *
     * @param MessageRequestDTO $requestDTO The message request data
     * @return bool Success status of the delivery attempt
     *
     * @throws MessageValidationException If phone number is invalid
     * @throws Exception If RabbitMQ publishing fails
     */
    public function send(MessageRequestDTO $requestDTO): bool
    {
        $this->logger->info('Attempting to send SMS message', [
            'recipient' => $requestDTO->recipient,
            'account_uuid' => $requestDTO->getAccountUuid(),
            'is_template_based' => $requestDTO->isTemplateBased(),
            'template_slug' => $requestDTO->templateSlug,
        ]);

        try {
            // Generate payload with SMS-specific metadata
            $smsMetadata = new MessageMetadataDTO(
                data: [],
                senderName: self::SENDER_NAME,
            );

            $payload = MessagePayloadGenerator::generate($requestDTO, $smsMetadata);

            // Validate and format phone number
            $formattedPhone = $this->validateAndFormatPhoneNumber($payload->recipient);

            // Create new payload with formatted phone number
            $formattedPayload = new MessagePayloadDTO(
                accountId: $payload->accountId,
                recipient: $formattedPhone,
                metadata: $payload->metadata,
                content: $payload->content,
                template: $payload->template,
                placeholders: $payload->placeholders,
                attachments: $payload->attachments,
            );

            // Send to RabbitMQ
            $this->pushRawToRabbitMQ(
                $formattedPayload->toArray(),
                self::SMS_QUEUE_NAME,
                properties: [
                    'priority' => Priority::Urgent->value,
                ]
            );

            $this->logger->info('SMS message sent successfully', [
                'recipient' => $formattedPhone,
                'account_id' => $payload->accountId,
                'queue' => self::SMS_QUEUE_NAME,
            ]);

            return true;
        } catch (MessageValidationException $e) {
            $this->logger->error('SMS validation failed', [
                'recipient' => $requestDTO->recipient,
                'account_uuid' => $requestDTO->getAccountUuid(),
                'error' => $e->getMessage(),
            ]);
            throw $e;
        } catch (Exception $e) {
            $this->logger->error('Failed to send SMS message', [
                'recipient' => $requestDTO->recipient,
                'account_uuid' => $requestDTO->getAccountUuid(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    /**
     * Validate and format phone number
     *
     * @param string $phone The phone number to validate and format
     * @return string The formatted phone number
     *
     * @throws MessageValidationException If phone number is invalid
     */
    private function validateAndFormatPhoneNumber(string $phone): string
    {
        if (!$this->phoneNumberService->isValidPhoneNumber($phone)) {
            throw new MessageValidationException(
                'phone',
                $phone,
                'Phone number must be in valid format'
            );
        }

        return $this->phoneNumberService->formatPhoneNumber($phone);
    }
}