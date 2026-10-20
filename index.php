
<?php
$name = "PHP";
?>

<!DOCTYPE html>
<html lang="ja>
<head>
    <meta charset="UTF-8">
    <title>PHPの練習</title>
</head>
<body>
    <h1>PHPの練習</h1>
    <p>こんにちは、 <?= htmlspecialchars($name) ?>! </p>
</body>
</html>