<?php

namespace App\Enums;

enum ServiceListingStatus: string
{
    case Draft = 'draft';
    case Active = 'active';
    case Paused = 'paused';
    case Archived = 'archived';
}
