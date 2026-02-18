<?php

/**
 * Crop black borders from logo PNG. Saves to public/images/logo.png
 * Run from project root: php scripts/crop_logo.php
 */

$src = __DIR__ . '/../public/images/liquidpilot-original.png';
$dest = __DIR__ . '/../public/images/logo.png';

if (!is_file($src)) {
    fwrite(STDERR, "Source not found: {$src}\n");
    exit(1);
}

$img = @imagecreatefromstring(file_get_contents($src));
if (!$img) {
    $img = @imagecreatefrompng($src);
}
if (!$img) {
    fwrite(STDERR, "Failed to load PNG. Check GD supports this PNG format.\n");
    exit(1);
}

$w = imagesx($img);
$h = imagesy($img);

// Find content bounds: treat pixel as "content" if not near black (R,G,B all < 30)
$threshold = 30;
$minX = $w;
$maxX = 0;
$minY = $h;
$maxY = 0;

for ($y = 0; $y < $h; $y++) {
    for ($x = 0; $x < $w; $x++) {
        $rgb = @imagecolorat($img, $x, $y);
        if ($rgb === false) {
            continue;
        }
        $a = ($rgb >> 24) & 0x7F;
        $r = ($rgb >> 16) & 0xFF;
        $g = ($rgb >> 8) & 0xFF;
        $b = $rgb & 0xFF;
        $isContent = ($a > 10) || ($r > $threshold || $g > $threshold || $b > $threshold);
        if ($isContent) {
            $minX = min($minX, $x);
            $maxX = max($maxX, $x);
            $minY = min($minY, $y);
            $maxY = max($maxY, $y);
        }
    }
}

$padding = 8;
if ($minX <= $maxX && $minY <= $maxY) {
    $cropX = max(0, $minX - $padding);
    $cropY = max(0, $minY - $padding);
    $cropW = min($w - $cropX, $maxX - $minX + 1 + 2 * $padding);
    $cropH = min($h - $cropY, $maxY - $minY + 1 + 2 * $padding);
} else {
    // Fallback: crop ~25% from each side (remove excess black margins)
    $cropX = (int) round($w * 0.22);
    $cropY = (int) round($h * 0.20);
    $cropW = (int) round($w * 0.56);
    $cropH = (int) round($h * 0.60);
}

$cropped = imagecreatetruecolor($cropW, $cropH);
if (!$cropped) {
    fwrite(STDERR, "Failed to create image\n");
    exit(1);
}

// Preserve transparency for PNG
imagealphablending($cropped, false);
imagesavealpha($cropped, true);
$transparent = imagecolorallocatealpha($cropped, 0, 0, 0, 127);
imagefill($cropped, 0, 0, $transparent);

imagecopy($cropped, $img, 0, 0, $cropX, $cropY, $cropW, $cropH);

$ok = imagepng($cropped, $dest, 9);
imagedestroy($img);
imagedestroy($cropped);

if (!$ok) {
    fwrite(STDERR, "Failed to write {$dest}\n");
    exit(1);
}

echo "Cropped logo saved to {$dest}\n";
