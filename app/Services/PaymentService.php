<?php

namespace App\Services;

use App\Enums\Payments\PaymentStatusEnum;
use App\Exceptions\ConflictException;
use App\Models\Payment;

class PaymentService
{
    public function create($data): Payment
    {
        return Payment::create($data);
    }

    /**
     * @throws ConflictException
     */
    public function process($paymentUuid, $success): Payment
    {
        $payment = Payment::findOrFail($paymentUuid);

        if ($payment->status !== PaymentStatusEnum::PENDING->value) {
            throw new ConflictException("Current payment status is invalid");
        }

        $payment->update([
            'status' => $success ? PaymentStatusEnum::SUCCESS->value : PaymentStatusEnum::FAILED->value,
        ]);

        return $payment;
    }

    public function get($paymentUuid): Payment
    {
        return Payment::findOrFail($paymentUuid);
    }
}
