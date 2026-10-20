param([string]$BaseUrl = 'http://127.0.0.1:8088')
$ErrorActionPreference = 'Stop'
$endpoint = "$BaseUrl/api/notes.php"
function Check($condition, $label) {
    if (-not $condition) { throw "FAIL: $label" }
    Write-Output "PASS: $label"
}
function Send($method, $data) {
    $json = ConvertTo-Json -InputObject $data -Compress
    Invoke-RestMethod -Uri $endpoint -Method $method -ContentType 'application/json; charset=utf-8' -Body ([System.Text.Encoding]::UTF8.GetBytes($json)) -TimeoutSec 10
}
$createdId = $null
try {
    $created = Send 'POST' @{ title = 'APIテスト <script>'; content = "日本語と引用符 ' `" & <b>本文</b>`n2行目" }
    $createdId = $created.note.id
    Check ($createdId -gt 0) 'create'
    $loaded = Invoke-RestMethod -Uri $endpoint -TimeoutSec 10
    $note = $loaded.notes | Where-Object id -EQ $createdId
    Check ($note.content -ceq $created.note.content) 'separate HTTP request persists Japanese and HTML text'
    $updated = Send 'PUT' @{ id = $createdId; title = '更新'; content = 'updated' }
    Check ($updated.note.content -eq 'updated') 'update'
    foreach ($case in @(
        @{ Method='POST'; Body='{"title":"","content":""}'; Status=422 },
        @{ Method='PUT'; Body='{"id":"1","title":"test","content":""}'; Status=422 },
        @{ Method='PUT'; Body='{"id":2147483647,"title":"test","content":""}'; Status=404 },
        @{ Method='POST'; Body='{'; Status=400 }
    )) {
        $response = Invoke-WebRequest -Uri $endpoint -Method $case.Method -ContentType 'application/json' -Body $case.Body -SkipHttpErrorCheck -TimeoutSec 10
        Check ([int]$response.StatusCode -eq $case.Status) "validation HTTP $($case.Status)"
    }
    $response = Invoke-WebRequest -Uri $endpoint -Method POST -ContentType 'text/plain' -Body '{}' -SkipHttpErrorCheck -TimeoutSec 10
    Check ([int]$response.StatusCode -eq 415) 'reject non-JSON writes'
    $response = Invoke-WebRequest -Uri $endpoint -Method POST -ContentType 'application/json' -Headers @{'Sec-Fetch-Site'='cross-site'} -Body '{}' -SkipHttpErrorCheck -TimeoutSec 10
    Check ([int]$response.StatusCode -eq 403) 'reject cross-site writes'
    $response = Invoke-WebRequest -Uri "$BaseUrl/../storage/myxp.sqlite" -SkipHttpErrorCheck -TimeoutSec 10
    # PHP組み込みサーバーは不存在パスにindex.phpを返す場合があります。
    Check ($response.Headers['Content-Type'] -match 'text/html' -and "$($response.Content)" -notmatch 'SQLite format 3') 'database outside document root'
} finally {
    # このテストが作成したメモだけを削除します。
    if ($null -ne $createdId) {
        $deleted = Send 'DELETE' @{ id = $createdId }
        Check ($deleted.deleted -eq $createdId) 'delete test note'
    }
}
