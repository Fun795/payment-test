<?php

namespace App\Models;

use App\Enums\Payments\PaymentStatusEnum;
use Illuminate\Database\Eloquent\Model;

class PaymentLog extends Model
{
    protected $table = 'payments_logs';

    protected $fillable = [
        'id',
        'payment_uuid',
        'action',
    ];
}
