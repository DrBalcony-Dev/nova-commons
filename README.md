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

8. Rabbit-mq listener:
   ```bash
   // listen custom queue or the 'default'
   php artisan nova-common:listen-rabbitmq {queue?}
   ```

9. RabbitmqPublisher Service:
   It's a service with singleton connection manager. Use it by dependency injection or creating service object.
   ```php
   class SomeController extends Controller
   {
       protected $rabbitMQPublisher;
   
       public function __construct(RabbitMQPublisher $rabbitMQPublisher)
       {
           // Inject RabbitMQPublisher into the controller
           $this->rabbitMQPublisher = $rabbitMQPublisher;
       }
   
       public function publishMessage(Request $request)
       {
           // Call the publish method from RabbitMQPublisher
           $message = "This is a test message.";
           $queue = "my_queue";  // Specify the queue name
   
           $this->rabbitMQPublisher->publish($message, $queue);
   
           return response()->json(['success' => true, 'message' => 'Message published successfully.']);
       }
   }
   ```

10. Redis Cache Management:
    ```bash
    // Manage Redis cache with various commands
    php artisan redis:cache flush                 # Flush entire Redis cache
    php artisan redis:cache key {key-name}        # Remove a specific cache key
    php artisan redis:cache tag {tag-name}        # Remove all cache entries with a specific tag
    ```

11. NovaBaseModel with Caching:
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