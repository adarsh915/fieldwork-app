<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $categories = ['Food', 'Hotel', 'Travel', 'Misc', 'Stationery', 'Other'];
        
        foreach ($categories as $cat) {
            DB::table('expense_categories')->insertOrIgnore([
                'name' => $cat,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No need to delete categories on rollback as other tables depend on them
    }
};
