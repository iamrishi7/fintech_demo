<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class MerchantAuthRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'aadhaar' => ['required', 'digits:12'],
            'latitude' => ['required', 'between:8,38'],
            'longitude' => ['required', 'between:68,98'],
            'pid_data' => ['required'],
        ];
    }
}
