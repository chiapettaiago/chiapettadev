<?php
/**
 * Gerenciador de Posts - CMS ChiapettaDev
 */

require_once __DIR__ . '/../../db/config.php';
require_once __DIR__ . '/../modules/Auth.php';
require_once __DIR__ . '/../modules/Post.php';
require_once __DIR__ . '/../modules/Image.php';
require_once __DIR__ . '/../modules/ExistingContentImporter.php';

// Verificar autenticação
if (!Auth::hasPermission('author')) {
    header('Location: /admin/login.php');
    exit;
}

$user = Auth::getCurrentUser();
$message = '';
$messageType = '';

ExistingContentImporter::ensureImported();

if (!function_exists('admin_format_datetime_local')) {
    function admin_format_datetime_local($value) {
        if (empty($value)) {
            return '';
        }

        $timestamp = strtotime($value);
        return $timestamp ? date('Y-m-d\TH:i', $timestamp) : '';
    }
}

if (!function_exists('sync_blog_highlight_image')) {
    function sync_blog_highlight_image($postId) {
        $post = Post::getById($postId);
        if (!$post) {
            return;
        }

        $db = Database::getInstance();
        $primaryUrl = '/blog/' . $post['slug'] . '/';
        $escapedUrl = str_replace("'", "''", $primaryUrl);
        $escapedTitle = str_replace("'", "''", $post['title']);

        $db->update(
            'site_items',
            [
                'image' => $post['featured_image'] ?? '',
                'updated_at' => date('Y-m-d H:i:s')
            ],
            "section = 'blog' AND (primary_url = '{$escapedUrl}' OR title = '{$escapedTitle}')"
        );
    }
}

// Processar ações
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'create' || $_POST['action'] === 'update') {
            $data = [
                'title' => $_POST['title'] ?? '',
                'slug' => $_POST['slug'] ?? '',
                'content' => $_POST['content'] ?? '',
                'excerpt' => $_POST['excerpt'] ?? '',
                'status' => $_POST['status'] ?? 'draft',
                'published_at' => $_POST['published_at'] ?? '',
                'featured_image' => $_POST['featured_image'] ?? '',
                'tags' => $_POST['tags'] ?? '',
                'categories' => $_POST['categories'] ?? ''
            ];

            if (empty($data['title'])) {
                $message = 'Título é obrigatório';
                $messageType = 'danger';
            } elseif (empty($data['content'])) {
                $message = 'Conteúdo é obrigatório';
                $messageType = 'danger';
            } else {
                if (($_POST['featured_image_mode'] ?? '') === 'upload') {
                    if (!empty($_FILES['featured_image_upload']['name'])) {
                        $uploadResult = Image::upload($_FILES['featured_image_upload'], $user['id'], [
                            'title' => $data['title'],
                            'alt_text' => $data['title'],
                            'description' => 'Imagem destacada enviada pela tela de edição de post.'
                        ]);

                        if ($uploadResult['success']) {
                            $data['featured_image'] = $uploadResult['filepath'];
                        } else {
                            $message = $uploadResult['message'];
                            $messageType = 'danger';
                        }
                    } else {
                        $message = 'Selecione uma imagem para enviar';
                        $messageType = 'danger';
                    }
                }

                if (!empty($message)) {
                    $post = [
                        'id' => intval($_POST['post_id'] ?? 0),
                        'title' => $data['title'],
                        'slug' => $data['slug'],
                        'content' => $data['content'],
                        'excerpt' => $data['excerpt'],
                        'status' => $data['status'],
                        'featured_image' => $data['featured_image'],
                        'tags' => [],
                        'categories' => []
                    ];
                } elseif ($_POST['action'] === 'create') {
                    $result = Post::create($data, $user['id']);
                } else {
                    $postId = intval($_POST['post_id'] ?? 0);
                    $result = Post::update($postId, $data, $user['id']);
                }

                if (isset($result) && $result['success']) {
                    $savedPostId = $_POST['action'] === 'create' ? intval($result['id']) : intval($_POST['post_id'] ?? 0);
                    sync_blog_highlight_image($savedPostId);

                    $autosaveToken = trim($_POST['autosave_token'] ?? '');
                    if ($autosaveToken !== '' && Database::getInstance()->tableExists('post_autosaves')) {
                        Database::getInstance()->query(
                            'DELETE FROM post_autosaves WHERE user_id = ? AND draft_token = ?',
                            [$user['id'], $autosaveToken]
                        );
                    }

                    $message = $result['message'];
                    $messageType = 'success';
                    
                    if ($_POST['action'] === 'create') {
                        $postId = $result['id'];
                        header('Location: /admin/pages/posts.php');
                        exit;
                    }
                } else {
                    if (isset($result)) {
                        $message = $result['message'];
                        $messageType = 'danger';
                    }
                }
            }
        } elseif ($_POST['action'] === 'delete') {
            $postId = intval($_POST['post_id'] ?? 0);
            $result = Post::delete($postId, $user['id']);

            if ($result['success']) {
                header('Location: /admin/pages/posts.php');
                exit;
            } else {
                $message = $result['message'];
                $messageType = 'danger';
            }
        }
    }
}

// Determinar modo (listar ou editar)
$editId = intval($_GET['edit'] ?? 0);
$requestedAutosaveToken = trim($_GET['autosave'] ?? '');
if (!preg_match('/^[a-zA-Z0-9_-]{12,80}$/', $requestedAutosaveToken)) {
    $requestedAutosaveToken = '';
}
$isNew = isset($_GET['new']) || $requestedAutosaveToken !== '';
$post = null;

if ($editId > 0) {
    $post = Post::getById($editId);
    if (!$post || ($post['author_id'] !== $user['id'] && !Auth::hasPermission('admin'))) {
        header('Location: /admin/pages/posts.php');
        exit;
    }
}

// Obter lista de imagens para seleção
$images = Image::getList();

// Obter categorias para seleção
$categories = Database::getInstance()->select('categories', "1=1 ORDER BY name");

if (!$editId && !$isNew) {
    // Listar posts
    $filters = ['limit' => 50];
    $posts = Post::getList($filters);
    $autosaveDrafts = [];

    if (Database::getInstance()->tableExists('post_autosaves')) {
        $autosaveRows = Database::getInstance()->query(
            'SELECT draft_token, payload, updated_at FROM post_autosaves WHERE user_id = ? AND post_id IS NULL ORDER BY updated_at DESC',
            [$user['id']]
        )->fetchAll(PDO::FETCH_ASSOC);

        foreach ($autosaveRows as $autosaveRow) {
            $autosavePayload = json_decode($autosaveRow['payload'] ?? '', true);
            if (!is_array($autosavePayload)) {
                continue;
            }

            $autosaveDrafts[] = [
                'token' => $autosaveRow['draft_token'],
                'title' => trim((string) ($autosavePayload['title'] ?? '')) ?: 'Rascunho sem título',
                'slug' => trim((string) ($autosavePayload['slug'] ?? '')),
                'updated_at' => $autosaveRow['updated_at']
            ];
        }
    }
    ?>
    <!DOCTYPE html>
    <html lang="pt-BR">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Posts | CMS ChiapettaDev</title>
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
                --danger: #dc3545;
            }

            * {
                margin: 0;
                padding: 0;
                box-sizing: border-box;
            }

            body {
                background: var(--primary);
                color: var(--text);
                font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            }

            .container {
                max-width: 1200px;
                margin: 0 auto;
                padding: 2rem;
            }

            .header {
                display: flex;
                justify-content: space-between;
                align-items: center;
                margin-bottom: 2rem;
                padding-bottom: 1.5rem;
                border-bottom: 2px solid rgba(0, 217, 163, 0.2);
            }

            .header h1 {
                font-size: 2rem;
                margin: 0;
            }

            .btn-primary {
                background: var(--accent);
                color: #000;
                border: none;
                border-radius: 6px;
                padding: 0.6rem 1.2rem;
                font-weight: 600;
                cursor: pointer;
                text-decoration: none;
                display: inline-flex;
                align-items: center;
                gap: 0.5rem;
            }

            .btn-primary:hover {
                background: #00b885;
            }

            .btn-secondary {
                background: var(--secondary);
                color: var(--text);
                border: 1px solid var(--accent);
                border-radius: 6px;
                padding: 0.5rem 1rem;
                font-weight: 600;
                cursor: pointer;
                text-decoration: none;
                display: inline-flex;
                align-items: center;
                gap: 0.5rem;
            }

            .btn-secondary:hover {
                background: rgba(0, 217, 163, 0.1);
            }

            .alert {
                border-radius: 6px;
                border: 1px solid;
                padding: 1rem;
                margin-bottom: 2rem;
            }

            .alert-success {
                background: rgba(0, 217, 163, 0.1);
                border-color: rgba(0, 217, 163, 0.3);
                color: var(--accent);
            }

            .alert-danger {
                background: rgba(220, 53, 69, 0.1);
                border-color: rgba(220, 53, 69, 0.3);
                color: #ff6b6b;
            }

            .table {
                background: var(--secondary);
                border: 1px solid rgba(0, 217, 163, 0.1);
                border-radius: 12px;
                overflow: hidden;
            }

            .table thead {
                border-bottom: 2px solid rgba(0, 217, 163, 0.2);
            }

            .table th {
                color: var(--accent);
                font-weight: 600;
                border-bottom: 2px solid rgba(0, 217, 163, 0.2);
                background: rgba(0, 217, 163, 0.05);
                padding: 1rem;
            }

            .table td {
                padding: 1rem;
                border-bottom: 1px solid rgba(0, 217, 163, 0.1);
            }

            .table tbody tr:hover {
                background: rgba(0, 217, 163, 0.05);
            }

            .badge {
                padding: 0.4rem 0.8rem;
                border-radius: 4px;
                font-size: 0.8rem;
                font-weight: 600;
            }

            .badge-published {
                background: rgba(0, 217, 163, 0.2);
                color: var(--accent);
            }

            .badge-draft {
                background: rgba(255, 193, 7, 0.2);
                color: #ffc107;
            }

            .action-buttons {
                display: flex;
                gap: 0.5rem;
            }

            .btn-small {
                padding: 0.4rem 0.8rem;
                font-size: 0.85rem;
                border-radius: 4px;
                cursor: pointer;
                text-decoration: none;
                border: none;
                transition: all 0.3s ease;
            }

            .btn-edit {
                background: var(--accent);
                color: #000;
            }

            .btn-edit:hover {
                background: #00b885;
            }

            .btn-delete {
                background: var(--danger);
                color: white;
            }

            .btn-delete:hover {
                background: #bb2d3b;
            }

            .back-link {
                color: var(--accent);
                text-decoration: none;
                display: inline-flex;
                align-items: center;
                gap: 0.5rem;
                margin-bottom: 2rem;
            }

            .back-link:hover {
                text-decoration: underline;
            }

            @media (max-width: 768px) {
                .container {
                    padding: 1rem;
                }

                .header {
                    flex-direction: column;
                    gap: 1rem;
                    align-items: flex-start;
                }

                .table-responsive {
                    overflow-x: auto;
                }
            }
        </style>
        <link rel="stylesheet" href="/admin/assets/admin.css?v=20260516">
</head>
    <body>
    <?php include __DIR__ . '/../partials/sidebar.php'; ?>
    <div class="container">
            <div class="header">
                <h1><i class="fas fa-file-alt"></i> Posts</h1>
                <a href="/admin/pages/posts.php?new=1" class="btn-primary">
                    <i class="fas fa-plus"></i>Novo Post
                </a>
            </div>

            <?php if (!empty($message)): ?>
                <div class="alert alert-<?= $messageType ?>">
                    <?= htmlspecialchars($message) ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($posts) || !empty($autosaveDrafts)): ?>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th style="width: 40%;">Título</th>
                                <th style="width: 15%;">Status</th>
                                <th style="width: 15%;">Autor</th>
                                <th style="width: 15%;">Data</th>
                                <th style="width: 15%;">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($autosaveDrafts as $draft): ?>
                                <tr>
                                    <td>
                                        <strong><?= htmlspecialchars($draft['title']) ?></strong><br>
                                        <small style="color: var(--text-muted);">
                                            <?= $draft['slug'] !== '' ? '/blog/' . htmlspecialchars($draft['slug']) . '/' : 'Ainda sem endereço definido' ?>
                                        </small>
                                    </td>
                                    <td><span class="badge badge-draft">Rascunho automático</span></td>
                                    <td><?= htmlspecialchars($user['full_name'] ?? $user['username']) ?></td>
                                    <td><?= date('d/m/Y H:i', strtotime($draft['updated_at'])) ?></td>
                                    <td>
                                        <div class="action-buttons">
                                            <a href="/admin/pages/posts.php?new=1&amp;autosave=<?= rawurlencode($draft['token']) ?>" class="btn-small btn-edit">
                                                <i class="fas fa-pen"></i> Continuar editando
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <?php foreach ($posts as $p): ?>
                                <tr>
                                    <td>
                                        <strong><?= htmlspecialchars($p['title']) ?></strong><br>
                                        <small style="color: var(--text-muted);">
                                            /blog/<?= htmlspecialchars($p['slug']) ?>/
                                        </small>
                                    </td>
                                    <td>
                                        <span class="badge badge-<?= $p['status'] ?>">
                                            <?= $p['status'] === 'published' ? 'Publicado' : 'Rascunho' ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php
                                        $author = Database::getInstance()->selectOne('users', "id = {$p['author_id']}");
                                        echo htmlspecialchars($author['full_name'] ?? 'Desconhecido');
                                        ?>
                                    </td>
                                    <td><?= date('d/m/Y', strtotime($p['created_at'])) ?></td>
                                    <td>
                                        <div class="action-buttons">
                                            <a href="/admin/pages/posts.php?edit=<?= $p['id'] ?>" class="btn-small btn-edit">
                                                <i class="fas fa-edit"></i> Editar
                                            </a>
                                            <form method="POST" style="display: inline;" onsubmit="return confirm('Tem certeza que deseja deletar este post?');">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="post_id" value="<?= $p['id'] ?>">
                                                <button type="submit" class="btn-small btn-delete">
                                                    <i class="fas fa-trash"></i> Deletar
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div style="text-align: center; padding: 3rem; background: var(--secondary); border-radius: 12px;">
                    <p style="color: var(--text-muted); margin-bottom: 1rem;">Nenhum post criado ainda</p>
                    <a href="/admin/pages/posts.php?new=1" class="btn-primary">
                        <i class="fas fa-plus"></i>Criar Primeiro Post
                    </a>
                </div>
            <?php endif; ?>
        </div>
        <?php include __DIR__ . '/../partials/sidebar-close.php'; ?>
</body>
    </html>
    <?php
} else {
    // Formulário de criação/edição
    ?>
    <!DOCTYPE html>
    <html lang="pt-BR">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title><?= $post ? 'Editar' : 'Novo' ?> Post | CMS ChiapettaDev</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.8.0/styles/atom-one-dark.min.css">
        <link rel="icon" href="/images/favicon-apple.jpg" type="image/jpeg">
        <style>
            :root {
                --primary: #1a1a1a;
                --secondary: #2d2d2d;
                --accent: #00d9a3;
                --text: #ffffff;
                --text-muted: #b0b0b0;
            }

            * {
                margin: 0;
                padding: 0;
                box-sizing: border-box;
            }

            body {
                background: var(--primary);
                color: var(--text);
                font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            }

            .container {
                max-width: 1000px;
                margin: 0 auto;
                padding: 2rem;
            }

            .header {
                display: flex;
                justify-content: space-between;
                align-items: center;
                margin-bottom: 2rem;
                padding-bottom: 1.5rem;
                border-bottom: 2px solid rgba(0, 217, 163, 0.2);
            }

            .header h1 {
                font-size: 2rem;
                margin: 0;
            }

            .form-group {
                margin-bottom: 1.5rem;
            }

            .form-group label {
                display: block;
                margin-bottom: 0.5rem;
                font-weight: 600;
                color: var(--text);
            }

            .form-group input,
            .form-group textarea,
            .form-group select {
                width: 100%;
                padding: 0.75rem;
                background: var(--primary);
                border: 1px solid rgba(0, 217, 163, 0.2);
                border-radius: 6px;
                color: var(--text);
                font-size: 1rem;
                transition: all 0.3s ease;
                font-family: inherit;
            }

            .form-group input:focus,
            .form-group textarea:focus,
            .form-group select:focus {
                outline: none;
                border-color: var(--accent);
                box-shadow: 0 0 0 3px rgba(0, 217, 163, 0.1);
                background: rgba(0, 217, 163, 0.02);
            }

            .form-group textarea {
                min-height: 120px;
                resize: vertical;
            }

            .form-group input::placeholder,
            .form-group textarea::placeholder {
                color: var(--text-muted);
            }

            .form-row {
                display: grid;
                grid-template-columns: 1fr 1fr;
                gap: 1.5rem;
            }

            .btn-submit,
            .btn-cancel {
                padding: 0.75rem 1.5rem;
                border: none;
                border-radius: 6px;
                font-weight: 600;
                cursor: pointer;
                transition: all 0.3s ease;
                text-decoration: none;
                display: inline-flex;
                align-items: center;
                gap: 0.5rem;
                font-size: 1rem;
            }

            .btn-submit {
                background: var(--accent);
                color: #000;
            }

            .btn-submit:hover {
                background: #00b885;
                transform: translateY(-2px);
            }

            .btn-cancel {
                background: var(--secondary);
                color: var(--text);
                border: 1px solid var(--accent);
            }

            .btn-cancel:hover {
                background: rgba(0, 217, 163, 0.1);
            }

            .button-group {
                display: flex;
                gap: 1rem;
                margin-top: 2rem;
            }

            .alert {
                border-radius: 6px;
                border: 1px solid;
                padding: 1rem;
                margin-bottom: 2rem;
            }

            .alert-danger {
                background: rgba(220, 53, 69, 0.1);
                border-color: rgba(220, 53, 69, 0.3);
                color: #ff6b6b;
            }

            .back-link {
                color: var(--accent);
                text-decoration: none;
                display: inline-flex;
                align-items: center;
                gap: 0.5rem;
                margin-bottom: 2rem;
            }

            .back-link:hover {
                text-decoration: underline;
            }

            .editor-toolbar {
                background: var(--secondary);
                border: 1px solid rgba(0, 217, 163, 0.2);
                border-bottom: none;
                border-radius: 6px 6px 0 0;
                padding: 0.75rem;
                display: flex;
                gap: 0.5rem;
                flex-wrap: wrap;
            }

            .editor-btn {
                background: var(--primary);
                border: 1px solid rgba(0, 217, 163, 0.2);
                color: var(--text);
                padding: 0.4rem 0.8rem;
                border-radius: 4px;
                cursor: pointer;
                font-size: 0.85rem;
                transition: all 0.3s ease;
            }

            .editor-btn:hover {
                border-color: var(--accent);
                color: var(--accent);
            }

            .content-editor {
                min-height: 430px;
                padding: 1rem;
                background: var(--primary);
                border: 1px solid rgba(0, 217, 163, 0.2);
                border-radius: 0 0 6px 6px;
                color: var(--text);
                font-size: 1rem;
                line-height: 1.75;
                overflow-y: auto;
                outline: none;
            }

            .content-editor:focus {
                border-color: var(--accent);
                box-shadow: 0 0 0 3px rgba(0, 217, 163, 0.1);
                background: rgba(0, 217, 163, 0.02);
            }

            .content-editor:empty::before {
                content: attr(data-placeholder);
                color: var(--text-muted);
                pointer-events: none;
            }

            .content-editor h2,
            .content-editor h3 {
                margin: 1.4rem 0 0.7rem;
                color: var(--text);
            }

            .content-editor p,
            .content-editor ul,
            .content-editor ol,
            .content-editor blockquote,
            .content-editor pre {
                margin-bottom: 1rem;
            }

            .content-editor blockquote {
                padding-left: 1rem;
                border-left: 3px solid var(--accent);
                color: var(--text-muted);
            }

            .content-editor code {
                padding: 0.15rem 0.35rem;
                border-radius: 4px;
                background: rgba(255, 255, 255, 0.08);
                color: var(--accent);
            }

            .content-source {
                display: none;
            }

            .permalink-row {
                display: flex;
                align-items: center;
                gap: 0.5rem;
                margin-top: 0.6rem;
                color: var(--text-muted);
                font-size: 0.9rem;
                flex-wrap: wrap;
            }

            .permalink-row code {
                color: var(--accent);
                background: rgba(0, 217, 163, 0.08);
                border-radius: 6px;
                padding: 0.25rem 0.5rem;
            }

            .featured-image-card {
                background: rgba(0, 217, 163, 0.04);
                border: 1px solid rgba(0, 217, 163, 0.2);
                border-radius: 8px;
                overflow: hidden;
            }

            .featured-image-preview {
                min-height: 220px;
                display: flex;
                align-items: center;
                justify-content: center;
                background: rgba(0, 0, 0, 0.22);
                color: var(--text-muted);
                text-align: center;
            }

            .featured-image-preview img {
                width: 100%;
                height: 260px;
                display: block;
                object-fit: cover;
            }

            .featured-image-empty {
                padding: 2rem;
            }

            .featured-image-actions {
                display: flex;
                gap: 0.75rem;
                flex-wrap: wrap;
                padding: 1rem;
                background: rgba(45, 45, 45, 0.55);
            }

            .media-modal {
                position: fixed;
                inset: 0;
                display: none;
                align-items: center;
                justify-content: center;
                padding: 1rem;
                background: rgba(0, 0, 0, 0.72);
                z-index: 2000;
            }

            .media-modal.active {
                display: flex;
            }

            .media-modal-content {
                width: min(1040px, 96vw);
                max-height: 90vh;
                display: flex;
                flex-direction: column;
                background: #1f1f1f;
                border: 1px solid rgba(0, 217, 163, 0.24);
                border-radius: 10px;
                box-shadow: 0 24px 80px rgba(0, 0, 0, 0.45);
                overflow: hidden;
            }

            .media-modal-header,
            .media-modal-footer {
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 1rem;
                padding: 1rem 1.25rem;
                border-bottom: 1px solid rgba(0, 217, 163, 0.12);
            }

            .media-modal-footer {
                border-top: 1px solid rgba(0, 217, 163, 0.12);
                border-bottom: none;
                justify-content: flex-end;
            }

            .media-modal-title {
                margin: 0;
                font-size: 1.15rem;
            }

            .media-modal-close {
                width: 38px;
                height: 38px;
                border: 1px solid rgba(0, 217, 163, 0.2);
                border-radius: 8px;
                background: var(--primary);
                color: var(--text);
                cursor: pointer;
            }

            .media-tabs {
                display: flex;
                gap: 0.5rem;
                padding: 1rem 1.25rem 0;
            }

            .media-tab {
                border: 1px solid rgba(0, 217, 163, 0.18);
                border-radius: 8px 8px 0 0;
                background: var(--primary);
                color: var(--text-muted);
                padding: 0.65rem 1rem;
                cursor: pointer;
                font-weight: 700;
            }

            .media-tab.active {
                color: #000;
                background: var(--accent);
                border-color: var(--accent);
            }

            .media-panel {
                display: none;
                padding: 1.25rem;
                overflow: auto;
            }

            .media-panel.active {
                display: block;
            }

            .media-grid {
                display: grid;
                grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
                gap: 1rem;
            }

            .media-item {
                border: 2px solid rgba(0, 217, 163, 0.12);
                border-radius: 8px;
                background: var(--primary);
                color: var(--text);
                padding: 0;
                overflow: hidden;
                cursor: pointer;
                text-align: left;
            }

            .media-item.selected {
                border-color: var(--accent);
                box-shadow: 0 0 0 3px rgba(0, 217, 163, 0.16);
            }

            .media-item img {
                width: 100%;
                aspect-ratio: 1 / 1;
                display: block;
                object-fit: cover;
                background: rgba(0, 0, 0, 0.24);
            }

            .media-item span {
                display: block;
                padding: 0.6rem;
                color: var(--text-muted);
                font-size: 0.82rem;
                white-space: nowrap;
                overflow: hidden;
                text-overflow: ellipsis;
            }

            .media-upload-box {
                border: 2px dashed rgba(0, 217, 163, 0.35);
                border-radius: 10px;
                padding: 1.5rem;
                background: rgba(0, 217, 163, 0.04);
            }

            .media-upload-preview {
                margin-top: 1rem;
                border-radius: 8px;
                overflow: hidden;
                background: rgba(0, 0, 0, 0.22);
            }

            .media-upload-preview img {
                width: 100%;
                max-height: 360px;
                display: block;
                object-fit: contain;
            }

            @media (max-width: 768px) {
                .form-row {
                    grid-template-columns: 1fr;
                }

                .button-group {
                    flex-direction: column;
                }

                .button-group button,
                .button-group a {
                    width: 100%;
                    justify-content: center;
                }

                .featured-image-preview img {
                    height: 190px;
                }
            }
        </style>
        <link rel="stylesheet" href="/admin/assets/admin.css?v=20260516">
        <link rel="stylesheet" href="/admin/assets/post-editor.css?v=20260805e">
</head>
    <body>
    <?php include __DIR__ . '/../partials/sidebar.php'; ?>
    <div class="wp-editor-shell">
        <div class="wp-editor-header">
            <div>
                <div class="post-editor-kicker"><i class="fas fa-pen-nib"></i> Conteúdo do blog</div>
                <h1><?= $post ? 'Editar post' : 'Criar novo post' ?></h1>
                <p class="wp-editor-note"><?= $post ? 'Revise o conteúdo, ajuste a publicação e salve suas alterações.' : 'Escreva, organize e publique um novo artigo no seu blog.' ?></p>
            </div>
            <div class="wp-editor-header-actions">
                <span class="post-save-state" id="postSaveState"><i class="fas fa-check-circle"></i> Tudo salvo</span>
                <a href="/admin/pages/posts.php" class="btn-cancel">
                    <i class="fas fa-arrow-left"></i>Voltar
                </a>
                <?php if ($post && ($post['status'] ?? '') === 'published'): ?>
                    <a href="/blog/<?= htmlspecialchars($post['slug']) ?>/" target="_blank" rel="noopener" class="post-view-button">
                        <i class="fas fa-arrow-up-right-from-square"></i><span>Ver post</span>
                    </a>
                <?php endif; ?>
            </div>
        </div>

        <?php if (!empty($message)): ?>
            <div class="alert alert-<?= htmlspecialchars($messageType ?: 'danger') ?>">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <div class="post-recovery-banner" id="postRecoveryBanner" hidden>
            <div>
                <i class="fas fa-clock-rotate-left"></i>
                <span><strong>Rascunho automático encontrado.</strong> Há alterações mais recentes que ainda não foram publicadas.</span>
            </div>
            <div class="post-recovery-actions">
                <button type="button" class="btn-cancel" id="discardAutosave">Descartar</button>
                <button type="button" class="btn-submit" id="restoreAutosave">Recuperar rascunho</button>
            </div>
        </div>

        <form method="POST" action="" enctype="multipart/form-data" id="postEditorForm">
            <input type="hidden" name="action" value="<?= $post ? 'update' : 'create' ?>">
            <input type="hidden" name="autosave_token" id="autosaveToken" value="">
            <?php if ($post): ?>
                <input type="hidden" name="post_id" value="<?= $post['id'] ?>">
            <?php endif; ?>

            <div class="wp-editor-layout">
                <div class="wp-editor-main">
                    <section class="wp-metabox">
                        <div class="wp-metabox-title">
                            <div><span class="post-section-icon"><i class="fas fa-heading"></i></span><h2>Título e endereço</h2></div>
                            <small>Como o artigo será identificado</small>
                        </div>
                        <div class="wp-metabox-body">
                            <div class="form-group">
                                <label for="title">Título do Post *</label>
                                <input
                                    type="text"
                                    id="title"
                                    name="title"
                                    class="wp-editor-title-input"
                                    value="<?= $post ? htmlspecialchars($post['title']) : '' ?>"
                                    placeholder="Digite o título do post"
                                    required
                                >
                                <div class="wp-slug-row">
                                    <span>Link permanente:</span>
                                    <code>/blog/<span id="slugPreview"><?= $post ? htmlspecialchars($post['slug']) : 'novo-post' ?></span>/</code>
                                </div>
                            </div>

                            <div class="form-group" style="margin-top: 1rem;">
                                <label for="slug">Slug</label>
                                <input
                                    type="text"
                                    id="slug"
                                    name="slug"
                                    value="<?= $post ? htmlspecialchars($post['slug']) : '' ?>"
                                    placeholder="sera-gerado-a-partir-do-titulo"
                                >
                            </div>
                        </div>
                    </section>

                    <section class="wp-metabox">
                        <div class="wp-metabox-title">
                            <div><span class="post-section-icon"><i class="fas fa-align-left"></i></span><h2>Conteúdo</h2></div>
                            <div class="post-content-metrics" aria-live="polite">
                                <span id="wordCount">0 palavras</span>
                                <span id="readingTime">0 min de leitura</span>
                            </div>
                        </div>
                        <div class="wp-metabox-body">
                            <div class="wp-editor-tabs" role="tablist" aria-label="Modo do editor">
                                <button type="button" class="wp-editor-tab active" data-editor-mode="visual">Visual</button>
                                <button type="button" class="wp-editor-tab" data-editor-mode="text">Texto</button>
                            </div>
                            <div class="form-group">
                                <textarea
                                    id="content"
                                    class="tinymce-editor wp-editor-textarea"
                                    name="content"
                                    required
                                ><?= $post ? htmlspecialchars($post['content']) : '' ?></textarea>
                            </div>
                        </div>
                    </section>

                    <section class="wp-metabox">
                        <div class="wp-metabox-title">
                            <div><span class="post-section-icon"><i class="fas fa-quote-left"></i></span><h2>Resumo</h2></div>
                            <small>Texto usado nos cards e resultados de busca</small>
                        </div>
                        <div class="wp-metabox-body">
                            <div class="form-group">
                                <div class="post-label-row"><label for="excerpt">Resumo do artigo</label><span id="excerptCount">0 caracteres</span></div>
                                <textarea
                                    id="excerpt"
                                    name="excerpt"
                                    placeholder="Resumo do post (será gerado automaticamente se deixado em branco)"
                                    style="min-height: 110px;"
                                ><?= $post ? htmlspecialchars($post['excerpt']) : '' ?></textarea>
                            </div>
                        </div>
                    </section>
                </div>

                <aside class="wp-editor-aside">
                    <details class="wp-publish-box wp-metabox-collapsible" data-storage-key="admin-post-publish" open>
                        <summary class="wp-metabox-title">
                            <h2><i class="fas fa-paper-plane"></i> Publicação</h2>
                            <span class="wp-metabox-toggle"><i class="fas fa-chevron-up"></i></span>
                        </summary>
                        <div class="wp-publish-body">
                            <div class="wp-publish-state">
                                <div class="status-line">
                                    <strong>Status</strong>
                                    <span class="status-meta" id="statusMeta"><?= (($post['status'] ?? 'draft') === 'published') ? 'Publicado' : 'Rascunho' ?></span>
                                </div>
                                <div class="form-group">
                                    <label for="status">Estado</label>
                                    <select id="status" name="status">
                                        <option value="draft" <?= (!$post || $post['status'] === 'draft') ? 'selected' : '' ?>>Rascunho</option>
                                        <option value="published" <?= ($post && $post['status'] === 'published') ? 'selected' : '' ?>>Publicado</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="published_at">Publicar em</label>
                                    <input
                                        type="datetime-local"
                                        id="published_at"
                                        name="published_at"
                                        value="<?= admin_format_datetime_local($post['published_at'] ?? ($post ? $post['created_at'] : date('Y-m-d H:i:s'))) ?>"
                                    >
                                </div>
                                <div class="wp-editor-actions-row">
                                    <button type="submit" class="btn-submit" id="primarySubmitButton">
                                        <i class="fas fa-save"></i><span><?= $post ? 'Atualizar post' : 'Salvar rascunho' ?></span>
                                    </button>
                                </div>
                                <p class="post-shortcut-hint"><kbd>Ctrl</kbd> + <kbd>S</kbd> para salvar</p>
                            </div>
                        </div>
                    </details>

                    <details class="wp-metabox wp-metabox-collapsible" data-storage-key="admin-post-featured-image" open>
                        <summary class="wp-metabox-title">
                            <h2><i class="fas fa-image"></i> Imagem destacada</h2>
                            <span class="wp-metabox-toggle"><i class="fas fa-chevron-up"></i></span>
                        </summary>
                        <div class="wp-metabox-body">
                            <input type="hidden" id="featured_image" name="featured_image" value="<?= $post ? htmlspecialchars($post['featured_image'] ?? '') : '' ?>">
                            <input type="hidden" id="featured_image_mode" name="featured_image_mode" value="library">
                            <div class="featured-image-card">
                                <div class="featured-image-preview" id="featuredImagePreview">
                                    <?php if ($post && !empty($post['featured_image'])): ?>
                                        <img src="<?= htmlspecialchars($post['featured_image']) ?>" alt="<?= htmlspecialchars($post['title']) ?>">
                                    <?php else: ?>
                                        <div class="featured-image-empty">
                                            <i class="fas fa-image" style="font-size: 2rem; margin-bottom: 0.75rem;"></i>
                                            <div>Nenhuma imagem destacada</div>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="featured-image-actions">
                                    <button type="button" class="btn-cancel" onclick="openMediaModal()">
                                        <i class="fas fa-photo-film"></i>Definir imagem
                                    </button>
                                    <button type="button" class="btn-cancel" onclick="removeFeaturedImage()">
                                        <i class="fas fa-trash"></i>Remover
                                    </button>
                                </div>
                            </div>
                        </div>
                    </details>

                    <details class="wp-metabox wp-metabox-collapsible" data-storage-key="admin-post-tags">
                        <summary class="wp-metabox-title">
                            <h2><i class="fas fa-tags"></i> Tags</h2>
                            <span class="wp-metabox-toggle"><i class="fas fa-chevron-up"></i></span>
                        </summary>
                        <div class="wp-metabox-body">
                            <div class="form-group">
                                <label for="tags">Tags separadas por vírgula</label>
                                <input
                                    type="text"
                                    id="tags"
                                    name="tags"
                                    placeholder="python, flask, web"
                                    value="<?= $post ? htmlspecialchars(implode(', ', array_map(function($t) { return $t['name']; }, $post['tags'] ?? []))) : '' ?>"
                                >
                            </div>
                        </div>
                    </details>

                    <details class="wp-metabox wp-metabox-collapsible" data-storage-key="admin-post-categories">
                        <summary class="wp-metabox-title">
                            <h2><i class="fas fa-folder-open"></i> Categorias</h2>
                            <span class="wp-metabox-toggle"><i class="fas fa-chevron-up"></i></span>
                        </summary>
                        <div class="wp-metabox-body">
                            <div class="form-group">
                                <div class="post-category-heading">
                                    <span>Selecione uma ou mais categorias</span>
                                    <a href="/admin/pages/categories.php" target="_blank" rel="noopener">
                                        <i class="fas fa-gear"></i> Gerenciar
                                    </a>
                                </div>
                                <?php if (!empty($categories)): ?>
                                    <div class="post-category-picker" id="categories">
                                        <?php foreach ($categories as $cat): ?>
                                            <label class="post-category-option">
                                                <input
                                                    type="checkbox"
                                                    name="categories[]"
                                                    value="<?= intval($cat['id']) ?>"
                                                    <?= ($post && array_any($post['categories'] ?? [], fn($c) => $c['id'] == $cat['id'])) ? 'checked' : '' ?>
                                                >
                                                <span><?= htmlspecialchars($cat['name']) ?></span>
                                            </label>
                                        <?php endforeach; ?>
                                    </div>
                                <?php else: ?>
                                    <div class="post-category-empty">
                                        Nenhuma categoria criada.
                                        <a href="/admin/pages/categories.php">Criar a primeira categoria</a>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </details>
                </aside>
            </div>

            <div class="post-mobile-actions" aria-label="Ações de publicação">
                <button type="submit" class="btn-submit post-mobile-primary"><i class="fas fa-save"></i><span>Salvar</span></button>
            </div>

            <div class="media-modal" id="mediaModal" aria-hidden="true">
                <div class="media-modal-content" role="dialog" aria-modal="true" aria-labelledby="mediaModalTitle">
                    <div class="media-modal-header">
                        <h2 class="media-modal-title" id="mediaModalTitle">Imagem destacada</h2>
                        <button type="button" class="media-modal-close" onclick="closeMediaModal()" aria-label="Fechar">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>

                    <div class="media-tabs">
                        <button type="button" class="media-tab active" data-panel="library" onclick="showMediaPanel('library')">
                            Biblioteca
                        </button>
                        <button type="button" class="media-tab" data-panel="upload" onclick="showMediaPanel('upload')">
                            Enviar nova
                        </button>
                    </div>

                    <div class="media-panel active" id="mediaPanelLibrary">
                        <div class="media-search-wrap">
                            <i class="fas fa-search"></i>
                            <input type="search" id="mediaSearch" placeholder="Buscar imagem por nome" autocomplete="off">
                        </div>
                        <?php if (!empty($images)): ?>
                            <div class="media-grid">
                                <?php foreach ($images as $img): ?>
                                    <button
                                        type="button"
                                        class="media-item"
                                        data-src="<?= htmlspecialchars($img['filepath']) ?>"
                                        data-title="<?= htmlspecialchars($img['title']) ?>"
                                        onclick="selectLibraryImage(this)"
                                    >
                                        <img src="<?= htmlspecialchars($img['filepath']) ?>" alt="<?= htmlspecialchars($img['alt_text'] ?: $img['title']) ?>" loading="lazy">
                                        <span title="<?= htmlspecialchars($img['title']) ?>"><?= htmlspecialchars($img['title']) ?></span>
                                    </button>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <p style="color: var(--text-muted); margin: 0;">Nenhuma imagem enviada ainda.</p>
                        <?php endif; ?>
                    </div>

                    <div class="media-panel" id="mediaPanelUpload">
                        <div class="media-upload-box">
                            <label for="featured_image_upload" style="margin-bottom: 0.75rem;">Escolha uma imagem do computador</label>
                            <input type="file" id="featured_image_upload" name="featured_image_upload" accept="image/*" onchange="previewUploadedImage(event)">
                            <div class="media-upload-preview" id="uploadPreview" style="display: none;"></div>
                        </div>
                    </div>

                    <div class="media-modal-footer">
                        <button type="button" class="btn-cancel" onclick="closeMediaModal()">Cancelar</button>
                        <button type="button" class="btn-submit" onclick="applyMediaSelection()">
                            <i class="fas fa-check"></i>Usar como destacada
                        </button>
                    </div>
                </div>
            </div>
        </form>

        <a href="/admin/pages/posts.php" class="back-link" style="margin-top: 2rem;">
            <i class="fas fa-arrow-left"></i>Voltar à Lista de Posts
        </a>
    </div>

        <script src="https://cdn.tiny.cloud/1/vzv83v6j3ph3tx55wbjhbuz9i0qsr8mhfhigw0k0kq9qqyhr/tinymce/8/tinymce.min.js" referrerpolicy="origin" crossorigin="anonymous"></script>
        <script src="/admin/assets/tinymce-media.js?v=20260603"></script>
        <script>
            const postForm = document.getElementById('postEditorForm');
            const contentField = document.getElementById('content');
            const titleField = document.getElementById('title');
            const slugField = document.getElementById('slug');
            const slugPreview = document.getElementById('slugPreview');
            const featuredImageField = document.getElementById('featured_image');
            const featuredImageMode = document.getElementById('featured_image_mode');
            const featuredImagePreview = document.getElementById('featuredImagePreview');
            const mediaModal = document.getElementById('mediaModal');
            const uploadInput = document.getElementById('featured_image_upload');
            const uploadPreview = document.getElementById('uploadPreview');
            const editorTabs = document.querySelectorAll('.wp-editor-tab');
            const metaboxes = document.querySelectorAll('.wp-metabox-collapsible');
            const excerptField = document.getElementById('excerpt');
            const excerptCount = document.getElementById('excerptCount');
            const wordCount = document.getElementById('wordCount');
            const readingTime = document.getElementById('readingTime');
            const statusField = document.getElementById('status');
            const statusMeta = document.getElementById('statusMeta');
            const primarySubmitButton = document.getElementById('primarySubmitButton');
            const mobileSubmitLabel = document.querySelector('.post-mobile-primary span');
            const saveState = document.getElementById('postSaveState');
            const mediaSearch = document.getElementById('mediaSearch');
            const autosaveTokenField = document.getElementById('autosaveToken');
            const recoveryBanner = document.getElementById('postRecoveryBanner');
            const restoreAutosaveButton = document.getElementById('restoreAutosave');
            const discardAutosaveButton = document.getElementById('discardAutosave');
            const postId = <?= intval($post['id'] ?? 0) ?>;
            const requestedAutosaveToken = <?= json_encode($requestedAutosaveToken, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>;
            const serverUpdatedAt = <?= json_encode($post['updated_at'] ?? $post['created_at'] ?? null) ?>;
            const newPostTokenKey = 'cms-new-post-autosave-token-<?= intval($user['id']) ?>';
            let pendingLibraryImage = featuredImageField.value;
            let pendingUploadSelected = false;
            let currentEditorMode = 'visual';
            let submittingAfterUpload = false;
            let formDirty = false;
            let isLeaving = false;
            let autosaveTimer = null;
            let autosaveRequest = null;
            let recoveredPayload = null;
            const contentImages = <?= json_encode(array_map(function($image) {
                return [
                    'title' => $image['title'],
                    'url' => $image['filepath']
                ];
            }, $images), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>;

            function setEditorMode(mode) {
                currentEditorMode = mode;
                const editor = typeof tinymce !== 'undefined' ? tinymce.get('content') : null;
                const editorContainer = editor ? editor.getContainer() : null;
                const showText = mode === 'text';

                editorTabs.forEach(tab => {
                    tab.classList.toggle('active', tab.dataset.editorMode === mode);
                });

                if (showText) {
                    if (editor) {
                        editor.save();
                        if (editorContainer) {
                            editorContainer.style.display = 'none';
                        }
                    }
                    contentField.style.display = 'block';
                    contentField.classList.add('wp-editor-textarea');
                    return;
                }

                if (editor) {
                    editor.setContent(contentField.value);
                    if (editorContainer) {
                        editorContainer.style.display = '';
                    }
                }
                contentField.style.display = 'none';
            }

            editorTabs.forEach(tab => {
                tab.addEventListener('click', function() {
                    setEditorMode(tab.dataset.editorMode || 'visual');
                });
            });

            function initMetaboxStates() {
                metaboxes.forEach(box => {
                    const storageKey = box.dataset.storageKey;
                    if (!storageKey) {
                        return;
                    }

                    const storedState = localStorage.getItem(storageKey);
                    if (storedState === 'open') {
                        box.open = true;
                    } else if (storedState === 'closed') {
                        box.open = false;
                    }

                    box.addEventListener('toggle', function() {
                        localStorage.setItem(storageKey, box.open ? 'open' : 'closed');
                    });
                });
            }

            tinymce.init({
                selector: '#content',
                height: 560,
                menubar: 'file edit view insert format tools table help',
                plugins: 'anchor autolink charmap code codesample fullscreen image link lists media preview searchreplace table visualblocks wordcount',
                toolbar: 'undo redo | blocks fontfamily fontsize | bold italic underline strikethrough | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | link image media table codesample blockquote | forecolor backcolor removeformat | code preview fullscreen',
                toolbar_mode: 'sliding',
                mobile: {
                    menubar: false,
                    toolbar: 'undo redo | blocks | bold italic | bullist numlist | link image | code',
                    toolbar_mode: 'scrolling'
                },
                branding: false,
                promotion: false,
                language: 'pt-BR',
                skin: 'oxide-dark',
                content_css: 'dark',
                automatic_uploads: true,
                relative_urls: false,
                remove_script_host: false,
                convert_urls: false,
                images_upload_url: '/admin/ajax/tinymce-upload.php?type=image',
                image_advtab: true,
                file_picker_types: 'image media',
                init_instance_callback: function(editor) {
                    setEditorMode('visual');
                    updateContentMetrics(editor.getContent({ format: 'text' }));
                    editor.on('input change undo redo', function() {
                        markFormDirty();
                        updateContentMetrics(editor.getContent({ format: 'text' }));
                    });
                },
                file_picker_callback: function(callback, value, meta) {
                    if (!window.CMSMediaTools) {
                        return;
                    }

                    if (meta.filetype === 'image') {
                        window.CMSMediaTools.openPicker('image/*', 'image', callback, meta);
                        return;
                    }

                    if (meta.filetype === 'media') {
                        window.CMSMediaTools.openPicker('video/*', 'media', callback, meta);
                    }
                },
                content_style: 'body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Arial, sans-serif; font-size: 16px; line-height: 1.75; }'
            });

            function slugify(value) {
                return value
                    .normalize('NFD')
                    .replace(/[\u0300-\u036f]/g, '')
                    .toLowerCase()
                    .replace(/[^a-z0-9]+/g, '-')
                    .replace(/^-+|-+$/g, '');
            }

            function updateSlugPreview() {
                const slug = slugField.value.trim() || slugify(titleField.value) || 'novo-post';
                slugPreview.textContent = slug;
            }

            function updateContentMetrics(value) {
                const text = String(value || '').replace(/\s+/g, ' ').trim();
                const totalWords = text ? text.split(' ').length : 0;
                const minutes = totalWords ? Math.max(1, Math.ceil(totalWords / 220)) : 0;
                wordCount.textContent = `${totalWords} ${totalWords === 1 ? 'palavra' : 'palavras'}`;
                readingTime.textContent = `${minutes} min de leitura`;
            }

            function updateExcerptCount() {
                const total = excerptField.value.length;
                excerptCount.textContent = `${total} ${total === 1 ? 'caractere' : 'caracteres'}`;
            }

            function updatePublishControls() {
                const isPublished = statusField.value === 'published';
                const label = isPublished ? '<?= $post ? 'Atualizar publicação' : 'Publicar agora' ?>' : 'Salvar rascunho';
                statusMeta.textContent = isPublished ? 'Publicado' : 'Rascunho';
                primarySubmitButton.querySelector('span').textContent = label;
                mobileSubmitLabel.textContent = isPublished ? 'Publicar' : 'Salvar';
            }

            function markFormDirty() {
                scheduleAutosave();
                if (!formDirty) {
                    formDirty = true;
                    saveState.classList.add('is-dirty');
                    saveState.innerHTML = '<i class="fas fa-circle"></i> Alterações não salvas';
                }
            }

            function markSaving() {
                saveState.classList.remove('is-dirty');
                saveState.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Salvando…';
            }

            function createAutosaveToken() {
                if (postId > 0) {
                    return `post_${postId}_<?= intval($user['id']) ?>_autosave`;
                }

                if (requestedAutosaveToken) {
                    localStorage.setItem(newPostTokenKey, requestedAutosaveToken);
                    return requestedAutosaveToken;
                }

                let token = localStorage.getItem(newPostTokenKey);
                const validToken = typeof token === 'string'
                    && /^[a-zA-Z0-9_-]{12,80}$/.test(token);

                if (!validToken) {
                    const randomPart = window.crypto && window.crypto.randomUUID
                        ? window.crypto.randomUUID().replace(/-/g, '')
                        : `${Date.now()}_${Math.random().toString(36).slice(2)}`;
                    token = `new_${randomPart}`;
                    localStorage.setItem(newPostTokenKey, token);
                }
                return token;
            }

            function getEditorContent() {
                const editor = typeof tinymce !== 'undefined' ? tinymce.get('content') : null;
                return currentEditorMode === 'text' || !editor ? contentField.value : editor.getContent();
            }

            function collectAutosavePayload() {
                return {
                    title: titleField.value,
                    slug: slugField.value,
                    content: getEditorContent(),
                    excerpt: excerptField.value,
                    status: statusField.value,
                    published_at: document.getElementById('published_at').value,
                    featured_image: featuredImageField.value,
                    tags: document.getElementById('tags').value,
                    categories: Array.from(document.querySelectorAll('input[name="categories[]"]:checked')).map(input => input.value)
                };
            }

            function encodeAutosavePayload(payload) {
                const json = JSON.stringify(payload);

                if (typeof TextEncoder !== 'undefined') {
                    const bytes = new TextEncoder().encode(json);
                    let binary = '';
                    const chunkSize = 8192;

                    for (let offset = 0; offset < bytes.length; offset += chunkSize) {
                        binary += String.fromCharCode(...bytes.subarray(offset, offset + chunkSize));
                    }

                    return btoa(binary);
                }

                return btoa(unescape(encodeURIComponent(json)));
            }

            function scheduleAutosave() {
                if (isLeaving) return;
                window.clearTimeout(autosaveTimer);
                autosaveTimer = window.setTimeout(saveAutosave, 1300);
            }

            async function saveAutosave() {
                if (isLeaving || !formDirty) return;

                if (autosaveRequest) {
                    autosaveRequest.abort();
                }
                const requestController = new AbortController();
                autosaveRequest = requestController;
                saveState.classList.add('is-dirty');
                saveState.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Salvando automaticamente…';

                const body = new FormData();
                body.append('token', autosaveTokenField.value);
                body.append('post_id', String(postId));
                body.append('payload_encoding', 'base64');
                body.append('payload', encodeAutosavePayload(collectAutosavePayload()));

                try {
                    const response = await fetch('/admin/ajax/post-autosave.php', {
                        method: 'POST',
                        body,
                        credentials: 'same-origin',
                        signal: requestController.signal
                    });
                    const responseText = await response.text();
                    let result = null;

                    try {
                        result = JSON.parse(responseText);
                    } catch (parseError) {
                        throw new Error('O servidor retornou uma resposta inválida');
                    }

                    if (!response.ok || !result.success) {
                        throw new Error(result.message || 'Falha no autosave');
                    }

                    formDirty = false;
                    saveState.classList.remove('is-dirty');
                    saveState.innerHTML = `<i class="fas fa-cloud-check"></i> Salvo automaticamente às ${escapeHtml(result.saved_at || '')}`;
                } catch (error) {
                    if (error.name === 'AbortError') return;
                    saveState.classList.add('is-dirty');
                    const errorMessage = escapeHtml(error.message || 'Não foi possível salvar');
                    saveState.innerHTML = navigator.onLine
                        ? `<i class="fas fa-triangle-exclamation"></i> ${errorMessage}`
                        : '<i class="fas fa-wifi"></i> Sem conexão — tentando novamente';
                    scheduleAutosave();
                } finally {
                    if (autosaveRequest === requestController) {
                        autosaveRequest = null;
                    }
                }
            }

            function applyRecoveredPayload(payload, shouldMarkDirty = true) {
                if (!payload) return;
                titleField.value = payload.title || '';
                slugField.value = payload.slug || '';
                contentField.value = payload.content || '';
                excerptField.value = payload.excerpt || '';
                statusField.value = payload.status === 'published' ? 'published' : 'draft';
                document.getElementById('published_at').value = payload.published_at || '';
                featuredImageField.value = payload.featured_image || '';
                document.getElementById('tags').value = payload.tags || '';

                const selectedCategories = new Set((payload.categories || []).map(String));
                document.querySelectorAll('input[name="categories[]"]').forEach(input => {
                    input.checked = selectedCategories.has(input.value);
                });

                const editor = typeof tinymce !== 'undefined' ? tinymce.get('content') : null;
                if (editor) editor.setContent(contentField.value);
                renderFeaturedImage(featuredImageField.value, titleField.value || 'Imagem destacada');
                updateSlugPreview();
                updateExcerptCount();
                updateContentMetrics(contentField.value.replace(/<[^>]*>/g, ' '));
                updatePublishControls();
                recoveryBanner.hidden = true;
                if (shouldMarkDirty) {
                    markFormDirty();
                } else {
                    formDirty = false;
                    saveState.classList.remove('is-dirty');
                    saveState.innerHTML = '<i class="fas fa-cloud-check"></i> Rascunho automático carregado';
                }
            }

            async function discardAutosave() {
                const body = new FormData();
                body.append('token', autosaveTokenField.value);
                body.append('autosave_action', 'discard');
                await fetch('/admin/ajax/post-autosave.php', { method: 'POST', body, credentials: 'same-origin' });
                recoveryBanner.hidden = true;
                recoveredPayload = null;
            }

            async function loadAutosave() {
                try {
                    const response = await fetch(`/admin/ajax/post-autosave.php?token=${encodeURIComponent(autosaveTokenField.value)}`, {
                        credentials: 'same-origin'
                    });
                    const result = await response.json();
                    if (!result.success || !result.autosave || !result.autosave.payload) return;

                    const autosaveTime = Date.parse(result.autosave.updated_at.replace(' ', 'T') + '-03:00');
                    const serverTime = serverUpdatedAt
                        ? Date.parse(serverUpdatedAt.replace(' ', 'T') + '-03:00')
                        : 0;
                    if (requestedAutosaveToken && postId === 0) {
                        recoveredPayload = result.autosave.payload;
                        applyRecoveredPayload(recoveredPayload, false);
                        return;
                    }

                    if (postId === 0 || autosaveTime > serverTime) {
                        recoveredPayload = result.autosave.payload;
                        recoveryBanner.hidden = false;
                    }
                } catch (error) {
                    // O editor continua funcional mesmo se a consulta do autosave falhar.
                }
            }

            function renderFeaturedImage(src, alt = 'Imagem destacada') {
                if (!src) {
                    featuredImagePreview.innerHTML = `
                        <div class="featured-image-empty">
                            <i class="fas fa-image" style="font-size: 2rem; margin-bottom: 0.75rem;"></i>
                            <div>Nenhuma imagem destacada</div>
                        </div>
                    `;
                    return;
                }

                featuredImagePreview.innerHTML = `<img src="${escapeHtml(src)}" alt="${escapeHtml(alt)}">`;
            }

            function openMediaModal() {
                mediaModal.classList.add('active');
                mediaModal.setAttribute('aria-hidden', 'false');
                highlightCurrentLibraryImage();
            }

            function closeMediaModal() {
                mediaModal.classList.remove('active');
                mediaModal.setAttribute('aria-hidden', 'true');
            }

            function showMediaPanel(panel) {
                document.querySelectorAll('.media-tab').forEach(tab => {
                    tab.classList.toggle('active', tab.dataset.panel === panel);
                });

                document.getElementById('mediaPanelLibrary').classList.toggle('active', panel === 'library');
                document.getElementById('mediaPanelUpload').classList.toggle('active', panel === 'upload');
            }

            function selectLibraryImage(button) {
                pendingLibraryImage = button.dataset.src || '';
                pendingUploadSelected = false;
                if (uploadInput) {
                    uploadInput.value = '';
                }
                if (uploadPreview) {
                    uploadPreview.style.display = 'none';
                    uploadPreview.innerHTML = '';
                }
                document.querySelectorAll('.media-item').forEach(item => item.classList.remove('selected'));
                button.classList.add('selected');
            }

            function highlightCurrentLibraryImage() {
                document.querySelectorAll('.media-item').forEach(item => {
                    item.classList.toggle('selected', item.dataset.src === featuredImageField.value);
                });
            }

            function previewUploadedImage(event) {
                const file = event.target.files && event.target.files[0];
                if (!file) return;

                if (!file.type.startsWith('image/')) {
                    alert('Selecione um arquivo de imagem válido');
                    event.target.value = '';
                    return;
                }

                pendingUploadSelected = true;
                pendingLibraryImage = '';
                document.querySelectorAll('.media-item').forEach(item => item.classList.remove('selected'));

                const reader = new FileReader();
                reader.onload = function(readerEvent) {
                    uploadPreview.innerHTML = `<img src="${readerEvent.target.result}" alt="Prévia da nova imagem">`;
                    uploadPreview.style.display = 'block';
                };
                reader.readAsDataURL(file);
            }

            function applyMediaSelection() {
                if (pendingUploadSelected) {
                    const file = uploadInput.files && uploadInput.files[0];
                    if (!file) {
                        alert('Selecione uma imagem para enviar');
                        return;
                    }

                    featuredImageField.value = '';
                    featuredImageMode.value = 'upload';
                    const previewImage = uploadPreview.querySelector('img');
                    renderFeaturedImage(previewImage ? previewImage.src : '', file.name);
                    markFormDirty();
                    closeMediaModal();
                    return;
                }

                featuredImageField.value = pendingLibraryImage || '';
                featuredImageMode.value = 'library';
                renderFeaturedImage(featuredImageField.value, document.getElementById('title').value || 'Imagem destacada');
                markFormDirty();
                closeMediaModal();
            }

            function removeFeaturedImage() {
                featuredImageField.value = '';
                featuredImageMode.value = 'library';
                pendingLibraryImage = '';
                pendingUploadSelected = false;
                if (uploadInput) {
                    uploadInput.value = '';
                }
                if (uploadPreview) {
                    uploadPreview.style.display = 'none';
                    uploadPreview.innerHTML = '';
                }
                document.querySelectorAll('.media-item').forEach(item => item.classList.remove('selected'));
                renderFeaturedImage('');
                markFormDirty();
            }

            function escapeHtml(value) {
                return String(value)
                    .replace(/&/g, '&amp;')
                    .replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;')
                    .replace(/"/g, '&quot;')
                    .replace(/'/g, '&#039;');
            }

            titleField.addEventListener('input', updateSlugPreview);
            slugField.addEventListener('input', function() {
                slugField.value = slugify(slugField.value);
                updateSlugPreview();
            });
            updateSlugPreview();
            updateExcerptCount();
            updateContentMetrics(contentField.value.replace(/<[^>]*>/g, ' '));
            updatePublishControls();
            initMetaboxStates();
            autosaveTokenField.value = createAutosaveToken();
            loadAutosave();

            excerptField.addEventListener('input', updateExcerptCount);
            statusField.addEventListener('change', updatePublishControls);
            contentField.addEventListener('input', function() {
                markFormDirty();
                updateContentMetrics(contentField.value.replace(/<[^>]*>/g, ' '));
            });

            postForm.addEventListener('input', function(event) {
                if (event.target !== contentField) {
                    markFormDirty();
                }
            });

            if (mediaSearch) {
                mediaSearch.addEventListener('input', function() {
                    const query = mediaSearch.value.trim().toLocaleLowerCase('pt-BR');
                    document.querySelectorAll('.media-item').forEach(item => {
                        const title = (item.dataset.title || '').toLocaleLowerCase('pt-BR');
                        item.hidden = query !== '' && !title.includes(query);
                    });
                });
            }

            restoreAutosaveButton.addEventListener('click', function() {
                applyRecoveredPayload(recoveredPayload);
            });
            discardAutosaveButton.addEventListener('click', discardAutosave);

            window.addEventListener('online', function() {
                if (formDirty) scheduleAutosave();
            });

            mediaModal.addEventListener('click', function(event) {
                if (event.target === mediaModal) {
                    closeMediaModal();
                }
            });

            document.addEventListener('keydown', function(event) {
                if (event.key === 'Escape' && mediaModal.classList.contains('active')) {
                    closeMediaModal();
                }

                if ((event.ctrlKey || event.metaKey) && event.key.toLowerCase() === 's') {
                    event.preventDefault();
                    postForm.requestSubmit(primarySubmitButton);
                }
            });

            window.addEventListener('beforeunload', function(event) {
                if (!formDirty || isLeaving) return;
                event.preventDefault();
                event.returnValue = '';
            });

            postForm.addEventListener('submit', function(event) {
                window.clearTimeout(autosaveTimer);
                if (autosaveRequest) {
                    autosaveRequest.abort();
                    autosaveRequest = null;
                }

                if (submittingAfterUpload) {
                    isLeaving = true;
                    markSaving();
                    submittingAfterUpload = false;
                    return;
                }

                if (typeof tinymce !== 'undefined' && currentEditorMode !== 'text') {
                    tinymce.triggerSave();
                }

                const editor = typeof tinymce !== 'undefined' ? tinymce.get('content') : null;
                const plainText = currentEditorMode === 'text'
                    ? contentField.value.trim()
                    : (editor ? editor.getContent({ format: 'text' }).trim() : contentField.value.trim());

                if (!plainText) {
                    event.preventDefault();
                    if (editor) {
                        editor.focus();
                    } else {
                        contentField.focus();
                    }
                    alert('Conteúdo é obrigatório');
                    return;
                }

                if (currentEditorMode !== 'text' && editor && typeof editor.uploadImages === 'function') {
                    event.preventDefault();
                    editor.uploadImages().then(function() {
                        submittingAfterUpload = true;
                        postForm.requestSubmit ? postForm.requestSubmit() : postForm.submit();
                    }).catch(function() {
                        alert('Não foi possível enviar todas as imagens do conteúdo.');
                    });
                    return;
                }

                isLeaving = true;
                markSaving();
            });
        </script>
        <?php include __DIR__ . '/../partials/sidebar-close.php'; ?>
</body>
    </html>
    <?php
}

if (!function_exists('array_any')) {
    function array_any($array, $callback) {
        foreach ($array as $item) {
            if ($callback($item)) return true;
        }
        return false;
    }
}

?>
