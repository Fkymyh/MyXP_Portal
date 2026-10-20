<?php

declare(strict_types=1);

require_once __DIR__ . '/../src/Movie.php';

$movie = null;
$error = null;

try {
    $movie = fetchMovie(11);
} catch (Exception $e) {
    $error = '映画情報を取得できませんでした。時間を置いて再試行してください。';
}

// HTMLに表示する文字列をエスケープする関数
function escape(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}
?>

<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>映画情報</title>
</head>

<body>
    <h1>映画情報</h1>

    <?php if ($error !== null): ?>
        <p><?= escape($error) ?></p>
    <?php elseif ($movie !== null): ?>
        <h2><?= escape($movie['title'] ?? '不明') ?></h2>

        <p>
            公開日：<?= escape($movie['release_date'] ?? '不明') ?>
        </p>

        <p><?= nl2br(escape($movie['overview'] ?? '情報なし')) ?></p>
    <?php endif; ?>
</body>

</html>