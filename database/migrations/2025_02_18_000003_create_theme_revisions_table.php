<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('theme_revisions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
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
    }

    public function down(): void
    {
        Schema::dropIfExists('theme_revisions');
    }
};
