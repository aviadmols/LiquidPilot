<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $logoInstruction = " If the brand has a logo (logo_path) or logo_design_notes, align section layout and styling with the logo design and use the logo where appropriate.";

        $plan = DB::table('prompt_templates')->where('key', 'HOMEPAGE_PLAN')->first();
        if ($plan && str_contains($plan->template_text ?? '', 'logo_path') === false) {
            DB::table('prompt_templates')->where('key', 'HOMEPAGE_PLAN')->update([
                'template_text' => str_replace(
                    'Use ONLY the provided section handles.',
                    'Use ONLY the provided section handles.' . $logoInstruction,
                    $plan->template_text
                ),
            ]);
        }

        $compose = DB::table('prompt_templates')->where('key', 'SECTION_COMPOSE')->first();
        if ($compose && str_contains($compose->template_text ?? '', 'logo_path') === false) {
            DB::table('prompt_templates')->where('key', 'SECTION_COMPOSE')->update([
                'template_text' => str_replace(
                    'where relevant for text and image hints.',
                    'where relevant for text and image hints. If the brand has a logo (logo_path) or logo_design_notes, align section layout, styling, and CTAs with the logo design and use the logo where appropriate.',
                    $compose->template_text
                ),
            ]);
        }
    }

    public function down(): void
    {
        // Reverting would require storing original text; leave no-op for safety.
    }
};
