<?php

namespace App\Domain\Media;

use Illuminate\Support\Facades\File;

/**
 * Local placeholder image generator: GD preferred, fallback Imagick, fallback SVG.
 * Input: brand colors (hex), width, height, optional text. Saves under theme assets/generated/.
 *
 * Inputs: Path to theme root, filename, width, height, colors (primary/secondary), optional text.
 * Outputs: Path relative to theme (e.g. assets/generated/filename.png).
 * Side effects: Writes image file.
 */
class MediaGenerator
{
    /**
     * @param array{primary?: string, secondary?: string, background?: string} $colors Hex colors from Brand Kit.
     * @param string|null $label Purpose or label (e.g. "Hero", "Hero – minimal"); used for placeholder text.
     */
    public function generate(
        string $themeRootPath,
        string $filename,
        int $width,
        int $height,
        array $colors = [],
        ?string $label = null
    ): string {
        $filename = $this->sanitizeFilename($filename);
        $themeRootPath = rtrim(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $themeRootPath), DIRECTORY_SEPARATOR);
        $outDir = $themeRootPath . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'generated';
        if (!is_dir($outDir)) {
            mkdir($outDir, 0755, true);
        }
        $primary = $colors['primary'] ?? '#4F46E5';
        $secondary = $colors['secondary'] ?? '#7C3AED';
        $background = $colors['background'] ?? null;

        if (extension_loaded('gd')) {
            $path = $this->generateWithGd($outDir, $filename, $width, $height, $primary, $secondary, $background, $label);
            return $this->relativePath($path, $themeRootPath);
        }
        if (extension_loaded('imagick')) {
            $path = $this->generateWithImagick($outDir, $filename, $width, $height, $primary, $secondary, $label);
            return $this->relativePath($path, $themeRootPath);
        }
        $path = $this->generateSvg($outDir, $filename, $width, $height, $primary, $secondary, $label);
        return $this->relativePath($path, $themeRootPath);
    }

    private function generateWithGd(string $outDir, string $filename, int $w, int $h, string $c1, string $c2, ?string $background, ?string $label): string
    {
        $img = imagecreatetruecolor($w, $h);
        if (!$img) {
            throw new \RuntimeException('GD imagecreatetruecolor failed');
        }
        $rgb1 = $this->hexToRgb($c1);
        $rgb2 = $this->hexToRgb($c2);
        for ($y = 0; $y < $h; $y++) {
            $ratio = $y / $h;
            $r = (int) ($rgb1[0] + ($rgb2[0] - $rgb1[0]) * $ratio);
            $g = (int) ($rgb1[1] + ($rgb2[1] - $rgb1[1]) * $ratio);
            $b = (int) ($rgb1[2] + ($rgb2[2] - $rgb1[2]) * $ratio);
            $c = imagecolorallocate($img, $r, $g, $b);
            imagefilledrectangle($img, 0, $y, $w, $y + 1, $c);
        }
        $textColor = imagecolorallocate($img, 255, 255, 255);
        if ($label !== null && $label !== '') {
            $ttfPath = $this->findTtfFont();
            if ($ttfPath !== null) {
                $fontSize = (int) min(24, max(12, $h / 25));
                $box = imagettfbbox($fontSize, 0, $ttfPath, $label);
                if ($box !== false) {
                    $tw = (int) abs($box[4] - $box[0]);
                    $th = (int) abs($box[5] - $box[1]);
                    $x = (int) (($w - $tw) / 2);
                    $yPos = (int) (($h + $th) / 2) - (int) ($th * 0.2);
                    imagettftext($img, $fontSize, 0, $x, $yPos, $textColor, $ttfPath, $label);
                }
            } else {
                $font = 5;
                $x = (int) (($w - imagefontwidth($font) * strlen($label)) / 2);
                $yPos = (int) (($h - imagefontheight($font)) / 2);
                imagestring($img, $font, $x, $yPos, $label, $textColor);
            }
        }
        $path = $outDir . DIRECTORY_SEPARATOR . $filename;
        if (!imagepng($img, $path)) {
            imagedestroy($img);
            throw new \RuntimeException('GD imagepng failed to write: ' . $path);
        }
        imagedestroy($img);
        return $path;
    }

    private function findTtfFont(): ?string
    {
        $candidates = [
            storage_path('fonts' . DIRECTORY_SEPARATOR . 'Inter-Regular.ttf'),
            storage_path('fonts' . DIRECTORY_SEPARATOR . 'inter.ttf'),
            base_path('storage' . DIRECTORY_SEPARATOR . 'fonts' . DIRECTORY_SEPARATOR . 'Inter-Regular.ttf'),
        ];
        foreach ($candidates as $path) {
            if (is_file($path)) {
                return $path;
            }
        }
        return null;
    }

    private function generateWithImagick(string $outDir, string $filename, int $w, int $h, string $c1, string $c2, ?string $label): string
    {
        $path = $outDir . DIRECTORY_SEPARATOR . $filename;
        $img = new \Imagick;
        $img->newImage($w, $h, new \ImagickPixel($c1));
        $img->setImageFormat('png');
        $img->writeImage($path);
        $img->destroy();
        return $path;
    }

    private function generateSvg(string $outDir, string $filename, int $w, int $h, string $c1, string $c2, ?string $label): string
    {
        $label = $label ?? 'Placeholder';
        $safeLabel = htmlspecialchars($label, ENT_XML1, 'UTF-8');
        $dimensions = $w . ' × ' . $h;
        $svg = <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="{$w}" height="{$h}" viewBox="0 0 {$w} {$h}">
  <defs><linearGradient id="g" x1="0%" y1="0%" x2="100%" y2="100%"><stop offset="0%" style="stop-color:{$c1}"/><stop offset="100%" style="stop-color:{$c2}"/></linearGradient></defs>
  <rect width="100%" height="100%" fill="url(#g)"/>
  <text x="50%" y="45%" dominant-baseline="middle" text-anchor="middle" fill="white" font-size="24" font-family="system-ui, -apple-system, sans-serif">{$safeLabel}</text>
  <text x="50%" y="55%" dominant-baseline="middle" text-anchor="middle" fill="rgba(255,255,255,0.85)" font-size="14" font-family="system-ui, -apple-system, sans-serif">{$dimensions}</text>
</svg>
SVG;
        $path = $outDir . DIRECTORY_SEPARATOR . preg_replace('/\.(png|jpe?g|gif|webp)$/i', '.svg', $filename);
        File::put($path, $svg);
        return $path;
    }

    /** @return array{0: int, 1: int, 2: int} */
    private function hexToRgb(string $hex): array
    {
        $hex = ltrim($hex, '#');
        if (strlen($hex) === 3) {
            $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
        }
        return [
            (int) hexdec(substr($hex, 0, 2)),
            (int) hexdec(substr($hex, 2, 2)),
            (int) hexdec(substr($hex, 4, 2)),
        ];
    }

    private function sanitizeFilename(string $filename): string
    {
        $filename = preg_replace('/[^a-zA-Z0-9_\-\.]/', '_', $filename);
        $filename = preg_replace('/_+/', '_', trim($filename, '_'));
        return $filename !== '' ? $filename : 'placeholder.png';
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
