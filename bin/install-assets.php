#!/usr/bin/env php
<?php

/**
 * Скрипт для установки ассетов бандла через composer.
 * Вызывается автоматически при composer install/update.
 *
 * @copyright  2024 Zhalayletdinov Vyacheslav evil_tut@mail.ru
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
$bundlePath = __DIR__ . '/..';
$packageJsonPath = $bundlePath . '/package.json';

if (!file_exists($packageJsonPath)) {
    echo "  [SlcorpFileBundle] package.json not found, skipping asset installation.\n";
    exit(0);
}

// Проверяем наличие npm
$npmPath = null;
$commands = ['npm', 'npm.cmd'];
foreach ($commands as $cmd) {
    $output = [];
    $returnCode = 0;
    exec('which ' . escapeshellarg($cmd) . ' 2>&1', $output, $returnCode);
    if ($returnCode === 0 && !empty($output)) {
        $npmPath = mb_trim($output[0]);
        break;
    }
    exec('where ' . escapeshellarg($cmd) . ' 2>&1', $output, $returnCode);
    if ($returnCode === 0 && !empty($output)) {
        $npmPath = mb_trim($output[0]);
        break;
    }
}

if ($npmPath === null) {
    echo "  [SlcorpFileBundle] WARNING: npm is not installed. Skipping asset installation.\n";
    echo "  [SlcorpFileBundle] Please install npm and run: cd {$bundlePath} && npm run install-assets\n";
    exit(0);
}

echo "  [SlcorpFileBundle] Installing assets...\n";
echo "  [SlcorpFileBundle] Found npm at: {$npmPath}\n";

$originalDir = getcwd();
if (!$originalDir) {
    exit(1);
}
chdir($bundlePath);

try {
    // Устанавливаем зависимости
    echo "  [SlcorpFileBundle] Installing npm dependencies...\n";
    $output = [];
    $returnCode = 0;
    exec($npmPath . ' install 2>&1', $output, $returnCode);

    if ($returnCode !== 0) {
        echo "  [SlcorpFileBundle] ERROR: npm install failed:\n";
        foreach ($output as $line) {
            echo "    {$line}\n";
        }
        exit(1);
    }

    echo "  [SlcorpFileBundle] npm dependencies installed successfully.\n";
    echo "  [SlcorpFileBundle] Building assets (copying vendor files, JS and compiling SCSS)...\n";

    // Собираем ассеты
    $output2 = [];
    $returnCode2 = 0;
    exec($npmPath . ' run build 2>&1', $output2, $returnCode2);

    if ($returnCode2 !== 0) {
        echo "  [SlcorpFileBundle] ERROR: npm build failed:\n";
        foreach ($output2 as $line) {
            echo "    {$line}\n";
        }
        exit(1);
    }

    echo "  [SlcorpFileBundle] Assets built and copied successfully.\n";
    echo "  [SlcorpFileBundle] Asset installation completed!\n";
} finally {
    chdir($originalDir);
}
