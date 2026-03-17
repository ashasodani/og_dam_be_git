<?php

require __DIR__.'/vendor/autoload.php';

use Spatie\PdfToImage\Pdf;

echo "Class: " . Pdf::class . "\n";

$pdf = new \Spatie\PdfToImage\Pdf(__DIR__.'/Bail-Application-Format-LawRato.pdf');

echo "Instance of: " . get_class($pdf) . "\n";

$methods = get_class_methods($pdf);
echo "File: " . (new ReflectionClass($pdf))->getFileName() . PHP_EOL;
print_r($methods);
