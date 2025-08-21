<?php

declare(strict_types=1);

namespace DrBalcony\NovaCommon\Services\Notification\Strategies;

use DrBalcony\NovaCommon\DTO\NotificationMetadataDTO;
use DrBalcony\NovaCommon\DTO\NotificationPayloadDTO;
use DrBalcony\NovaCommon\DTO\NotificationRequestDTO;
use DrBalcony\NovaCommon\Enums\Priority;
use DrBalcony\NovaCommon\Exceptions\NotificationValidationException;
use DrBalcony\NovaCommon\Services\Notification\Contracts\NotificationDeliveryStrategyInterface;
use DrBalcony\NovaCommon\Services\Notification\Generators\NotificationPayloadGenerator;
use DrBalcony\NovaCommon\Services\PhoneNumberService;
use DrBalcony\NovaCommon\Traits\RabbitMQPublisher;
use Exception;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * SMS notification delivery strategy
 *
 * Handles SMS notification delivery via RabbitMQ queue system with phone number validation.
 */
final class SmsNotificationStrategy implements NotificationDeliveryStrategyInterface
{
    use RabbitMQPublisher;

    private const string SMS_QUEUE_NAME = 'pulse_sms_events';
    private const string SENDER_NAME = 'DrBalcony';

    private LoggerInterface $logger;
    private PhoneNumberService $phoneNumberService;

    public function __construct(?LoggerInterface $logger = null, ?PhoneNumberService $phoneNumberService = null)
    {
        $this->logger = $logger ?? new NullLogger();
        $this->phoneNumberService = $phoneNumberService ?? app(PhoneNumberService::class);
    }

    /**
     * Send an SMS notification using either template or content approach
     *
     * @param NotificationRequestDTO $requestDTO The notification request data
     * @return bool Success status of the delivery attempt
     *
     * @throws NotificationValidationException If phone number is invalid
     * @throws Exception If RabbitMQ publishing fails
     */
    public function send(NotificationRequestDTO $requestDTO): bool
    {
        $this->logger->info('Attempting to send SMS notification', [
            'recipient' => $requestDTO->recipient,
            'account_uuid' => $requestDTO->getAccountUuid(),
            'is_template_based' => $requestDTO->isTemplateBased(),
            'template_slug' => $requestDTO->templateSlug,
        ]);

        try {
            // Generate payload with SMS-specific metadata
            $smsMetadata = new NotificationMetadataDTO(
                data: [],
                senderName: self::SENDER_NAME,
            );

            $payload = NotificationPayloadGenerator::generate($requestDTO, $smsMetadata);

            // Validate and format phone number
            $formattedPhone = $this->validateAndFormatPhoneNumber($payload->recipient);

            // Create new payload with formatted phone number
            $formattedPayload = new NotificationPayloadDTO(
                accountId: $payload->accountId,
                recipient: $formattedPhone,
                metadata: $payload->metadata,
                content: $payload->content,
                template: $payload->template,
                placeholders: $payload->placeholders,
            );

            // Send to RabbitMQ
            $this->pushRawToRabbitMQ(
                $formattedPayload->toArray(),
                self::SMS_QUEUE_NAME,
                properties: [
                    'priority' => Priority::Urgent->value,
                ]
            );

            $this->logger->info('SMS notification sent successfully', [
                'recipient' => $formattedPhone,
                'account_id' => $payload->accountId,
                'queue' => self::SMS_QUEUE_NAME,
            ]);

            return true;
        } catch (NotificationValidationException $e) {
            $this->logger->error('SMS validation failed', [
                'recipient' => $requestDTO->recipient,
                'account_uuid' => $requestDTO->getAccountUuid(),
                'error' => $e->getMessage(),
            ]);
            throw $e;
        } catch (Exception $e) {
            $this->logger->error('Failed to send SMS notification', [
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
     * @throws NotificationValidationException If phone number is invalid
     */
    private function validateAndFormatPhoneNumber(string $phone): string
    {
        if (!$this->phoneNumberService->isValidPhoneNumber($phone)) {
            throw new NotificationValidationException(
                'phone',
                $phone,
                'Phone number must be in valid format'
            );
        }

        return $this->phoneNumberService->formatPhoneNumber($phone);
    }
}