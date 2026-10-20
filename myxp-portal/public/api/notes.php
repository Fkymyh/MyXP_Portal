<?php

declare(strict_types=1);

// 詳細はサーバーログへ。レスポンスにPHP警告やファイルパスを混ぜません。
ini_set('display_errors', '0');
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');
require_once __DIR__ . '/../../src/Database.php';

function respond(int $status, array $data): never
{
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    exit;
}

try {
    $method = $_SERVER['REQUEST_METHOD'];
    if (!in_array($method, ['GET', 'POST', 'PUT', 'DELETE'], true)) {
        header('Allow: GET, POST, PUT, DELETE');
        respond(405, ['error' => '対応していない操作です。']);
    }
    if ($method !== 'GET') {
        // JSONのみ受け付け、他サイトのフォームからの書き込みを防ぎます。
        $type = strtolower(trim(explode(';', $_SERVER['CONTENT_TYPE'] ?? '')[0]));
        if ($type !== 'application/json') {
            respond(415, ['error' => 'JSON形式で送信してください。']);
        }
        if (($_SERVER['HTTP_SEC_FETCH_SITE'] ?? '') === 'cross-site') {
            respond(403, ['error' => '別サイトからの操作はできません。']);
        }
        $raw = file_get_contents('php://input', false, null, 0, 1100001);
        if ($raw === false || strlen($raw) > 1100000) {
            respond(413, ['error' => '送信データが大きすぎます。']);
        }
        try {
            $data = json_decode($raw, true, 32, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            respond(400, ['error' => 'JSONの形式が正しくありません。']);
        }
        if (!is_array($data)) {
            respond(400, ['error' => '入力データが正しくありません。']);
        }
    }
    if ($method === 'PUT' || $method === 'DELETE') {
        $id = $data['id'] ?? null;
        if (!is_int($id) || $id < 1) {
            respond(422, ['error' => 'メモIDが正しくありません。']);
        }
    }
    if ($method === 'POST' || $method === 'PUT') {
        $title = $data['title'] ?? null;
        $content = $data['content'] ?? null;
        if (!is_string($title) || trim($title) === '' || strlen($title) > 600
            || !is_string($content) || strlen($content) > 100000) {
            respond(422, ['error' => 'タイトルは必須・600バイト以内、本文は100,000バイト以内です。']);
        }
        $title = trim($title);
    }
    $pdo = Database::connect();
    if ($method === 'GET') {
        $statement = $pdo->prepare('SELECT * FROM notes ORDER BY updated_at DESC, id DESC');
        $statement->execute();
        respond(200, ['notes' => $statement->fetchAll()]);
    }
    if ($method === 'DELETE') {
        $statement = $pdo->prepare('DELETE FROM notes WHERE id = :id');
        $statement->execute(['id' => $id]);
        if ($statement->rowCount() === 0) respond(404, ['error' => 'メモが見つかりません。']);
        respond(200, ['deleted' => $id]);
    }
    if ($method === 'POST') {
        $statement = $pdo->prepare('INSERT INTO notes (title, content) VALUES (:title, :content)');
        $statement->execute(['title' => $title, 'content' => $content]);
        $id = (int) $pdo->lastInsertId();
    } else {
        $statement = $pdo->prepare('UPDATE notes SET title = :title, content = :content,
            updated_at = CURRENT_TIMESTAMP WHERE id = :id');
        $statement->execute(['title' => $title, 'content' => $content, 'id' => $id]);
        if ($statement->rowCount() === 0) respond(404, ['error' => 'メモが見つかりません。']);
    }
    $statement = $pdo->prepare('SELECT * FROM notes WHERE id = :id');
    $statement->execute(['id' => $id]);
    respond($method === 'POST' ? 201 : 200, ['note' => $statement->fetch()]);
} catch (Throwable $e) {
    error_log((string) $e);
    respond(500, ['error' => '保存先にアクセスできません。PHPのSQLite設定とstorageの書き込み権限を確認してください。']);
}
