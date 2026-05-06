<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$paths = [
    $root . DIRECTORY_SEPARATOR . 'src',
    $root . DIRECTORY_SEPARATOR . 'public',
    $root . DIRECTORY_SEPARATOR . 'config',
    $root . DIRECTORY_SEPARATOR . 'bin',
];

$files = [];
foreach ($paths as $path) {
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path));
    foreach ($iterator as $file) {
        if ($file->isFile() && $file->getExtension() === 'php') {
            $files[] = $file->getPathname();
        }
    }
}

sort($files);
$failures = [];

foreach ($files as $file) {
    $command = escapeshellarg(PHP_BINARY) . ' -l ' . escapeshellarg($file);
    exec($command, $output, $status);
    if ($status !== 0) {
        $failures[$file] = implode(PHP_EOL, $output);
    }
}

if ($failures !== []) {
    foreach ($failures as $file => $failure) {
        fwrite(STDERR, $file . PHP_EOL . $failure . PHP_EOL);
    }
    exit(1);
}

echo 'Lint passed for ' . count($files) . ' PHP files.' . PHP_EOL;

