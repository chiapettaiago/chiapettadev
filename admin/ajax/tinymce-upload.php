<?php
require_once __DIR__ . '/../../db/config.php';
require_once __DIR__ . '/../modules/Auth.php';
require_once __DIR__ . '/../modules/Image.php';

header('Content-Type: application/json; charset=utf-8');

if (!Auth::hasPermission('author')) {
    http_response_code(403);
    echo json_encode(['error' => 'Não autorizado']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Método não permitido']);
    exit;
}

$type = $_GET['type'] ?? 'image';
$file = $_FILES['file'] ?? $_FILES['upload'] ?? null;

if (!$file || empty($file['name'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Nenhum arquivo enviado']);
    exit;
}

$user = Auth::getCurrentUser();

if ($type === 'image') {
    $result = Image::upload($file, $user['id'], [
        'title' => pathinfo($file['name'], PATHINFO_FILENAME),
        'alt_text' => pathinfo($file['name'], PATHINFO_FILENAME),
        'description' => 'Arquivo enviado pelo editor TinyMCE.'
    ]);

    if (!$result['success']) {
        http_response_code(400);
        echo json_encode(['error' => $result['message']]);
        exit;
    }

    echo json_encode(['location' => $result['filepath']]);
    exit;
}

if ($type !== 'media') {
    http_response_code(400);
    echo json_encode(['error' => 'Tipo de upload inválido']);
    exit;
}

$allowedTypes = [
    'video/mp4' => 'mp4',
    'video/webm' => 'webm',
    'video/ogg' => 'ogv',
    'video/quicktime' => 'mov'
];

$mimeType = null;
if (function_exists('finfo_open')) {
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    if ($finfo) {
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
    }
}

if (!$mimeType && function_exists('mime_content_type')) {
    $mimeType = mime_content_type($file['tmp_name']);
}

$mimeType = $mimeType ?: ($file['type'] ?? '');

if (!isset($allowedTypes[$mimeType])) {
    http_response_code(400);
    echo json_encode(['error' => 'Tipo de vídeo não permitido. Use MP4, WebM, OGG ou MOV.']);
    exit;
}

if ($file['size'] > 52428800) {
    http_response_code(400);
    echo json_encode(['error' => 'Vídeo muito grande (máximo 50MB)']);
    exit;
}

$targetDir = UPLOADS_PATH . 'media/';
if (!is_dir($targetDir) && !mkdir($targetDir, 0755, true)) {
    http_response_code(500);
    echo json_encode(['error' => 'Não foi possível criar a pasta de mídia']);
    exit;
}

if (!is_writable($targetDir)) {
    http_response_code(500);
    echo json_encode(['error' => 'Pasta de mídia sem permissão de escrita']);
    exit;
}

$extension = $allowedTypes[$mimeType];
$filename = uniqid('video_') . '.' . $extension;
$destination = $targetDir . $filename;

if (!move_uploaded_file($file['tmp_name'], $destination)) {
    http_response_code(500);
    echo json_encode(['error' => 'Erro ao salvar o vídeo']);
    exit;
}

echo json_encode(['location' => '/admin/uploads/media/' . $filename]);
