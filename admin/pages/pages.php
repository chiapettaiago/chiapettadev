<?php
/**
 * Gerenciador de Páginas - CMS ChiapettaDev
 */

require_once __DIR__ . '/../../db/config.php';
require_once __DIR__ . '/../modules/Auth.php';
require_once __DIR__ . '/../modules/Page.php';
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

// Processar ações
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'create' || $_POST['action'] === 'update') {
            $data = [
                'title' => $_POST['title'] ?? '',
                'slug' => $_POST['slug'] ?? '',
                'content' => $_POST['content'] ?? '',
                'status' => $_POST['status'] ?? 'draft',
                'featured_image' => $_POST['featured_image'] ?? '',
                'parent_id' => !empty($_POST['parent_id']) ? intval($_POST['parent_id']) : false,
                'order_num' => intval($_POST['order_num'] ?? 0)
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
                            'description' => 'Imagem destacada enviada pela tela de edição de página.'
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
                    $page = [
                        'id' => intval($_POST['page_id'] ?? 0),
                        'title' => $data['title'],
                        'slug' => $data['slug'],
                        'content' => $data['content'],
                        'status' => $data['status'],
                        'featured_image' => $data['featured_image'],
                        'parent_id' => $data['parent_id'],
                        'order_num' => $data['order_num']
                    ];
                } elseif ($_POST['action'] === 'create') {
                    $result = Page::create($data, $user['id']);
                } else {
                    $pageId = intval($_POST['page_id'] ?? 0);
                    $result = Page::update($pageId, $data, $user['id']);
                }

                if (isset($result) && $result['success']) {
                    $message = $result['message'];
                    $messageType = 'success';
                    
                    if ($_POST['action'] === 'create') {
                        header('Location: /admin/pages/pages.php');
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
            $pageId = intval($_POST['page_id'] ?? 0);
            $result = Page::delete($pageId, $user['id']);

            if ($result['success']) {
                header('Location: /admin/pages/pages.php');
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
$isNew = isset($_GET['new']);
$page = null;

if ($editId > 0) {
    $page = Page::getById($editId);
    if (!$page || ($page['author_id'] !== $user['id'] && !Auth::hasPermission('admin'))) {
        header('Location: /admin/pages/pages.php');
        exit;
    }
}

// Obter lista de imagens e páginas para seleção
$images = Image::getList();
$allPages = Page::getList(['limit' => 100]);
$parentPages = array_filter($allPages, fn($p) => $p['id'] != $editId);

if (!$editId && !$isNew) {
    // Listar páginas
    $pages = Page::getList(['limit' => 50]);
    ?>
    <!DOCTYPE html>
    <html lang="pt-BR">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Páginas | CMS ChiapettaDev</title>
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
            }
        </style>
        <link rel="stylesheet" href="/admin/assets/admin.css?v=20260516">
</head>
    <body>
    <?php include __DIR__ . '/../partials/sidebar.php'; ?>
    <div class="container">
            <div class="header">
                <h1><i class="fas fa-file"></i> Páginas</h1>
                <a href="/admin/pages/pages.php?new=1" class="btn-primary">
                    <i class="fas fa-plus"></i>Nova Página
                </a>
            </div>

            <?php if (!empty($message)): ?>
                <div class="alert alert-<?= $messageType ?>">
                    <?= htmlspecialchars($message) ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($pages)): ?>
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
                            <?php foreach ($pages as $p): ?>
                                <tr>
                                    <td>
                                        <strong><?= htmlspecialchars($p['title']) ?></strong><br>
                                        <small style="color: var(--text-muted);">
                                            /<?= htmlspecialchars($p['slug']) ?>/
                                        </small>
                                    </td>
                                    <td>
                                        <span class="badge badge-<?= $p['status'] ?>">
                                            <?= ucfirst($p['status']) ?>
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
                                            <a href="/admin/pages/pages.php?edit=<?= $p['id'] ?>" class="btn-small btn-edit">
                                                <i class="fas fa-edit"></i> Editar
                                            </a>
                                            <form method="POST" style="display: inline;" onsubmit="return confirm('Tem certeza que deseja deletar esta página?');">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="page_id" value="<?= $p['id'] ?>">
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
                    <p style="color: var(--text-muted); margin-bottom: 1rem;">Nenhuma página criada ainda</p>
                    <a href="/admin/pages/pages.php?new=1" class="btn-primary">
                        <i class="fas fa-plus"></i>Criar Primeira Página
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
        <title><?= $page ? 'Editar' : 'Nova' ?> Página | CMS ChiapettaDev</title>
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

            .back-link {
                color: var(--accent);
                text-decoration: none;
                display: inline-flex;
                align-items: center;
                gap: 0.5rem;
                margin-top: 2rem;
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
</head>
    <body>
    <?php include __DIR__ . '/../partials/sidebar.php'; ?>
    <div class="container">
            <div class="header">
                <h1><?= $page ? 'Editar Página' : 'Nova Página' ?></h1>
            </div>

            <?php if (!empty($message)): ?>
                <div class="alert alert-<?= $messageType ?>">
                    <?= htmlspecialchars($message) ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="" enctype="multipart/form-data">
                <input type="hidden" name="action" value="<?= $page ? 'update' : 'create' ?>">
                <?php if ($page): ?>
                    <input type="hidden" name="page_id" value="<?= $page['id'] ?>">
                <?php endif; ?>

                <div class="form-group">
                    <label for="title">Título da Página *</label>
                    <input 
                        type="text" 
                        id="title" 
                        name="title" 
                        value="<?= $page ? htmlspecialchars($page['title']) : '' ?>"
                        placeholder="Digite o título da página"
                        required
                    >
                    <div class="permalink-row">
                        <span>Link:</span>
                        <code>/<span id="slugPreview"><?= $page ? htmlspecialchars($page['slug']) : 'nova-pagina' ?></span>/</code>
                    </div>
                </div>

                <div class="form-group">
                    <label for="slug">Slug</label>
                    <input
                        type="text"
                        id="slug"
                        name="slug"
                        value="<?= $page ? htmlspecialchars($page['slug']) : '' ?>"
                        placeholder="sera-gerado-a-partir-do-titulo"
                    >
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="status">Status</label>
                        <select id="status" name="status">
                            <option value="draft" <?= (!$page || $page['status'] === 'draft') ? 'selected' : '' ?>>Rascunho</option>
                            <option value="published" <?= ($page && $page['status'] === 'published') ? 'selected' : '' ?>>Publicado</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Imagem Destacada</label>
                        <input type="hidden" id="featured_image" name="featured_image" value="<?= $page ? htmlspecialchars($page['featured_image'] ?? '') : '' ?>">
                        <input type="hidden" id="featured_image_mode" name="featured_image_mode" value="library">
                        <div class="featured-image-card">
                            <div class="featured-image-preview" id="featuredImagePreview">
                                <?php if ($page && !empty($page['featured_image'])): ?>
                                    <img src="<?= htmlspecialchars($page['featured_image']) ?>" alt="<?= htmlspecialchars($page['title']) ?>">
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
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="parent_id">Página Principal</label>
                        <select id="parent_id" name="parent_id">
                            <option value="">Nenhuma (página raiz)</option>
                            <?php foreach ($parentPages as $p): ?>
                                <option value="<?= $p['id'] ?>" <?= ($page && $page['parent_id'] === $p['id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($p['title']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="order_num">Ordem</label>
                        <input 
                            type="number" 
                            id="order_num" 
                            name="order_num" 
                            value="<?= $page ? $page['order_num'] : 0 ?>"
                            placeholder="0"
                        >
                    </div>
                </div>

                <div class="form-group">
                    <label for="content">Conteúdo *</label>
                    <textarea 
                        id="content" 
                        class="tinymce-editor"
                        name="content" 
                        required
                    ><?= $page ? htmlspecialchars($page['content']) : '' ?></textarea>
                </div>

                <div class="button-group">
                    <button type="submit" class="btn-submit">
                        <i class="fas fa-save"></i><?= $page ? 'Atualizar Página' : 'Criar Página' ?>
                    </button>
                    <a href="/admin/pages/pages.php" class="btn-cancel">
                        <i class="fas fa-times"></i>Cancelar
                    </a>
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
                            <button type="button" class="media-tab active" data-panel="library" onclick="showMediaPanel('library')">Biblioteca</button>
                            <button type="button" class="media-tab" data-panel="upload" onclick="showMediaPanel('upload')">Enviar nova</button>
                        </div>

                        <div class="media-panel active" id="mediaPanelLibrary">
                            <?php if (!empty($images)): ?>
                                <div class="media-grid">
                                    <?php foreach ($images as $img): ?>
                                        <button type="button" class="media-item" data-src="<?= htmlspecialchars($img['filepath']) ?>" data-title="<?= htmlspecialchars($img['title']) ?>" onclick="selectLibraryImage(this)">
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

            <a href="/admin/pages/pages.php" class="back-link">
                <i class="fas fa-arrow-left"></i>Voltar à Lista de Páginas
            </a>
        </div>
        <script src="https://cdn.tiny.cloud/1/vzv83v6j3ph3tx55wbjhbuz9i0qsr8mhfhigw0k0kq9qqyhr/tinymce/8/tinymce.min.js" referrerpolicy="origin" crossorigin="anonymous"></script>
        <script>
            const pageForm = document.querySelector('form');
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
            let pendingLibraryImage = featuredImageField.value;
            let pendingUploadSelected = false;
            const contentImages = <?= json_encode(array_map(function($image) {
                return [
                    'title' => $image['title'],
                    'url' => $image['filepath']
                ];
            }, $images), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>;

            tinymce.init({
                selector: '#content',
                height: 560,
                menubar: 'file edit view insert format tools table help',
                plugins: 'anchor autolink charmap code codesample fullscreen image link lists media preview searchreplace table visualblocks wordcount',
                toolbar: 'undo redo | blocks fontfamily fontsize | bold italic underline strikethrough | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | link image media table codesample blockquote | forecolor backcolor removeformat | code preview fullscreen',
                toolbar_mode: 'sliding',
                branding: false,
                promotion: false,
                language: 'pt-BR',
                skin: 'oxide-dark',
                content_css: 'dark',
                automatic_uploads: false,
                relative_urls: false,
                remove_script_host: false,
                convert_urls: false,
                image_advtab: true,
                file_picker_types: 'image',
                file_picker_callback: function(callback, value, meta) {
                    if (meta.filetype !== 'image') {
                        return;
                    }

                    if (!contentImages.length) {
                        const url = window.prompt('Cole a URL da imagem:');
                        if (url) {
                            callback(url);
                        }
                        return;
                    }

                    tinymce.activeEditor.windowManager.open({
                        title: 'Selecionar imagem',
                        body: {
                            type: 'panel',
                            items: [
                                {
                                    type: 'selectbox',
                                    name: 'imageIndex',
                                    label: 'Biblioteca de mídia',
                                    items: contentImages.map(function(image, index) {
                                        return { text: image.title, value: String(index) };
                                    })
                                }
                            ]
                        },
                        buttons: [
                            { type: 'cancel', text: 'Cancelar' },
                            { type: 'submit', text: 'Inserir', primary: true }
                        ],
                        onSubmit: function(api) {
                            const data = api.getData();
                            const image = contentImages[Number(data.imageIndex)];
                            api.close();
                            callback(image.url, { alt: image.title, title: image.title });
                        }
                    });
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
                const slug = slugField.value.trim() || slugify(titleField.value) || 'nova-pagina';
                slugPreview.textContent = slug;
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
                if (uploadInput) uploadInput.value = '';
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
                    closeMediaModal();
                    return;
                }

                featuredImageField.value = pendingLibraryImage || '';
                featuredImageMode.value = 'library';
                renderFeaturedImage(featuredImageField.value, titleField.value || 'Imagem destacada');
                closeMediaModal();
            }

            function removeFeaturedImage() {
                featuredImageField.value = '';
                featuredImageMode.value = 'library';
                pendingLibraryImage = '';
                pendingUploadSelected = false;
                if (uploadInput) uploadInput.value = '';
                if (uploadPreview) {
                    uploadPreview.style.display = 'none';
                    uploadPreview.innerHTML = '';
                }
                document.querySelectorAll('.media-item').forEach(item => item.classList.remove('selected'));
                renderFeaturedImage('');
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

            mediaModal.addEventListener('click', function(event) {
                if (event.target === mediaModal) {
                    closeMediaModal();
                }
            });

            document.addEventListener('keydown', function(event) {
                if (event.key === 'Escape' && mediaModal.classList.contains('active')) {
                    closeMediaModal();
                }
            });

            pageForm.addEventListener('submit', function(event) {
                if (typeof tinymce !== 'undefined') {
                    tinymce.triggerSave();
                }

                const editor = typeof tinymce !== 'undefined' ? tinymce.get('content') : null;
                const plainText = editor ? editor.getContent({ format: 'text' }).trim() : contentField.value.trim();

                if (!plainText) {
                    event.preventDefault();
                    if (editor) {
                        editor.focus();
                    } else {
                        contentField.focus();
                    }
                    alert('Conteúdo é obrigatório');
                }
            });
        </script>
        <?php include __DIR__ . '/../partials/sidebar-close.php'; ?>
</body>
    </html>
    <?php
}
?>
