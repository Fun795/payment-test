<?php

namespace App\Enums\Payments;

use App\Enums\Traits\HasOptions;

enum PaymentCurrencyEnum: string
{
    use HasOptions;

    case RUB = 'RUB';
    case EUR = 'EUR';
    case USD = 'USD';
}
