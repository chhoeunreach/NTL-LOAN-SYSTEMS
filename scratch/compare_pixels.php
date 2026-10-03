<?php

$im1 = imagecreatefrompng(__DIR__ . '/crop1_full.png');
$im2 = imagecreatefrompng(__DIR__ . '/crop2_full.png');

// Find the bounding box of the active card in both images
// The active card has a border with color:
// In crop1, y is around 25 to 65
// In crop2, y is around 25 to 65

function findCardRect($im, $name) {
    $w = imagesx($im);
    $h = imagesy($im);
    
    // Scan for top border of card
    $minX = $w; $maxX = 0; $minY = $h; $maxY = 0;
    for ($y = 0; $y < $h; $y++) {
        for ($x = 0; $x < $w; $x++) {
            $rgb = imagecolorat($im, $x, $y);
            $r = ($rgb >> 16) & 0xFF;
            $g = ($rgb >> 8) & 0xFF;
            $b = $rgb & 0xFF;
            // Active border or background is tinted orange
            // Border is orange ~ R>200, G>100, B<100, or line R>180, G<120, B<40
            if ($r > 200 && $g > 80 && $b < 100) {
                if ($x < $minX) $minX = $x;
                if ($x > $maxX) $maxX = $x;
                if ($y < $minY) $minY = $y;
                if ($y > $maxY) $maxY = $y;
            }
        }
    }
    echo "$name card bounds: X=[$minX, $maxX] (width " . ($maxX-$minX+1) . "), Y=[$minY, $maxY] (height " . ($maxY-$minY+1) . ")\n";
    return ['minX' => $minX, 'maxX' => $maxX, 'minY' => $minY, 'maxY' => $maxY];
}

$r1 = findCardRect($im1, "crop1 (Admin Installment)");
$r2 = findCardRect($im2, "crop2 (Dashboard Reports)");

// Now print vertical slices of the left edge of both cards
echo "\n--- CROP 1 LEFT EDGE PIXELS (x from " . ($r1['minX']-2) . " to " . ($r1['minX']+15) . ", y from " . ($r1['minY']) . " to " . ($r1['minY']+35) . ") ---\n";
for ($y = $r1['minY']; $y <= $r1['minY']+35 && $y < imagesy($im1); $y++) {
    $row = sprintf("%3d: ", $y);
    for ($x = $r1['minX']-2; $x <= $r1['minX']+12; $x++) {
        $rgb = imagecolorat($im1, $x, $y);
        $r = ($rgb >> 16) & 0xFF;
        $g = ($rgb >> 8) & 0xFF;
        $b = $rgb & 0xFF;
        if ($r > 180 && $g < 120 && $b < 50) $row .= "█"; // Dark orange line
        elseif ($r > 200 && $g > 140 && $b > 80) $row .= "░"; // Light border / bg
        else $row .= " ";
    }
    echo $row . "\n";
}

echo "\n--- CROP 2 LEFT EDGE PIXELS (x from " . ($r2['minX']-2) . " to " . ($r2['minX']+15) . ", y from " . ($r2['minY']) . " to " . ($r2['minY']+35) . ") ---\n";
for ($y = $r2['minY']; $y <= $r2['minY']+35 && $y < imagesy($im2); $y++) {
    $row = sprintf("%3d: ", $y);
    for ($x = $r2['minX']-2; $x <= $r2['minX']+12; $x++) {
        $rgb = imagecolorat($im2, $x, $y);
        $r = ($rgb >> 16) & 0xFF;
        $g = ($rgb >> 8) & 0xFF;
        $b = $rgb & 0xFF;
        if ($r > 180 && $g < 120 && $b < 50) $row .= "█"; // Dark orange line
        elseif ($r > 200 && $g > 140 && $b > 80) $row .= "░"; // Light border / bg
        else $row .= " ";
    }
    echo $row . "\n";
}
