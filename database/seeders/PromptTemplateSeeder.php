<?php

namespace Database\Seeders;

use App\Models\PromptTemplate;
use Illuminate\Database\Seeder;

class PromptTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $templates = [
            [
                'key' => 'THEME_SUMMARY_MAP',
                'template_text' => "Summarize this theme catalog chunk into a compact JSON with section handles and a brief description of each section's purpose and best use (e.g. hero, features, CTA, gallery) so the planner can choose the best homepage structure.\n\nCatalog chunk:\n{{ catalog_chunk }}\n\nOutput valid JSON only.",
                'input_schema_json' => ['catalog_chunk' => 'string'],
                'output_schema_json' => ['sections' => 'array'],
                'version' => '1',
                'is_active' => true,
                'default_model_name' => 'openai/gpt-4o-mini',
            ],
            [
                'key' => 'THEME_SUMMARY_REDUCE',
                'template_text' => "Merge these theme summary chunks into one compact theme profile. Focus on section purpose and best use (hero, features, CTA, etc.) so the planner can choose the best homepage structure. Include all section handles and a short overall description.\n\nSummaries:\n{{ summaries }}\n\nOutput valid JSON only.",
                'input_schema_json' => ['summaries' => 'string'],
                'output_schema_json' => ['section_handles' => 'array', 'description' => 'string'],
                'version' => '1',
                'is_active' => true,
                'default_model_name' => 'openai/gpt-4o-mini',
            ],
            [
                'key' => 'HOMEPAGE_PLAN',
                'template_text' => "You are planning a Shopify theme homepage. Choose sections that create a conversion-focused homepage with clear visual hierarchy. Use the brand's tone_of_voice and audience from the brand context. Order sections for the best visual flow and conversion (e.g. hero first, then value props, then content, then CTA). Use ONLY the provided section handles. Each value in 'sections' MUST be an exact copy of one of the handles from the Available section handles list (no variations: e.g. if the list has 'hero_banner' do not use 'hero'). If the brand has a logo (logo_path) or logo_design_notes, align section layout and styling with the logo design and use the logo where appropriate.\n\nCatalog summary:\n{{ catalog_summary }}\n\nAvailable section handles:\n{{ section_handles }}\n\nBrand context:\n{{ brand }}\n\nAdditional instructions from the user: {{ creative_brief }}\n\nOutput valid JSON with a key 'sections' (array of section handle strings in order).",
                'input_schema_json' => ['catalog_summary' => 'object', 'section_handles' => 'array', 'brand' => 'object', 'creative_brief' => 'string'],
                'output_schema_json' => ['sections' => 'array'],
                'version' => '1',
                'is_active' => true,
                'default_model_name' => 'openai/gpt-4o',
            ],
            [
                'key' => 'SECTION_COMPOSE',
                'template_text' => "Generate Shopify section settings and blocks for this section. Use ONLY setting ids and block types from the schema; respect max_blocks and the block order defined in the schema. Write all copy (headings, CTAs, body text) in the brand's tone_of_voice and for the brand's audience; keep headings and CTAs concise and on-brand. Use imagery_style and product_info from the brand (value props, disclaimers) where relevant for text and image hints. If the brand has a logo (logo_path) or logo_design_notes, align section layout, styling, and CTAs with the logo design and use the logo where appropriate.\n\nSection handle: {{ section_handle }}\n\nSchema:\n{{ section_schema }}\n\nBrand:\n{{ brand }}\n\nAdditional instructions from the user: {{ creative_brief }}\n\nOutput valid JSON with keys: settings (object), blocks (object), block_order (array).",
                'input_schema_json' => ['section_handle' => 'string', 'section_schema' => 'string', 'brand' => 'object', 'creative_brief' => 'string'],
                'output_schema_json' => ['settings' => 'object', 'blocks' => 'object', 'block_order' => 'array'],
                'version' => '1',
                'is_active' => true,
                'default_model_name' => 'openai/gpt-4o',
            ],
            [
                'key' => 'MEDIA_PLAN',
                'template_text' => "List image assets required for the given section settings. Output JSON with key 'assets' (array of { purpose, width, height }).",
                'input_schema_json' => ['sections' => 'object'],
                'output_schema_json' => ['assets' => 'array'],
                'version' => '1',
                'is_active' => true,
                'default_model_name' => 'openai/gpt-4o-mini',
            ],
            [
                'key' => 'JSON_FIX',
                'template_text' => "Repair the following invalid JSON. Return only valid JSON, no explanation.\n\nInvalid JSON:\n{{ invalid_json }}",
                'input_schema_json' => ['invalid_json' => 'string'],
                'output_schema_json' => null,
                'version' => '1',
                'is_active' => true,
                'default_model_name' => 'openai/gpt-4o-mini',
            ],
        ];

        foreach ($templates as $t) {
            PromptTemplate::updateOrCreate(
                ['key' => $t['key']],
                $t
            );
        }
    }
}
