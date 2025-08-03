<?php

error_reporting(E_ALL & ~E_DEPRECATED);

$vendor_path = __DIR__.'/vendor/autoload.php';
if (stripos(__FILE__, 'bin'.DIRECTORY_SEPARATOR)) {
    $vendor_path = __DIR__ . '/../vendor/autoload.php';
}

if (!file_exists($vendor_path)) {
    echo 'Please run composer install first!';
    die;
}

require $vendor_path;

use App\MChefCLI;

$cli = new MChefCLI();
$cli->run();
