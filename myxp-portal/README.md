# MyXP Portal

フレームワークを使わないPHP学習用の、Windows XP風ポータルです。
メモ帳はSQLiteに保存します。他のアプリは起動用の枠のみです。

## 起動

PowerShellで次を実行します。

```powershell
cd C:\Users\user\Desktop\php-practice\myxp-portal
php --ini
php -m
php -S 127.0.0.1:8000 -t public
```

ブラウザで http://127.0.0.1:8000 を開きます。停止はCtrl+C。
設定変更前から起動していたPHPサーバーは再起動してください。
必ず `-t public` を指定します。storageやsrcを公開フォルダにしません。
PCを再起動した後は、上のサーバー起動コマンドを再実行します。

## PHP環境

調査時のPHPは `C:\php\php.exe`（8.5.11）です。
`C:\php\php.ini` は既に存在し、読み込まれていました。
必要なDLLとphp.ini-developmentも存在したため再インストールせず、次の3設定を有効化しました。

```ini
extension_dir = "C:/php/ext"
extension=pdo_sqlite
extension=sqlite3
```

変更前のバックアップ：`C:\php\php.ini.myxp-20261020120230.bak`。
`php -m` にpdo_sqliteとsqlite3が表示され、PDOの接続テストも成功しています。
このアプリで実際に使用するのはPDOとpdo_sqliteです。

## メモ帳

デスクトップのメモ帳をダブルクリックするか、スタートメニューから起動します。
新規ボタンでメモを作り、タイトルと本文を入力します。
入力を止めると1秒後に自動保存します。保存ボタンでも保存できます。
タイトルは必須（UTF-8で600バイトまで）、本文は100,000バイトまでです。
日本語は通常1文字3バイトなので、文字数とバイト数は異なります。

一覧からメモを選択して編集できます。削除時には確認が表示されます。
保存中は入力と操作を一時停止し、送信の重複を防ぎます。
通信エラー時は入力を保持します。接続を復旧して保存ボタンで再試行してください。
未保存の変更がある状態で切り替え・閉じる場合は確認します。
ブラウザ終了時の確認表示はブラウザの仕様に依存します。
「保存しました。」を確認してから終了してください。

保存先は `storage/myxp.sqlite`。初回APIアクセスでファイルとnotesテーブルを自動作成します。
ブラウザの保存領域ではなくディスク上のファイルなので、サーバーやPCを再起動しても残ります。
バックアップ時はサーバーを止めてこのファイルをコピーします。
検証用に「動作確認メモ」を1件残しています。

## ファイルの役割・変更理由

| ファイル | 担当・変更 |
| --- | --- |
| public/index.php | PHPの配列とforeachで初期画面を生成。acriptの誤記をscriptに修正し、notes.jsを追加 |
| public/css/style.css | XP風の配色を維持。メモ一覧・編集領域、小さい画面、ウィンドウの重なりを調整 |
| public/js/desktop.js | ウィンドウ操作。メモ帳を接続し、閉じる前の未保存確認、タスクバー、ドラッグ終了、メニューとキーボード操作を改善 |
| public/js/notes.js | 一覧・編集・自動保存・fetch通信・状態とエラー表示 |
| src/Database.php | PDO接続、初回テーブル作成、例外設定 |
| public/api/notes.php | HTTPメソッド別のCRUD、入力検証、プリペアドステートメント、JSON応答 |
| tests/notes-api.ps1 | HTTP経由のCRUD・日本語・入力検証を確認 |

## PHPとJavaScriptの役割

JavaScriptはブラウザで動き、入力やクリックを受け付け、fetchでJSONを送ります。
PHPはサーバーでリクエストごとに実行され、入力を再検証してSQLを実行し、JSONを返します。
ブラウザからSQLiteファイルを直接読み書きすることはありません。

APIはGETで一覧、POSTで作成、PUTで更新、DELETEで削除します。
PUT/DELETEには整数のidが必要です。POST/PUTには文字列のtitle/contentが必要です。
成功時はnotes/note/deleted、失敗時はerrorを含むJSONを返します。
SQLの値は `:title` などのプレースホルダーにexecuteで渡し、SQL文字列に連結しません。
画面への出力では、PHPのhtmlspecialcharsとJavaScriptのtextContent/valueを使います。
内部エラーの詳細はPHPサーバーのログに出し、画面には操作可能な説明を表示します。

Java/Pythonとの比較：

- PHPの変数名には `$` が付きます。連想配列はPythonのdictやJavaのMapに近い役割です。
- `->` はインスタンスのメソッド呼び出し、`::` はクラスの静的メソッド呼び出しです。
- `require_once` は別ファイルの定義を一度だけ読み込みます。
- `declare(strict_types=1)` は型宣言のある呼び出しで暗黙の型変換を抑えます。
  JSONやHTTP入力の安全性を保証するものではないため、別途is_string/is_int等で検証します。
- PHPの通常の変数はリクエストをまたいで保持されません。永続データはSQLiteに保存します。
- PDOのprepare/executeはJavaのPreparedStatementと同じく、SQLと値を分離します。

## 動作確認

PHPサーバーを起動したまま、PowerShell 7で実行します。

```powershell
pwsh -File tests/notes-api.ps1 -BaseUrl http://127.0.0.1:8000
php -l public/index.php
php -l public/api/notes.php
php -l src/Database.php
node --check public/js/desktop.js
node --check public/js/notes.js
```

APIテストは専用メモだけを作成・更新・削除し、既存メモを削除しません。
Node.jsは構文チェックにだけ使います。アプリの起動には不要です。

ブラウザでは新規作成→保存→編集→自動保存→再読込→メモ再表示を確認します。
最小化→タスクバーで復帰、最大化→復元、ドラッグ、閉じる→再起動も確認します。
サーバー停止中に保存するとエラーが出て入力が残り、再起動後に再試行できます。
検証時にPHPサーバー再起動後とブラウザ再読込後の保存内容を確認済みです。
PC自体の再起動は実施していません。

個人のローカル利用向けです。認証や複数タブ間の編集競合検出は未実装で、同じメモは最後の保存が優先されます。
作成リクエストがサーバーで成功した直後に通信が切れると、再試行で同内容のメモが増える場合があります。
この場合、入力を控えて一覧を再読込し、保存結果を確認してください。
