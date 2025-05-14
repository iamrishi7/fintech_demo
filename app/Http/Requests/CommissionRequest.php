<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CommissionRequest extends FormRequest
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
            'service' => ['required', 'in:payout,aeps,dmt,bbps,lic'],
            'from' => ['required', 'min:1', 'numeric'],
            'to' => ['required', 'min:1', 'numeric'],
            'service_type' => ['required_if:service,aeps', 'string', 'in:CW,MS,AP,BE'],
            'operator_id' => ['required_if:service,bbps', 'exists:operators,id'],
            'plan_id' => ['required', 'exists:plans,id'],
            'role_id' => ['required', 'exists:roles,name'],
            'fixed_charge' => ['required', 'numeric', 'min:0'],
            'is_flat' => ['required', 'boolean'],
            'commission' => ['required', 'numeric', 'min:0'],
        ];
    }
}
