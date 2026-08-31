<?php

namespace App\Http\Requests\Admin\Party;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PartyUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('customers.manage');
    }

    public function rules(): array
    {
        $partyId = $this->route('id');

        return [
            'name'            => ['required', 'string', 'max:255'],
            'owner_name'      => ['nullable', 'string', 'max:255'],
            'mobile'          => [
                'required',
                'string',
                'regex:/^[6-9]\d{9}$/',
                Rule::unique('customers', 'mobile')->ignore($partyId),
            ],
            'email'           => ['nullable', 'email', 'max:255'],
            'address'         => ['nullable', 'string', 'max:1000'],
            'city'            => ['nullable', 'string', 'max:100'],
            'state'           => ['nullable', 'string', 'max:100'],
            'pincode'         => ['nullable', 'string', 'max:10'],
            'party_type'      => ['nullable', 'string', 'max:50'],
            'category'        => ['nullable', 'string', 'max:100'],
            'credit_limit'    => ['nullable', 'numeric', 'min:0'],
            'credit_days'     => ['nullable', 'integer', 'min:0'],
            'opening_balance' => ['prohibited'],   // read-only after creation
            'is_active'       => ['boolean'],
            'latitude'        => ['nullable', 'numeric', 'between:-90,90'],
            'longitude'       => ['nullable', 'numeric', 'between:-180,180'],
            'land_area'       => ['nullable', 'string', 'max:100'],
            'employee_ids'    => ['nullable', 'array'],
            'employee_ids.*'  => ['exists:users,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required'      => 'Party name is required.',
            'mobile.required'    => 'Mobile number is required.',
            'mobile.regex'       => 'Enter a valid 10-digit Indian mobile number starting with 6-9.',
            'mobile.unique'      => 'This mobile number is already registered.',
            'credit_limit.min'   => 'Credit limit cannot be negative.',
            'credit_days.min'    => 'Credit days cannot be negative.',
            'opening_balance.prohibited' => 'Opening balance cannot be changed after party creation.',
        ];
    }
}
