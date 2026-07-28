<?php

namespace App\Http\Requests\Admin\Party;

use App\Rules\ActiveFieldWorker;
use Illuminate\Foundation\Http\FormRequest;

class PartyAssignRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('customers.manage');
    }

    public function rules(): array
    {
        return [
            'party_ids'   => ['required', 'array', 'min:1'],
            'party_ids.*' => ['integer', 'distinct', 'exists:customers,id'],
            'employee_id' => ['required', 'integer', new ActiveFieldWorker()],
        ];
    }

    public function messages(): array
    {
        return [
            'party_ids.required'   => 'At least one party must be selected.',
            'party_ids.min'        => 'At least one party must be selected.',
            'party_ids.*.exists'   => 'One or more selected parties do not exist.',
            'employee_id.required' => 'An employee must be selected.',
        ];
    }
}
