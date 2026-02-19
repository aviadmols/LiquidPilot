<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $driver = Schema::getConnection()->getDriverName();
        if ($driver === 'sqlite') {
            // SQLite: recreate table with nullable project_id (no doctrine/dbal needed)
            Schema::dropIfExists('theme_revisions_new');
            Schema::create('theme_revisions_new', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('project_id')->nullable();
                $table->string('original_filename');
                $table->string('zip_path');
                $table->string('extracted_path')->nullable();
                $table->string('signature_sha256', 64)->nullable();
                $table->string('status', 32)->default('pending');
                $table->text('error')->nullable();
                $table->string('catalog_path')->nullable();
                $table->timestamp('scanned_at')->nullable();
                $table->timestamps();
            });
            DB::statement('INSERT INTO theme_revisions_new (id, project_id, original_filename, zip_path, extracted_path, signature_sha256, status, error, catalog_path, scanned_at, created_at, updated_at) SELECT id, project_id, original_filename, zip_path, extracted_path, signature_sha256, status, error, catalog_path, scanned_at, created_at, updated_at FROM theme_revisions');
            Schema::drop('theme_revisions');
            Schema::rename('theme_revisions_new', 'theme_revisions');
            return;
        }
        Schema::table('theme_revisions', function (Blueprint $table) {
            $table->unsignedBigInteger('project_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        $driver = Schema::getConnection()->getDriverName();
        if ($driver === 'sqlite') {
            Schema::table('theme_revisions', function (Blueprint $table) {
                $table->unsignedBigInteger('project_id')->nullable(false)->change();
            });
            return;
        }
        Schema::table('theme_revisions', function (Blueprint $table) {
            $table->unsignedBigInteger('project_id')->nullable(false)->change();
        });
    }
};
