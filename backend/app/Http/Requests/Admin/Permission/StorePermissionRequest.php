<?php

namespace App\Http\Requests\Admin\Permission;

use Illuminate\Foundation\Http\FormRequest;

/**
 * FormRequest for creating a new permission
 */
class StorePermissionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     * Only Super Admin can create permissions.
     */
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->hasRole('Super Admin');
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:255',
                'unique:permissions,name',
                'regex:/^[a-z0-9\-\.]+$/', // Only lowercase, numbers, hyphens, and dots
            ],
        ];
    }

    /**
     * Get custom error messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Permission name is required.',
            'name.unique' => 'A permission with this name already exists.',
            'name.max' => 'Permission name cannot exceed 255 characters.',
            'name.regex' => 'Permission name can only contain lowercase letters, numbers, hyphens, and dots (e.g., users.view, employees.manage).',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'name' => 'permission name',
        ];
    }
}
