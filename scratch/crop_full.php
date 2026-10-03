<?php

$im1 = imagecreatefrompng('C:/Users/CHHOEUNREACH/.gemini/antigravity-ide/brain/33beb89e-d65c-41e1-852a-8442f50b996d/.user_uploaded/media_1791053697436.png');
$im2 = imagecreatefrompng('C:/Users/CHHOEUNREACH/.gemini/antigravity-ide/brain/33beb89e-d65c-41e1-852a-8442f50b996d/.user_uploaded/media_1791053719858.png');

$crop1 = imagecrop($im1, ['x' => 0, 'y' => 150, 'width' => 260, 'height' => 180]);
$crop2 = imagecrop($im2, ['x' => 0, 'y' => 220, 'width' => 260, 'height' => 180]);

imagepng($crop1, __DIR__ . '/crop1_full.png');
imagepng($crop2, __DIR__ . '/crop2_full.png');
echo "Done\n";
