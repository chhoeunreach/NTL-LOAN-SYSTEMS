<?php

$im1 = imagecreatefrompng(__DIR__ . '/crop1_full.png');
$im2 = imagecreatefrompng(__DIR__ . '/crop2_full.png');

function measureButtonPrecise($im, $name, $expectedX, $ySearch1, $ySearch2) {
    // Find the topmost and bottommost orange border pixels on the vertical line (x around expectedX)
    $minY = 999;
    $maxY = 0;
    for ($y = $ySearch1; $y <= $ySearch2; $y++) {
        for ($x = $expectedX - 3; $x <= $expectedX + 3; $x++) {
            $rgb = imagecolorat($im, $x, $y);
            $r = ($rgb >> 16) & 0xFF;
            $g = ($rgb >> 8) & 0xFF;
            $b = $rgb & 0xFF;
            // Orange line pixel
            if ($r > 190 && $g > 70 && $g < 130 && $b < 50) {
                if ($y < $minY) $minY = $y;
                if ($y > $maxY) $maxY = $y;
            }
        }
    }
    echo "$name: Orange line Y range: [$minY, $maxY], Total height: " . ($maxY - $minY + 1) . "px\n";

    // Also find top border and bottom border (horizontal borders)
    $topBorderY = 999;
    $bottomBorderY = 0;
    for ($y = $ySearch1; $y <= $ySearch2; $y++) {
        for ($x = $expectedX + 30; $x <= $expectedX + 80; $x++) {
            $rgb = imagecolorat($im, $x, $y);
            $r = ($rgb >> 16) & 0xFF;
            $g = ($rgb >> 8) & 0xFF;
            $b = $rgb & 0xFF;
            // Light orange border: R ~ 250-254, G ~ 210-230, B ~ 170-200
            if ($r > 240 && $g > 200 && $g < 235 && $b > 160 && $b < 210) {
                if ($y < $topBorderY) $topBorderY = $y;
                if ($y > $bottomBorderY) $bottomBorderY = $y;
            }
        }
    }
    echo "$name: Horizontal border Y range: [$topBorderY, $bottomBorderY], Outer Card height: " . ($bottomBorderY - $topBorderY + 1) . "px\n\n";
}

measureButtonPrecise($im1, "Admin Installment (Crop 1)", 15, 45, 90);
measureButtonPrecise($im2, "Dashboard Reports (Crop 2)", 19, 35, 85);
