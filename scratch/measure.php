<?php

$im1 = imagecreatefrompng(__DIR__ . '/crop1.png');
$im2 = imagecreatefrompng(__DIR__ . '/crop2.png');

// Find the left-most orange pixel (hue orange ~ r: 200+, g: 80-120, b: 0-50)
function analyzeBar($im, $name) {
    $w = imagesx($im);
    $h = imagesy($im);
    $minX = $w;
    $maxX = 0;
    $minY = $h;
    $maxY = 0;
    for ($y = 0; $y < $h; $y++) {
        for ($x = 0; $x < $w; $x++) {
            $rgb = imagecolorat($im, $x, $y);
            $r = ($rgb >> 16) & 0xFF;
            $g = ($rgb >> 8) & 0xFF;
            $b = $rgb & 0xFF;
            // Orange detection: high R, medium G, low B
            if ($r > 180 && $g > 60 && $g < 150 && $b < 50) {
                if ($x < $minX) $minX = $x;
                if ($x > $maxX) $maxX = $x;
                if ($y < $minY) $minY = $y;
                if ($y > $maxY) $maxY = $y;
            }
        }
    }
    echo "$name orange bar bounds: x=[$minX, $maxX] (width: " . ($maxX - $minX + 1) . "), y=[$minY, $maxY] (height: " . ($maxY - $minY + 1) . ")\n";
}

analyzeBar($im1, "crop1 (Admin Installment)");
analyzeBar($im2, "crop2 (Dashboard Reports)");

// Find icon positions (look for dark slate pixels around icon y ranges)
function findIconBounds($im, $yStart, $yEnd, $name) {
    $w = imagesx($im);
    $minX = $w; $maxX = 0;
    for ($y = $yStart; $y <= $yEnd; $y++) {
        for ($x = 10; $x < 60; $x++) {
            $rgb = imagecolorat($im, $x, $y);
            $r = ($rgb >> 16) & 0xFF;
            $g = ($rgb >> 8) & 0xFF;
            $b = $rgb & 0xFF;
            // Icon color slate ~ 70-130
            if ($r > 60 && $r < 160 && $g > 70 && $g < 170 && $b > 80 && $b < 190) {
                if ($x < $minX) $minX = $x;
                if ($x > $maxX) $maxX = $x;
            }
        }
    }
    echo "$name icon X: [$minX, $maxX]\n";
}

findIconBounds($im1, 75, 115, "crop1 Admin Installment icon");
findIconBounds($im2, 135, 160, "crop2 Dashboard Reports icon");
findIconBounds($im1, 40, 70, "crop1 Dashboard icon");
findIconBounds($im2, 40, 70, "crop2 Dashboard icon");
