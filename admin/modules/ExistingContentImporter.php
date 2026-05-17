<?php
/**
 * Importa conteúdo estático existente para o CMS.
 */

require_once __DIR__ . '/../../db/config.php';

class ExistingContentImporter {
    public static function ensureImported() {
        self::importImages();
        self::importPages();
        self::importPosts();
    }

    private static function importImages() {
        $files = glob(__DIR__ . '/../../images/*.{jpg,jpeg,png,gif,webp}', GLOB_BRACE);
        $userId = self::getDefaultUserId();

        foreach ($files as $file) {
            $filename = basename($file);
            $publicPath = '/images/' . $filename;
            $existing = Database::getInstance()->selectOne('images', "filepath = '" . self::escape($publicPath) . "'");

            if ($existing) {
                continue;
            }

            $title = self::titleFromFilename($filename);
            $mimeType = function_exists('mime_content_type') ? mime_content_type($file) : self::mimeFromExtension($file);

            Database::getInstance()->insert('images', [
                'title' => $title,
                'filename' => $filename,
                'filepath' => $publicPath,
                'mime_type' => $mimeType,
                'file_size' => filesize($file) ?: 0,
                'uploaded_by' => $userId,
                'alt_text' => $title,
                'description' => 'Imagem existente importada automaticamente.'
            ]);
        }
    }

    private static function importPages() {
        $pages = [
            [
                'title' => 'Página Inicial',
                'slug' => 'pagina-inicial',
                'file' => __DIR__ . '/../../index.php',
                'order_num' => 10
            ],
            [
                'title' => 'Blog',
                'slug' => 'blog',
                'file' => __DIR__ . '/../../blog/index.php',
                'order_num' => 20
            ]
        ];

        $userId = self::getDefaultUserId();

        foreach ($pages as $page) {
            $existing = Database::getInstance()->selectOne('pages', "slug = '" . self::escape($page['slug']) . "'");

            if ($existing || !is_file($page['file'])) {
                continue;
            }

            Database::getInstance()->insert('pages', [
                'title' => $page['title'],
                'slug' => $page['slug'],
                'content' => self::extractMainContent($page['file']),
                'featured_image' => '',
                'author_id' => $userId,
                'status' => 'published',
                'parent_id' => null,
                'order_num' => $page['order_num'],
                'published_at' => date('Y-m-d H:i:s')
            ]);
        }
    }

    private static function importPosts() {
        $postFiles = glob(__DIR__ . '/../../blog/*/index.php');
        $userId = self::getDefaultUserId();

        foreach ($postFiles as $file) {
            $slug = basename(dirname($file));
            $existing = Database::getInstance()->selectOne('posts', "slug = '" . self::escape($slug) . "'");

            if ($existing) {
                continue;
            }

            $html = file_get_contents($file);
            $title = self::extractFirstMatch($html, '/<h1[^>]*>(.*?)<\/h1>/is') ?: self::titleFromFilename($slug);
            $content = self::extractArticleContent($html);
            $excerpt = self::extractFirstParagraph($content);
            $publishedAt = self::publishedDateForSlug($slug);

            Database::getInstance()->insert('posts', [
                'title' => self::cleanText($title),
                'slug' => $slug,
                'content' => $content,
                'excerpt' => $excerpt,
                'featured_image' => '',
                'author_id' => $userId,
                'status' => 'published',
                'views' => 0,
                'published_at' => $publishedAt,
                'created_at' => $publishedAt,
                'updated_at' => date('Y-m-d H:i:s')
            ]);
        }
    }

    private static function extractMainContent($file) {
        $html = file_get_contents($file);
        $content = self::extractFirstMatch($html, '/<main[^>]*>(.*?)<\/main>/is');

        if (!$content) {
            $content = preg_replace('/<\?php.*?\?>/is', '', $html);
        }

        return trim($content);
    }

    private static function extractArticleContent($html) {
        $content = self::extractFirstMatch(
            $html,
            '/<!-- Article Content -->(.*?)<!-- Related Articles -->/is'
        );

        if (!$content) {
            $content = self::extractFirstMatch($html, '/<main[^>]*>(.*?)<\/main>/is');
        }

        return trim($content ?: $html);
    }

    private static function extractFirstParagraph($html) {
        $paragraph = self::extractFirstMatch($html, '/<p[^>]*>(.*?)<\/p>/is');
        $text = self::cleanText($paragraph ?: strip_tags($html));

        return mb_substr($text, 0, 180);
    }

    private static function extractFirstMatch($text, $pattern) {
        if (preg_match($pattern, $text, $matches)) {
            return trim($matches[1]);
        }

        return '';
    }

    private static function cleanText($text) {
        return trim(html_entity_decode(strip_tags($text), ENT_QUOTES, 'UTF-8'));
    }

    private static function titleFromFilename($filename) {
        $name = pathinfo($filename, PATHINFO_FILENAME);
        $name = str_replace(['-', '_'], ' ', $name);
        return mb_convert_case($name, MB_CASE_TITLE, 'UTF-8');
    }

    private static function publishedDateForSlug($slug) {
        $dates = [
            'minha-primeira-experiencia-como-programador-clt-como-esta-sendo' => '2025-12-01 00:00:00',
            'testei-o-gpt-5-3-codex-veja-o-que-encontrei' => '2026-01-15 00:00:00',
            'ia-codifica-desenvolvedores-hora-de-reimaginarmos-nosso-papel' => '2026-02-01 00:00:00'
        ];

        return $dates[$slug] ?? date('Y-m-d H:i:s');
    }

    private static function getDefaultUserId() {
        $admin = Database::getInstance()->selectOne('users', "username = 'admin'");

        if ($admin) {
            return intval($admin['id']);
        }

        $user = Database::getInstance()->selectOne('users', '1=1');

        if ($user) {
            return intval($user['id']);
        }

        return intval(Database::getInstance()->insert('users', [
            'username' => 'admin',
            'email' => 'admin@chiapetta.dev',
            'password' => password_hash('admin123', PASSWORD_BCRYPT),
            'full_name' => 'Administrador',
            'role' => 'admin',
            'status' => 'active'
        ]));
    }

    private static function mimeFromExtension($file) {
        $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));
        $types = [
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'webp' => 'image/webp'
        ];

        return $types[$extension] ?? 'application/octet-stream';
    }

    private static function escape($value) {
        return str_replace("'", "''", trim($value));
    }
}
?>
