<?php

namespace DrBalcony\NovaCommon\DTO;

class HubbleRabbitMqMessageDTO
{
    public function __construct(
        private readonly string $uuid,
        private readonly string $source,
        private readonly string $event,
        private readonly string $queue
    ){

    }
    
    public function toArray() : array{
        return [
            'uuid' => $this->uuid,
            'source' => $this->source,
            'event' => $this->event,
            'queue' => $this->queue,
        ];
    }
    
    public function getUserUuid() : string{
        return $this->uuid;
    }
    
    public function getSource() : string{
        return $this->source;
    }

    public function getEvent() : string{
        return $this->event;
    }

    public function getQueue() : string{
        return $this->queue;
    }
}
