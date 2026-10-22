<?php

declare(strict_types=1);

require_once __DIR__ . '/../src/Movie.php';

$searchInput = $_GET['query'] ?? '';
$searchQuery = is_string($searchInput) ? trim($searchInput) : '';

$movie = null;
$error = null;

// URLの「?id=11」の値を取得します。指定がなければ11を使います。
$movieId = filter_var(
    $_GET['id'] ?? '11',
    FILTER_VALIDATE_INT,
    ['options' => ['min_range' => 1]]
);

try {
    if ($movieId === false) {
        throw new InvalidArgumentException('映画IDが正しくありません。');
    }

    $movie = fetchmovie($movieId);
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
    <p>
        <a href="movie-search.php?<?= escape(http_build_query([
                                        'query' => $searchQuery,
                                    ])) ?>">
            映画検索へ戻る
        </a>
    </p>
    <h1>映画情報</h1>

    <form action="movie.php" method="get">
        <label for="movie-id">映画ID：</label>

        <input
            type="number"
            id="movie-id"
            name="id"
            min="1"
            step="1"
            value="<?= escape((string) ($movieId === false ? '' :
                        $movieId)) ?>"
            required>

        <button type="submit">表示</button>
    </form>
    <?php if ($error !== null): ?>
        <p><?= escape($error) ?></p>
    <?php elseif ($movie !== null): ?>
        <h2><?= escape($movie['title'] ?? '不明') ?></h2>
        <?php $posterPath = $movie['poster_path'] ?? null; ?>

        <?php if (is_string($posterPath) && $posterPath !== ''): ?>
            <img
                src="<?= escape('https://image.tmdb.org/t/p/w500' . $posterPath) ?>"
                alt="<?= escape(($movie['title'] ?? '映画') . 'のポスター') ?>"
                width="250"
                style="max-width: 100%; height: auto;">
        <?php else: ?>
            <p>ポスター画像はありません</p>
        <?php endif; ?>

        <p>
            公開日：<?= escape($movie['release_date'] ?? '不明') ?>
        </p>

        <p><?= nl2br(escape($movie['overview'] ?? '情報なし')) ?></p>
    <?php endif; ?>
</body>

</html>