<?php

namespace App\Jobs;

use App\Domain\Media\MediaGenerator;
use App\Models\AgentRun;
use App\Models\BrandKit;
use App\Models\MediaAsset;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\File;

class GenerateMediaJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public int $agentRunId) {}

    public function handle(MediaGenerator $generator): void
    {
        $run = AgentRun::findOrFail($this->agentRunId);
        $extractedPath = $run->themeRevision->extracted_path;
        $zygDir = $extractedPath . DIRECTORY_SEPARATOR . config('theme.zyg_dir', '.zyg');
        $manifestPath = $zygDir . DIRECTORY_SEPARATOR . config('theme.zyg_media_manifest_filename', 'media_manifest.json');
        $required = [];
        if (is_file($manifestPath)) {
            $data = json_decode(File::get($manifestPath), true);
            $required = $data['assets'] ?? [];
        }
        $brand = BrandKit::where('project_id', $run->project_id)->first();
        $colors = $brand?->colors_json ?? ['primary' => '#4F46E5', 'secondary' => '#7C3AED'];
        $imageryVibe = $this->imageryStyleLabel($brand);
        foreach ($required as $i => $asset) {
            $purpose = $asset['purpose'] ?? 'asset_' . $i;
            $width = $asset['width'] ?? 1200;
            $height = $asset['height'] ?? 800;
            $label = $imageryVibe ? $purpose . ' – ' . $imageryVibe : $purpose;
            $filename = $purpose . '.png';
            $relPath = $generator->generate($extractedPath, $filename, $width, $height, $colors, $label);
            MediaAsset::create([
                'agent_run_id' => $run->id,
                'filename' => $filename,
                'rel_path' => str_replace('\\', '/', $relPath),
                'width' => $width,
                'height' => $height,
                'mime' => 'image/png',
                'purpose' => $purpose,
                'status' => 'ready',
            ]);
        }
        $run->update(['progress' => 75]);
        ExportThemeJob::dispatch($this->agentRunId);
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
