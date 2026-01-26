<?php

namespace App\Http\Controllers\Payments;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreatePaymentRequest;
use App\Http\Requests\ProcessPaymentRequest;
use App\Http\Resources\PaymentResource;
use App\Services\PaymentService;

class PaymentController extends Controller
{
    protected PaymentService $paymentService;

    public function __construct(PaymentService $paymentService)
    {
        $this->paymentService = $paymentService;
    }

    public function create(CreatePaymentRequest $request)
    {
        $payment = $this->paymentService->create($request->validated());

        return $this->sendSuccess(['uuid' => $payment->uuid], 201);
    }

    public function process(ProcessPaymentRequest $request, $paymentUuid)
    {
        $payment = $this->paymentService->process($paymentUuid, $request->boolean('success'));

        return $this->sendSuccess(new PaymentResource($payment));
    }

    public function get($paymentUuid)
    {
        $payment = $this->paymentService->get($paymentUuid);

        return $this->sendSuccess(new PaymentResource($payment));
    }
}
