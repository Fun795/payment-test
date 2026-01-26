<?php

namespace App\Enums\Payments;

use App\Enums\Traits\HasOptions;

enum PaymentStatusEnum: string
{
    use HasOptions;

    case PENDING = 'pending';
    case SUCCESS = 'success';
    case FAILED = 'failed';
}
