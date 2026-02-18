<?php

namespace Tests\Unit\Theme;

use App\Domain\Theme\ThemeScanner;
use Tests\TestCase;

class SchemaExtractionTest extends TestCase
{
    public function test_extracts_and_normalizes_section_schema(): void
    {
        $dir = sys_get_temp_dir() . '/theme_scan_' . uniqid();
        mkdir($dir, 0755, true);
        $sectionsDir = $dir . '/sections';
        mkdir($sectionsDir, 0755, true);
        $liquid = <<<'LIQUID'
{% comment %} Header section {% endcomment %}
{% schema %}
{
  "name": "Header",
  "settings": [
    { "type": "text", "id": "title", "label": "Title", "default": "Hello" },
    { "type": "image_picker", "id": "logo", "label": "Logo" }
  ],
  "blocks": [
    { "type": "link", "name": "Link", "settings": [{ "type": "url", "id": "url" }] }
  ],
  "max_blocks": 5
}
{% endschema %}
LIQUID;
        file_put_contents($sectionsDir . '/header.liquid', $liquid);

        $scanner = new ThemeScanner;
        $result = $scanner->scan($dir);
        $this->assertArrayHasKey('sections', $result);
        $this->assertCount(1, $result['sections']);
        $section = $result['sections'][0];
        $this->assertSame('header', $section['handle']);
        $this->assertSame('Header', $section['name']);
        $this->assertCount(2, $section['settings']);
        $this->assertCount(1, $section['blocks']);
        $this->assertSame(5, $section['max_blocks']);

        \Illuminate\Support\Facades\File::deleteDirectory($dir);
    }
}
