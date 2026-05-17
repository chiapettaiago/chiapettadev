<?php
/**
 * Comentários de leitores nos posts públicos.
 */

require_once __DIR__ . '/../../db/config.php';

class Comment {
    public static function ensureSchema() {
        $db = Database::getInstance()->getPDO();

        $db->exec("CREATE TABLE IF NOT EXISTS comments (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            post_slug VARCHAR(190) NOT NULL,
            user_id INT UNSIGNED NOT NULL,
            content TEXT NOT NULL,
            status VARCHAR(30) DEFAULT 'published',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $database = Database::getInstance();
        $database->createIndexIfMissing('comments', 'idx_comments_post_slug', '`post_slug`');
        $database->createIndexIfMissing('comments', 'idx_comments_status', '`status`');
        $database->createIndexIfMissing('comments', 'idx_comments_created_at', '`created_at`');
    }

    public static function create($postSlug, $userId, $content) {
        try {
            self::ensureSchema();

            $postSlug = trim($postSlug);
            $content = trim($content);

            if ($postSlug === '') {
                return ['success' => false, 'message' => 'Post inválido'];
            }

            if ($content === '') {
                return ['success' => false, 'message' => 'Comentário não pode ficar vazio'];
            }

            $contentLength = function_exists('mb_strlen') ? mb_strlen($content) : strlen($content);
            if ($contentLength > 2000) {
                return ['success' => false, 'message' => 'Comentário deve ter no máximo 2000 caracteres'];
            }

            Database::getInstance()->insert('comments', [
                'post_slug' => $postSlug,
                'user_id' => intval($userId),
                'content' => $content,
                'status' => 'published',
                'created_at' => date('Y-m-d H:i:s')
            ]);

            return ['success' => true, 'message' => 'Comentário publicado'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Erro ao comentar: ' . $e->getMessage()];
        }
    }

    public static function getByPost($postSlug) {
        self::ensureSchema();

        return Database::getInstance()
            ->query(
                "SELECT comments.*, users.full_name, users.username
                 FROM comments
                 INNER JOIN users ON users.id = comments.user_id
                 WHERE comments.post_slug = ? AND comments.status = 'published'
                 ORDER BY comments.created_at ASC",
                [$postSlug]
            )
            ->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>
