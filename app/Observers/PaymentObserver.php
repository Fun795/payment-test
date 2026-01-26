<?php

namespace App\Observers;

use App\Enums\Payments\PaymentLogActionEnum;
use App\Enums\Payments\PaymentStatusEnum;
use App\Models\Payment;
use App\Models\PaymentLog;

class PaymentObserver
{
    /**
     * Handle the Payment "created" event.
     */
    public function created(Payment $payment): void
    {
        //
    }

    /**
     * Handle the Payment "updated" event.
     */
    public function updated(Payment $payment): void
    {
        $action = match ($payment->status) {
            PaymentStatusEnum::SUCCESS->value => PaymentLogActionEnum::SUCCESS->value,
            PaymentStatusEnum::FAILED->value => PaymentLogActionEnum::FAILED->value,
            default => PaymentLogActionEnum::FAILED->value,
        };

        PaymentLog::create([
            'payment_uuid' => $payment->uuid,
            'action' => $action,
        ]);
    }

    /**
     * Handle the Payment "deleted" event.
     */
    public function deleted(Payment $payment): void
    {
        //
    }

    /**
     * Handle the Payment "restored" event.
     */
    public function restored(Payment $payment): void
    {
        //
    }

    /**
     * Handle the Payment "force deleted" event.
     */
    public function forceDeleted(Payment $payment): void
    {
        //
    }
}
