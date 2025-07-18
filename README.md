# 📡 Nova Commons Package

## How to Include the Project
0. Configure repository in composer.json

Add the following to your composer.json:
```json
{
   "repositories": [
      {
         "type": "vcs",
         "url": "git@gitlab.com:drbalcony/nova-commons.git"
      }
   ]
}
```
1. Require the package:
   ```bash
   composer require drbalcony/nova-commons
   ```

2. Publish the vendor config file:
   ```bash
   php artisan vendor:publish --provider="DrBalcony\NovaCommon\Providers\NovaCommonServiceProvider"
   ```

3. Register the service provider in:
   ```php
   $this->app->register(NovaCommonServiceProvider::class);
   ```

4. Register Exception Handler:
   ```php
   $this->app->singleton(ExceptionHandler::class, \DrBalcony\NovaCommon\Handlers\ExceptionHandler::class);
   ```

5. JsonResponseTrait:
   ```php
   use DrBalcony\NovaCommon\Traits\JsonResponseTrait;
   
   //inside your class block:
   use JsonResponseTrait;
   // use sendResponse, sendPaginatedResponse, and sendError
   ```

6. ResponseMacroServiceProvider:
   ```php
   use Illuminate\Http\Response;
   // use withoutData, success, paginate, created, unAuthorized, forbidden ... methods
   Response::success(message:'fetched successfully')
   ```

7. RabbitMQLogger:
   ```php
   use DrBalcony\NovaCommon\Services\RabbitMQLogger;
   RabbitMQLogger::log('Account created.', ['user_id' => 489,'name' => 'John Doe'], 'custom-log-queue-name');
   ```

8. RabbitMQ Publisher and Consumer Facades:
   ```php
   use DrBalcony\NovaCommon\Facades\Publisher;
   use DrBalcony\NovaCommon\Facades\Consumer;
   
   // Publish a message to a queue
   Publisher::publish('queue_name', ['message' => 'Hello World'], ['priority' => 1]);
   
   // Consume messages from a queue
   Consumer::consume('queue_name', function($message) {
       // Process the message
       $data = json_decode($message->getBody(), true);
       // Your handling logic here
   }, ['prefetch_count' => 5]);
   
   // Process messages with a timeout
   Consumer::processMessages(30); // Process for 30 seconds
   
   // Stop consuming and close connections
   Consumer::stopConsuming();
   Consumer::close();
   ```

9. RabbitMQ Commands:
   ```bash
   # Publish a message to a queue
   php artisan rabbitmq:publish queue_name '{"key":"value"}' --properties='{"priority":1}'
   
   # Publish test message(s)
   php artisan rabbitmq:publish-test --queue=test_queue --message="Test message" --count=5
   
   # Consume messages from a queue with options
   php artisan rabbitmq:consume --queue=queue_name --sleep=3 --tries=3 --backoff=0 --timeout=60 --prefetch-count=1
   
   # Consume and display test messages
   php artisan rabbitmq:consume-test --queue=test_queue --timeout=30 --count=10
   
   # Test RabbitMQ connection (For publisher client)
   php artisan rabbitmq:test-connection --queue=test_queue
   
   # Test consumer connection
   php artisan rabbitmq:test-consumer-connection --queue=test_queue --timeout=10
   ```

10. Implementing Custom Consumer Job:
    
    First, extend the `ConsumerJob` abstract class:
    ```php
    <?php
    
    namespace App\Jobs;
    
    use DrBalcony\NovaCommon\Jobs\ConsumerJob;
    use App\Listeners\EmailEventListener;
    use App\Listeners\SmsEventListener;
    
    class MyCustomConsumerJob extends ConsumerJob
    {
        /**
         * Define the mapping between queue names and their handler classes.
         *
         * @return array<string, string>
         */
        public function consumers(): array
        {
            return [
                'email_events' => EmailEventListener::class,
                'sms_events' => SmsEventListener::class,
                // Add more queue-to-listener mappings as needed
            ];
        }
    }
    ```
    
    Then, register your consumer job class in the `nova-common.php` config:
    ```php
    // config/nova-common.php
    return [
        'rabbitmq' => [
            // Other configs...
            
            'consume' => [
                // Specify your custom consumer job class
                'job' => \App\Jobs\MyCustomConsumerJob::class,
            ]
        ],
        // Other configs...
    ];
    ```
    
    The listener class should handle the message payload:
    ```php
    <?php
    
    namespace App\Listeners;
    
    class EmailEventListener
    {
        public function __construct(protected array $payload)
        {
        }
        
        public function handle(): void
        {
            // Access the message data
            $data = $this->payload['data'];
            
            // Your message handling logic here
        }
    }
    ```

11. Supervisor Configuration Example:
    ```ini
    [program:rabbitmq-consumer]
    directory=/path/to/your/project
    process_name=%(program_name)s_%(process_num)02d
    command=php artisan rabbitmq:consume --queue=your_queue --verbose --sleep=3 --tries=3 --backoff=300 --timeout=40000 --prefetch-count=1
    numprocs=1
    autostart=true
    autorestart=true
    stopasgroup=true
    killasgroup=true
    stopwaitsecs=3600
    stdout_logfile=/dev/stdout
    stdout_logfile_maxbytes=0
    stderr_logfile=/dev/stderr
    stderr_logfile_maxbytes=0
    ```

12. Redis Cache Management:
    ```bash
    // Manage Redis cache with various commands
    php artisan redis:cache flush                 # Flush entire Redis cache
    php artisan redis:cache key {key-name}        # Remove a specific cache key
    php artisan redis:cache tag {tag-name}        # Remove all cache entries with a specific tag
    ```

13. NovaBaseModel with Caching:
    ```php
    use DrBalcony\NovaCommon\Models\NovaBaseModel;
    
    class YourModel extends NovaBaseModel
    {
        // All models extending NovaBaseModel automatically have:
        // - UUID generation
        // - Cache support with tags
        // - Helper scopes (active, inactive, byAccount, byUser, etc.)
        // - Enhanced search functionality
        
        // Optional: Override cache settings
        protected int $cacheTTL = 3600; // Default: 86400 (24 hours)
        protected bool $enableCache = true; // Default: true
        protected array $cacheTags = ['custom-tag']; // Add custom cache tags
    }
    ```

    ### Basic UUID Operations
    ```php
    // Find a model by UUID (with caching)
    $model = YourModel::findByUuid($uuid);
    
    // Find a model by UUID or throw an exception
    $model = YourModel::findByUuidOrFail($uuid);
    
    // Find multiple models by UUIDs (with caching)
    $models = YourModel::findByUuids([$uuid1, $uuid2]);
    ```

    ### Cached Find Operations
    ```php
    // Find by ID with caching
    $model = YourModel::find($id);
    
    // Manually clear the model's cache
    $model->clearModelCache();
    ```

    ### Scoping Queries
    ```php
    // Search across specified columns
    $results = YourModel::search('query', ['name', 'description'])->get();
    
    // Paginate with dynamic per_page from request
    $paginator = YourModel::query()->paginateWithPerPage(15);
    
    // Filter by status
    $active = YourModel::active()->get();
    $inactive = YourModel::inactive()->get();
    $custom = YourModel::byStatus('pending')->get();
    
    // Filter by relationships
    $accountItems = YourModel::byAccount($accountId)->get();
    $accountItemsByUuid = YourModel::byAccountUuid($accountUuid)->get();
    $userItems = YourModel::byUser($userId)->get();
    $userItemsByUuid = YourModel::byUserUuid($userUuid)->get();
    ```

    ### Working with Cached Attributes
    ```php
    // Example of using cached attributes in your model
    public function getCalculatedValueAttribute()
    {
        return $this->getCachedAttribute('calculated_value', function() {
            // Perform expensive calculation here
            return $this->someExpensiveComputation();
        });
    }
    
    // Usage
    $value = $model->calculated_value;
    ```

    ### UUID Auto-Generation
    UUIDs are automatically generated when models are created:
    ```php
    $model = new YourModel();
    $model->name = 'Test';
    $model->save();
    
    echo $model->uuid; // Automatically generated UUID
    ```

## 🌌 HubbleEventPublisher

The `HubbleEventPublisher` allows your application to publish structured user-related events to  **Hubble**.

### 1. Publish the Configuration File

Run the following Artisan command to publish the config file:

```bash
php artisan vendor:publish --tag=hubble
````

This will create a new config file at:

```
config/hubble-publisher.php
```

---

### 2. Configure the Source Name

Inside the `hubble-publisher.php` config file, set your service source name:

```php
return [
    'source' => env('HUBBLE_PUBLISHER_SOURCE', 'earth'), // e.g. 'earth', 'pulse', 'orbit', etc.
];
```

You may also define it in your `.env` file:

```env
HUBBLE_PUBLISHER_SOURCE=orbit
```

---

### 3. Use the Facade to Publish Events

The `HubblePublisher` facade provides convenient methods to publish event data. For example, to publish a complete user payload:

```php
use DrBalcony\NovaCommon\Facades\HubblePublisher;
$userUuid = '18c5edad-6417-4a81-ab61-ec1b57b9c875'
HubblePublisher::publishFullUser($userUuid);
```

> 🔔 More publishing methods will be added as needed. Refer to the facade implementation for available methods.

