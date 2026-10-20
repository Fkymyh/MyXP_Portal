<?php

declare(strict_types=1);

$apps = [
    [
        "id" => "memo",
        "name" => "メモ帳",
        "icon" => "📝"
    ],
    [
        "id" => "movies",
        "name" => "映画情報",
        "icon" => "🎬"
    ],
    [
        "id" => "bookmarks",
        "name" => "お気に入り",
        "icon" => "📁"
    ],
    [
        "id" => "news",
        "name" => "ITニュース",
        "icon" => "📰"
    ],
    [
        "id" => "weather",
        "name" => "天気",
        "icon" => "☀️"
    ],
    [
        "id" => "calendar",
        "name" => "カレンダー",
        "icon" => "📅"
    ]
];
?>

<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MyXP Portal</title>
    <link rel="stylesheet" href="css/style.css">
    <script src="js/notes.js" defer></script>
    <script src="js/desktop.js" defer></script>
</head>

<body>

    <main id="desktop">

        <div id="desktop-icons">
            <?php foreach ($apps as $app): ?>
                <button
                    class="desktop-icon"
                    type="button"
                    data-app="<?= htmlspecialchars($app['id']) ?>">
                    <span class="app-icon">
                        <?= $app['icon'] ?>
                    </span>
                    <span>
                        <?= htmlspecialchars($app['name']) ?>
                    </span>
                </button>
            <?php endforeach; ?>
        </div>

        <div id="windows"></div>

    </main>

    <footer id="taskbar">
        <button id="start-button" type="button">
            スタート
        </button>

        <div id="start-menu" hidden>
            <?php foreach ($apps as $app): ?>
                <button type="button"
                    data-app="<?= htmlspecialchars($app['id']) ?>">
                    <?= $app['icon'] ?>
                    <?= htmlspecialchars($app['name']) ?>
                </button>
            <?php endforeach; ?>
        </div>

        <div id="taskbar-apps"></div>

        <div id="clock"></div>
    </footer>
</body>

</html>
