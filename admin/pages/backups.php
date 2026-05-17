<?php
/**
 * Backups do site com envio opcional ao Google Drive.
 */

require_once __DIR__ . '/../../db/config.php';
require_once __DIR__ . '/../modules/Auth.php';
require_once __DIR__ . '/../modules/Backup.php';

if (!Auth::hasPermission('admin')) {
    header('Location: /admin/login.php');
    exit;
}

$user = Auth::getCurrentUser();
$message = '';
$messageType = '';

if (($_GET['action'] ?? '') === 'download') {
    $backup = Backup::getById($_GET['id'] ?? 0);
    $path = $backup['filepath'] ?? '';

    if (!$backup || !is_file($path) || strpos(realpath($path), realpath(__DIR__ . '/../../backups')) !== 0) {
        http_response_code(404);
        echo 'Backup não encontrado';
        exit;
    }

    header('Content-Type: application/zip');
    header('Content-Disposition: attachment; filename="' . basename($backup['filename']) . '"');
    header('Content-Length: ' . filesize($path));
    header('X-Content-Type-Options: nosniff');
    readfile($path);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'save_settings') {
        $result = Backup::saveSettings($_POST['google_drive_folder_id'] ?? '', $_POST['google_drive_service_account'] ?? '');
        $message = $result['message'];
        $messageType = $result['success'] ? 'success' : 'danger';
    }

    if ($action === 'create_backup') {
        $result = Backup::create($user['id'], !empty($_POST['upload_to_drive']));
        $message = $result['message'];
        $messageType = $result['success'] ? 'success' : 'danger';
    }

    if ($action === 'restore_uploaded') {
        if (($_POST['confirm_restore'] ?? '') !== 'RESTAURAR') {
            $message = 'Digite RESTAURAR para confirmar a restauração';
            $messageType = 'danger';
        } else {
            $result = Backup::restoreFromUploadedFile($_FILES['backup_file'] ?? [], $user['id']);
            $message = $result['message'];
            $messageType = $result['success'] ? 'success' : 'danger';
        }
    }

    if ($action === 'restore_existing') {
        if (($_POST['confirmed_restore'] ?? '') !== '1') {
            $message = 'Confirmação de restauração ausente';
            $messageType = 'danger';
        } else {
            $result = Backup::restoreFromBackupId($_POST['backup_id'] ?? 0, $user['id']);
            $message = $result['message'];
            $messageType = $result['success'] ? 'success' : 'danger';
        }
    }
}

$settings = Backup::getSettings();
$credentials = json_decode($settings['google_drive_service_account'] ?? '', true);
$serviceAccountEmail = is_array($credentials) ? ($credentials['client_email'] ?? '') : '';
$backups = Backup::getList();

function backup_format_size($bytes) {
    $bytes = (int) $bytes;
    if ($bytes >= 1073741824) {
        return number_format($bytes / 1073741824, 2, ',', '.') . ' GB';
    }

    if ($bytes >= 1048576) {
        return number_format($bytes / 1048576, 2, ',', '.') . ' MB';
    }

    if ($bytes >= 1024) {
        return number_format($bytes / 1024, 2, ',', '.') . ' KB';
    }

    return $bytes . ' B';
}

function backup_status_label($status) {
    $labels = [
        'local' => 'Local',
        'drive_uploaded' => 'Google Drive',
        'drive_failed' => 'Falha no Drive'
    ];

    return $labels[$status] ?? $status;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Backups | CMS ChiapettaDev</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="icon" href="/images/favicon-apple.jpg" type="image/jpeg">
    <link rel="stylesheet" href="/admin/assets/admin.css?v=20260516">
    <style>
        :root {
            --primary: #1a1a1a;
            --secondary: #2d2d2d;
            --accent: #00d9a3;
            --text: #ffffff;
            --text-muted: #b0b0b0;
            --border: rgba(0, 217, 163, 0.16);
        }

        body {
            background: var(--primary);
            color: var(--text);
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
        }

        .backup-container {
            max-width: 1120px;
            margin: 0 auto;
            padding: 2rem;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 1rem;
            margin-bottom: 2rem;
            padding-bottom: 1.5rem;
            border-bottom: 2px solid rgba(0, 217, 163, 0.2);
        }

        .header h1 {
            font-size: 2rem;
            margin: 0 0 0.35rem;
        }

        .header p,
        .muted {
            color: var(--text-muted);
            margin: 0;
        }

        .section {
            background: var(--secondary);
            border: 1px solid var(--border);
            border-radius: 8px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
        }

        .section h2 {
            font-size: 1.2rem;
            margin: 0 0 1.2rem;
            display: flex;
            align-items: center;
            gap: 0.6rem;
        }

        .form-grid {
            display: grid;
            grid-template-columns: minmax(0, 1fr) minmax(280px, 0.55fr);
            gap: 1.5rem;
            align-items: start;
        }

        .form-group {
            margin-bottom: 1.2rem;
        }

        label {
            display: block;
            margin-bottom: 0.45rem;
            font-weight: 650;
        }

        input[type="text"],
        input[type="file"],
        textarea {
            width: 100%;
            padding: 0.75rem;
            background: var(--primary);
            border: 1px solid rgba(0, 217, 163, 0.22);
            border-radius: 6px;
            color: var(--text);
            font: inherit;
        }

        textarea {
            min-height: 180px;
            resize: vertical;
            font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
            font-size: 0.9rem;
        }

        input:focus,
        textarea:focus {
            outline: none;
            border-color: var(--accent);
            box-shadow: 0 0 0 3px rgba(0, 217, 163, 0.1);
        }

        small {
            display: block;
            margin-top: 0.35rem;
            color: var(--text-muted);
        }

        .btn-action,
        .btn-secondary-action,
        .btn-danger-action,
        .download-link {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            min-height: 42px;
            padding: 0.7rem 1.05rem;
            border-radius: 6px;
            border: 1px solid transparent;
            font-weight: 700;
            text-decoration: none;
            cursor: pointer;
        }

        .btn-action {
            background: var(--accent);
            color: #04100d;
        }

        .btn-secondary-action,
        .download-link {
            background: rgba(0, 217, 163, 0.08);
            color: var(--accent);
            border-color: rgba(0, 217, 163, 0.22);
        }

        .btn-danger-action {
            background: rgba(220, 53, 69, 0.14);
            color: #ff8a8a;
            border-color: rgba(220, 53, 69, 0.32);
        }

        .backup-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 0.9rem;
            align-items: center;
        }

        .check-row {
            display: inline-flex;
            align-items: center;
            gap: 0.55rem;
            color: var(--text-muted);
            min-height: 42px;
            margin: 0;
        }

        .hint-box {
            background: rgba(0, 217, 163, 0.07);
            border: 1px solid rgba(0, 217, 163, 0.18);
            border-radius: 8px;
            padding: 1rem;
        }

        .hint-box p {
            margin: 0 0 0.75rem;
            color: var(--text-muted);
        }

        .hint-box p:last-child {
            margin-bottom: 0;
        }

        .danger-box {
            background: rgba(220, 53, 69, 0.1);
            border-color: rgba(220, 53, 69, 0.24);
        }

        .danger-box strong {
            color: #ff8a8a;
        }

        .email-chip {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            max-width: 100%;
            padding: 0.4rem 0.55rem;
            border-radius: 6px;
            background: rgba(0, 0, 0, 0.22);
            color: var(--accent);
            font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
            font-size: 0.88rem;
            overflow-wrap: anywhere;
        }

        .alert {
            border-radius: 6px;
            border: 1px solid;
            padding: 1rem;
            margin-bottom: 1.5rem;
        }

        .alert-success {
            background: rgba(0, 217, 163, 0.1);
            border-color: rgba(0, 217, 163, 0.3);
            color: var(--accent);
        }

        .alert-danger {
            background: rgba(220, 53, 69, 0.1);
            border-color: rgba(220, 53, 69, 0.3);
            color: #ff7b7b;
        }

        .table-wrap {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th,
        td {
            padding: 0.85rem 0.7rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
            vertical-align: middle;
        }

        th {
            color: var(--text-muted);
            font-size: 0.82rem;
            font-weight: 700;
            text-transform: uppercase;
        }

        .status {
            display: inline-flex;
            align-items: center;
            padding: 0.28rem 0.55rem;
            border-radius: 999px;
            font-size: 0.82rem;
            font-weight: 700;
            background: rgba(255, 255, 255, 0.08);
            color: var(--text-muted);
            white-space: nowrap;
        }

        .status.drive_uploaded {
            background: rgba(0, 217, 163, 0.13);
            color: var(--accent);
        }

        .status.drive_failed {
            background: rgba(220, 53, 69, 0.14);
            color: #ff7b7b;
        }

        .filename {
            font-weight: 700;
            word-break: break-word;
        }

        .row-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 0.45rem;
            align-items: center;
        }

        @media (max-width: 860px) {
            .backup-container {
                padding: 1rem;
            }

            .header,
            .form-grid {
                display: block;
            }

            .hint-box {
                margin-top: 1rem;
            }
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../partials/sidebar.php'; ?>
    <div class="backup-container">
        <div class="header">
            <div>
                <h1><i class="fas fa-cloud-arrow-up"></i> Backups</h1>
                <p>Gere cópias do banco, imagens, posts, páginas, slides e arquivos principais do site.</p>
            </div>
        </div>

        <?php if (!empty($message)): ?>
            <div class="alert alert-<?= htmlspecialchars($messageType) ?>">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <div class="section">
            <h2><i class="fas fa-shield-halved"></i> Gerar backup</h2>
            <form method="POST" class="backup-actions">
                <input type="hidden" name="action" value="create_backup">
                <button type="submit" class="btn-action">
                    <i class="fas fa-box-archive"></i> Criar backup agora
                </button>
                <label class="check-row">
                    <input type="checkbox" name="upload_to_drive" value="1">
                    Enviar ao Google Drive após gerar
                </label>
            </form>
        </div>

        <div class="section">
            <h2><i class="fab fa-google-drive"></i> Google Drive</h2>
            <div class="form-grid">
                <form method="POST">
                    <input type="hidden" name="action" value="save_settings">
                    <div class="form-group">
                        <label for="google_drive_folder_id">ID da pasta no Drive</label>
                        <input
                            type="text"
                            id="google_drive_folder_id"
                            name="google_drive_folder_id"
                            value="<?= htmlspecialchars($settings['google_drive_folder_id'] ?? '') ?>"
                            placeholder="Ex.: 1AbCDeFg..."
                        >
                        <small>Opcional. Sem ID, o backup vai para o Drive da conta de serviço.</small>
                    </div>
                    <div class="form-group">
                        <label for="google_drive_service_account">JSON da Service Account</label>
                        <textarea id="google_drive_service_account" name="google_drive_service_account" placeholder='{"type":"service_account", ...}'></textarea>
                        <small><?= $serviceAccountEmail ? 'Já existe uma credencial salva. Deixe em branco para manter a atual.' : 'Cole aqui o JSON baixado no Google Cloud.' ?></small>
                    </div>
                    <button type="submit" class="btn-secondary-action">
                        <i class="fas fa-save"></i> Salvar integração
                    </button>
                </form>

                <div class="hint-box">
                    <p>Para enviar para uma pasta específica, compartilhe essa pasta no Google Drive com o e-mail da Service Account.</p>
                    <?php if ($serviceAccountEmail): ?>
                        <p class="email-chip"><i class="fas fa-envelope"></i> <?= htmlspecialchars($serviceAccountEmail) ?></p>
                    <?php else: ?>
                        <p>Depois de salvar o JSON, o e-mail da Service Account aparecerá aqui.</p>
                    <?php endif; ?>
                    <p class="muted">A integração usa upload direto pela API do Google Drive, sem dependências externas.</p>
                </div>
            </div>
        </div>

        <div class="section">
            <h2><i class="fas fa-rotate-left"></i> Restaurar backup</h2>
            <div class="form-grid">
                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="restore_uploaded">
                    <div class="form-group">
                        <label for="backup_file">Arquivo ZIP de backup</label>
                        <input type="file" id="backup_file" name="backup_file" accept=".zip,application/zip" required>
                        <small>Use um ZIP gerado por esta ferramenta, baixado do histórico ou salvo no Google Drive.</small>
                    </div>
                    <div class="form-group">
                        <label for="confirm_restore">Confirmação</label>
                        <input type="text" id="confirm_restore" name="confirm_restore" placeholder="Digite RESTAURAR" autocomplete="off" required>
                        <small>Restaurar sobrescreve banco, imagens, posts, páginas, slides e arquivos principais do site.</small>
                    </div>
                    <button type="submit" class="btn-danger-action">
                        <i class="fas fa-rotate-left"></i> Restaurar arquivo enviado
                    </button>
                </form>

                <div class="hint-box danger-box">
                    <p><strong>Antes da restauração</strong>, o sistema cria automaticamente um backup local do estado atual.</p>
                    <p>O arquivo é validado antes de extrair para evitar caminhos fora da estrutura esperada do CMS.</p>
                    <p class="muted">Após restaurar, faça login novamente se a sessão atual não existir no backup importado.</p>
                </div>
            </div>
        </div>

        <div class="section">
            <h2><i class="fas fa-clock-rotate-left"></i> Histórico</h2>
            <?php if (empty($backups)): ?>
                <p class="muted">Nenhum backup gerado ainda.</p>
            <?php else: ?>
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>Arquivo</th>
                                <th>Tamanho</th>
                                <th>Status</th>
                                <th>Data</th>
                                <th>Drive</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($backups as $backup): ?>
                                <tr>
                                    <td>
                                        <div class="filename"><?= htmlspecialchars($backup['filename']) ?></div>
                                        <?php if (!empty($backup['message'])): ?>
                                            <small><?= htmlspecialchars($backup['message']) ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= backup_format_size($backup['file_size'] ?? 0) ?></td>
                                    <td><span class="status <?= htmlspecialchars($backup['status']) ?>"><?= htmlspecialchars(backup_status_label($backup['status'])) ?></span></td>
                                    <td><?= date('d/m/Y H:i', strtotime($backup['created_at'])) ?></td>
                                    <td><?= !empty($backup['drive_file_id']) ? htmlspecialchars($backup['drive_file_id']) : '<span class="muted">-</span>' ?></td>
                                    <td>
                                        <?php if (is_file($backup['filepath'])): ?>
                                            <div class="row-actions">
                                                <a class="download-link" href="/admin/pages/backups.php?action=download&id=<?= intval($backup['id']) ?>">
                                                    <i class="fas fa-download"></i> Baixar
                                                </a>
                                                <form method="POST" onsubmit="return confirm('Restaurar este backup vai sobrescrever o conteúdo atual do site. Continuar?');">
                                                    <input type="hidden" name="action" value="restore_existing">
                                                    <input type="hidden" name="backup_id" value="<?= intval($backup['id']) ?>">
                                                    <input type="hidden" name="confirmed_restore" value="1">
                                                    <button type="submit" class="btn-danger-action">
                                                        <i class="fas fa-rotate-left"></i> Restaurar
                                                    </button>
                                                </form>
                                            </div>
                                        <?php else: ?>
                                            <span class="muted">Arquivo ausente</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <?php include __DIR__ . '/../partials/sidebar-close.php'; ?>
</body>
</html>
