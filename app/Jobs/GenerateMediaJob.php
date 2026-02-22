<?php

namespace App\Jobs;

use App\Domain\Agent\AIProgressLogger;
use App\Domain\Media\MediaGenerator;
use App\Domain\Media\NanobannaImageGenerator;
use App\Models\AgentRun;
use App\Models\BrandKit;
use App\Models\MediaAsset;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class GenerateMediaJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public int $agentRunId) {}

    public function handle(MediaGenerator $placeholderGenerator): void
    {
        Log::info('GenerateMediaJob started', ['agent_run_id' => $this->agentRunId]);

        $run = AgentRun::findOrFail($this->agentRunId);
        AIProgressLogger::forRun($run->id)->log('generate_media', 'info', 'GenerateMedia job started', []);

        try {
            $extractedPath = $run->themeRevision->extracted_path;
            $zygDir = $extractedPath . DIRECTORY_SEPARATOR . config('theme.zyg_dir', '.zyg');
            $manifestPath = $zygDir . DIRECTORY_SEPARATOR . config('theme.zyg_media_manifest_filename', 'media_manifest.json');
            $required = [];
            if (is_file($manifestPath)) {
                $data = json_decode(File::get($manifestPath), true);
                $required = $data['assets'] ?? [];
            }

            $generator = $run->image_generator === AgentRun::IMAGE_GENERATOR_NANOBANNA
                ? new NanobannaImageGenerator()
                : $placeholderGenerator;

            $maxGenerate = $run->max_images_per_run !== null
                ? min($run->max_images_per_run, count($required))
                : count($required);

            $brand = $run->resolveBrandKit();
            $colors = $brand?->colors_json ?? ['primary' => '#4F46E5', 'secondary' => '#7C3AED'];
            $imageryVibe = $this->imageryStyleLabel($brand);

            $generatedRelPaths = [];
            $assetsDir = $extractedPath . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'generated';

            foreach ($required as $i => $asset) {
                $purpose = $asset['purpose'] ?? 'asset_' . $i;
                $width = $asset['width'] ?? 1200;
                $height = $asset['height'] ?? 800;
                $label = $imageryVibe ? $purpose . ' – ' . $imageryVibe : $purpose;
                $filename = $purpose . '.png';

                if ($i < $maxGenerate) {
                    $relPath = $generator->generate($extractedPath, $filename, $width, $height, $colors, $label);
                    $generatedRelPaths[] = str_replace('\\', '/', $relPath);
                } else {
                    $reuseIndex = $i % $maxGenerate;
                    $sourceRelPath = $generatedRelPaths[$reuseIndex];
                    $sourceFull = $extractedPath . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $sourceRelPath);
                    $destFull = $assetsDir . DIRECTORY_SEPARATOR . $filename;
                    if (is_file($sourceFull)) {
                        @copy($sourceFull, $destFull);
                    }
                    $relPath = 'assets/generated/' . $filename;
                }

                MediaAsset::create([
                    'agent_run_id' => $run->id,
                    'filename' => $filename,
                    'rel_path' => $relPath,
                    'width' => $width,
                    'height' => $height,
                    'mime' => 'image/png',
                    'purpose' => $purpose,
                    'status' => 'ready',
                ]);
            }

            $run->update(['progress' => 75]);
            ExportThemeJob::dispatch($this->agentRunId);
        } catch (\Throwable $e) {
            Log::error('GenerateMediaJob failed', [
                'agent_run_id' => $this->agentRunId,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            AIProgressLogger::forRun($run->id)->logError('generate_media', $e->getMessage(), [
                'exception' => get_class($e),
            ]);
            $run->update(['status' => AgentRun::STATUS_FAILED, 'error' => $e->getMessage(), 'finished_at' => now()]);
            throw $e;
        }
    }

    private function imageryStyleLabel(?BrandKit $brand): string
    {
        if ($brand === null) {
            return '';
        }
        $json = $brand->imagery_style_json;
        if (is_array($json)) {
            $v = $json['keywords'] ?? $json['vibe'] ?? $json['style'] ?? null;
            if (is_string($v)) {
                return strlen($v) > 80 ? substr(trim($v), 0, 77) . '...' : trim($v);
            }
            $str = json_encode($json);
            return strlen($str) > 80 ? substr($str, 0, 77) . '...' : $str;
        }
        if (is_string($json)) {
            return strlen($json) > 80 ? substr(trim($json), 0, 77) . '...' : trim($json);
        }
        return '';
    }
}
