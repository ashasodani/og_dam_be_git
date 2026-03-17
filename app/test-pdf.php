<?php

require __DIR__.'/vendor/autoload.php';

use Spatie\PdfToImage\Pdf;

echo "Class: " . Pdf::class . "\n";

$pdf = new Pdf(__DIR__.'/example.pdf');

echo "Instance of: " . get_class($pdf) . "\n";

$methods = get_class_methods($pdf);
print_r($methods);
