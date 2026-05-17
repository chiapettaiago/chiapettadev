<?php
/**
 * Inicialização do Banco de Dados - Criar tabelas
 */

require_once __DIR__ . '/config.php';

$db = Database::getInstance()->getPDO();

try {
    // Tabela de Usuários
    $db->exec("CREATE TABLE IF NOT EXISTS users (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        username TEXT UNIQUE NOT NULL,
        email TEXT UNIQUE NOT NULL,
        password TEXT NOT NULL,
        full_name TEXT NOT NULL,
        role TEXT DEFAULT 'editor',
        status TEXT DEFAULT 'active',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");

    // Tabela de Posts (Blog)
    $db->exec("CREATE TABLE IF NOT EXISTS posts (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        title TEXT NOT NULL,
        slug TEXT UNIQUE NOT NULL,
        content TEXT NOT NULL,
        excerpt TEXT,
        featured_image TEXT,
        author_id INTEGER NOT NULL,
        status TEXT DEFAULT 'draft',
        views INTEGER DEFAULT 0,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        published_at DATETIME,
        FOREIGN KEY (author_id) REFERENCES users(id)
    )");

    // Tabela de Páginas
    $db->exec("CREATE TABLE IF NOT EXISTS pages (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        title TEXT NOT NULL,
        slug TEXT UNIQUE NOT NULL,
        content TEXT NOT NULL,
        featured_image TEXT,
        author_id INTEGER NOT NULL,
        status TEXT DEFAULT 'draft',
        parent_id INTEGER,
        order_num INTEGER DEFAULT 0,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        published_at DATETIME,
        FOREIGN KEY (author_id) REFERENCES users(id),
        FOREIGN KEY (parent_id) REFERENCES pages(id)
    )");

    // Tabela de Imagens
    $db->exec("CREATE TABLE IF NOT EXISTS images (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        title TEXT NOT NULL,
        filename TEXT NOT NULL,
        filepath TEXT NOT NULL,
        mime_type TEXT,
        file_size INTEGER,
        uploaded_by INTEGER NOT NULL,
        alt_text TEXT,
        description TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (uploaded_by) REFERENCES users(id)
    )");

    // Tabela de Tags
    $db->exec("CREATE TABLE IF NOT EXISTS tags (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name TEXT UNIQUE NOT NULL,
        slug TEXT UNIQUE NOT NULL,
        description TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");

    // Tabela de Relacionamento entre Posts e Tags
    $db->exec("CREATE TABLE IF NOT EXISTS post_tags (
        post_id INTEGER NOT NULL,
        tag_id INTEGER NOT NULL,
        PRIMARY KEY (post_id, tag_id),
        FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE,
        FOREIGN KEY (tag_id) REFERENCES tags(id) ON DELETE CASCADE
    )");

    // Tabela de Categorias
    $db->exec("CREATE TABLE IF NOT EXISTS categories (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name TEXT UNIQUE NOT NULL,
        slug TEXT UNIQUE NOT NULL,
        description TEXT,
        parent_id INTEGER,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (parent_id) REFERENCES categories(id)
    )");

    // Tabela de Relacionamento entre Posts e Categorias
    $db->exec("CREATE TABLE IF NOT EXISTS post_categories (
        post_id INTEGER NOT NULL,
        category_id INTEGER NOT NULL,
        PRIMARY KEY (post_id, category_id),
        FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE,
        FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE
    )");

    // Tabela de Configurações
    $db->exec("CREATE TABLE IF NOT EXISTS settings (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        key TEXT UNIQUE NOT NULL,
        value TEXT,
        description TEXT,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");

    // Tabela de Itens do Site (habilidades, projetos e destaques do blog)
    $db->exec("CREATE TABLE IF NOT EXISTS site_items (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        section TEXT NOT NULL,
        title TEXT NOT NULL,
        description TEXT,
        image TEXT,
        icon TEXT,
        tags TEXT,
        primary_label TEXT,
        primary_url TEXT,
        secondary_label TEXT,
        secondary_url TEXT,
        status TEXT DEFAULT 'published',
        order_num INTEGER DEFAULT 0,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");

    // Tabela de acessos às páginas públicas
    $db->exec("CREATE TABLE IF NOT EXISTS site_accesses (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        path TEXT NOT NULL,
        title TEXT,
        referrer TEXT,
        user_agent TEXT,
        ip_hash TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");

    // Tabela de comentários dos leitores
    $db->exec("CREATE TABLE IF NOT EXISTS comments (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        post_slug TEXT NOT NULL,
        user_id INTEGER NOT NULL,
        content TEXT NOT NULL,
        status TEXT DEFAULT 'published',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id)
    )");

    // Tabelas de apresentações de slides
    $db->exec("CREATE TABLE IF NOT EXISTS slide_decks (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        title TEXT NOT NULL,
        slug TEXT UNIQUE NOT NULL,
        description TEXT,
        status TEXT DEFAULT 'draft',
        created_by INTEGER NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        published_at DATETIME,
        FOREIGN KEY (created_by) REFERENCES users(id)
    )");

    $db->exec("CREATE TABLE IF NOT EXISTS slide_items (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        deck_id INTEGER NOT NULL,
        title TEXT,
        content TEXT,
        image TEXT,
        order_num INTEGER DEFAULT 0,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (deck_id) REFERENCES slide_decks(id) ON DELETE CASCADE
    )");

    // Tabela de backups do site
    $db->exec("CREATE TABLE IF NOT EXISTS backup_runs (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        filename TEXT NOT NULL,
        filepath TEXT NOT NULL,
        file_size INTEGER DEFAULT 0,
        status TEXT DEFAULT 'local',
        drive_file_id TEXT,
        message TEXT,
        created_by INTEGER NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (created_by) REFERENCES users(id)
    )");

    // Criar usuário admin padrão (senha: admin123)
    $existingAdmin = Database::getInstance()->selectOne('users', "username = 'admin'");
    
    if (!$existingAdmin) {
        $adminPassword = password_hash('admin123', PASSWORD_BCRYPT);
        Database::getInstance()->insert('users', [
            'username' => 'admin',
            'email' => 'admin@chiapetta.dev',
            'password' => $adminPassword,
            'full_name' => 'Administrador',
            'role' => 'admin',
            'status' => 'active'
        ]);
        
        echo "✓ Banco de dados inicializado com sucesso!<br>";
        echo "✓ Usuário admin criado (usuário: admin, senha: admin123)<br>";
        echo "⚠️ IMPORTANTE: Altere a senha do admin após o primeiro login!<br>";
    }

    // Criar índices para melhor performance
    @$db->exec("CREATE INDEX IF NOT EXISTS idx_posts_slug ON posts(slug)");
    @$db->exec("CREATE INDEX IF NOT EXISTS idx_posts_status ON posts(status)");
    @$db->exec("CREATE INDEX IF NOT EXISTS idx_posts_author ON posts(author_id)");
    @$db->exec("CREATE INDEX IF NOT EXISTS idx_pages_slug ON pages(slug)");
    @$db->exec("CREATE INDEX IF NOT EXISTS idx_pages_status ON pages(status)");
    @$db->exec("CREATE INDEX IF NOT EXISTS idx_users_username ON users(username)");
    @$db->exec("CREATE INDEX IF NOT EXISTS idx_site_items_section ON site_items(section)");
    @$db->exec("CREATE INDEX IF NOT EXISTS idx_site_items_status ON site_items(status)");
    @$db->exec("CREATE INDEX IF NOT EXISTS idx_site_items_order ON site_items(order_num)");
    @$db->exec("CREATE INDEX IF NOT EXISTS idx_site_accesses_created_at ON site_accesses(created_at)");
    @$db->exec("CREATE INDEX IF NOT EXISTS idx_site_accesses_path ON site_accesses(path)");
    @$db->exec("CREATE INDEX IF NOT EXISTS idx_comments_post_slug ON comments(post_slug)");
    @$db->exec("CREATE INDEX IF NOT EXISTS idx_comments_status ON comments(status)");
    @$db->exec("CREATE INDEX IF NOT EXISTS idx_comments_created_at ON comments(created_at)");
    @$db->exec("CREATE INDEX IF NOT EXISTS idx_slide_decks_slug ON slide_decks(slug)");
    @$db->exec("CREATE INDEX IF NOT EXISTS idx_slide_decks_status ON slide_decks(status)");
    @$db->exec("CREATE INDEX IF NOT EXISTS idx_slide_items_deck ON slide_items(deck_id)");
    @$db->exec("CREATE INDEX IF NOT EXISTS idx_backup_runs_created_at ON backup_runs(created_at)");

} catch (Exception $e) {
    echo "Erro ao inicializar banco de dados: " . $e->getMessage();
    exit;
}
?>
