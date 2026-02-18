<?php

namespace Tests\Unit\Theme;

use App\Domain\Theme\ThemeExtractor;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class ZipSlipTest extends TestCase
{
    private string $destDir;

    protected function setUp(): void
    {
        parent::setUp();
        $this->destDir = sys_get_temp_dir() . '/theme_extract_test_' . uniqid();
        mkdir($this->destDir, 0755, true);
    }

    protected function tearDown(): void
    {
        if (is_dir($this->destDir)) {
            File::deleteDirectory($this->destDir);
        }
        parent::tearDown();
    }

    /**
     * Ensure paths with .. do not escape the extraction directory.
     */
    public function test_zip_slip_paths_are_rejected(): void
    {
        $zipPath = $this->createMaliciousZip();
        $extractor = new ThemeExtractor;
        $extractor->extract($zipPath, $this->destDir);
        $this->assertFileDoesNotExist($this->destDir . '/../escaped.txt');
        $parentDir = dirname($this->destDir);
        $this->assertDirectoryDoesNotExist($parentDir . '/etc');
        unlink($zipPath);
    }

    private function createMaliciousZip(): string
    {
        $zipPath = sys_get_temp_dir() . '/malicious_' . uniqid() . '.zip';
        $zip = new \ZipArchive;
        $zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);
        $zip->addFromString('../escaped.txt', 'must not appear');
        $zip->addFromString('normal.txt', 'ok');
        $zip->close();
        return $zipPath;
    }
}
