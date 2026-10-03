<?php

$im1 = imagecreatefrompng(__DIR__ . '/crop1_full.png');
$im2 = imagecreatefrompng(__DIR__ . '/crop2_full.png');

// Check the top border of the card in crop 1 and crop 2
// In crop 1, card is at y=49, top border is y=49-50
// In crop 2, card is at y=41, top border is y=41-42

echo "Crop 1 (Admin Installment) top border pixels (y=49, x=30 to 120):\n";
for ($x = 30; $x <= 120; $x += 5) {
    $rgb = imagecolorat($im1, $x, 49);
    $r = ($rgb >> 16) & 0xFF; $g = ($rgb >> 8) & 0xFF; $b = $rgb & 0xFF;
    echo "x=$x: rgb($r, $g, $b) | ";
}
echo "\n\nCrop 2 (Dashboard Reports) top border pixels (y=41, x=30 to 120):\n";
for ($x = 30; $x <= 120; $x += 5) {
    $rgb = imagecolorat($im2, $x, 41);
    $r = ($rgb >> 16) & 0xFF; $g = ($rgb >> 8) & 0xFF; $b = $rgb & 0xFF;
    echo "x=$x: rgb($r, $g, $b) | ";
}

// Check right border in crop 1 vs crop 2
echo "\n\nCrop 1 right side (x=160 to 164, y=60):\n";
for ($x = 155; $x <= 165; $x++) {
    $rgb = imagecolorat($im1, $x, 60);
    $r = ($rgb >> 16) & 0xFF; $g = ($rgb >> 8) & 0xFF; $b = $rgb & 0xFF;
    echo "x=$x: rgb($r, $g, $b) | ";
}
echo "\n\nCrop 2 right side (x=180 to 195, y=55):\n";
for ($x = 180; $x <= 195; $x++) {
    $rgb = imagecolorat($im2, $x, 55);
    $r = ($rgb >> 16) & 0xFF; $g = ($rgb >> 8) & 0xFF; $b = $rgb & 0xFF;
    echo "x=$x: rgb($r, $g, $b) | ";
}
