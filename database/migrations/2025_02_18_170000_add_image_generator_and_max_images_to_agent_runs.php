<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('agent_runs', function (Blueprint $table) {
            $table->string('image_generator', 32)->default('placeholder')->after('creative_brief');
            $table->unsignedInteger('max_images_per_run')->nullable()->after('image_generator');
        });
    }

    public function down(): void
    {
        Schema::table('agent_runs', function (Blueprint $table) {
            $table->dropColumn(['image_generator', 'max_images_per_run']);
        });
    }
};
