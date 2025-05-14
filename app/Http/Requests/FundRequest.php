<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class FundRequest extends FormRequest
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
            'transaction_id' => ['required', 'unique:fund_requests,transaction_id'],
            'transaction_date' => ['required', 'date', 'before:tomorrow'],
            'amount' => ['required', 'numeric', 'min:1'],
            'user_remarks' => ['nullable', 'string'],
            'bank' => ['required', 'exists:banks,id'],
            'receipt' => ['required', 'file', 'mimes:png,jpg,jpeg'],
        ];
    }
}
