<?php

namespace App\Http\Requests\Admin\Role;

use Illuminate\Foundation\Http\FormRequest;

/**
 * FormRequest for updating an existing role
 */
class UpdateRoleRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     * Only Super Admin can update roles.
     */
    public function authorize(): bool
    {
        $user = auth()->user();
        if (!auth()->check()) {
            return false;
        }

        if ($user->hasRole('Super Admin')) {
            return true;
        }

        $roleId = $this->route('id');
        $role = \Spatie\Permission\Models\Role::find($roleId);
        if ($role && in_array($role->name, ['Super Admin', 'Admin'])) {
            return false;
        }

        return $user->hasPermissionTo('roles.edit')
            || $user->hasPermissionTo('roles.manage');
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $roleId = $this->route('id');

        return [
            'name' => ['required', 'string', 'max:255', 'unique:roles,name,' . $roleId],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string', 'exists:permissions,name'],
        ];
    }

    /**
     * Get custom error messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Role name is required.',
            'name.unique' => 'A role with this name already exists.',
            'name.max' => 'Role name cannot exceed 255 characters.',
            'permissions.array' => 'Permissions must be provided as an array.',
            'permissions.*.exists' => 'One or more selected permissions do not exist.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'name' => 'role name',
            'permissions' => 'permissions',
        ];
    }
}
