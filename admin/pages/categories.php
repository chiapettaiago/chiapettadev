<?php
/**
 * Gerenciamento de categorias dos posts.
 */

require_once __DIR__ . '/../../db/config.php';
require_once __DIR__ . '/../modules/Auth.php';

if (!Auth::hasPermission('author')) {
    header('Location: /admin/login.php');
    exit;
}

$db = Database::getInstance();
$message = '';
$messageType = 'success';

if (empty($_SESSION['category_csrf_token'])) {
    $_SESSION['category_csrf_token'] = bin2hex(random_bytes(24));
}
$csrfToken = $_SESSION['category_csrf_token'];

if (!function_exists('category_slugify')) {
    function category_slugify($value) {
        $value = trim((string) $value);
        $transliterated = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);
        if ($transliterated !== false) {
            $value = $transliterated;
        }
        $value = strtolower($value);
        $value = preg_replace('/[^a-z0-9]+/', '-', $value);
        return trim($value, '-');
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $submittedToken = (string) ($_POST['csrf_token'] ?? '');
    if (!hash_equals($csrfToken, $submittedToken)) {
        $message = 'A sessão do formulário expirou. Recarregue a página e tente novamente.';
        $messageType = 'danger';
    } else {
        $action = $_POST['action'] ?? '';

        if ($action === 'save') {
            $categoryId = intval($_POST['category_id'] ?? 0);
            $name = trim($_POST['name'] ?? '');
            $slugInput = trim($_POST['slug'] ?? '');
            $slug = category_slugify($slugInput !== '' ? $slugInput : $name);
            $description = trim($_POST['description'] ?? '');

            if ($name === '') {
                $message = 'Informe o nome da categoria.';
                $messageType = 'danger';
            } elseif ($slug === '') {
                $message = 'Não foi possível gerar um slug válido para a categoria.';
                $messageType = 'danger';
            } else {
                $duplicate = $db->query(
                    'SELECT id FROM categories WHERE (name = ? OR slug = ?) AND id <> ? LIMIT 1',
                    [$name, $slug, $categoryId]
                )->fetch(PDO::FETCH_ASSOC);

                if ($duplicate) {
                    $message = 'Já existe uma categoria com este nome ou slug.';
                    $messageType = 'danger';
                } else {
                    $data = [
                        'name' => $name,
                        'slug' => $slug,
                        'description' => $description !== '' ? $description : null
                    ];

                    if ($categoryId > 0) {
                        $db->update('categories', $data, 'id = ' . $categoryId);
                        header('Location: /admin/pages/categories.php?saved=updated');
                    } else {
                        $db->insert('categories', $data);
                        header('Location: /admin/pages/categories.php?saved=created');
                    }
                    exit;
                }
            }
        }

        if ($action === 'delete') {
            $categoryId = intval($_POST['category_id'] ?? 0);
            $children = (int) $db->query(
                'SELECT COUNT(*) FROM categories WHERE parent_id = ?',
                [$categoryId]
            )->fetchColumn();

            if ($categoryId <= 0) {
                $message = 'Categoria inválida.';
                $messageType = 'danger';
            } elseif ($children > 0) {
                $message = 'Essa categoria possui subcategorias e não pode ser excluída.';
                $messageType = 'danger';
            } else {
                $db->delete('categories', 'id = ' . $categoryId);
                header('Location: /admin/pages/categories.php?saved=deleted');
                exit;
            }
        }
    }
}

$savedState = $_GET['saved'] ?? '';
if ($savedState === 'created') {
    $message = 'Categoria criada com sucesso.';
} elseif ($savedState === 'updated') {
    $message = 'Categoria atualizada com sucesso.';
} elseif ($savedState === 'deleted') {
    $message = 'Categoria excluída. Os posts vinculados foram preservados.';
}

$editId = intval($_GET['edit'] ?? 0);
$editingCategory = $editId > 0
    ? $db->selectOne('categories', 'id = ?', [$editId])
    : null;

$categories = $db->query(
    'SELECT c.*, COUNT(DISTINCT pc.post_id) AS post_count
     FROM categories c
     LEFT JOIN post_categories pc ON pc.category_id = c.id
     GROUP BY c.id
     ORDER BY c.name ASC'
)->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Categorias | CMS ChiapettaDev</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="icon" href="/images/favicon-apple.jpg" type="image/jpeg">
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif; }
        .categories-shell { max-width: 1180px; margin: 0 auto; }
        .categories-header { display: flex; justify-content: space-between; gap: 1rem; align-items: center; margin-bottom: 1.5rem; }
        .categories-header h1 { margin: 0 0 0.35rem; }
        .categories-header p { margin: 0; color: var(--cms-muted); }
        .categories-layout { display: grid; grid-template-columns: minmax(280px, 0.75fr) minmax(0, 1.45fr); gap: 1.25rem; align-items: start; }
        .category-card { padding: 1.25rem; border: 1px solid var(--cms-border); border-radius: 14px; background: #fff; box-shadow: var(--cms-shadow); }
        .category-card h2 { margin: 0 0 1rem; font-size: 1.15rem; }
        .category-form { display: grid; gap: 1rem; }
        .category-form label { display: block; margin-bottom: 0.4rem; font-weight: 700; }
        .category-form input, .category-form textarea { width: 100%; padding: 0.7rem 0.8rem; }
        .category-form textarea { min-height: 115px; resize: vertical; }
        .category-form-actions { display: flex; flex-wrap: wrap; gap: 0.65rem; }
        .category-table-wrap { overflow-x: auto; }
        .category-table { width: 100%; min-width: 620px; border-collapse: collapse; }
        .category-table th, .category-table td { padding: 0.85rem; border-bottom: 1px solid var(--cms-border); text-align: left; vertical-align: middle; }
        .category-table th { color: var(--cms-muted); font-size: 0.78rem; text-transform: uppercase; letter-spacing: 0.04em; }
        .category-name { font-weight: 750; }
        .category-slug { color: var(--cms-muted); font-family: ui-monospace, monospace; font-size: 0.82rem; }
        .category-actions { display: flex; flex-wrap: wrap; gap: 0.45rem; }
        .category-empty { padding: 2rem; text-align: center; color: var(--cms-muted); }
        @media (max-width: 800px) {
            .categories-header { align-items: flex-start; flex-direction: column; }
            .categories-layout { grid-template-columns: 1fr; }
            .category-card { padding: 1rem; }
        }
    </style>
    <link rel="stylesheet" href="/admin/assets/admin.css?v=20260805e">
</head>
<body>
<?php include __DIR__ . '/../partials/sidebar.php'; ?>
<div class="container categories-shell">
    <header class="categories-header">
        <div>
            <h1><i class="fas fa-folder-tree"></i> Categorias</h1>
            <p>Organize os assuntos do blog e vincule cada post a uma ou mais categorias.</p>
        </div>
        <a class="btn-primary" href="/admin/pages/posts.php?new=1"><i class="fas fa-plus"></i> Novo post</a>
    </header>

    <?php if ($message !== ''): ?>
        <div class="alert alert-<?= htmlspecialchars($messageType) ?>"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <div class="categories-layout">
        <section class="category-card">
            <h2><?= $editingCategory ? 'Editar categoria' : 'Nova categoria' ?></h2>
            <form method="POST" class="category-form">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                <input type="hidden" name="action" value="save">
                <input type="hidden" name="category_id" value="<?= intval($editingCategory['id'] ?? 0) ?>">
                <div>
                    <label for="name">Nome *</label>
                    <input id="name" name="name" required maxlength="120" value="<?= htmlspecialchars($editingCategory['name'] ?? '') ?>" placeholder="Ex.: Inteligência Artificial">
                </div>
                <div>
                    <label for="slug">Slug</label>
                    <input id="slug" name="slug" maxlength="160" value="<?= htmlspecialchars($editingCategory['slug'] ?? '') ?>" placeholder="gerado-automaticamente">
                </div>
                <div>
                    <label for="description">Descrição</label>
                    <textarea id="description" name="description" placeholder="Explique quais posts pertencem a esta categoria."><?= htmlspecialchars($editingCategory['description'] ?? '') ?></textarea>
                </div>
                <div class="category-form-actions">
                    <button class="btn-primary" type="submit"><i class="fas fa-save"></i> <?= $editingCategory ? 'Atualizar' : 'Criar categoria' ?></button>
                    <?php if ($editingCategory): ?>
                        <a class="btn-cancel" href="/admin/pages/categories.php">Cancelar</a>
                    <?php endif; ?>
                </div>
            </form>
        </section>

        <section class="category-card">
            <h2>Categorias cadastradas</h2>
            <?php if ($categories): ?>
                <div class="category-table-wrap">
                    <table class="category-table">
                        <thead><tr><th>Categoria</th><th>Posts</th><th>Descrição</th><th>Ações</th></tr></thead>
                        <tbody>
                        <?php foreach ($categories as $category): ?>
                            <tr>
                                <td><div class="category-name"><?= htmlspecialchars($category['name']) ?></div><div class="category-slug"><?= htmlspecialchars($category['slug']) ?></div></td>
                                <td><?= intval($category['post_count']) ?></td>
                                <td><?= htmlspecialchars($category['description'] ?: '—') ?></td>
                                <td>
                                    <div class="category-actions">
                                        <a class="btn-small btn-edit" href="/admin/pages/categories.php?edit=<?= intval($category['id']) ?>"><i class="fas fa-pen"></i> Editar</a>
                                        <form method="POST" onsubmit="return confirm('Excluir esta categoria? Os posts serão preservados.');">
                                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="category_id" value="<?= intval($category['id']) ?>">
                                            <button class="btn-small btn-delete" type="submit"><i class="fas fa-trash"></i> Excluir</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="category-empty">Nenhuma categoria criada ainda.</div>
            <?php endif; ?>
        </section>
    </div>
</div>
<?php include __DIR__ . '/../partials/sidebar-close.php'; ?>
</body>
</html>
