<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');

$action = $_GET['action'] ?? '';
$room = $_GET['room'] ?? '';

// 5桁の数字コードの検証
if (!preg_match('/^\d{5}$/', $room)) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid room code']);
    exit;
}

$filename = __DIR__ . "/data_{$room}.json";

// --- メッセージ書き込み (POST) ---
if ($action === 'send' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input || empty($input['text'])) {
        echo json_encode(['status' => 'error', 'message' => 'No text provided']);
        exit;
    }

    // 既存ファイルの読み込み（存在しなければ新規作成）
    $data = [];
    if (file_exists($filename)) {
        $jsonStr = file_get_contents($filename);
        $data = json_decode($jsonStr, true) ?: [];
    }

    // 新規メッセージオブジェクトの生成
    $newMessage = [
        'id' => uniqid('msg_', true),
        'userId' => $input['userId'] ?? '',
        'sender' => $input['sender'] ?? '名無し',
        'text' => $input['text'],
        'fromIso' => $input['fromIso'] ?? 'ja',
        'time' => date('H:i:s'),
        'timestamp' => time()
    ];

    $data[] = $newMessage;

    // テキストファイル（JSON）への保存
    file_put_contents($filename, json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT), LOCK_EX);

    echo json_encode(['status' => 'success', 'data' => $newMessage]);
    exit;
}

// --- メッセージ読み込み (GET) ---
if ($action === 'get') {
    if (!file_exists($filename)) {
        echo json_encode(['status' => 'success', 'messages' => []]);
        exit;
    }

    $jsonStr = file_get_contents($filename);
    $data = json_decode($jsonStr, true) ?: [];

    echo json_encode(['status' => 'success', 'messages' => $data]);
    exit;
}

echo json_encode(['status' => 'error', 'message' => 'Invalid action']);
