<?php

$img1Path = 'C:/Users/CHHOEUNREACH/.gemini/antigravity-ide/brain/33beb89e-d65c-41e1-852a-8442f50b996d/.user_uploaded/media_1791053697436.png';
$img2Path = 'C:/Users/CHHOEUNREACH/.gemini/antigravity-ide/brain/33beb89e-d65c-41e1-852a-8442f50b996d/.user_uploaded/media_1791053719858.png';

$im1 = imagecreatefrompng($img1Path);
$im2 = imagecreatefrompng($img2Path);

echo "Img1 size: " . imagesx($im1) . " x " . imagesy($im1) . "\n";
echo "Img2 size: " . imagesx($im2) . " x " . imagesy($im2) . "\n";

// Crop around Overview section (y: 120 to 300)
$crop1 = imagecrop($im1, ['x' => 0, 'y' => 120, 'width' => min(imagesx($im1), 240), 'height' => 160]);
$crop2 = imagecrop($im2, ['x' => 0, 'y' => 120, 'width' => min(imagesx($im2), 240), 'height' => 160]);

imagepng($crop1, __DIR__ . '/crop1.png');
imagepng($crop2, __DIR__ . '/crop2.png');
echo "Cropped images saved to scratch/crop1.png and scratch/crop2.png\n";
