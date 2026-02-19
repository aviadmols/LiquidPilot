<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('theme_revisions', function (Blueprint $table) {
            $table->json('analysis_steps')->nullable()->after('error');
        });
    }

    public function down(): void
    {
        Schema::table('theme_revisions', function (Blueprint $table) {
            $table->dropColumn('analysis_steps');
        });
    }
};
