<?php

namespace Database\Seeders;

use App\Models\SystemSetting;
use Illuminate\Database\Seeder;

class PaymentSettingsSeeder extends Seeder
{
    public function run(): void
    {
        SystemSetting::firstOrCreate(
            ['setting_key' => 'company_upi_id'],
            [
                'setting_value' => 'amplindustries@icici',
                'value_type' => 'string',
                'group' => 'payment',
                'description' => 'Official Company UPI ID for dealer payments',
            ]
        );

        SystemSetting::firstOrCreate(
            ['setting_key' => 'company_name'],
            [
                'setting_value' => 'AMPL Industries Ltd',
                'value_type' => 'string',
                'group' => 'payment',
                'description' => 'Official Company Account Name',
            ]
        );

        SystemSetting::firstOrCreate(
            ['setting_key' => 'company_bank_details'],
            [
                'setting_value' => json_encode([
                    'bank_name' => 'ICICI Bank',
                    'account_no' => '001105001234',
                    'ifsc_code' => 'ICIC0000011',
                    'branch' => 'Head Office Branch',
                ]),
                'value_type' => 'json',
                'group' => 'payment',
                'description' => 'Official Company Bank Account Details',
            ]
        );
    }
}
