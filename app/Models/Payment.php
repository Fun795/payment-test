<?php

namespace App\Models;

use App\Enums\Payments\PaymentStatusEnum;
use App\Observers\PaymentObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

#[ObservedBy([PaymentObserver::class])]
class Payment extends Model
{
    use HasUuids;
    protected $primaryKey = 'uuid';
    protected $table = 'payments';

    protected $fillable = [
        'guid',
        'amount',
        'currency',
        'status',
    ];

    protected static function booted()
    {
        static::creating(function ($model) {
                $model->status = $model->status ?? PaymentStatusEnum::PENDING->value;
        });

    }
}
