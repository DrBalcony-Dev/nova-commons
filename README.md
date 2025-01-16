# 📡 nova Commons Package



## How to Include the Project
0. configure repository in composer.json

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
1. require the package:
   ```bash
    composer require drbalcony/nova-commons
   ```

2. publish the vendor config file:
   ```bash
   php artisan vendor:publish --provider="DrBalcony\NovaCommon\Providers\NovaCommonServiceProvider"
   ```

3. Register the service provider in :
      ```bash
      $this->app->register(NovaCommonServiceProvider::class);
      ```

4. Register Exception Handler:
   ```bash
   $this->app->singleton(ExceptionHandler::class, \DrBalcony\NovaCommon\Handlers\ExceptionHandler::class);

   ```

5. JsonResponseTrait:
   ```bash
   use DrBalcony\NovaCommon\Traits\JsonResponseTrait;
   
   //inside your class block:
   use JsonResponseTrait;
   // use sendResponse,  sendPaginatedResponse, and sendError
   
   ```

6. ResponseMacroServiceProvider:
   ```bash
   use Illuminate\Http\Response;
   // use withoutData, success, paginate, created, unAuthorized, forbidden ... methods
   Response::success(message:'fetched successfully')
   
   ```
7. RabbitMQLogger:
    ```bash
   use DrBalcony\NovaCommon\Services\RabbitMQLogger;
   RabbitMQLogger::log('Account created.', ['user_id' => 489,'name' => 'John Doe'], 'custom-log-queue-name');

   ```

8. RabbitMQLogger:
    ```bash
   use DrBalcony\NovaCommon\Services\RabbitMQLogger;
    RabbitMQLogger::log('Account created.', ['user_id' => 489,'name' => 'John Doe'], 'custom-log-queue-name');
   ```
   
9. Rabbit-mq listener:
   ```
   // listen custom queue or the 'default'
   php artisan nova-common:listen-rabbitmq {queue?}
   ```

10. RabbitmqPublisher Service:
   Its' a service with singleton connection manager. Use it by dependency injection or creating service object. 
   ```
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
   

    
