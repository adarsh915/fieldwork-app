<?php

use App\Models\VehicleType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('vehicle_types', 'slug')) {
            Schema::table('vehicle_types', function (Blueprint $table) {
                $table->string('slug')->nullable()->unique()->after('name');
            });
        }

        // Backfill slugs for existing vehicle types
        $vehicleTypes = VehicleType::all();
        foreach ($vehicleTypes as $type) {
            if (empty($type->slug)) {
                $slug = Str::slug($type->name);
                $baseSlug = $slug;
                $count = 1;
                while (VehicleType::where('slug', $slug)->where('id', '!=', $type->id)->exists()) {
                    $slug = "{$baseSlug}-{$count}";
                    $count++;
                }
                $type->slug = $slug;
                $type->save();
            }
        }

        // Ensure bike and car exist with standard slugs
        if (!VehicleType::where('slug', 'bike')->exists()) {
            VehicleType::create([
                'name' => 'Bike',
                'slug' => 'bike',
                'requires_vehicle_no' => true,
                'is_active' => true,
                'rate_per_km' => 3.50,
            ]);
        }

        if (!VehicleType::where('slug', 'car')->exists()) {
            VehicleType::create([
                'name' => 'Car',
                'slug' => 'car',
                'requires_vehicle_no' => true,
                'is_active' => true,
                'rate_per_km' => 8.00,
            ]);
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('vehicle_types', 'slug')) {
            Schema::table('vehicle_types', function (Blueprint $table) {
                $table->dropColumn('slug');
            });
        }
    }
};
