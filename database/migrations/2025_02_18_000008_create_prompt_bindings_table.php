<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('prompt_bindings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('prompt_template_id')->constrained()->cascadeOnDelete();
            $table->foreignId('model_config_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['project_id', 'prompt_template_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('prompt_bindings');
    }
};
