# 📡 nova Commons Package



## How to Run the Project

1. require the package:
   ```bash
    composer require drbalcony/nova-common
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

    RabbitMQLogger::log('Critical error occurred.', ['error_code' => 500], 'custom-log-queue-name');

   
   ```
   

    
