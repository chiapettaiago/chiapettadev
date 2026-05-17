<?php
/**
 * Sobre o Sistema - CMS ChiapettaDev
 */

require_once __DIR__ . '/../../db/config.php';
require_once __DIR__ . '/../modules/Auth.php';
require_once __DIR__ . '/../modules/SiteItem.php';
require_once __DIR__ . '/../modules/SlideDeck.php';

if (!Auth::hasPermission('author')) {
    header('Location: /admin/login.php');
    exit;
}

SiteItem::ensureSchema();
SlideDeck::ensureSchema();

function about_count_table($table) {
    try {
        return count(Database::getInstance()->select($table, '1=1'));
    } catch (Exception $e) {
        return 0;
    }
}

function about_format_bytes($bytes) {
    $bytes = (float) $bytes;
    $units = ['B', 'KB', 'MB', 'GB'];
    $unit = 0;

    while ($bytes >= 1024 && $unit < count($units) - 1) {
        $bytes /= 1024;
        $unit++;
    }

    return round($bytes, 1) . ' ' . $units[$unit];
}

$cmsVersion = '1.3.0';
$mysqlVersion = Database::getInstance()->getPDO()->query('SELECT VERSION()')->fetchColumn();
$dbSize = Database::getInstance()
    ->query(
        "SELECT COALESCE(SUM(data_length + index_length), 0)
         FROM information_schema.tables
         WHERE table_schema = DATABASE()"
    )
    ->fetchColumn();

$stats = [
    ['label' => 'Posts', 'value' => about_count_table('posts'), 'icon' => 'fas fa-file-alt'],
    ['label' => 'Páginas', 'value' => about_count_table('pages'), 'icon' => 'fas fa-file'],
    ['label' => 'Slides', 'value' => about_count_table('slide_decks'), 'icon' => 'fas fa-clapperboard'],
    ['label' => 'Imagens', 'value' => about_count_table('images'), 'icon' => 'fas fa-image'],
    ['label' => 'Usuários', 'value' => about_count_table('users'), 'icon' => 'fas fa-users'],
    ['label' => 'Comentários', 'value' => about_count_table('comments'), 'icon' => 'fas fa-comments'],
];

$modules = [
    ['name' => 'Dashboard', 'description' => 'Métricas, acessos recentes e atalhos operacionais.'],
    ['name' => 'Posts', 'description' => 'Blog com TinyMCE, imagem destacada, tags, categorias e comentários.'],
    ['name' => 'Páginas', 'description' => 'Páginas institucionais com hierarquia, slug editável e mídia destacada.'],
    ['name' => 'Slides', 'description' => 'Gerador de apresentações públicas navegáveis por botões e teclado.'],
    ['name' => 'Imagens', 'description' => 'Biblioteca de mídia com upload, metadados e cópia para pasta pública.'],
    ['name' => 'Itens do Site', 'description' => 'Gerenciamento de habilidades, projetos, blog e menu da navbar.'],
    ['name' => 'Usuários', 'description' => 'Controle de contas, papéis e status.'],
    ['name' => 'Configurações', 'description' => 'Dados gerais do site e alteração de senha.'],
];

$systemInfo = [
    'Versão do CMS' => $cmsVersion,
    'PHP' => PHP_VERSION,
    'Banco de dados' => strtoupper(DB_TYPE) . ' / MySQL ' . $mysqlVersion,
    'Tamanho do banco' => about_format_bytes($dbSize),
    'Servidor' => $_SERVER['SERVER_SOFTWARE'] ?? 'Indisponível',
    'Timezone' => date_default_timezone_get(),
    'Ambiente' => __DIR__ . '/../..',
    'Uploads' => UPLOADS_PATH,
    'Imagens públicas' => PUBLIC_IMAGES_PATH,
];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sobre o Sistema | CMS ChiapettaDev</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="icon" href="/images/favicon-apple.jpg" type="image/jpeg">
    <style>
        :root {
            --primary: #1a1a1a;
            --secondary: #2d2d2d;
            --accent: #00d9a3;
            --text: #ffffff;
            --text-muted: #b0b0b0;
        }

        * { box-sizing: border-box; }

        body {
            background: var(--primary);
            color: var(--text);
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Arial, sans-serif;
        }

        .container {
            max-width: 1120px;
            margin: 0 auto;
            padding: 2rem;
        }

        .header {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 2rem;
            padding-bottom: 1.5rem;
            border-bottom: 2px solid rgba(0, 217, 163, 0.2);
        }

        .header img {
            width: 58px;
            height: 58px;
            object-fit: cover;
            border-radius: 14px;
            border: 1px solid rgba(0, 217, 163, 0.22);
        }

        .header h1 {
            margin: 0;
            font-size: 2rem;
        }

        .header p {
            margin: 0.25rem 0 0;
            color: var(--text-muted);
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .stat-card,
        .section {
            background: var(--secondary);
            border: 1px solid rgba(0, 217, 163, 0.1);
            border-radius: 12px;
        }

        .stat-card {
            padding: 1.25rem;
        }

        .stat-card i {
            color: var(--accent);
            font-size: 1.45rem;
            margin-bottom: 0.8rem;
        }

        .stat-number {
            font-size: 2rem;
            font-weight: 800;
        }

        .stat-label {
            color: var(--text-muted);
            font-size: 0.9rem;
        }

        .content-grid {
            display: grid;
            grid-template-columns: minmax(0, 1fr) minmax(300px, 0.85fr);
            gap: 1.5rem;
        }

        .section {
            padding: 1.5rem;
            margin-bottom: 1.5rem;
        }

        .section h2 {
            font-size: 1.25rem;
            margin-bottom: 1.25rem;
            padding-bottom: 0.8rem;
            border-bottom: 1px solid rgba(0, 217, 163, 0.1);
        }

        .info-list,
        .module-list {
            display: grid;
            gap: 0.85rem;
        }

        .info-row {
            display: grid;
            grid-template-columns: 160px minmax(0, 1fr);
            gap: 1rem;
            align-items: start;
        }

        .info-label {
            color: var(--text-muted);
            font-weight: 700;
        }

        .info-value {
            word-break: break-word;
        }

        .module-item {
            padding: 1rem;
            border: 1px solid rgba(0, 217, 163, 0.1);
            border-radius: 8px;
            background: rgba(0, 0, 0, 0.12);
        }

        .module-item strong {
            color: var(--accent);
        }

        .module-item p {
            margin: 0.35rem 0 0;
            color: var(--text-muted);
        }

        @media (max-width: 900px) {
            .content-grid,
            .info-row {
                grid-template-columns: 1fr;
            }
        }
    </style>
    <link rel="stylesheet" href="/admin/assets/admin.css?v=20260516">
</head>
<body>
<?php include __DIR__ . '/../partials/sidebar.php'; ?>
<div class="container">
    <div class="header">
        <img src="/images/favicon-apple.jpg" alt="ChiapettaDev">
        <div>
            <h1>Sobre o Sistema</h1>
            <p>Informações técnicas e versão do CMS ChiapettaDev.</p>
        </div>
    </div>

    <div class="stats-grid">
        <?php foreach ($stats as $stat): ?>
            <div class="stat-card">
                <i class="<?= htmlspecialchars($stat['icon']) ?>"></i>
                <div class="stat-number"><?= intval($stat['value']) ?></div>
                <div class="stat-label"><?= htmlspecialchars($stat['label']) ?></div>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="content-grid">
        <div>
            <section class="section">
                <h2>Módulos do CMS</h2>
                <div class="module-list">
                    <?php foreach ($modules as $module): ?>
                        <div class="module-item">
                            <strong><?= htmlspecialchars($module['name']) ?></strong>
                            <p><?= htmlspecialchars($module['description']) ?></p>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>
        </div>

        <div>
            <section class="section">
                <h2>Informações do Sistema</h2>
                <div class="info-list">
                    <?php foreach ($systemInfo as $label => $value): ?>
                        <div class="info-row">
                            <div class="info-label"><?= htmlspecialchars($label) ?></div>
                            <div class="info-value"><?= htmlspecialchars((string) $value) ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>

            <section class="section">
                <h2>Recursos Ativos</h2>
                <div class="info-list">
                    <div class="info-row">
                        <div class="info-label">Editor</div>
                        <div class="info-value">TinyMCE 7</div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Autenticação</div>
                        <div class="info-value">Sessões PHP com papéis Author, Editor e Admin</div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Uploads</div>
                        <div class="info-value"><?= extension_loaded('gd') ? 'GD ativo para otimização de imagens' : 'GD indisponível' ?></div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Última checagem</div>
                        <div class="info-value"><?= date('d/m/Y H:i:s') ?></div>
                    </div>
                </div>
            </section>
        </div>
    </div>
</div>
<?php include __DIR__ . '/../partials/sidebar-close.php'; ?>
</body>
</html>
