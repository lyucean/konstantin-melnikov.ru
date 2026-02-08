<?php
$isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest');

function jsonResponse($ok, $error = null) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['ok' => $ok, 'error' => $error]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    if ($isAjax) jsonResponse(false, 0);
    header('Location: index.html');
    exit;
}

$name = trim($_POST['name'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$email = trim($_POST['email'] ?? '');
$usluga = trim($_POST['usluga'] ?? '');
$message = trim($_POST['message'] ?? '');

if (!$name || !$phone || !$email || !$message) {
    if ($isAjax) jsonResponse(false, 1);
    header('Location: index.html?error=1');
    exit;
}

$token = trim(getenv('TELEGRAM_BOT_TOKEN') ?: ($_ENV['TELEGRAM_BOT_TOKEN'] ?? ''));
$chatId = trim(getenv('TELEGRAM_CHAT_ID') ?: ($_ENV['TELEGRAM_CHAT_ID'] ?? ''));

if ((!$token || !$chatId) && is_readable('/var/run/app.env')) {
    $lines = @file('/var/run/app.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if ($lines) {
        foreach ($lines as $line) {
            if (strpos($line, '=') !== false && $line[0] !== '#') {
                [$k, $v] = explode('=', $line, 2);
                $v = trim($v);
                if ($k === 'TELEGRAM_BOT_TOKEN') $token = $v;
                if ($k === 'TELEGRAM_CHAT_ID') $chatId = $v;
            }
        }
    }
}

if (!$token || !$chatId) {
    if ($isAjax) jsonResponse(false, 2);
    header('Location: index.html?error=2');
    exit;
}

$text = "Новая заявка с сайта\n\n";
$text .= "Имя: " . $name . "\n";
$text .= "Телефон: " . $phone . "\n";
$text .= "Email: " . $email . "\n";
if ($usluga) {
    $text .= "Интересует: " . $usluga . "\n";
}
$text .= "\nСообщение:\n" . $message;

$token = trim(preg_replace('/[\x00-\x1F\x7F]/', '', $token));
$chatId = trim(preg_replace('/[\x00-\x1F\x7F]/', '', $chatId));

$url = 'https://api.telegram.org/bot' . $token . '/sendMessage';
$data = ['chat_id' => $chatId, 'text' => $text];

$ctx = stream_context_create([
    'http' => [
        'method' => 'POST',
        'header' => 'Content-Type: application/x-www-form-urlencoded',
        'content' => http_build_query($data),
        'ignore_errors' => true,
        'timeout' => 10,
    ],
    'ssl' => [
        'verify_peer' => true,
        'verify_peer_name' => true,
    ],
]);

$result = @file_get_contents($url, false, $ctx);
$json = $result ? json_decode($result, true) : null;

if (empty($json['ok'])) {
    if ($json && isset($json['description'])) {
        error_log('[telegram.php] Telegram API: ' . $json['description']);
    } elseif (!$result) {
        error_log('[telegram.php] Telegram API: no response (check SSL/network)');
    }
    if ($isAjax) jsonResponse(false, 3);
    header('Location: index.html?error=3');
    exit;
}

if ($isAjax) jsonResponse(true);
header('Location: index.html?sent=1');
