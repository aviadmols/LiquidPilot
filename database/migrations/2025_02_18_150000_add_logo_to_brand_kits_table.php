<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('brand_kits', function (Blueprint $table) {
            $table->string('logo_path')->nullable()->after('product_info_json');
            $table->text('logo_design_notes')->nullable()->after('logo_path');
        });
    }

    public function down(): void
    {
        Schema::table('brand_kits', function (Blueprint $table) {
            $table->dropColumn(['logo_path', 'logo_design_notes']);
        });
    }
};
