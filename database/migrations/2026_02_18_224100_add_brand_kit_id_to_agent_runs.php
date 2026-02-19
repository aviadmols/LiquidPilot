<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('agent_runs', function (Blueprint $table) {
            $table->foreignId('brand_kit_id')->nullable()->after('theme_revision_id')->constrained('brand_kits')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('agent_runs', function (Blueprint $table) {
            $table->dropForeign(['brand_kit_id']);
        });
    }
};
