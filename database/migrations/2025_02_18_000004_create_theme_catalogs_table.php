<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('theme_catalogs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('theme_revision_id')->constrained()->cascadeOnDelete();
            $table->json('catalog_json')->nullable();
            $table->string('version', 16)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('theme_catalogs');
    }
};
