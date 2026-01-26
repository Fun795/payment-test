<?php

namespace App\Http\Requests;

use App\Enums\Payments\PaymentCurrencyEnum;
use Illuminate\Foundation\Http\FormRequest;

class CreatePaymentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'amount' => [
                'required',
                'numeric',
                'decimal:0,2',
            ],
            'currency' => [
                'required',
                'string',
                'in:' . implode(',', PaymentCurrencyEnum::values())
            ],
        ];
    }

    public function messages()
    {
        return [
            'currency.in' => 'The selected :attribute must contain one of the values: ' . implode(', ', PaymentCurrencyEnum::values()),
        ];
    }
}
