<?php

namespace App\Domain\Route\Enums;

enum RouteStatus: string
{
    case Active = 'active';
    case Inactive = 'inactive';
}
