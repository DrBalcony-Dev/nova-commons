<?php
namespace DrBalcony\NovaCommon\Services\Hubble;


use \Illuminate\Support\Facades\Log;
use DrBalcony\NovaCommon\Enums\HubblePublisherEventEnum;
use \http\Exception\InvalidArgumentException;
use \DrBalcony\NovaCommon\DTO\HubbleRabbitMqMessageDTO;
use Illuminate\Support\Str;
use DrBalcony\NovaCommon\Services\RabbitMQPublisher;


class HubblePublisher
{
    public function __construct(private readonly RabbitMQPublisher $publisher) {
        
    }
    public function publishFullUser(string $uuid) : void{

        if(empty($uuid) || !Str::isUuid($uuid)){
            Log::error("HubblePublisher::publishFullUser  provided uuid is invalid");
            return;
        }
        try {
            $message = $this->createMessage($uuid,HubblePublisherEventEnum::FULL_SYNC);
            $this->publishMessage($message);
        }catch (Throwable $exception){
            Log::error("HubblePublisher::publishFullUser error occurred.",[
                'error' => $exception->getMessage(),
                'code' => $exception->getCode()
            ]);
            return;
        }



    }

    private function createMessage(string $userUuid,HubblePublisherEventEnum $event) : HubbleRabbitMqMessageDTO{
        
        return new HubbleRabbitMqMessageDTO(
            uuid: $userUuid,
            source: config('hubble-publisher.source'),
            event: $event->value,
            queue: $this->resolveQueue($event)
        );

    }

    private function resolveQueue(HubblePublisherEventEnum $eventEnum){
        return match($eventEnum){
            HubblePublisherEventEnum::FULL_SYNC => 'hubble_users_event',
            default => throw new InvalidArgumentException('Hubble queue not implemented.')
        };
    }
    
    private function publishMessage(HubbleRabbitMqMessageDTO $DTO) : void{
        $this->publisher->publish(json_encode([
            'user_uuid' => $DTO->getUserUuid(),
            'source' => $DTO->getSource(),
            'event' => $DTO->getEvent(),
        ]), $DTO->getQueue());
    }
}
