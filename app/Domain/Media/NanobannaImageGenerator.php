<?php

namespace App\Domain\Media;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\File;

/**
 * NanoBanana API image generator. Uses text-to-image and polls for result.
 * Requires NANOBANNA_API_KEY in .env.
 */
class NanobannaImageGenerator
{
    private string $baseUrl;

    private ?string $apiKey;

    public function __construct(?string $baseUrl = null, ?string $apiKey = null)
    {
        $this->baseUrl = rtrim($baseUrl ?? config('theme.nanobanna_base_url', 'https://api.nanobananaapi.ai'), '/');
        $this->apiKey = $apiKey ?? config('theme.nanobanna_api_key');
    }

    /**
     * Generate image via NanoBanana API and save to theme. Same signature as MediaGenerator::generate().
     *
     * @param array{primary?: string, secondary?: string, background?: string} $colors
     */
    public function generate(
        string $themeRootPath,
        string $filename,
        int $width,
        int $height,
        array $colors = [],
        ?string $label = null
    ): string {
        if (!$this->apiKey) {
            throw new \RuntimeException('NANOBANNA_API_KEY is not set. Set it in .env or use Placeholder image generator.');
        }

        $themeRootPath = rtrim(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $themeRootPath), DIRECTORY_SEPARATOR);
        $outDir = $themeRootPath . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'generated';
        if (!is_dir($outDir)) {
            mkdir($outDir, 0755, true);
        }

        $prompt = $label ?? 'Professional e-commerce image, clean background';
        $imageSize = $this->aspectRatioToSize($width, $height);

        $response = Http::withToken($this->apiKey)
            ->timeout(30)
            ->post($this->baseUrl . '/api/v1/nanobanana/generate', [
                'prompt' => $prompt,
                'type' => 'TEXTTOIAMGE',
                'numImages' => 1,
                'image_size' => $imageSize,
                'callBackUrl' => 'https://placeholder.callback/required',
            ]);

        if (!$response->successful()) {
            throw new \RuntimeException('NanoBanana API error: ' . $response->body());
        }

        $body = $response->json();
        $taskId = $body['data']['taskId'] ?? null;
        if (!$taskId) {
            throw new \RuntimeException('NanoBanana API did not return taskId: ' . json_encode($body));
        }

        $imageUrl = $this->pollUntilComplete($taskId);
        $fullPath = $outDir . DIRECTORY_SEPARATOR . $filename;
        $this->downloadTo($imageUrl, $fullPath);

        return $this->relativePath($fullPath, $themeRootPath);
    }

    private function aspectRatioToSize(int $width, int $height): string
    {
        $ratio = $width / max(1, $height);
        $ratios = [
            '1:1' => 1.0,
            '16:9' => 16/9,
            '9:16' => 9/16,
            '4:3' => 4/3,
            '3:4' => 3/4,
            '3:2' => 3/2,
            '2:3' => 2/3,
            '21:9' => 21/9,
        ];
        $best = '16:9';
        $bestDiff = PHP_FLOAT_MAX;
        foreach ($ratios as $name => $r) {
            $diff = abs($ratio - $r);
            if ($diff < $bestDiff) {
                $bestDiff = $diff;
                $best = $name;
            }
        }
        return $best;
    }

    private function pollUntilComplete(string $taskId, int $maxAttempts = 60): string
    {
        for ($i = 0; $i < $maxAttempts; $i++) {
            $response = Http::withToken($this->apiKey)
                ->timeout(15)
                ->get($this->baseUrl . '/api/v1/nanobanana/record-info', ['taskId' => $taskId]);

            if (!$response->successful()) {
                sleep(2);
                continue;
            }

            $data = $response->json('data');
            $successFlag = (int) ($data['successFlag'] ?? -1);

            if ($successFlag === 1) {
                $url = $data['response']['resultImageUrl'] ?? $data['response']['originImageUrl'] ?? null;
                if ($url) {
                    return $url;
                }
            }
            if ($successFlag === 2 || $successFlag === 3) {
                throw new \RuntimeException('NanoBanana generation failed: ' . ($data['errorMessage'] ?? 'Unknown error'));
            }

            sleep(2);
        }

        throw new \RuntimeException('NanoBanana task did not complete in time (taskId: ' . $taskId . ')');
    }

    private function downloadTo(string $url, string $path): void
    {
        $content = Http::timeout(60)->get($url)->body();
        if (empty($content)) {
            throw new \RuntimeException('Failed to download image from NanoBanana');
        }
        File::put($path, $content);
    }

    private function relativePath(string $fullPath, string $themeRoot): string
    {
        $fullPath = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $fullPath);
        $themeRoot = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $themeRoot);
        if (strpos($fullPath, $themeRoot) === 0) {
            return ltrim(substr($fullPath, strlen($themeRoot)), DIRECTORY_SEPARATOR);
        }
        return $fullPath;
    }
}
