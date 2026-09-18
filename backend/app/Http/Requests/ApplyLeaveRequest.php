<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class ApplyLeaveRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // auth handled by Sanctum middleware
    }

    public function rules(): array
    {
        return [
            'leave_type_id' => ['required', 'integer', 'exists:leave_types,id'],
            'from_date'     => ['required', 'date', 'date_format:Y-m-d', 'after_or_equal:today'],
            'to_date'       => ['required', 'date', 'date_format:Y-m-d', 'gte:from_date'],
            'duration'      => ['nullable', 'in:full,half_am,half_pm'],
            'reason'        => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'from_date.after_or_equal' => 'Leave cannot be applied for a past date.',
            'to_date.gte'              => 'End date must be on or after start date.',
            'leave_type_id.exists'     => 'Invalid leave type selected.',
        ];
    }

    protected function failedValidation(Validator $validator): never
    {
        throw new HttpResponseException(response()->json([
            'success' => false,
            'message' => 'Validation failed.',
            'errors'  => $validator->errors(),
        ], 422));
    }
}
