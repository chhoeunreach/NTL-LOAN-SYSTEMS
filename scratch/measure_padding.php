<?php

$im1 = imagecreatefrompng(__DIR__ . '/crop1_full.png');
$im2 = imagecreatefrompng(__DIR__ . '/crop2_full.png');

// Card left edge:
// Crop 1: left edge of card is at x=13
// Crop 2: left edge of card is at x=17

// Find icon in Crop 1 (Admin Installment: fa-table around y=50-80)
// Find icon in Crop 2 (Dashboard Reports: fa-line-chart around y=42-76)

function getIconLeft($im, $cardLeft, $y1, $y2) {
    $minX = 999;
    for ($y = $y1; $y <= $y2; $y++) {
        for ($x = $cardLeft + 2; $x < $cardLeft + 40; $x++) {
            $rgb = imagecolorat($im, $x, $y);
            $r = ($rgb >> 16) & 0xFF;
            $g = ($rgb >> 8) & 0xFF;
            $b = $rgb & 0xFF;
            // The icon is dark slate / gray: r < 140, g < 150, b < 160, but NOT orange bg
            // Let's check if it's the icon stroke
            if ($r < 130 && $g < 140 && $b < 150) {
                if ($x < $minX) $minX = $x;
            }
        }
    }
    return $minX;
}

$icon1 = getIconLeft($im1, 13, 50, 80);
$icon2 = getIconLeft($im2, 17, 42, 76);

echo "Crop 1 (Admin Installment): Card Left = 13, Icon Left = $icon1 => Padding-left = " . ($icon1 - 13) . "px\n";
echo "Crop 2 (Dashboard Reports): Card Left = 17, Icon Left = $icon2 => Padding-left = " . ($icon2 - 17) . "px\n";

// Also measure text left position
function getTextLeft($im, $cardLeft, $y1, $y2) {
    $minX = 999;
    for ($y = $y1; $y <= $y2; $y++) {
        for ($x = $cardLeft + 35; $x < $cardLeft + 80; $x++) {
            $rgb = imagecolorat($im, $x, $y);
            $r = ($rgb >> 16) & 0xFF;
            $g = ($rgb >> 8) & 0xFF;
            $b = $rgb & 0xFF;
            // Orange text: r > 190, g between 70 and 130, b < 40
            if ($r > 190 && $g > 70 && $g < 130 && $b < 40) {
                if ($x < $minX) $minX = $x;
            }
        }
    }
    return $minX;
}

$text1 = getTextLeft($im1, 13, 50, 80);
$text2 = getTextLeft($im2, 17, 42, 76);

echo "Crop 1 (Admin Installment): Text Left = $text1 => Text distance from Card Left = " . ($text1 - 13) . "px\n";
echo "Crop 2 (Dashboard Reports): Text Left = $text2 => Text distance from Card Left = " . ($text2 - 17) . "px\n";
