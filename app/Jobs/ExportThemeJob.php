<?php

namespace App\Jobs;

use App\Models\AgentRun;
use App\Models\AgentStep;
use App\Models\Export;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use ZipArchive;

class ExportThemeJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public int $agentRunId) {}

    public function handle(): void
    {
        $run = AgentRun::findOrFail($this->agentRunId);
        $extractedPath = $run->themeRevision->extracted_path;
        $composeStep = AgentStep::where('agent_run_id', $run->id)->where('step_key', 'compose')->first();
        $indexJson = $composeStep?->output_json ?? ['sections' => [], 'order' => []];

        $templatesDir = $extractedPath . DIRECTORY_SEPARATOR . 'templates';
        if (!is_dir($templatesDir)) {
            mkdir($templatesDir, 0755, true);
        }
        $indexPath = $templatesDir . DIRECTORY_SEPARATOR . 'index.json';
        File::put($indexPath, json_encode($indexJson, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        $zygDir = $extractedPath . DIRECTORY_SEPARATOR . config('theme.zyg_dir', '.zyg');
        if (!is_dir($zygDir)) {
            mkdir($zygDir, 0755, true);
        }
        $manifest = [
            'run_id' => $run->id,
            'mode' => $run->mode,
            'sections' => $indexJson['order'] ?? [],
            'generated_at' => now()->toIso8601String(),
        ];
        File::put(
            $zygDir . DIRECTORY_SEPARATOR . config('theme.zyg_generated_manifest_filename', 'generated_manifest.json'),
            json_encode($manifest, JSON_PRETTY_PRINT)
        );

        $exportDir = Storage::disk('local')->path('exports');
        if (!is_dir($exportDir)) {
            mkdir($exportDir, 0755, true);
        }

        $outputFormat = $run->output_format ?? AgentRun::OUTPUT_FULL_ZIP;
        $zipPath = null;
        $sha256 = null;
        $size = null;
        $templateJsonPath = null;
        $mediaArchivePath = null;

        if ($outputFormat === AgentRun::OUTPUT_FULL_ZIP || $outputFormat === AgentRun::OUTPUT_BOTH) {
            $fullZipPath = $exportDir . DIRECTORY_SEPARATOR . $run->id . '_theme.zip';
            $this->zipDirectory($extractedPath, $fullZipPath);
            $zipPath = 'exports/' . $run->id . '_theme.zip';
            $sha256 = hash_file('sha256', $fullZipPath);
            $size = filesize($fullZipPath);
        }

        if ($outputFormat === AgentRun::OUTPUT_MEDIA_AND_JSON || $outputFormat === AgentRun::OUTPUT_BOTH) {
            $templateJsonPath = 'exports/' . $run->id . '_index.json';
            Storage::disk('local')->put(
                $templateJsonPath,
                json_encode($indexJson, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
            );

            $mediaDir = $extractedPath . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'generated';
            if (is_dir($mediaDir)) {
                $mediaZipPath = $exportDir . DIRECTORY_SEPARATOR . $run->id . '_media.zip';
                $this->zipDirectory($mediaDir, $mediaZipPath);
                $mediaArchivePath = 'exports/' . $run->id . '_media.zip';
            }
        }

        Export::create([
            'agent_run_id' => $run->id,
            'zip_path' => $zipPath,
            'template_json_path' => $templateJsonPath,
            'media_archive_path' => $mediaArchivePath,
            'sha256' => $sha256,
            'size' => $size,
        ]);

        $run->update(['status' => AgentRun::STATUS_COMPLETED, 'progress' => 100, 'finished_at' => now()]);
    }

    private function zipDirectory(string $sourcePath, string $zipPath): void
    {
        $zip = new ZipArchive;
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new \RuntimeException('Cannot create ZIP: ' . $zipPath);
        }
        $sourcePath = rtrim(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $sourcePath), DIRECTORY_SEPARATOR);
        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($sourcePath, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::LEAVES_ONLY
        );
        foreach ($files as $file) {
            $filePath = $file->getRealPath();
            $relative = substr($filePath, strlen($sourcePath) + 1);
            $zip->addFile($filePath, $relative);
        }
        $zip->close();
    }
}
