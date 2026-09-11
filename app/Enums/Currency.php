<?php

namespace App\Enums;

enum Currency: string
{
    case TTD = 'TTD';
    case USD = 'USD';
    case GBP = 'GBP';

    public function minorUnitFactor(): int
    {
        return 100;
    }
}
