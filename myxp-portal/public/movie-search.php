<?php

declare(strict_types=1);

require_once __DIR__ . '/../src/Movie.php';

$input = $_GET['query'] ?? '';
$query = is_string($input) ? trim($input) : '';

$movies = [];
$error = null;

if ($query !== '') {
    try {
        $movies = searchMovies($query);
    } catch (Exception $e) {
        $error = '映画を検索できませんでした。時間を置いて再試行してください。';
    }
}

function escape(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}
?>

<!DOCTYPE hrml>
<html lang='ja'>

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>映画検索</title>
</head>

<body>
    <h1>映画検索</h1>

    <form action="movie-search.php" method="get">
        <label for="query">映画タイトル：</label>

        <input
            type="search"
            id="query"
            name="query"
            value="<?= escape($query) ?>"
            required>

        <button type="submit">検索</button>
    </form>

    <?php if ($query !== ''): ?>
        <p>検索キーワード：<?= escape($query) ?></p>
    <?php endif; ?>
</body>
<?php if ($error !== null): ?>
    <p><?= escape($error) ?></p>

<?php elseif ($query !== ''): ?>
    <?php if ($movies === []): ?>
        <p>該当する映画は見つかりませんでした。</p>
    <?php else: ?>
        <ul>
            <?php foreach ($movies as $movie): ?>
                <li>
                    <?php $posterPath = $movie['poster_path'] ?? null; ?>

                    <?php if (is_string($posterPath) && $posterPath !== ''): ?>
                        <img
                            src="<?= escape('https://image.tmdb.org/t/p/w500' . $posterPath) ?>"
                            alt=""
                            width="80"
                            style="height: auto; vertical-align: middle;"
                            loading="lazy">
                    <?php else: ?>
                        <span>画像なし</span>
                    <?php endif; ?>
                    <a href="movie.php?<?= escape(http_build_query([
                                            'id' => (int) $movie['id'],
                                            'query' => $query,
                                        ])) ?>">
                        <?= escape($movie['title'] ?? 'タイトル不明') ?>
                    </a>

                    （<?= escape($movie['release_date'] ?? '') ?>）
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
<?php endif; ?>

</html>