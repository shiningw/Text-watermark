<?php

namespace Shiningw;

require __DIR__.'/Watermark.php';

$fname = './example.jpeg';
$inst = new Watermark($fname, 'this is a test','topright','./yahei.ttf');
$inst->setFontSize(45);
$inst->setBboxBorderColor('cyan');
$inst->setTextColor('gray');
$inst->setBgColor('black');
$inst->execute();
