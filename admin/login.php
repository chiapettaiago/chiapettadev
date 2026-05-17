<?php
/**
 * Login - CMS ChiapettaDev
 */

require_once __DIR__ . '/../db/config.php';
require_once __DIR__ . '/modules/Auth.php';
require_once __DIR__ . '/modules/Captcha.php';

// Se já está autenticado como equipe do CMS, redirecionar para dashboard
if (Auth::hasPermission('author')) {
    header('Location: /admin/dashboard.php');
    exit;
} elseif (Auth::isAuthenticated()) {
    Auth::logout();
    header('Location: /admin/login.php?restricted=1');
    exit;
}

$error = '';
$success = '';

if (isset($_GET['restricted'])) {
    $error = 'Sua conta de leitor não possui acesso ao CMS';
}

// Processar login
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $error = 'Usuário e senha são obrigatórios';
    } elseif (!Captcha::verify('admin_login', $_POST['captcha_answer'] ?? '')) {
        $error = 'Confirme o captcha corretamente';
    } else {
        $result = Auth::login($username, $password);
        if ($result['success'] && Auth::hasPermission('author')) {
            header('Location: /admin/dashboard.php');
            exit;
        } elseif ($result['success']) {
            Auth::logout();
            $error = 'Sua conta é de leitor e não possui acesso ao CMS';
        } else {
            $error = $result['message'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Login | ChiapettaDev</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
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
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            color: var(--text);
        }

        .login-container {
            background: var(--secondary);
            border: 1px solid rgba(0, 217, 163, 0.1);
            border-radius: 12px;
            padding: 2.5rem;
            width: 100%;
            max-width: 400px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.4);
        }

        .login-header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .login-header h1 {
            font-size: 1.8rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            background: linear-gradient(90deg, var(--text), var(--accent));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .login-header p {
            color: var(--text-muted);
            font-size: 0.9rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: var(--text);
            font-weight: 500;
            font-size: 0.95rem;
        }

        .form-group input {
            width: 100%;
            padding: 0.75rem 1rem;
            background: var(--primary);
            border: 1px solid rgba(0, 217, 163, 0.2);
            border-radius: 6px;
            color: var(--text);
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        .form-group input:focus {
            outline: none;
            border-color: var(--accent);
            box-shadow: 0 0 0 3px rgba(0, 217, 163, 0.1);
            background: rgba(0, 217, 163, 0.05);
        }

        .form-group input::placeholder {
            color: var(--text-muted);
        }

        .captcha-box {
            padding: 0.85rem;
            background: rgba(0, 217, 163, 0.06);
            border: 1px solid rgba(0, 217, 163, 0.18);
            border-radius: 6px;
            margin-bottom: 1.5rem;
        }

        .btn-login {
            width: 100%;
            padding: 0.85rem;
            background: linear-gradient(90deg, var(--accent), #00b885);
            border: none;
            border-radius: 6px;
            color: #000;
            font-weight: 600;
            font-size: 1rem;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-bottom: 1rem;
        }

        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(0, 217, 163, 0.3);
        }

        .btn-login:active {
            transform: translateY(0);
        }

        .alert {
            border-radius: 6px;
            border: 1px solid;
            padding: 0.85rem 1rem;
            margin-bottom: 1.5rem;
            font-size: 0.9rem;
        }

        .alert-danger {
            background: rgba(220, 53, 69, 0.1);
            border-color: rgba(220, 53, 69, 0.3);
            color: #ff6b6b;
        }

        .alert-success {
            background: rgba(0, 217, 163, 0.1);
            border-color: rgba(0, 217, 163, 0.3);
            color: var(--accent);
        }

        .login-footer {
            text-align: center;
            color: var(--text-muted);
            font-size: 0.85rem;
            padding-top: 1.5rem;
            border-top: 1px solid rgba(0, 217, 163, 0.1);
        }

        .login-footer a {
            color: var(--accent);
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .login-footer a:hover {
            text-decoration: underline;
        }

        @media (max-width: 480px) {
            .login-container {
                padding: 1.5rem;
            }

            .login-header h1 {
                font-size: 1.5rem;
            }
        }
    </style>
    <link rel="stylesheet" href="/admin/assets/admin.css?v=20260516">
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <h1>ChiapettaDev</h1>
            <p>Painel Administrativo</p>
        </div>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger">
                <strong>Erro:</strong> <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <input type="hidden" name="login" value="1">

            <div class="form-group">
                <label for="username">Usuário</label>
                <input 
                    type="text" 
                    id="username" 
                    name="username" 
                    placeholder="Digite seu usuário"
                    required
                    autofocus
                >
            </div>

            <div class="form-group">
                <label for="password">Senha</label>
                <input 
                    type="password" 
                    id="password" 
                    name="password" 
                    placeholder="Digite sua senha"
                    required
                >
            </div>

            <div class="captcha-box">
                <div class="form-group mb-0">
                    <label for="captcha_answer">Quanto é <?= htmlspecialchars(Captcha::getQuestion('admin_login')) ?>?</label>
                    <input
                        type="number"
                        id="captcha_answer"
                        name="captcha_answer"
                        placeholder="Digite o resultado"
                        required
                        inputmode="numeric"
                        autocomplete="off"
                    >
                </div>
            </div>

            <button type="submit" class="btn-login">Entrar</button>
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
