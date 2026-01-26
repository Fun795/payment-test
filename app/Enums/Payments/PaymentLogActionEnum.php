<?php

namespace App\Enums\Payments;

use App\Enums\Traits\HasOptions;

enum PaymentLogActionEnum: string
{
    use HasOptions;

    case SUCCESS = 'processed_success';
    case FAILED = 'processed_failed';
}
