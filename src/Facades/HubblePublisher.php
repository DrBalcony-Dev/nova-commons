<?php

namespace DrBalcony\NovaCommon\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * Facade for the HubblePublisher  client.
 *
 * @method void publishFullUser(string $uuid)
 * @mixin \DrBalcony\NovaCommon\Services\Hubble\HubblePublisher
 * @see \DrBalcony\NovaCommon\Services\Hubble\HubblePublisher
 */
class HubblePublisher extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor(): string
    {
        return 'app.hubble.publisher';
    }
}
