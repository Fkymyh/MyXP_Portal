<?php

declare(strict_types=1);

$config = require __DIR__ . '/config.local.php';

$apiKey = $config['tmdb_api_key'] ?? '';

if (is_string($apiKey) && strlen($apiKey) === 32) {
    echo "設定を読み込めました。" . PHP_EOL;
} else {
    echo "APIキーの設定を確認してください。" . PHP_EOL;
}
