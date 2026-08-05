<?php
/**
 * Rascunhos automáticos do editor de posts.
 * Mantém o autosave separado do post publicado até o salvamento manual.
 */

define('CMS_SKIP_AUTO_INIT', true);
require_once __DIR__ . '/../../db/config.php';
require_once __DIR__ . '/../modules/Auth.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

if (!Auth::hasPermission('author')) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Sessão expirada']);
    exit;
}

$user = Auth::getCurrentUser();
$db = Database::getInstance();
$pdo = $db->getPDO();

$pdo->exec("CREATE TABLE IF NOT EXISTS post_autosaves (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    post_id INT UNSIGNED NULL,
    draft_token VARCHAR(80) NOT NULL,
    payload LONGTEXT NOT NULL,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_post_autosave_user_token (user_id, draft_token),
    KEY idx_post_autosave_post (post_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$token = trim(($method === 'GET' ? ($_GET['token'] ?? '') : ($_POST['token'] ?? '')));

if ($token === '' || !preg_match('/^[a-zA-Z0-9_-]{12,80}$/', $token)) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Identificador de rascunho inválido']);
    exit;
}

if ($method === 'GET') {
    $autosave = $db->selectOne('post_autosaves', 'user_id = ? AND draft_token = ?', [$user['id'], $token]);
    echo json_encode([
        'success' => true,
        'autosave' => $autosave ? [
            'payload' => json_decode($autosave['payload'], true),
            'updated_at' => $autosave['updated_at']
        ] : null
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

if ($method !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método não permitido']);
    exit;
}

$action = $_POST['autosave_action'] ?? 'save';
if ($action === 'discard') {
    $stmt = $pdo->prepare('DELETE FROM post_autosaves WHERE user_id = ? AND draft_token = ?');
    $stmt->execute([$user['id'], $token]);
    echo json_encode(['success' => true]);
    exit;
}

$postId = intval($_POST['post_id'] ?? 0);
if ($postId > 0) {
    $post = $db->selectOne('posts', 'id = ?', [$postId]);
    if (!$post || (intval($post['author_id']) !== intval($user['id']) && !Auth::hasPermission('admin'))) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Sem permissão para este post']);
        exit;
    }
}

$payload = json_decode($_POST['payload'] ?? '', true);
if (!is_array($payload)) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Conteúdo do rascunho inválido']);
    exit;
}

$allowedFields = ['title', 'slug', 'content', 'excerpt', 'status', 'published_at', 'featured_image', 'tags', 'categories'];
$cleanPayload = [];
foreach ($allowedFields as $field) {
    if (array_key_exists($field, $payload)) {
        $cleanPayload[$field] = is_array($payload[$field])
            ? array_values(array_map('strval', $payload[$field]))
            : (string) $payload[$field];
    }
}

$encodedPayload = json_encode($cleanPayload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
if ($encodedPayload === false || strlen($encodedPayload) > 4000000) {
    http_response_code(413);
    echo json_encode(['success' => false, 'message' => 'Rascunho muito grande']);
    exit;
}

$stmt = $pdo->prepare("INSERT INTO post_autosaves (user_id, post_id, draft_token, payload)
    VALUES (?, NULLIF(?, 0), ?, ?)
    ON DUPLICATE KEY UPDATE post_id = VALUES(post_id), payload = VALUES(payload), updated_at = CURRENT_TIMESTAMP");
$stmt->execute([$user['id'], $postId, $token, $encodedPayload]);

echo json_encode([
    'success' => true,
    'saved_at' => date('H:i')
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
