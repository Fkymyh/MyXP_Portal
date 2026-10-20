<?php

declare(strict_types=1);

require_once __DIR__ . '/src/Movie.php';

try {
    $movie = fetchMovie(11);

    echo 'タイトル：' . ($movie['title'] ?? '不明') . PHP_EOL;
    echo '公開日：' . ($movie['release_date'] ?? '不明') . PHP_EOL;
    echo 'あらすじ：' . ($movie['overview'] ?? '情報なし') . PHP_EOL;
} catch (Exception $e) {
    echo 'エラー：' . $e->getMessage() . PHP_EOL;
    exit(1);
}
