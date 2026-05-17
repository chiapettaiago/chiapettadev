<?php
/**
 * Gerenciador de Itens do Site - CMS ChiapettaDev
 */

require_once __DIR__ . '/../../db/config.php';
require_once __DIR__ . '/../modules/Auth.php';
require_once __DIR__ . '/../modules/SiteItem.php';
require_once __DIR__ . '/../modules/Image.php';

if (!Auth::hasPermission('author')) {
    header('Location: /admin/login.php');
    exit;
}

$user = Auth::getCurrentUser();
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $data = [
        'section' => $_POST['section'] ?? 'skill',
        'title' => $_POST['title'] ?? '',
        'description' => $_POST['description'] ?? '',
        'image' => $_POST['image'] ?? '',
        'icon' => $_POST['icon'] ?? '',
        'tags' => $_POST['tags'] ?? '',
        'primary_label' => $_POST['primary_label'] ?? '',
        'primary_url' => $_POST['primary_url'] ?? '',
        'secondary_label' => $_POST['secondary_label'] ?? '',
        'secondary_url' => $_POST['secondary_url'] ?? '',
        'status' => $_POST['status'] ?? 'draft',
        'order_num' => intval($_POST['order_num'] ?? 0)
    ];

    if ($_POST['action'] === 'create') {
        $result = SiteItem::create($data);
    } elseif ($_POST['action'] === 'update') {
        $result = SiteItem::update(intval($_POST['item_id'] ?? 0), $data);
    } elseif ($_POST['action'] === 'delete') {
        $result = SiteItem::delete(intval($_POST['item_id'] ?? 0));
    } else {
        $result = ['success' => false, 'message' => 'Ação inválida'];
    }

    if ($result['success']) {
        if ($_POST['action'] === 'create' || $_POST['action'] === 'delete') {
            $redirectSection = $_POST['redirect_section'] ?? '';
            $redirectUrl = '/admin/pages/site-items.php' . ($redirectSection ? '?section=' . urlencode($redirectSection) : '');
            header('Location: ' . $redirectUrl);
            exit;
        }

        $message = $result['message'];
        $messageType = 'success';
    } else {
        $message = $result['message'];
        $messageType = 'danger';
    }
}

$sections = SiteItem::getSections();
$editId = intval($_GET['edit'] ?? 0);
$item = $editId ? SiteItem::getById($editId) : null;
$images = Image::getList();
$filterSection = $_GET['section'] ?? '';
$items = !$editId ? SiteItem::getList(['section' => $filterSection]) : [];
$newItemUrl = '/admin/pages/site-items.php?edit=-1' . ($filterSection ? '&section=' . urlencode($filterSection) : '');

function cms_section_label($section, $sections) {
    return $sections[$section] ?? $section;
}

function cms_first_words($text, $limit = 110) {
    $text = trim(strip_tags($text ?? ''));
    return strlen($text) > $limit ? substr($text, 0, $limit) . '...' : $text;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Itens do Site | CMS ChiapettaDev</title>
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

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            background: var(--primary);
            color: var(--text);
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
        }

        .container { max-width: 1200px; margin: 0 auto; padding: 2rem; }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 1rem;
            margin-bottom: 2rem;
            padding-bottom: 1.5rem;
            border-bottom: 2px solid rgba(0, 217, 163, 0.2);
        }

        .header h1 { font-size: 2rem; margin: 0; }

        .toolbar {
            display: flex;
            gap: 1rem;
            align-items: center;
            flex-wrap: wrap;
            margin-bottom: 1.5rem;
        }

        .btn-primary,
        .btn-small {
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

        .btn-primary:hover,
        .btn-small:hover { background: #00b885; color: #000; }

        .btn-small { padding: 0.4rem 0.8rem; font-size: 0.85rem; }

        .btn-edit { background: #0dcaf0; }
        .btn-delete { background: var(--danger); color: #fff; }
        .btn-delete:hover { background: #bb2d3b; color: #fff; }

        .btn-cancel,
        .back-link {
            color: var(--text-muted);
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-cancel:hover,
        .back-link:hover { color: var(--accent); }

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

        .table-wrap {
            background: var(--secondary);
            border: 1px solid rgba(0, 217, 163, 0.1);
            border-radius: 12px;
            overflow: hidden;
        }

        .table { margin: 0; color: var(--text); }
        .table th {
            color: var(--accent);
            background: rgba(0, 217, 163, 0.05);
            border-bottom: 2px solid rgba(0, 217, 163, 0.2);
            padding: 1rem;
        }

        .table td {
            padding: 1rem;
            border-bottom: 1px solid rgba(0, 217, 163, 0.1);
            vertical-align: middle;
        }

        .table tbody tr:hover { background: rgba(0, 217, 163, 0.05); }

        .badge {
            padding: 0.35rem 0.65rem;
            border-radius: 4px;
            font-size: 0.78rem;
            font-weight: 700;
        }

        .badge-published { background: rgba(0, 217, 163, 0.2); color: var(--accent); }
        .badge-draft { background: rgba(255, 193, 7, 0.2); color: #ffc107; }
        .badge-section { background: rgba(13, 202, 240, 0.16); color: #70dfff; }

        .form-card {
            background: var(--secondary);
            border: 1px solid rgba(0, 217, 163, 0.1);
            border-radius: 12px;
            padding: 2rem;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 1.25rem;
        }

        .form-group.full { grid-column: 1 / -1; }

        label {
            display: block;
            margin-bottom: 0.5rem;
            color: var(--accent);
            font-weight: 600;
        }

        input,
        textarea,
        select {
            width: 100%;
            background: var(--primary);
            color: var(--text);
            border: 1px solid rgba(0, 217, 163, 0.2);
            border-radius: 6px;
            padding: 0.75rem;
        }

        textarea { min-height: 120px; resize: vertical; }
        small { color: var(--text-muted); display: block; margin-top: 0.35rem; }

        .actions {
            display: flex;
            justify-content: flex-end;
            align-items: center;
            gap: 1rem;
            margin-top: 2rem;
        }

        .empty-state {
            background: var(--secondary);
            border: 1px solid rgba(0, 217, 163, 0.1);
            border-radius: 12px;
            padding: 3rem 2rem;
            text-align: center;
            color: var(--text-muted);
        }

        @media (max-width: 768px) {
            .container { padding: 1rem; }
            .header { flex-direction: column; align-items: flex-start; }
            .form-grid { grid-template-columns: 1fr; }
            .table-wrap { overflow-x: auto; }
        }
    </style>
    <link rel="stylesheet" href="/admin/assets/admin.css?v=20260516">
</head>
<body>
    <?php include __DIR__ . '/../partials/sidebar.php'; ?>
    <div class="container">
        <div class="header">
            <div>
                <h1><?= $item ? 'Editar Item do Site' : ($filterSection === 'nav' ? 'Menu Navbar' : 'Itens do Site') ?></h1>
                <p style="color: var(--text-muted); margin: 0;">Gerencie habilidades, projetos, destaques do blog e links da navbar exibidos no site.</p>
            </div>
            <?php if (!$item): ?>
                <a href="<?= htmlspecialchars($newItemUrl) ?>" class="btn-primary">
                    <i class="fas fa-plus"></i>Novo Item
                </a>
            <?php endif; ?>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-<?= $messageType ?>"><?= $message ?></div>
        <?php endif; ?>

        <?php if ($editId || isset($_GET['edit'])): ?>
            <?php $isNew = !$item; ?>
            <div class="form-card">
                <form method="POST">
                    <input type="hidden" name="action" value="<?= $isNew ? 'create' : 'update' ?>">
                    <input type="hidden" name="redirect_section" value="<?= htmlspecialchars($item['section'] ?? $filterSection) ?>">
                    <?php if (!$isNew): ?>
                        <input type="hidden" name="item_id" value="<?= intval($item['id']) ?>">
                    <?php endif; ?>

                    <div class="form-grid">
                        <div class="form-group">
                            <label for="section">Tipo</label>
                            <select id="section" name="section" required>
                                <?php foreach ($sections as $key => $label): ?>
                                    <option value="<?= htmlspecialchars($key) ?>" <?= (($item['section'] ?? $filterSection ?: 'skill') === $key) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($label) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="status">Status</label>
                            <select id="status" name="status">
                                <option value="draft" <?= (($item['status'] ?? 'published') === 'draft') ? 'selected' : '' ?>>Rascunho</option>
                                <option value="published" <?= (($item['status'] ?? 'published') === 'published') ? 'selected' : '' ?>>Publicado</option>
                            </select>
                        </div>

                        <div class="form-group full">
                            <label for="title">Título</label>
                            <input type="text" id="title" name="title" value="<?= htmlspecialchars($item['title'] ?? '') ?>" required>
                        </div>

                        <div class="form-group full">
                            <label for="description">Descrição</label>
                            <textarea id="description" name="description"><?= htmlspecialchars($item['description'] ?? '') ?></textarea>
                            <small>Usada em projetos e destaques do blog. Habilidades e links da navbar podem ficar sem descrição.</small>
                        </div>

                        <div class="form-group">
                            <label for="image">Imagem</label>
                            <select id="image" name="image">
                                <option value="">Sem imagem</option>
                                <?php foreach ($images as $image): ?>
                                    <option value="<?= htmlspecialchars($image['filepath']) ?>" <?= (($item['image'] ?? '') === $image['filepath']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($image['title']) ?>
                                    </option>
                                <?php endforeach; ?>
                                <?php if (!empty($item['image']) && !array_filter($images, fn($img) => $img['filepath'] === $item['image'])): ?>
                                    <option value="<?= htmlspecialchars($item['image']) ?>" selected><?= htmlspecialchars($item['image']) ?></option>
                                <?php endif; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="icon">Ícone ou emoji</label>
                            <input type="text" id="icon" name="icon" value="<?= htmlspecialchars($item['icon'] ?? '') ?>" placeholder="Ex.: 👩‍💼">
                            <small>Aparece quando o projeto não tem imagem.</small>
                        </div>

                        <div class="form-group">
                            <label for="tags">Tags</label>
                            <input type="text" id="tags" name="tags" value="<?= htmlspecialchars($item['tags'] ?? '') ?>" placeholder="PHP, Linux, Python">
                            <small>Separe por vírgula.</small>
                        </div>

                        <div class="form-group">
                            <label for="order_num">Ordem</label>
                            <input type="number" id="order_num" name="order_num" value="<?= intval($item['order_num'] ?? 0) ?>">
                        </div>

                        <div class="form-group">
                            <label for="primary_label">Texto do link principal</label>
                            <input type="text" id="primary_label" name="primary_label" value="<?= htmlspecialchars($item['primary_label'] ?? '') ?>" placeholder="Ver projeto">
                        </div>

                        <div class="form-group">
                            <label for="primary_url">URL principal</label>
                            <input type="text" id="primary_url" name="primary_url" value="<?= htmlspecialchars($item['primary_url'] ?? '') ?>" placeholder="https://... ou /blog/...">
                            <small>Para a navbar, use links como <strong>#sobre</strong>, <strong>#blog</strong>, <strong>/blog/</strong> ou uma URL externa.</small>
                        </div>

                        <div class="form-group">
                            <label for="secondary_label">Texto do link secundário</label>
                            <input type="text" id="secondary_label" name="secondary_label" value="<?= htmlspecialchars($item['secondary_label'] ?? '') ?>" placeholder="GitHub">
                        </div>

                        <div class="form-group">
                            <label for="secondary_url">URL secundária</label>
                            <input type="text" id="secondary_url" name="secondary_url" value="<?= htmlspecialchars($item['secondary_url'] ?? '') ?>">
                        </div>
                    </div>

                    <div class="actions">
                        <a href="/admin/pages/site-items.php" class="btn-cancel">
                            <i class="fas fa-times"></i>Cancelar
                        </a>
                        <button type="submit" class="btn-primary">
                            <i class="fas fa-save"></i><?= $isNew ? 'Criar Item' : 'Atualizar Item' ?>
                        </button>
                    </div>
                </form>
            </div>
        <?php else: ?>
            <form method="GET" class="toolbar">
                <select name="section" style="max-width: 260px;">
                    <option value="">Todos os tipos</option>
                    <?php foreach ($sections as $key => $label): ?>
                        <option value="<?= htmlspecialchars($key) ?>" <?= $filterSection === $key ? 'selected' : '' ?>>
                            <?= htmlspecialchars($label) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" class="btn-small"><i class="fas fa-filter"></i>Filtrar</button>
                <?php if ($filterSection): ?>
                    <a href="/admin/pages/site-items.php" class="back-link">Limpar filtro</a>
                <?php endif; ?>
            </form>

            <?php if (!empty($items)): ?>
                <div class="table-wrap">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Ordem</th>
                                <th>Tipo</th>
                                <th>Título</th>
                                <th>Descrição</th>
                                <th>Status</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($items as $row): ?>
                                <tr>
                                    <td><?= intval($row['order_num']) ?></td>
                                    <td><span class="badge badge-section"><?= htmlspecialchars(cms_section_label($row['section'], $sections)) ?></span></td>
                                    <td>
                                        <strong><?= htmlspecialchars($row['title']) ?></strong>
                                        <?php if (!empty($row['tags'])): ?>
                                            <br><small><?= htmlspecialchars($row['tags']) ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= htmlspecialchars(cms_first_words($row['description'])) ?></td>
                                    <td><span class="badge badge-<?= htmlspecialchars($row['status']) ?>"><?= $row['status'] === 'published' ? 'Publicado' : 'Rascunho' ?></span></td>
                                    <td>
                                        <a href="/admin/pages/site-items.php?edit=<?= intval($row['id']) ?><?= $filterSection ? '&section=' . urlencode($filterSection) : '' ?>" class="btn-small btn-edit">
                                            <i class="fas fa-edit"></i>Editar
                                        </a>
                                        <form method="POST" style="display: inline;" onsubmit="return confirm('Remover este item do site?');">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="redirect_section" value="<?= htmlspecialchars($filterSection) ?>">
                                            <input type="hidden" name="item_id" value="<?= intval($row['id']) ?>">
                                            <button type="submit" class="btn-small btn-delete">
                                                <i class="fas fa-trash"></i>Remover
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <p>Nenhum item encontrado.</p>
                    <a href="<?= htmlspecialchars($newItemUrl) ?>" class="btn-primary">
                        <i class="fas fa-plus"></i>Criar primeiro item
                    </a>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
    <?php include __DIR__ . '/../partials/sidebar-close.php'; ?>
</body>
</html>
