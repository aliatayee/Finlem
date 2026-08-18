<?php

// One-off script to generate square, iOS-ready app icons from the wide
// logo-white.png mark on a solid brand-color background. Run with:
//   php scripts/generate-icons.php

$root = dirname(__DIR__);
$source = $root.'/public/logo-white.png';
$bgHex = '#4f46e5'; // matches theme_color in manifest.json

[$r, $g, $b] = sscanf($bgHex, '#%02x%02x%02x');

$sizes = [
    'apple-touch-icon.png' => 180,
    'icon-192.png' => 192,
    'icon-512.png' => 512,
];

$logo = imagecreatefrompng($source);
$logoW = imagesx($logo);
$logoH = imagesy($logo);

foreach ($sizes as $filename => $size) {
    $canvas = imagecreatetruecolor($size, $size);
    imagesavealpha($canvas, true);
    $bg = imagecolorallocate($canvas, $r, $g, $b);
    imagefill($canvas, 0, 0, $bg);

    // Leave ~18% padding on each side (Apple HIG safe zone).
    $maxW = (int) round($size * 0.64);
    $maxH = (int) round($size * 0.64);

    $scale = min($maxW / $logoW, $maxH / $logoH);
    $destW = (int) round($logoW * $scale);
    $destH = (int) round($logoH * $scale);
    $destX = (int) round(($size - $destW) / 2);
    $destY = (int) round(($size - $destH) / 2);

    imagecopyresampled($canvas, $logo, $destX, $destY, 0, 0, $destW, $destH, $logoW, $logoH);

    imagepng($canvas, $root.'/public/'.$filename);
    imagedestroy($canvas);

    echo "Wrote public/{$filename} ({$size}x{$size})\n";
}

imagedestroy($logo);
