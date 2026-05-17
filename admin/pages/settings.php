<?php
/**
 * Configurações - CMS ChiapettaDev
 */

require_once __DIR__ . '/../../db/config.php';
require_once __DIR__ . '/../modules/Auth.php';

// Verificar autenticação e permissão
if (!Auth::hasPermission('admin')) {
    header('Location: /admin/login.php');
    exit;
}

$user = Auth::getCurrentUser();
$message = '';
$messageType = '';

// Processar alterações de senha
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'change_password') {
        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        if ($newPassword !== $confirmPassword) {
            $message = 'As senhas não coincidem';
            $messageType = 'danger';
        } else {
            $result = Auth::changePassword($user['id'], $currentPassword, $newPassword);
            $message = $result['message'];
            $messageType = $result['success'] ? 'success' : 'danger';
        }
    } elseif ($_POST['action'] === 'save_settings') {
        // Salvar configurações
        $settings = [
            'site_title' => $_POST['site_title'] ?? '',
            'site_description' => $_POST['site_description'] ?? '',
            'site_url' => $_POST['site_url'] ?? '',
            'items_per_page' => $_POST['items_per_page'] ?? 10
        ];

        foreach ($settings as $key => $value) {
            $existing = Database::getInstance()->selectOne('settings', "key = '$key'");
            if ($existing) {
                Database::getInstance()->update('settings', 
                    ['value' => $value, 'updated_at' => date('Y-m-d H:i:s')],
                    "key = '$key'"
                );
            } else {
                Database::getInstance()->insert('settings', [
                    'key' => $key,
                    'value' => $value
                ]);
            }
        }

        $message = 'Configurações salvas com sucesso';
        $messageType = 'success';
    }
}

// Obter configurações
$settings = Database::getInstance()->select('settings', '1=1');
$settingsArray = [];
foreach ($settings as $s) {
    $settingsArray[$s['key']] = $s['value'];
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configurações | CMS ChiapettaDev</title>
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
            max-width: 900px;
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

        .section {
            background: var(--secondary);
            border: 1px solid rgba(0, 217, 163, 0.1);
            border-radius: 12px;
            padding: 2rem;
            margin-bottom: 2rem;
        }

        .section h2 {
            font-size: 1.3rem;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid rgba(0, 217, 163, 0.1);
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
            min-height: 100px;
            resize: vertical;
        }

        .form-group small {
            color: var(--text-muted);
            display: block;
            margin-top: 0.3rem;
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
            margin-left: 1rem;
        }

        .btn-cancel:hover {
            background: rgba(0, 217, 163, 0.1);
        }

        .button-group {
            display: flex;
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

        @media (max-width: 768px) {
            .button-group {
                flex-direction: column;
            }

            .btn-cancel {
                margin-left: 0;
                margin-top: 0.5rem;
            }
        }
    </style>
    <link rel="stylesheet" href="/admin/assets/admin.css?v=20260516">
</head>
<body>
    <?php include __DIR__ . '/../partials/sidebar.php'; ?>
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-cog"></i> Configurações</h1>
        </div>

        <?php if (!empty($message)): ?>
            <div class="alert alert-<?= $messageType ?>">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <!-- Site Settings -->
        <div class="section">
            <h2><i class="fas fa-globe"></i> Configurações do Site</h2>
            
            <form method="POST">
                <input type="hidden" name="action" value="save_settings">

                <div class="form-group">
                    <label for="site_title">Título do Site</label>
                    <input 
                        type="text" 
                        id="site_title" 
                        name="site_title"
                        value="<?= htmlspecialchars($settingsArray['site_title'] ?? 'ChiapettaDev') ?>"
                        placeholder="ChiapettaDev"
                    >
                    <small>O título que aparece no navegador e na página</small>
                </div>

                <div class="form-group">
                    <label for="site_description">Descrição do Site</label>
                    <textarea 
                        id="site_description" 
                        name="site_description"
                        placeholder="Descrição breve do seu site"
                    ><?= htmlspecialchars($settingsArray['site_description'] ?? '') ?></textarea>
                    <small>Usado em meta tags e SEO</small>
                </div>

                <div class="form-group">
                    <label for="site_url">URL do Site</label>
                    <input 
                        type="url" 
                        id="site_url" 
                        name="site_url"
                        value="<?= htmlspecialchars($settingsArray['site_url'] ?? 'https://chiapetta.dev') ?>"
                        placeholder="https://chiapetta.dev"
                    >
                    <small>URL completa do seu site</small>
                </div>

                <div class="form-group">
                    <label for="items_per_page">Itens por Página</label>
                    <input 
                        type="number" 
                        id="items_per_page" 
                        name="items_per_page"
                        value="<?= htmlspecialchars($settingsArray['items_per_page'] ?? '10') ?>"
                        placeholder="10"
                        min="1"
                    >
                    <small>Quantidade de posts/páginas por página</small>
                </div>

                <div class="button-group">
                    <button type="submit" class="btn-submit">
                        <i class="fas fa-save"></i> Salvar Configurações
                    </button>
                </div>
            </form>
        </div>

        <!-- Change Password -->
        <div class="section">
            <h2><i class="fas fa-lock"></i> Alterar Senha</h2>
            
            <form method="POST">
                <input type="hidden" name="action" value="change_password">

                <div class="form-group">
                    <label for="current_password">Senha Atual</label>
                    <input 
                        type="password" 
                        id="current_password" 
                        name="current_password"
                        required
                    >
                </div>

                <div class="form-group">
                    <label for="new_password">Nova Senha</label>
                    <input 
                        type="password" 
                        id="new_password" 
                        name="new_password"
                        required
                        minlength="6"
                    >
                    <small>Mínimo de 6 caracteres</small>
                </div>

                <div class="form-group">
                    <label for="confirm_password">Confirmar Senha</label>
                    <input 
                        type="password" 
                        id="confirm_password" 
                        name="confirm_password"
                        required
                        minlength="6"
                    >
                </div>

                <div class="button-group">
                    <button type="submit" class="btn-submit">
                        <i class="fas fa-lock"></i> Alterar Senha
                    </button>
                </div>
            </form>
        </div>

        <!-- System Info -->
        <div class="section">
            <h2><i class="fas fa-info-circle"></i> Informações do Sistema</h2>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                <div>
                    <p style="color: var(--text-muted); margin-bottom: 0.25rem;">Versão do PHP</p>
                    <p style="font-weight: 600;"><?= phpversion() ?></p>
                </div>
                <div>
                    <p style="color: var(--text-muted); margin-bottom: 0.25rem;">Diretório Temporário</p>
                    <p style="font-weight: 600;"><?= sys_get_temp_dir() ?></p>
                </div>
                <div>
                    <p style="color: var(--text-muted); margin-bottom: 0.25rem;">Banco de Dados</p>
                    <p style="font-weight: 600;">SQLite</p>
                </div>
                <div>
                    <p style="color: var(--text-muted); margin-bottom: 0.25rem;">Seu Usuário</p>
                    <p style="font-weight: 600;"><?= htmlspecialchars($user['username']) ?> (<?= $user['role'] ?>)</p>
                </div>
            </div>
        </div>
    </div>
    <?php include __DIR__ . '/../partials/sidebar-close.php'; ?>
</body>
</html>
