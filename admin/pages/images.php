<?php
/**
 * Gerenciador de Imagens - CMS ChiapettaDev
 */

require_once __DIR__ . '/../../db/config.php';
require_once __DIR__ . '/../modules/Auth.php';
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

// Processar upload de imagem
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['image'])) {
    $metadata = [
        'title' => $_POST['title'] ?? '',
        'alt_text' => $_POST['alt_text'] ?? '',
        'description' => $_POST['description'] ?? ''
    ];

    $result = Image::upload($_FILES['image'], $user['id'], $metadata);
    
    if ($result['success']) {
        $message = $result['message'];
        $messageType = 'success';
    } else {
        $message = $result['message'];
        $messageType = 'danger';
    }
}

// Processar ações
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'copy_public') {
        $imageId = intval($_POST['image_id'] ?? 0);
        $result = Image::copyToPublic($imageId);
        
        if ($result['success']) {
            $message = $result['message'];
            $messageType = 'success';
        } else {
            $message = $result['message'];
            $messageType = 'danger';
        }
    } elseif ($_POST['action'] === 'delete') {
        $imageId = intval($_POST['image_id'] ?? 0);
        $result = Image::delete($imageId);
        
        if ($result['success']) {
            $message = $result['message'];
            $messageType = 'success';
        } else {
            $message = $result['message'];
            $messageType = 'danger';
        }
    } elseif ($_POST['action'] === 'update_meta') {
        $imageId = intval($_POST['image_id'] ?? 0);
        $data = [
            'title' => $_POST['title'] ?? '',
            'alt_text' => $_POST['alt_text'] ?? '',
            'description' => $_POST['description'] ?? ''
        ];
        
        $result = Image::updateMetadata($imageId, $data);
        
        if ($result['success']) {
            $message = $result['message'];
            $messageType = 'success';
        } else {
            $message = $result['message'];
            $messageType = 'danger';
        }
    }
}

// Obter lista de imagens
$images = Image::getList(['limit' => 100]);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Imagens | CMS ChiapettaDev</title>
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
            margin-bottom: 2rem;
            padding-bottom: 1.5rem;
            border-bottom: 2px solid rgba(0, 217, 163, 0.2);
        }

        .header h1 {
            font-size: 2rem;
            margin: 0;
        }

        .upload-section {
            background: var(--secondary);
            border: 2px dashed rgba(0, 217, 163, 0.3);
            border-radius: 12px;
            padding: 2rem;
            margin-bottom: 2rem;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .upload-section:hover {
            border-color: var(--accent);
            background: rgba(0, 217, 163, 0.05);
        }

        .upload-section.dragover {
            border-color: var(--accent);
            background: rgba(0, 217, 163, 0.1);
        }

        .upload-section input[type="file"] {
            display: none;
        }

        .upload-icon {
            font-size: 3rem;
            color: var(--accent);
            margin-bottom: 1rem;
        }

        .upload-section p {
            margin: 0;
            color: var(--text-muted);
        }

        .form-group {
            margin-bottom: 1rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: var(--text);
            font-size: 0.9rem;
        }

        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 0.6rem;
            background: var(--primary);
            border: 1px solid rgba(0, 217, 163, 0.2);
            border-radius: 6px;
            color: var(--text);
            font-size: 0.9rem;
        }

        .form-group input:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: var(--accent);
            box-shadow: 0 0 0 2px rgba(0, 217, 163, 0.1);
        }

        .form-group textarea {
            min-height: 60px;
            resize: vertical;
        }

        .btn-upload {
            background: var(--accent);
            color: #000;
            border: none;
            border-radius: 6px;
            padding: 0.7rem 1.5rem;
            font-weight: 600;
            cursor: pointer;
            width: 100%;
            transition: all 0.3s ease;
        }

        .btn-upload:hover {
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

        .gallery {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .gallery-item {
            background: var(--secondary);
            border: 1px solid rgba(0, 217, 163, 0.1);
            border-radius: 12px;
            overflow: hidden;
            transition: all 0.3s ease;
        }

        .gallery-item:hover {
            border-color: var(--accent);
            transform: translateY(-5px);
        }

        .gallery-item img {
            width: 100%;
            height: 200px;
            object-fit: cover;
            display: block;
        }

        .gallery-item-content {
            padding: 1rem;
        }

        .gallery-item-title {
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: var(--text);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .gallery-item-info {
            font-size: 0.8rem;
            color: var(--text-muted);
            margin-bottom: 0.75rem;
        }

        .gallery-item-actions {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }

        .btn-small {
            padding: 0.35rem 0.7rem;
            border: none;
            border-radius: 4px;
            font-size: 0.75rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
        }

        .btn-edit {
            background: var(--accent);
            color: #000;
        }

        .btn-edit:hover {
            background: #00b885;
        }

        .btn-copy {
            background: #0dcaf0;
            color: #000;
        }

        .btn-copy:hover {
            background: #0bb5d8;
        }

        .btn-delete {
            background: var(--danger);
            color: white;
        }

        .btn-delete:hover {
            background: #bb2d3b;
        }

        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.7);
            align-items: center;
            justify-content: center;
        }

        .modal.active {
            display: flex;
        }

        .modal-content {
            background: var(--secondary);
            border: 1px solid rgba(0, 217, 163, 0.2);
            border-radius: 12px;
            padding: 2rem;
            max-width: 500px;
            width: 90%;
            max-height: 90vh;
            overflow-y: auto;
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid rgba(0, 217, 163, 0.2);
        }

        .modal-header h2 {
            margin: 0;
            font-size: 1.5rem;
        }

        .close-btn {
            background: none;
            border: none;
            color: var(--text);
            font-size: 1.5rem;
            cursor: pointer;
        }

        .close-btn:hover {
            color: var(--accent);
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
            .gallery {
                grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            }
        }

        @media (max-width: 480px) {
            .container {
                padding: 1rem;
            }

            .gallery {
                grid-template-columns: repeat(2, 1fr);
            }

            .modal-content {
                width: 95%;
            }
        }
    </style>
    <link rel="stylesheet" href="/admin/assets/admin.css?v=20260516">
</head>
<body>
    <?php include __DIR__ . '/../partials/sidebar.php'; ?>
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-image"></i> Gerenciador de Imagens</h1>
        </div>

        <?php if (!empty($message)): ?>
            <div class="alert alert-<?= $messageType ?>">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <!-- Upload Section -->
        <div class="upload-section" id="uploadSection" ondrop="handleDrop(event)" ondragover="handleDragOver(event)" ondragleave="handleDragLeave(event)">
            <input type="file" id="fileInput" accept="image/*" onchange="handleFileSelect(event)">
            <div class="upload-icon">
                <i class="fas fa-cloud-upload-alt"></i>
            </div>
            <h3>Clique ou arraste imagens aqui</h3>
            <p>JPG, PNG, GIF ou WEBP (máximo 5MB)</p>
        </div>

        <!-- Upload Form -->
        <form method="POST" enctype="multipart/form-data" id="uploadForm" style="display: none;">
            <input type="hidden" id="fileInputHidden" name="image" required>
            
            <div style="background: var(--secondary); border: 1px solid rgba(0, 217, 163, 0.1); border-radius: 12px; padding: 1.5rem; margin-bottom: 2rem;">
                <div class="form-group">
                    <label>Visualização</label>
                    <img id="preview" style="max-width: 100%; max-height: 300px; border-radius: 6px; margin-bottom: 1rem;">
                </div>

                <div class="form-group">
                    <label for="title">Título *</label>
                    <input type="text" id="title" name="title" placeholder="Nome descritivo da imagem" required>
                </div>

                <div class="form-group">
                    <label for="alt_text">Texto Alternativo (Alt Text)</label>
                    <input type="text" id="alt_text" name="alt_text" placeholder="Descrição para leitores de tela">
                </div>

                <div class="form-group">
                    <label for="description">Descrição</label>
                    <textarea id="description" name="description" placeholder="Descrição detalhada da imagem"></textarea>
                </div>

                <div style="display: flex; gap: 1rem;">
                    <button type="submit" class="btn-upload">
                        <i class="fas fa-upload"></i> Upload
                    </button>
                    <button type="button" onclick="cancelUpload()" style="background: var(--secondary); border: 1px solid var(--accent); color: var(--text); border-radius: 6px; padding: 0.7rem 1.5rem; font-weight: 600; cursor: pointer; width: 100%;">
                        <i class="fas fa-times"></i> Cancelar
                    </button>
                </div>
            </div>
        </form>

        <!-- Gallery -->
        <div>
            <h2 style="margin-bottom: 1.5rem;">Imagens Enviadas</h2>
            
            <?php if (!empty($images)): ?>
                <div class="gallery">
                    <?php foreach ($images as $image): ?>
                        <div class="gallery-item">
                            <img src="<?= htmlspecialchars($image['filepath']) ?>" alt="<?= htmlspecialchars($image['alt_text']) ?>" loading="lazy">
                            <div class="gallery-item-content">
                                <div class="gallery-item-title" title="<?= htmlspecialchars($image['title']) ?>">
                                    <?= htmlspecialchars($image['title']) ?>
                                </div>
                                <div class="gallery-item-info">
                                    <?= round($image['file_size'] / 1024) ?> KB
                                </div>
                                <div class="gallery-item-actions">
                                    <button class="btn-small btn-edit" onclick="editImage(<?= $image['id'] ?>, '<?= htmlspecialchars(json_encode($image)) ?>')">
                                        <i class="fas fa-edit"></i> Editar
                                    </button>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="action" value="copy_public">
                                        <input type="hidden" name="image_id" value="<?= $image['id'] ?>">
                                        <button type="submit" class="btn-small btn-copy" title="Copiar para pasta pública">
                                            <i class="fas fa-copy"></i> Copiar
                                        </button>
                                    </form>
                                    <form method="POST" style="display: inline;" onsubmit="return confirm('Tem certeza?');">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="image_id" value="<?= $image['id'] ?>">
                                        <button type="submit" class="btn-small btn-delete">
                                            <i class="fas fa-trash"></i> Deletar
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div style="text-align: center; padding: 3rem; background: var(--secondary); border-radius: 12px;">
                    <p style="color: var(--text-muted);">Nenhuma imagem enviada ainda</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Edit Modal -->
    <div class="modal" id="editModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Editar Metadados</h2>
                <button class="close-btn" onclick="closeModal()">&times;</button>
            </div>
            
            <form method="POST">
                <input type="hidden" name="action" value="update_meta">
                <input type="hidden" name="image_id" id="editImageId">

                <div class="form-group">
                    <label for="editTitle">Título</label>
                    <input type="text" id="editTitle" name="title" required>
                </div>

                <div class="form-group">
                    <label for="editAltText">Texto Alternativo</label>
                    <input type="text" id="editAltText" name="alt_text">
                </div>

                <div class="form-group">
                    <label for="editDescription">Descrição</label>
                    <textarea id="editDescription" name="description"></textarea>
                </div>

                <button type="submit" class="btn-upload">
                    <i class="fas fa-save"></i> Salvar Alterações
                </button>
            </form>
        </div>
    </div>

    <script>
        const uploadSection = document.getElementById('uploadSection');
        const fileInput = document.getElementById('fileInput');
        const uploadForm = document.getElementById('uploadForm');
        const fileInputHidden = document.getElementById('fileInputHidden');
        const preview = document.getElementById('preview');

        uploadSection.addEventListener('click', () => fileInput.click());

        function handleDragOver(e) {
            e.preventDefault();
            uploadSection.classList.add('dragover');
        }

        function handleDragLeave() {
            uploadSection.classList.remove('dragover');
        }

        function handleDrop(e) {
            e.preventDefault();
            uploadSection.classList.remove('dragover');
            const files = e.dataTransfer.files;
            if (files.length > 0) {
                handleFileSelect({ target: { files: files } });
            }
        }

        function handleFileSelect(e) {
            const file = e.target.files[0];
            if (!file) return;

            // Validar tipo
            if (!file.type.startsWith('image/')) {
                alert('Por favor, selecione uma imagem válida');
                return;
            }

            // Validar tamanho
            if (file.size > 5 * 1024 * 1024) {
                alert('A imagem deve ter no máximo 5MB');
                return;
            }

            // Mostrar preview e formulário
            const reader = new FileReader();
            reader.onload = (e) => {
                preview.src = e.target.result;
                fileInputHidden.files = new DataTransfer().items.add(file).files;
                uploadForm.style.display = 'block';
                uploadSection.style.display = 'none';
                document.getElementById('title').focus();
            };
            reader.readAsDataURL(file);
        }

        function cancelUpload() {
            uploadForm.style.display = 'none';
            uploadSection.style.display = 'block';
            fileInput.value = '';
            preview.src = '';
        }

        function editImage(id, data) {
            const image = JSON.parse(data.replace(/&quot;/g, '"'));
            document.getElementById('editImageId').value = id;
            document.getElementById('editTitle').value = image.title;
            document.getElementById('editAltText').value = image.alt_text || '';
            document.getElementById('editDescription').value = image.description || '';
            document.getElementById('editModal').classList.add('active');
        }

        function closeModal() {
            document.getElementById('editModal').classList.remove('active');
        }

        window.onclick = (e) => {
            const modal = document.getElementById('editModal');
            if (e.target === modal) {
                closeModal();
            }
        };
    </script>
    <?php include __DIR__ . '/../partials/sidebar-close.php'; ?>
</body>
</html>
