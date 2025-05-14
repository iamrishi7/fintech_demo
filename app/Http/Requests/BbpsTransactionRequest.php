<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;

class BbpsTransactionRequest extends FormRequest
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
            'service_id' => ['required', Rule::exists('services', 'id')->where('name', 'bbps')],
            'operator_id' => ['required', 'exists:operators,id'],
            'amount'      => ['required', 'numeric', 'min:1'],
            'utility_number' => ['required', 'string', 'max:30'],
            'bill_response' => ['required', 'json']
        ];
    }
}
