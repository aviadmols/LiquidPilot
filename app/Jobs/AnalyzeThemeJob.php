<?php

namespace App\Jobs;

use App\Domain\Theme\ThemeCatalogLoader;
use App\Domain\Theme\ThemeCatalogWriter;
use App\Domain\Theme\ThemeExtractor;
use App\Domain\Theme\ThemeSignature;
use App\Domain\Theme\ThemeScanner;
use App\Domain\Theme\ThemeZipService;
use App\Models\ThemeCatalog;
use App\Models\ThemeRevision;
use App\Models\ThemeSection;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Extracts theme ZIP, computes signature, loads cached catalog or scans and writes .zyg.
 * Persists ThemeRevision, ThemeCatalog, ThemeSections.
 */
class AnalyzeThemeJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public int $themeRevisionId,
        public bool $forceRescan = false
    ) {}

    public function handle(): void
    {
        $revision = ThemeRevision::findOrFail($this->themeRevisionId);
        $revision->update(['status' => 'analyzing']);

        $zipService = ThemeZipService::fromConfig();
        $zipPath = $zipService->fullPath($revision->zip_path);
        $extractBase = dirname($zipPath);
        $extractedPath = $extractBase . DIRECTORY_SEPARATOR . 'extracted';
        if (!is_dir($extractedPath)) {
            mkdir($extractedPath, 0755, true);
        }

        $extractor = new ThemeExtractor;
        $themeRoot = $extractor->extract($zipPath, $extractedPath);
        $revision->update(['extracted_path' => $themeRoot]);

        $signature = new ThemeSignature;
        $signatureSha256 = $signature->compute($themeRoot);
        $revision->update(['signature_sha256' => $signatureSha256]);

        $loader = new ThemeCatalogLoader;
        $catalog = null;
        if (!$this->forceRescan) {
            $catalog = $loader->loadIfValid($themeRoot, $signatureSha256);
        }

        if ($catalog === null) {
            $scanner = new ThemeScanner;
            $scanResult = $scanner->scan($themeRoot);
            $writer = new ThemeCatalogWriter;
            $writer->write($themeRoot, $scanResult, $signatureSha256, $scanResult['summary'] ?? null);
            $catalog = $scanResult;
        }

        $revision->update([
            'status' => 'ready',
            'error' => null,
            'catalog_path' => config('theme.zyg_dir') . '/' . config('theme.zyg_catalog_filename'),
            'scanned_at' => now(),
        ]);

        ThemeCatalog::updateOrCreate(
            ['theme_revision_id' => $revision->id],
            ['catalog_json' => $catalog, 'version' => config('theme.catalog_version', '1')]
        );

        ThemeSection::where('theme_revision_id', $revision->id)->delete();
        $sections = $catalog['sections'] ?? [];
        foreach ($sections as $s) {
            ThemeSection::create([
                'theme_revision_id' => $revision->id,
                'handle' => $s['handle'] ?? '',
                'schema_json' => $s,
                'presets_json' => $s['presets'] ?? [],
                'metadata_json' => [
                    'max_blocks' => $s['max_blocks'] ?? null,
                    'enabled_on' => $s['enabled_on'] ?? null,
                ],
            ]);
        }
    }
}
