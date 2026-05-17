<?php
/**
 * Gerador de páginas de slides.
 */

require_once __DIR__ . '/../../db/config.php';

class SlideDeck {
    public static function ensureSchema() {
        $db = Database::getInstance()->getPDO();

        $db->exec("CREATE TABLE IF NOT EXISTS slide_decks (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(255) NOT NULL,
            slug VARCHAR(190) NOT NULL UNIQUE,
            description TEXT,
            status VARCHAR(30) DEFAULT 'draft',
            created_by INT UNSIGNED NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            published_at DATETIME,
            FOREIGN KEY (created_by) REFERENCES users(id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $db->exec("CREATE TABLE IF NOT EXISTS slide_items (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            deck_id INT UNSIGNED NOT NULL,
            title VARCHAR(255),
            content LONGTEXT,
            image VARCHAR(500),
            order_num INT DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (deck_id) REFERENCES slide_decks(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $database = Database::getInstance();
        $database->createIndexIfMissing('slide_decks', 'idx_slide_decks_status', '`status`');
        $database->createIndexIfMissing('slide_items', 'idx_slide_items_deck', '`deck_id`');
    }

    public static function getList($filters = []) {
        self::ensureSchema();
        $where = "1=1";

        if (!empty($filters['status'])) {
            $where .= " AND status = '" . self::escape($filters['status']) . "'";
        }

        if (!empty($filters['search'])) {
            $search = self::escape($filters['search']);
            $where .= " AND (title LIKE '%$search%' OR description LIKE '%$search%')";
        }

        $limit = !empty($filters['limit']) ? " LIMIT " . intval($filters['limit']) : "";
        return Database::getInstance()->select('slide_decks', $where, "ORDER BY updated_at DESC, created_at DESC" . $limit);
    }

    public static function getById($deckId) {
        self::ensureSchema();
        $deck = Database::getInstance()->selectOne('slide_decks', "id = ?", [$deckId]);
        if (!$deck) {
            return null;
        }

        $deck['slides'] = self::getSlides($deckId);
        return $deck;
    }

    public static function getBySlug($slug) {
        self::ensureSchema();
        $deck = Database::getInstance()->selectOne('slide_decks', "slug = ?", [$slug]);
        if (!$deck) {
            return null;
        }

        $deck['slides'] = self::getSlides($deck['id']);
        return $deck;
    }

    public static function create($data, $userId) {
        try {
            self::ensureSchema();
            $slug = self::normalizeSlug($data['slug'] ?? $data['title']);
            $errors = self::validate($data, $slug);

            if (!empty($errors)) {
                return ['success' => false, 'message' => implode('<br>', $errors)];
            }

            if (Database::getInstance()->selectOne('slide_decks', "slug = ?", [$slug])) {
                return ['success' => false, 'message' => 'Já existe uma apresentação com este slug'];
            }

            $deckId = Database::getInstance()->insert('slide_decks', [
                'title' => trim($data['title']),
                'slug' => $slug,
                'description' => trim($data['description'] ?? ''),
                'status' => $data['status'] ?? 'draft',
                'created_by' => $userId,
                'published_at' => ($data['status'] ?? 'draft') === 'published' ? date('Y-m-d H:i:s') : null
            ]);

            self::saveSlides($deckId, $data['slides'] ?? []);
            self::syncPublicPage($slug);

            return ['success' => true, 'message' => 'Apresentação criada com sucesso', 'id' => $deckId];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Erro ao criar apresentação: ' . $e->getMessage()];
        }
    }

    public static function update($deckId, $data) {
        try {
            self::ensureSchema();
            $deck = self::getById($deckId);
            if (!$deck) {
                return ['success' => false, 'message' => 'Apresentação não encontrada'];
            }

            $slug = self::normalizeSlug($data['slug'] ?? $data['title']);
            $errors = self::validate($data, $slug);

            if (!empty($errors)) {
                return ['success' => false, 'message' => implode('<br>', $errors)];
            }

            $existing = Database::getInstance()->selectOne('slide_decks', "slug = ?", [$slug]);
            if ($existing && intval($existing['id']) !== intval($deckId)) {
                return ['success' => false, 'message' => 'Já existe uma apresentação com este slug'];
            }

            Database::getInstance()->update('slide_decks', [
                'title' => trim($data['title']),
                'slug' => $slug,
                'description' => trim($data['description'] ?? ''),
                'status' => $data['status'] ?? 'draft',
                'updated_at' => date('Y-m-d H:i:s'),
                'published_at' => (($data['status'] ?? 'draft') === 'published' && empty($deck['published_at'])) ? date('Y-m-d H:i:s') : $deck['published_at']
            ], "id = " . intval($deckId));

            self::saveSlides($deckId, $data['slides'] ?? []);

            if ($deck['slug'] !== $slug) {
                self::removePublicPage($deck['slug']);
            }

            self::syncPublicPage($slug);

            return ['success' => true, 'message' => 'Apresentação atualizada com sucesso'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Erro ao atualizar apresentação: ' . $e->getMessage()];
        }
    }

    public static function delete($deckId) {
        try {
            self::ensureSchema();
            $deck = self::getById($deckId);
            if (!$deck) {
                return ['success' => false, 'message' => 'Apresentação não encontrada'];
            }

            Database::getInstance()->delete('slide_decks', "id = " . intval($deckId));
            self::removePublicPage($deck['slug']);

            return ['success' => true, 'message' => 'Apresentação removida com sucesso'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Erro ao remover apresentação: ' . $e->getMessage()];
        }
    }

    public static function publicUrl($slug) {
        return '/slides/' . self::normalizeSlug($slug) . '/';
    }

    private static function getSlides($deckId) {
        return Database::getInstance()->select('slide_items', "deck_id = " . intval($deckId), "ORDER BY order_num ASC, id ASC");
    }

    private static function saveSlides($deckId, $slides) {
        Database::getInstance()->delete('slide_items', "deck_id = " . intval($deckId));

        foreach ($slides as $index => $slide) {
            $title = trim($slide['title'] ?? '');
            $content = trim($slide['content'] ?? '');
            $image = trim($slide['image'] ?? '');

            if ($title === '' && $content === '' && $image === '') {
                continue;
            }

            Database::getInstance()->insert('slide_items', [
                'deck_id' => intval($deckId),
                'title' => $title,
                'content' => $content,
                'image' => $image,
                'order_num' => intval($slide['order_num'] ?? (($index + 1) * 10))
            ]);
        }
    }

    private static function validate($data, $slug) {
        $errors = [];

        if (trim($data['title'] ?? '') === '') {
            $errors[] = 'Título é obrigatório';
        }

        if ($slug === '') {
            $errors[] = 'Slug é obrigatório';
        }

        if (!in_array($data['status'] ?? 'draft', ['draft', 'published'], true)) {
            $errors[] = 'Status inválido';
        }

        $hasSlide = false;
        foreach (($data['slides'] ?? []) as $slide) {
            if (trim($slide['title'] ?? '') !== '' || trim($slide['content'] ?? '') !== '' || trim($slide['image'] ?? '') !== '') {
                $hasSlide = true;
                break;
            }
        }

        if (!$hasSlide) {
            $errors[] = 'Adicione pelo menos um slide';
        }

        return $errors;
    }

    private static function syncPublicPage($slug) {
        $dir = __DIR__ . '/../../slides/' . self::normalizeSlug($slug);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $index = $dir . '/index.php';
        $content = "<?php\n\$slideDeckSlug = '" . addslashes(self::normalizeSlug($slug)) . "';\nrequire __DIR__ . '/../../templates/slides-viewer.php';\n";
        file_put_contents($index, $content);
    }

    private static function removePublicPage($slug) {
        $dir = __DIR__ . '/../../slides/' . self::normalizeSlug($slug);
        $index = $dir . '/index.php';

        if (is_file($index)) {
            unlink($index);
        }

        if (is_dir($dir)) {
            @rmdir($dir);
        }
    }

    private static function normalizeSlug($value) {
        $slug = iconv('UTF-8', 'ASCII//TRANSLIT', trim($value ?? ''));
        $slug = preg_replace('/[^a-z0-9]+/i', '-', $slug);
        $slug = trim($slug, '-');
        return strtolower($slug);
    }

    private static function escape($value) {
        return str_replace("'", "''", trim($value));
    }
}
?>
