<?php

namespace App\Http\Requests\Admin\Dealer;

use Illuminate\Foundation\Http\FormRequest;

/**
 * FormRequest for creating a new dealer
 */
class StoreDealerRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->hasPermissionTo('dealers.create');
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'dealer_code' => ['nullable', 'string', 'max:50', 'unique:dealers,dealer_code'],
            'name' => ['required', 'string', 'max:255'],
            'company_name' => ['nullable', 'string', 'max:255'],
            'contact_person' => ['nullable', 'string', 'max:255'],
            'mobile' => ['required', 'digits:10'],
            'alternate_mobile' => ['nullable', 'digits:10'],
            'email' => ['nullable', 'email', 'max:255'],
            'whatsapp' => ['nullable', 'digits:10'],
            'address' => ['nullable', 'string', 'max:1000'],
            'city' => ['nullable', 'string', 'max:100'],
            'state' => ['nullable', 'string', 'max:100'],
            'pincode' => ['nullable', 'digits:6'],
            'credit_limit' => ['nullable', 'numeric', 'min:0', 'max:99999999999.99'],
            'outstanding_balance' => ['nullable', 'numeric', 'min:0', 'max:99999999999.99'],
            'balance_type' => ['nullable', 'in:Db,Cr'],
            'status' => ['required', 'in:active,inactive'],
            'dealer_type' => ['required', 'in:distributor,retailer,wholesaler,dealer'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'gst_number' => ['nullable', 'string', 'max:20', 'regex:/^[0-9]{2}[A-Z]{5}[0-9]{4}[A-Z]{1}[1-9A-Z]{1}Z[0-9A-Z]{1}$/'],
            'pan_number' => ['nullable', 'string', 'max:10', 'regex:/^[A-Z]{5}[0-9]{4}[A-Z]{1}$/'],
            'assigned_to' => ['nullable', 'exists:users,id'],
        ];
    }

    /**
     * Get custom error messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Dealer name is required.',
            'mobile.required' => 'Mobile number is required.',
            'mobile.digits' => 'Mobile number must be exactly 10 digits.',
            'alternate_mobile.digits' => 'Alternate mobile must be exactly 10 digits.',
            'whatsapp.digits' => 'WhatsApp number must be exactly 10 digits.',
            'email.email' => 'Please enter a valid email address.',
            'pincode.digits' => 'Pincode must be exactly 6 digits.',
            'gst_number.regex' => 'Invalid GST number format.',
            'pan_number.regex' => 'Invalid PAN number format.',
            'dealer_code.unique' => 'This dealer code is already in use.',
            'assigned_to.exists' => 'Selected user does not exist.',
            'status.required' => 'Status is required.',
            'dealer_type.required' => 'Dealer type is required.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'dealer_code' => 'dealer code',
            'name' => 'dealer name',
            'company_name' => 'company name',
            'contact_person' => 'contact person',
            'mobile' => 'mobile number',
            'alternate_mobile' => 'alternate mobile',
            'whatsapp' => 'WhatsApp number',
            'gst_number' => 'GST number',
            'pan_number' => 'PAN number',
            'assigned_to' => 'assigned user',
        ];
    }
}
