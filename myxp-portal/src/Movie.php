<?php

declare(strict_types=1);

function fetchMovie(int $movieId): array
{
    if ($movieId < 1) {
        throw new InvalidArgumentException('映画IDは1以上にしてください。');
    }

    //srcの1つ上にある設定ファイルを読み込みます。
    $config = require __DIR__ . '/../config.local.php';

    $query = http_build_query([
        'api_key' => $config['tmdb_api_key'],
        'language' => 'ja-JP',
    ]);

    // 引数で受け取った映画IDをURLに使います。
    $url = 'https://api.themoviedb.org/3/movie/'
        . $movieId . '?' . $query;

    $curl = curl_init($url);

    curl_setopt_array($curl, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_TIMEOUT => 20,
    ]);

    $response = curl_exec($curl);

    if ($response === false) {
        throw new RuntimeException(
            '映画情報の通信に失敗しました。 エラー番号：'
                . curl_errno($curl)
        );
    }

    $status = curl_getinfo($curl, CURLINFO_HTTP_CODE);

    if ($status !== 200) {
        throw new RuntimeException(
            '映画情報を取得できませんでした。 HTTP：' . $status
        );
    }

    $movie = json_decode(
        $response,
        true,
        512,
        JSON_THROW_ON_ERROR
    );

    if (!is_array($movie)) {
        throw new RuntimeException('映画情報の形式が正しくありません。');
    }

    return $movie;
}
