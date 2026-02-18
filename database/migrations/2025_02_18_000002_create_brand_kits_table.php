<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('brand_kits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('brand_name')->nullable();
            $table->string('brand_type')->nullable();
            $table->string('industry')->nullable();
            $table->string('tone_of_voice')->nullable();
            $table->string('language', 16)->nullable();
            $table->json('colors_json')->nullable();
            $table->json('typography_json')->nullable();
            $table->json('imagery_style_json')->nullable();
            $table->json('audience_json')->nullable();
            $table->json('product_info_json')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('brand_kits');
    }
};
