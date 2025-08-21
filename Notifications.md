# Notification System - DrBalcony NovaCommon

A professional, extensible notification system built for Laravel applications using the Strategy pattern and DTOs for type safety.

## Features

- **Multi-channel support**: Email, SMS, and Call notifications
- **Template-based or content-based** notifications
- **Strategy pattern** for easy extensibility
- **Strong typing** with DTOs and strict type declarations
- **RabbitMQ integration** for reliable message delivery
- **Comprehensive logging** with PSR-3 compatibility
- **Configuration-based account UUID** with environment fallback
- **Backward compatibility** with legacy implementations
- **Professional Laravel standards** with proper DI and service providers

## Installation


 Set up your environment variables:

```env
# Required: Default account UUID
DEFAULT_ACCOUNT_UUID=your-default-account-uuid-here
```

## Basic Usage

### Using the Main Service

```php

$notificationService = app(NotificationService::class);

// Simple notification (uses default account UUID from config)
$request = $notificationService->createRequest(
    recipient: 'user@example.com',
    content: 'Your order has been confirmed!'
);
$success = $notificationService->sendEmail($request);

// With specific account UUID
$request = $notificationService->createRequest(
    recipient: 'user@example.com',
    accountUuid: '12345678-1234-1234-1234-123456789012',
    content: 'Your order has been confirmed!'
);
$success = $notificationService->sendEmail($request);

// Template-based notification
$request = $notificationService->createRequest(
    recipient: 'user@example.com',
    templateSlug: 'order_confirmation',
    placeholders: ['order_id' => '12345', 'customer_name' => 'John Doe']
);
$success = $notificationService->sendEmail($request);
```

### Channel-Specific Methods

```php
// Email notification
$emailRequest = $notificationService->createRequest(
    'user@example.com',
    content: 'Welcome to our platform!'
);
$notificationService->sendEmail($emailRequest);

// SMS notification
$smsRequest = $notificationService->createRequest(
    '+1234567890',
    content: 'Your verification code is: 123456'
);
$notificationService->sendSms($smsRequest);

// Generic channel sending
$notificationService->send(NotificationChannelEnum::EMAIL, $emailRequest);
```

### Using DTOs Directly

```php

$metadata = new NotificationMetadataDTO(
    data: ['priority' => 'high'],
    category: 'user-alert',
    subject: 'Important Update'
);

$request = new NotificationRequestDTO(
    recipient: 'user@example.com',
    accountUuid: null, // Will use config default
    content: 'This is an important update.',
    templateSlug: null,
    placeholders: [],
    metadata: $metadata
);

$notificationService->send(NotificationChannelEnum::EMAIL, $request);
```

## Configuration Management

### Account UUID Handling

The system automatically handles account UUIDs in the following priority:

1. **Explicit UUID**: If provided in the request
2. **Config default**: From `NOTIFICATION_DEFAULT_ACCOUNT_UUID` environment variable
3. **Empty string**: If neither is available (will log a warning)

```php
// Uses explicit UUID
$request = $notificationService->createRequest(
    'user@example.com', 
    '12345678-1234-1234-1234-123456789012'
);

// Uses config default
$request = $notificationService->createRequest('user@example.com');

// Also uses config default
$request = $notificationService->createRequest('user@example.com', null);
```

### Template-Based Notifications

When using templates, the content parameter is ignored:

```php
$request = $notificationService->createRequest(
    recipient: 'user@example.com',
    content: '', // Ignored when using templates
    templateSlug: 'welcome_email',
    placeholders: [
        'user_name' => 'John Doe',
        'activation_link' => 'https://example.com/activate/abc123'
    ]
);

$notificationService->sendEmail($request);
```

## Advanced Usage

### Custom Metadata

```php

$metadata = new NotificationMetadataDTO(
    data: ['utm_source' => 'notification', 'campaign_id' => 'spring2024'],
    category: 'marketing',
    senderName: 'Custom Sender',
    subject: 'Special Offer'
);

$request = $notificationService->createRequest(
    'user@example.com',
    content: 'Check out our latest offers!',
    metadata: $metadata
);
```

### Custom Strategies

Register custom notification strategies:

```php

class SlackNotificationStrategy implements NotificationDeliveryStrategyInterface
{
    public function send(NotificationRequestDTO $requestDTO): bool
    {
        // Implementation for Slack notifications
        return true;
    }
}

$notificationService->registerStrategy('slack', SlackNotificationStrategy::class);
```

### Creating from Arrays

```php
// Create request from array data
$requestData = [
    'recipient' => 'user@example.com',
    'account_uuid' => '12345678-1234-1234-1234-123456789012', // Optional
    'content' => 'Hello World!',
    'template_slug' => null,
    'placeholders' => [],
    'metadata' => [
        'category' => 'system-alert',
        'sender_name' => 'Custom Sender'
    ]
];

$request = $notificationService->createRequestFromArray($requestData);
$notificationService->sendEmail($request);
```

## Legacy Compatibility

### For Existing Code

```php
// Legacy method with separate parameters
$success = $notificationService->sendLegacy(
    channel: 'email',
    recipient: 'user@example.com',
    accountUuid: '12345678-1234-1234-1234-123456789012', // Optional
    content: 'Hello World!',
    templateSlug: 'greeting_template',
    placeholders: ['name' => 'John'],
    metadata: ['category' => 'greeting']
);

// Legacy method with concatenated identifier (old format)
$success = $notificationService->sendLegacyWithIdentifier(
    channel: 'email',
    identifier: 'user@example.com_12345678-1234-1234-1234-123456789012',
    content: 'Hello World!'
);
```

## Error Handling

The system throws specific exceptions for different error scenarios:

```php
use DrBalcony\NovaCommon\Exceptions\{
    InvalidNotificationIdentifierException,
    UnsupportedNotificationMethodException,
    NotificationValidationException
};

try {
    $notificationService->send(NotificationChannelEnum::EMAIL, $request);
} catch (NotificationValidationException $e) {
    // Handle validation errors (invalid email/phone)
    logger()->error('Notification validation failed', ['error' => $e->getMessage()]);
} catch (UnsupportedNotificationMethodException $e) {
    // Handle unsupported notification channels
    logger()->error('Unsupported channel', ['error' => $e->getMessage()]);
} catch (Exception $e) {
    // Handle other errors (RabbitMQ failures, etc.)
    logger()->error('Notification system error', ['error' => $e->getMessage()]);
}
```

## Validation

### Automatic Validation

- **Email format**: Validated using PHP's `filter_var()` with `FILTER_VALIDATE_EMAIL`
- **Phone format**: Validated and formatted using `DrBalcony\NovaCommon\Services\PhoneNumberService`
- **UUID format**: Account UUIDs are validated if provided
- **Template slugs**: Must match pattern `^[a-z0-9_-]+$`

### Manual Validation

```php
// Validate legacy identifier format
$isValid = $notificationService->validateLegacyIdentifier('user@example.com_account123'); // true/false

// Parse legacy identifier
$components = $notificationService->parseLegacyIdentifier('user@example.com_account123');
// Returns: ['recipient' => 'user@example.com', 'accountUuid' => 'account123']
```

## Logging

The system uses PSR-3 compatible logging throughout:

- **INFO level**: Successful operations, processing starts
- **ERROR level**: Validation failures, system errors with full context
- **WARNING level**: Non-critical issues (e.g., call notifications not implemented)
- **DEBUG level**: Strategy registration, detailed processing steps

```php
// All operations are automatically logged with context
// Example log entries:
// [INFO] Processing notification request {"channel":"email","recipient":"user@example.com","account_uuid":"123..."}
// [INFO] Email notification sent successfully {"recipient":"user@example.com","account_id":"123...","queue":"pulse_email_events"}
// [ERROR] Email validation failed {"recipient":"invalid-email","account_uuid":"123...","error":"Invalid email address: invalid-email"}
```

## RabbitMQ Integration

### Message Format

The system maintains backward compatibility with existing RabbitMQ consumers:

```json
{
  "account_id": "12345678-1234-1234-1234-123456789012",
  "to": "user@example.com",
  "metadata": {
    "sender_name": "DrBalcony-earth",
    "subject": "DrBalcony Notification"
  },
  "content": "Your notification content",
  "category": "system-alert"
}
```

For template-based notifications:

```json
{
  "account_id": "12345678-1234-1234-1234-123456789012",
  "to": "user@example.com",
  "metadata": {
    "sender_name": "DrBalcony-earth"
  },
  "template": "welcome_email",
  "placeholders": {
    "user_name": "John Doe",
    "activation_link": "https://example.com/activate/abc123"
  }
}
```

### Queue Names

- **Email**: `pulse_email_events` (configurable via `NOTIFICATION_EMAIL_QUEUE`)
- **SMS**: `pulse_sms_events` (configurable via `NOTIFICATION_SMS_QUEUE`)
- **Call**: `pulse_call_events` (configurable via `NOTIFICATION_CALL_QUEUE`)

### Message Properties

All messages are sent with:
- **Priority**: `Priority::Urgent->value` (from `DrBalcony\NovaCommon\Enums\Priority`)

## Migration from Legacy Code

### Old Implementation

```php
// Old way - concatenated identifier
$payload = NotificationPayloadGenerator::generate(
    'user@example.com_12345',
    $content,
    $template_slug,
    $placeholders,
    $metadata
);

$strategy = $factory->create('email');
$strategy->send('user@example.com_12345', $content, $template_slug, $placeholders, $metadata);
```

### New Implementation

```php
// New way - clean parameters
$request = $notificationService->createRequest(
    recipient: 'user@example.com',
    accountUuid: '12345', // Optional - uses config default if null
    content: $content,
    templateSlug: $template_slug,
    placeholders: $placeholders,
    metadata: NotificationMetadataDTO::fromArray($metadata)
);

$notificationService->sendEmail($request);
```

## Best Practices

1. **Always set `NOTIFICATION_DEFAULT_ACCOUNT_UUID`** in your environment
2. **Use DTOs** instead of arrays for type safety
3. **Handle exceptions** appropriately in your application layer
4. **Use template-based notifications** for consistent messaging
5. **Log notification attempts** for debugging and monitoring
6. **Use dependency injection** rather than direct instantiation
7. **Set account UUID explicitly** for multi-tenant applications
8. **Use config values** for environment-specific settings

## Dependencies

- `DrBalcony\NovaCommon\Enums\Priority`
- `DrBalcony\NovaCommon\Services\PhoneNumberService`
- `DrBalcony\NovaCommon\Traits\RabbitMQPublisher`
- PSR-3 LoggerInterface
- Laravel Framework

## Testing

The system is designed to be easily testable:

```php
// Mock the service in tests
$mockService = Mockery::mock(NotificationService::class);
$mockService->shouldReceive('sendEmail')->andReturn(true);

// Or test with real service using test logger
$testLogger = new TestLogger();
$service = new NotificationService(null, $testLogger);

// Test with different account UUIDs
$request = $service->createRequest('test@example.com', 'test-uuid-123');
$this->assertEquals('test-uuid-123', $request->getAccountUuid());

// Test config fallback
config(['notification.default_account_uuid' => 'default-uuid-456']);
$request = $service->createRequest('test@example.com');
$this->assertEquals('default-uuid-456', $request->getAccountUuid());
```

## Architecture

The system follows these design patterns:

- **Strategy Pattern**: For different notification channels
- **Factory Pattern**: For creating strategy instances
- **DTO Pattern**: For type-safe data transfer
- **Dependency Injection**: For loose coupling
- **Configuration Pattern**: For environment-specific settings
- **Single Responsibility**: Each class has one clear purpose