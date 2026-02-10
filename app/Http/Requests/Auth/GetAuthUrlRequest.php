<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class GetAuthUrlRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'redirect_uri' => [
                'required',
                function ($attribute, $value, $fail) {
                    $allowedPrefixes = config('auth.allowed_redirects', []);
                    foreach ($allowedPrefixes as $prefix) {
                        if (str_starts_with($value, $prefix)) {
                            return;
                        }
                    }
                    $fail("{$attribute} contains invalid values");
                },
            ],
        ];
    }
}
