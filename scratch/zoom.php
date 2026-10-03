<?php

$im1 = imagecreatefrompng(__DIR__ . '/crop1_full.png');
$im2 = imagecreatefrompng(__DIR__ . '/crop2_full.png');

// Crop exactly the active button in both
// Admin Installment button in crop1: x=10 to 220, y=45 to 85 (height 40)
// Dashboard Reports button in crop2: x=14 to 224, y=38 to 80 (height 42)

$btn1 = imagecrop($im1, ['x' => 10, 'y' => 45, 'width' => 200, 'height' => 42]);
$btn2 = imagecrop($im2, ['x' => 14, 'y' => 38, 'width' => 200, 'height' => 42]);

// Scale both 3x
$scale = 3;
$w = 200 * $scale;
$h = 42 * $scale;

$big = imagecreatetruecolor($w, $h * 2 + 30);
$bg = imagecolorallocate($big, 240, 240, 240);
imagefill($big, 0, 0, $bg);

imagecopyresampled($big, $btn1, 0, 0, 0, 0, $w, $h, 200, 42);
imagecopyresampled($big, $btn2, 0, $h + 30, 0, 0, $w, $h, 200, 42);

imagepng($big, __DIR__ . '/zoomed_comparison.png');
echo "Saved zoomed_comparison.png\n";
