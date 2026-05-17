<?php
/**
 * Gerenciador de Usuários - CMS ChiapettaDev
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

// Processar ações
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'create') {
            $result = Auth::createUser([
                'username' => $_POST['username'] ?? '',
                'email' => $_POST['email'] ?? '',
                'password' => $_POST['password'] ?? '',
                'full_name' => $_POST['full_name'] ?? '',
                'role' => $_POST['role'] ?? 'author'
            ]);

            if ($result['success']) {
                $message = $result['message'];
                $messageType = 'success';
            } else {
                $message = $result['message'] ?? (is_array($result['messages'] ?? null) ? implode(', ', $result['messages']) : '');
                $messageType = 'danger';
            }
        }
    }
}

// Obter lista de usuários
$users = Database::getInstance()->select('users', "1=1 ORDER BY created_at DESC");
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Usuários | CMS ChiapettaDev</title>
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

        .content {
            display: grid;
            grid-template-columns: 1fr 2fr;
            gap: 2rem;
        }

        .form-section {
            background: var(--secondary);
            border: 1px solid rgba(0, 217, 163, 0.1);
            border-radius: 12px;
            padding: 1.5rem;
        }

        .form-section h2 {
            font-size: 1.3rem;
            margin-bottom: 1.5rem;
        }

        .form-group {
            margin-bottom: 1rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.4rem;
            font-weight: 600;
            color: var(--text);
            font-size: 0.9rem;
        }

        .form-group input,
        .form-group select {
            width: 100%;
            padding: 0.6rem;
            background: var(--primary);
            border: 1px solid rgba(0, 217, 163, 0.2);
            border-radius: 6px;
            color: var(--text);
            font-size: 0.9rem;
        }

        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: var(--accent);
            box-shadow: 0 0 0 2px rgba(0, 217, 163, 0.1);
        }

        .btn-submit {
            width: 100%;
            background: var(--accent);
            color: #000;
            border: none;
            border-radius: 6px;
            padding: 0.7rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn-submit:hover {
            background: #00b885;
        }

        .users-section {
            background: var(--secondary);
            border: 1px solid rgba(0, 217, 163, 0.1);
            border-radius: 12px;
            overflow: hidden;
        }

        .users-section h2 {
            padding: 1.5rem;
            margin: 0;
            font-size: 1.3rem;
            border-bottom: 1px solid rgba(0, 217, 163, 0.1);
        }

        .table {
            margin: 0;
            background: transparent;
        }

        .table thead {
            border-bottom: 1px solid rgba(0, 217, 163, 0.2);
        }

        .table th {
            color: var(--accent);
            font-weight: 600;
            padding: 1rem 1.5rem;
            background: transparent;
            border: none;
        }

        .table td {
            padding: 1rem 1.5rem;
            border-bottom: 1px solid rgba(0, 217, 163, 0.1);
        }

        .table tbody tr:hover {
            background: rgba(0, 217, 163, 0.05);
        }

        .badge {
            padding: 0.3rem 0.6rem;
            border-radius: 4px;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .badge-admin {
            background: rgba(255, 193, 7, 0.2);
            color: #ffc107;
        }

        .badge-editor {
            background: rgba(0, 217, 163, 0.2);
            color: var(--accent);
        }

        .badge-author {
            background: rgba(13, 202, 240, 0.2);
            color: #0dcaf0;
        }

        .badge-reader {
            background: rgba(176, 176, 176, 0.18);
            color: var(--text-muted);
        }

        .badge-active {
            background: rgba(0, 217, 163, 0.2);
            color: var(--accent);
        }

        .badge-inactive {
            background: rgba(220, 53, 69, 0.2);
            color: #ff6b6b;
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
            .content {
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
            <h1><i class="fas fa-users"></i> Gerenciador de Usuários</h1>
        </div>

        <?php if (!empty($message)): ?>
            <div class="alert alert-<?= $messageType ?>">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <div class="content">
            <div class="form-section">
                <h2><i class="fas fa-user-plus"></i> Novo Usuário</h2>
                <form method="POST">
                    <input type="hidden" name="action" value="create">

                    <div class="form-group">
                        <label for="username">Nome de Usuário</label>
                        <input type="text" id="username" name="username" required>
                    </div>

                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" required>
                    </div>

                    <div class="form-group">
                        <label for="full_name">Nome Completo</label>
                        <input type="text" id="full_name" name="full_name" required>
                    </div>

                    <div class="form-group">
                        <label for="password">Senha</label>
                        <input type="password" id="password" name="password" required>
                    </div>

                    <div class="form-group">
                        <label for="role">Papel (Role)</label>
                        <select id="role" name="role">
                            <option value="reader">Leitor (comentar posts)</option>
                            <option value="author">Autor (criar posts)</option>
                            <option value="editor">Editor (gerenciar posts)</option>
                            <option value="admin">Admin (acesso total)</option>
                        </select>
                    </div>

                    <button type="submit" class="btn-submit">
                        <i class="fas fa-plus"></i> Criar Usuário
                    </button>
                </form>
            </div>

            <div class="users-section">
                <h2><i class="fas fa-list"></i> Usuários Cadastrados</h2>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Nome</th>
                                <th>Email</th>
                                <th>Papel</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $u): ?>
                                <tr>
                                    <td><strong><?= htmlspecialchars($u['full_name']) ?></strong></td>
                                    <td><?= htmlspecialchars($u['email']) ?></td>
                                    <td><span class="badge badge-<?= $u['role'] ?>"><?= ucfirst($u['role']) ?></span></td>
                                    <td><span class="badge badge-<?= $u['status'] ?>"><?= ucfirst($u['status']) ?></span></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <?php include __DIR__ . '/../partials/sidebar-close.php'; ?>
</body>
</html>
