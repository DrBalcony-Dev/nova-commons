<?php

namespace DrBalcony\NovaCommon\Enums;


enum HubblePublisherEventEnum: string
{
    case FULL_SYNC = 'full_sync';
    case PARTIAL_SYNC = 'partial_sync';
}
