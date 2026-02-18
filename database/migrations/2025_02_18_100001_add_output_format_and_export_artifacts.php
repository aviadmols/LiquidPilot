<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('agent_runs', function (Blueprint $table) {
            $table->string('output_format', 32)->default('full_zip')->after('selected_section_handle');
        });

        Schema::table('exports', function (Blueprint $table) {
            $table->string('zip_path')->nullable()->change();
            $table->string('template_json_path')->nullable()->after('zip_path');
            $table->string('media_archive_path')->nullable()->after('template_json_path');
        });
    }

    public function down(): void
    {
        Schema::table('agent_runs', function (Blueprint $table) {
            $table->dropColumn('output_format');
        });
        Schema::table('exports', function (Blueprint $table) {
            $table->string('zip_path')->nullable(false)->change();
            $table->dropColumn(['template_json_path', 'media_archive_path']);
        });
    }
};
