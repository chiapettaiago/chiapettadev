<?php
/**
 * Inicializacao do banco MySQL - criar tabelas e dados minimos.
 */

require_once __DIR__ . '/config.php';

$db = Database::getInstance()->getPDO();

try {
    $db->exec("SET NAMES utf8mb4");
    $db->exec("SET FOREIGN_KEY_CHECKS = 0");

    $db->exec("CREATE TABLE IF NOT EXISTS users (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(80) NOT NULL UNIQUE,
        email VARCHAR(190) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        full_name VARCHAR(190) NOT NULL,
        role VARCHAR(30) DEFAULT 'editor',
        status VARCHAR(30) DEFAULT 'active',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $db->exec("CREATE TABLE IF NOT EXISTS posts (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(255) NOT NULL,
        slug VARCHAR(190) NOT NULL UNIQUE,
        content LONGTEXT NOT NULL,
        excerpt TEXT,
        featured_image VARCHAR(500),
        author_id INT UNSIGNED NOT NULL,
        status VARCHAR(30) DEFAULT 'draft',
        views INT UNSIGNED DEFAULT 0,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        published_at DATETIME NULL,
        FOREIGN KEY (author_id) REFERENCES users(id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $db->exec("CREATE TABLE IF NOT EXISTS pages (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(255) NOT NULL,
        slug VARCHAR(190) NOT NULL UNIQUE,
        content LONGTEXT NOT NULL,
        featured_image VARCHAR(500),
        author_id INT UNSIGNED NOT NULL,
        status VARCHAR(30) DEFAULT 'draft',
        parent_id INT UNSIGNED NULL,
        order_num INT DEFAULT 0,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        published_at DATETIME NULL,
        FOREIGN KEY (author_id) REFERENCES users(id),
        FOREIGN KEY (parent_id) REFERENCES pages(id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $db->exec("CREATE TABLE IF NOT EXISTS images (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(255) NOT NULL,
        filename VARCHAR(255) NOT NULL,
        filepath VARCHAR(500) NOT NULL,
        mime_type VARCHAR(120),
        file_size BIGINT UNSIGNED,
        uploaded_by INT UNSIGNED NOT NULL,
        alt_text VARCHAR(255),
        description TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (uploaded_by) REFERENCES users(id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $db->exec("CREATE TABLE IF NOT EXISTS tags (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(120) NOT NULL UNIQUE,
        slug VARCHAR(160) NOT NULL UNIQUE,
        description TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $db->exec("CREATE TABLE IF NOT EXISTS post_tags (
        post_id INT UNSIGNED NOT NULL,
        tag_id INT UNSIGNED NOT NULL,
        PRIMARY KEY (post_id, tag_id),
        FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE,
        FOREIGN KEY (tag_id) REFERENCES tags(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $db->exec("CREATE TABLE IF NOT EXISTS categories (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(120) NOT NULL UNIQUE,
        slug VARCHAR(160) NOT NULL UNIQUE,
        description TEXT,
        parent_id INT UNSIGNED NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (parent_id) REFERENCES categories(id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $db->exec("CREATE TABLE IF NOT EXISTS post_categories (
        post_id INT UNSIGNED NOT NULL,
        category_id INT UNSIGNED NOT NULL,
        PRIMARY KEY (post_id, category_id),
        FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE,
        FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $db->exec("CREATE TABLE IF NOT EXISTS settings (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        `key` VARCHAR(190) NOT NULL UNIQUE,
        value LONGTEXT,
        description TEXT,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $db->exec("CREATE TABLE IF NOT EXISTS site_items (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        section VARCHAR(40) NOT NULL,
        title VARCHAR(255) NOT NULL,
        description TEXT,
        image VARCHAR(500),
        icon VARCHAR(80),
        tags TEXT,
        primary_label VARCHAR(120),
        primary_url VARCHAR(500),
        secondary_label VARCHAR(120),
        secondary_url VARCHAR(500),
        status VARCHAR(30) DEFAULT 'published',
        order_num INT DEFAULT 0,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $db->exec("CREATE TABLE IF NOT EXISTS site_accesses (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        path VARCHAR(500) NOT NULL,
        title VARCHAR(255),
        referrer VARCHAR(500),
        user_agent VARCHAR(500),
        ip_hash CHAR(64),
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

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

    $db->exec("CREATE TABLE IF NOT EXISTS slide_decks (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(255) NOT NULL,
        slug VARCHAR(190) NOT NULL UNIQUE,
        description TEXT,
        status VARCHAR(30) DEFAULT 'draft',
        created_by INT UNSIGNED NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        published_at DATETIME NULL,
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

    $db->exec("CREATE TABLE IF NOT EXISTS backup_runs (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        filename VARCHAR(255) NOT NULL,
        filepath VARCHAR(500) NOT NULL,
        file_size BIGINT UNSIGNED DEFAULT 0,
        status VARCHAR(40) DEFAULT 'local',
        drive_file_id VARCHAR(255),
        message TEXT,
        created_by INT UNSIGNED NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (created_by) REFERENCES users(id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $db->exec("SET FOREIGN_KEY_CHECKS = 1");

    $existingAdmin = Database::getInstance()->selectOne('users', "username = ?", ['admin']);
    if (!$existingAdmin) {
        Database::getInstance()->insert('users', [
            'username' => 'admin',
            'email' => 'admin@chiapetta.dev',
            'password' => password_hash('admin123', PASSWORD_BCRYPT),
            'full_name' => 'Administrador',
            'role' => 'admin',
            'status' => 'active'
        ]);
    }

    $database = Database::getInstance();
    $database->createIndexIfMissing('posts', 'idx_posts_status', '`status`');
    $database->createIndexIfMissing('posts', 'idx_posts_author', '`author_id`');
    $database->createIndexIfMissing('pages', 'idx_pages_status', '`status`');
    $database->createIndexIfMissing('site_items', 'idx_site_items_section', '`section`');
    $database->createIndexIfMissing('site_items', 'idx_site_items_status', '`status`');
    $database->createIndexIfMissing('site_items', 'idx_site_items_order', '`order_num`');
    $database->createIndexIfMissing('site_accesses', 'idx_site_accesses_created_at', '`created_at`');
    $database->createIndexIfMissing('site_accesses', 'idx_site_accesses_path', '`path`');
    $database->createIndexIfMissing('comments', 'idx_comments_post_slug', '`post_slug`');
    $database->createIndexIfMissing('comments', 'idx_comments_status', '`status`');
    $database->createIndexIfMissing('comments', 'idx_comments_created_at', '`created_at`');
    $database->createIndexIfMissing('slide_decks', 'idx_slide_decks_status', '`status`');
    $database->createIndexIfMissing('slide_items', 'idx_slide_items_deck', '`deck_id`');
    $database->createIndexIfMissing('backup_runs', 'idx_backup_runs_created_at', '`created_at`');
} catch (Exception $e) {
    try {
        $db->exec("SET FOREIGN_KEY_CHECKS = 1");
    } catch (Exception $ignored) {
    }

    echo "Erro ao inicializar banco de dados MySQL: " . $e->getMessage();
    exit;
}
?>
