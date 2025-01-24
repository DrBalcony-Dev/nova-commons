<?php

namespace DrBalcony\NovaCommon\Enums;

enum Priority: int
{
    case Low = 1;
    case Medium = 5;
    case High = 8;
    case Urgent = 10;
}
