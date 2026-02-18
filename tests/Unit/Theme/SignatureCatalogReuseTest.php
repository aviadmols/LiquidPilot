<?php

namespace Tests\Unit\Theme;

use App\Domain\Theme\ThemeCatalogLoader;
use App\Domain\Theme\ThemeCatalogWriter;
use App\Domain\Theme\ThemeSignature;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class SignatureCatalogReuseTest extends TestCase
{
    private string $themePath;

    protected function setUp(): void
    {
        parent::setUp();
        $this->themePath = sys_get_temp_dir() . '/theme_sig_' . uniqid();
        mkdir($this->themePath, 0755, true);
        mkdir($this->themePath . '/sections', 0755, true);
        file_put_contents($this->themePath . '/sections/header.liquid', "{% schema %}{\"name\":\"Header\"}{% endschema %}");
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->themePath);
        parent::tearDown();
    }

    public function test_loader_returns_null_when_signature_mismatch(): void
    {
        $sig = new ThemeSignature;
        $hash = $sig->compute($this->themePath);
        $writer = new ThemeCatalogWriter;
        $writer->write($this->themePath, ['sections' => []], 'wrong_signature');
        $loader = new ThemeCatalogLoader;
        $this->assertNull($loader->loadIfValid($this->themePath, $hash));
    }

    public function test_loader_returns_catalog_when_signature_matches(): void
    {
        $sig = new ThemeSignature;
        $hash = $sig->compute($this->themePath);
        $catalog = ['sections' => [['handle' => 'header']], 'summary' => []];
        $writer = new ThemeCatalogWriter;
        $writer->write($this->themePath, $catalog, $hash);
        $loader = new ThemeCatalogLoader;
        $loaded = $loader->loadIfValid($this->themePath, $hash);
        $this->assertNotNull($loaded);
        $this->assertSame($catalog['sections'][0]['handle'], $loaded['sections'][0]['handle']);
    }
}
