<?php
/**
 * Gerador de slides - CMS ChiapettaDev
 */

require_once __DIR__ . '/../../db/config.php';
require_once __DIR__ . '/../modules/Auth.php';
require_once __DIR__ . '/../modules/Image.php';
require_once __DIR__ . '/../modules/SlideDeck.php';

if (!Auth::hasPermission('author')) {
    header('Location: /admin/login.php');
    exit;
}

$user = Auth::getCurrentUser();
$message = '';
$messageType = '';

SlideDeck::ensureSchema();

function collect_slide_data() {
    $slides = [];
    $titles = $_POST['slide_title'] ?? [];
    $contents = $_POST['slide_content'] ?? [];
    $images = $_POST['slide_image'] ?? [];
    $orders = $_POST['slide_order'] ?? [];

    $count = max(count($titles), count($contents), count($images), count($orders));
    for ($i = 0; $i < $count; $i++) {
        $slides[] = [
            'title' => $titles[$i] ?? '',
            'content' => $contents[$i] ?? '',
            'image' => $images[$i] ?? '',
            'order_num' => $orders[$i] ?? (($i + 1) * 10)
        ];
    }

    return $slides;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'create' || $_POST['action'] === 'update') {
        $data = [
            'title' => $_POST['title'] ?? '',
            'slug' => $_POST['slug'] ?? '',
            'description' => $_POST['description'] ?? '',
            'status' => $_POST['status'] ?? 'draft',
            'slides' => collect_slide_data()
        ];

        $result = $_POST['action'] === 'create'
            ? SlideDeck::create($data, $user['id'])
            : SlideDeck::update(intval($_POST['deck_id'] ?? 0), $data);

        if ($result['success']) {
            header('Location: /admin/pages/slides.php?edit=' . intval($result['id'] ?? ($_POST['deck_id'] ?? 0)) . '&saved=1');
            exit;
        }

        $message = $result['message'];
        $messageType = 'danger';
        $deck = array_merge($data, ['id' => intval($_POST['deck_id'] ?? 0)]);
    } elseif ($_POST['action'] === 'delete') {
        $result = SlideDeck::delete(intval($_POST['deck_id'] ?? 0));

        if ($result['success']) {
            header('Location: /admin/pages/slides.php');
            exit;
        }

        $message = $result['message'];
        $messageType = 'danger';
    }
}

$editId = intval($_GET['edit'] ?? 0);
$isNew = isset($_GET['new']);

if (!isset($deck)) {
    $deck = $editId ? SlideDeck::getById($editId) : null;
}

if (isset($_GET['saved'])) {
    $message = 'Apresentação salva com sucesso';
    $messageType = 'success';
}

$images = Image::getList(['limit' => 200]);

if (!$editId && !$isNew) {
    $decks = SlideDeck::getList(['limit' => 100]);
} else {
    $slides = $deck['slides'] ?? [
        ['title' => '', 'content' => '', 'image' => '', 'order_num' => 10]
    ];
}

function slide_status_label($status) {
    return $status === 'published' ? 'Publicado' : 'Rascunho';
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Slides | CMS ChiapettaDev</title>
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

        * { box-sizing: border-box; }

        body {
            background: var(--primary);
            color: var(--text);
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Arial, sans-serif;
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

        .header h1 { margin: 0; font-size: 2rem; }

        .btn-primary,
        .btn-small,
        .btn-submit,
        .btn-cancel {
            border: 0;
            border-radius: 6px;
            padding: 0.65rem 1rem;
            font-weight: 700;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            cursor: pointer;
        }

        .btn-primary,
        .btn-submit {
            background: var(--accent);
            color: #07120f;
        }

        .btn-cancel {
            background: var(--secondary);
            color: var(--text);
            border: 1px solid rgba(0, 217, 163, 0.26);
        }

        .btn-small { padding: 0.42rem 0.75rem; font-size: 0.85rem; }
        .btn-edit { background: rgba(100, 210, 255, 0.18); color: #64d2ff; border: 1px solid rgba(100, 210, 255, 0.28); }
        .btn-delete { background: rgba(255, 95, 109, 0.16); color: #ff9ca5; border: 1px solid rgba(255, 95, 109, 0.28); }

        .alert {
            border-radius: 6px;
            border: 1px solid;
            padding: 1rem;
            margin-bottom: 2rem;
        }

        .alert-success { background: rgba(0, 217, 163, 0.1); border-color: rgba(0, 217, 163, 0.3); color: var(--accent); }
        .alert-danger { background: rgba(220, 53, 69, 0.1); border-color: rgba(220, 53, 69, 0.3); color: #ff9ca5; }

        .table-wrap,
        .form-card,
        .slide-card {
            background: var(--secondary);
            border: 1px solid rgba(0, 217, 163, 0.1);
            border-radius: 12px;
        }

        .table-wrap { overflow: hidden; }
        .table { margin: 0; color: var(--text); }
        .table th { color: var(--accent); padding: 1rem; }
        .table td { padding: 1rem; vertical-align: middle; }

        .badge {
            padding: 0.35rem 0.65rem;
            border-radius: 4px;
            font-size: 0.78rem;
            font-weight: 800;
        }

        .badge-published { background: rgba(0, 217, 163, 0.18); color: var(--accent); }
        .badge-draft { background: rgba(255, 193, 7, 0.18); color: #ffcc66; }

        .form-card { padding: 1.5rem; }
        .form-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 1.25rem; }
        .form-group.full { grid-column: 1 / -1; }
        label { display: block; margin-bottom: 0.5rem; font-weight: 700; color: var(--text); }
        input, textarea, select {
            width: 100%;
            background: var(--primary);
            color: var(--text);
            border: 1px solid rgba(0, 217, 163, 0.2);
            border-radius: 6px;
            padding: 0.75rem;
        }
        textarea { min-height: 90px; resize: vertical; }
        small { color: var(--text-muted); display: block; margin-top: 0.35rem; }

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

        .slides-heading {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 1rem;
            margin: 2rem 0 1rem;
        }

        .slide-card {
            padding: 1rem;
            margin-bottom: 1rem;
        }

        .slide-card-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .slide-card-title {
            color: var(--accent);
            font-weight: 800;
        }

        .slide-fields {
            display: grid;
            grid-template-columns: minmax(0, 1fr) 180px;
            gap: 1rem;
        }

        .slide-content-wrap {
            grid-column: 1 / -1;
        }

        .actions {
            display: flex;
            justify-content: flex-end;
            gap: 1rem;
            margin-top: 2rem;
        }

        @media (max-width: 800px) {
            .form-grid,
            .slide-fields { grid-template-columns: 1fr; }
            .header,
            .slides-heading,
            .actions { flex-direction: column; align-items: stretch; }
        }
    </style>
    <link rel="stylesheet" href="/admin/assets/admin.css?v=20260516">
</head>
<body>
<?php include __DIR__ . '/../partials/sidebar.php'; ?>
<div class="container">
    <div class="header">
        <div>
            <h1><?= ($editId || $isNew) ? 'Gerador de Slides' : 'Slides' ?></h1>
            <p style="color: var(--text-muted); margin: 0;">Crie páginas públicas navegáveis slide a slide.</p>
        </div>
        <?php if (!$editId && !$isNew): ?>
            <a href="/admin/pages/slides.php?new=1" class="btn-primary">
                <i class="fas fa-plus"></i>Nova apresentação
            </a>
        <?php endif; ?>
    </div>

    <?php if ($message): ?>
        <div class="alert alert-<?= htmlspecialchars($messageType) ?>"><?= $message ?></div>
    <?php endif; ?>

    <?php if (!$editId && !$isNew): ?>
        <?php if (!empty($decks)): ?>
            <div class="table-wrap">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Título</th>
                            <th>Status</th>
                            <th>Slides</th>
                            <th>URL</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($decks as $row): ?>
                            <?php $deckSlides = SlideDeck::getById($row['id'])['slides'] ?? []; ?>
                            <tr>
                                <td>
                                    <strong><?= htmlspecialchars($row['title']) ?></strong><br>
                                    <small><?= htmlspecialchars($row['description']) ?></small>
                                </td>
                                <td><span class="badge badge-<?= htmlspecialchars($row['status']) ?>"><?= slide_status_label($row['status']) ?></span></td>
                                <td><?= count($deckSlides) ?></td>
                                <td>
                                    <a href="<?= htmlspecialchars(SlideDeck::publicUrl($row['slug'])) ?>" target="_blank" style="color: var(--accent);">
                                        <?= htmlspecialchars(SlideDeck::publicUrl($row['slug'])) ?>
                                    </a>
                                </td>
                                <td>
                                    <a href="/admin/pages/slides.php?edit=<?= intval($row['id']) ?>" class="btn-small btn-edit">
                                        <i class="fas fa-edit"></i>Editar
                                    </a>
                                    <form method="POST" style="display: inline;" onsubmit="return confirm('Remover esta apresentação?');">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="deck_id" value="<?= intval($row['id']) ?>">
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
            <div class="form-card" style="text-align: center; padding: 3rem;">
                <p style="color: var(--text-muted);">Nenhuma apresentação criada ainda.</p>
                <a href="/admin/pages/slides.php?new=1" class="btn-primary">
                    <i class="fas fa-plus"></i>Criar primeira apresentação
                </a>
            </div>
        <?php endif; ?>
    <?php else: ?>
        <form method="POST" class="form-card" id="slidesForm">
            <input type="hidden" name="action" value="<?= $deck ? 'update' : 'create' ?>">
            <?php if ($deck): ?>
                <input type="hidden" name="deck_id" value="<?= intval($deck['id']) ?>">
            <?php endif; ?>

            <div class="form-grid">
                <div class="form-group full">
                    <label for="title">Título da apresentação *</label>
                    <input type="text" id="title" name="title" value="<?= htmlspecialchars($deck['title'] ?? '') ?>" required>
                    <div class="permalink-row">
                        <span>Link:</span>
                        <code>/slides/<span id="slugPreview"><?= htmlspecialchars($deck['slug'] ?? 'nova-apresentacao') ?></span>/</code>
                    </div>
                </div>

                <div class="form-group">
                    <label for="slug">Slug</label>
                    <input type="text" id="slug" name="slug" value="<?= htmlspecialchars($deck['slug'] ?? '') ?>" placeholder="nova-apresentacao">
                </div>

                <div class="form-group">
                    <label for="status">Status</label>
                    <select id="status" name="status">
                        <option value="draft" <?= (($deck['status'] ?? 'draft') === 'draft') ? 'selected' : '' ?>>Rascunho</option>
                        <option value="published" <?= (($deck['status'] ?? 'draft') === 'published') ? 'selected' : '' ?>>Publicado</option>
                    </select>
                </div>

                <div class="form-group full">
                    <label for="description">Descrição</label>
                    <textarea id="description" name="description"><?= htmlspecialchars($deck['description'] ?? '') ?></textarea>
                </div>
            </div>

            <div class="slides-heading">
                <h2 style="margin: 0;">Slides</h2>
                <button type="button" class="btn-cancel" onclick="addSlide()">
                    <i class="fas fa-plus"></i>Adicionar slide
                </button>
            </div>

            <div id="slidesList">
                <?php foreach ($slides as $index => $slide): ?>
                    <div class="slide-card">
                        <div class="slide-card-header">
                            <div class="slide-card-title">Slide <span class="slide-number"><?= $index + 1 ?></span></div>
                            <button type="button" class="btn-small btn-delete" onclick="removeSlide(this)">
                                <i class="fas fa-trash"></i>Remover
                            </button>
                        </div>

                        <div class="slide-fields">
                            <div class="form-group">
                                <label>Título do slide</label>
                                <input type="text" name="slide_title[]" value="<?= htmlspecialchars($slide['title'] ?? '') ?>">
                            </div>
                            <div class="form-group">
                                <label>Ordem</label>
                                <input type="number" name="slide_order[]" value="<?= intval($slide['order_num'] ?? (($index + 1) * 10)) ?>">
                            </div>
                            <div class="form-group">
                                <label>Imagem</label>
                                <select name="slide_image[]">
                                    <option value="">Sem imagem</option>
                                    <?php foreach ($images as $image): ?>
                                        <option value="<?= htmlspecialchars($image['filepath']) ?>" <?= (($slide['image'] ?? '') === $image['filepath']) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($image['title']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                    <?php if (!empty($slide['image']) && !array_filter($images, fn($img) => $img['filepath'] === $slide['image'])): ?>
                                        <option value="<?= htmlspecialchars($slide['image']) ?>" selected><?= htmlspecialchars($slide['image']) ?></option>
                                    <?php endif; ?>
                                </select>
                            </div>
                            <div class="form-group slide-content-wrap">
                                <label>Conteúdo</label>
                                <textarea name="slide_content[]" class="slide-content"><?= htmlspecialchars($slide['content'] ?? '') ?></textarea>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="actions">
                <a href="/admin/pages/slides.php" class="btn-cancel">
                    <i class="fas fa-times"></i>Cancelar
                </a>
                <?php if ($deck): ?>
                    <a href="<?= htmlspecialchars(SlideDeck::publicUrl($deck['slug'])) ?>" target="_blank" class="btn-cancel">
                        <i class="fas fa-eye"></i>Ver página
                    </a>
                <?php endif; ?>
                <button type="submit" class="btn-submit">
                    <i class="fas fa-save"></i>Salvar apresentação
                </button>
            </div>
        </form>
    <?php endif; ?>
</div>

<?php if ($editId || $isNew): ?>
    <template id="slideTemplate">
        <div class="slide-card">
            <div class="slide-card-header">
                <div class="slide-card-title">Slide <span class="slide-number"></span></div>
                <button type="button" class="btn-small btn-delete" onclick="removeSlide(this)">
                    <i class="fas fa-trash"></i>Remover
                </button>
            </div>
            <div class="slide-fields">
                <div class="form-group">
                    <label>Título do slide</label>
                    <input type="text" name="slide_title[]">
                </div>
                <div class="form-group">
                    <label>Ordem</label>
                    <input type="number" name="slide_order[]">
                </div>
                <div class="form-group">
                    <label>Imagem</label>
                    <select name="slide_image[]">
                        <option value="">Sem imagem</option>
                        <?php foreach ($images as $image): ?>
                            <option value="<?= htmlspecialchars($image['filepath']) ?>"><?= htmlspecialchars($image['title']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group slide-content-wrap">
                    <label>Conteúdo</label>
                    <textarea name="slide_content[]" class="slide-content"></textarea>
                </div>
            </div>
        </div>
    </template>
    <script src="https://cdn.tiny.cloud/1/no-api-key/tinymce/7/tinymce.min.js" referrerpolicy="origin"></script>
    <script>
        const titleField = document.getElementById('title');
        const slugField = document.getElementById('slug');
        const slugPreview = document.getElementById('slugPreview');
        const slidesList = document.getElementById('slidesList');

        function slugify(value) {
            return value.normalize('NFD')
                .replace(/[\u0300-\u036f]/g, '')
                .toLowerCase()
                .replace(/[^a-z0-9]+/g, '-')
                .replace(/^-+|-+$/g, '');
        }

        function updateSlugPreview() {
            slugPreview.textContent = slugField.value.trim() || slugify(titleField.value) || 'nova-apresentacao';
        }

        function initEditors() {
            document.querySelectorAll('textarea.slide-content').forEach((textarea, index) => {
                if (!textarea.id) {
                    textarea.id = 'slideContent' + Date.now() + index;
                }

                if (tinymce.get(textarea.id)) {
                    return;
                }

                tinymce.init({
                    selector: '#' + textarea.id,
                    height: 230,
                    menubar: false,
                    plugins: 'autolink code codesample link lists table visualblocks wordcount',
                    toolbar: 'undo redo | blocks | bold italic underline | bullist numlist | link table codesample blockquote | removeformat code',
                    branding: false,
                    promotion: false,
                    language: 'pt_BR',
                    skin: 'oxide-dark',
                    content_css: 'dark',
                    convert_urls: false
                });
            });
        }

        function renumberSlides() {
            document.querySelectorAll('.slide-card').forEach((card, index) => {
                card.querySelector('.slide-number').textContent = index + 1;
                const orderField = card.querySelector('input[name="slide_order[]"]');
                if (orderField && !orderField.value) {
                    orderField.value = (index + 1) * 10;
                }
            });
        }

        function addSlide() {
            if (typeof tinymce !== 'undefined') {
                tinymce.triggerSave();
            }

            const template = document.getElementById('slideTemplate');
            slidesList.appendChild(template.content.firstElementChild.cloneNode(true));
            renumberSlides();
            initEditors();
        }

        function removeSlide(button) {
            const cards = document.querySelectorAll('.slide-card');
            if (cards.length <= 1) {
                alert('A apresentação precisa ter pelo menos um slide');
                return;
            }

            const card = button.closest('.slide-card');
            card.querySelectorAll('textarea.slide-content').forEach(textarea => {
                const editor = tinymce.get(textarea.id);
                if (editor) editor.remove();
            });
            card.remove();
            renumberSlides();
        }

        titleField.addEventListener('input', updateSlugPreview);
        slugField.addEventListener('input', function() {
            slugField.value = slugify(slugField.value);
            updateSlugPreview();
        });

        document.getElementById('slidesForm').addEventListener('submit', function(event) {
            if (typeof tinymce !== 'undefined') {
                tinymce.triggerSave();
            }

            const hasSlide = Array.from(document.querySelectorAll('.slide-card')).some(card => {
                return card.querySelector('input[name="slide_title[]"]').value.trim()
                    || card.querySelector('textarea[name="slide_content[]"]').value.trim()
                    || card.querySelector('select[name="slide_image[]"]').value.trim();
            });

            if (!hasSlide) {
                event.preventDefault();
                alert('Adicione pelo menos um slide');
            }
        });

        updateSlugPreview();
        renumberSlides();
        initEditors();
    </script>
<?php endif; ?>

<?php include __DIR__ . '/../partials/sidebar-close.php'; ?>
</body>
</html>
