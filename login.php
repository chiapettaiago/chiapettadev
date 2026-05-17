<?php
require_once __DIR__ . '/admin/modules/Auth.php';
require_once __DIR__ . '/admin/modules/Captcha.php';

$redirect = $_GET['redirect'] ?? ($_POST['redirect'] ?? '/');
if (!is_string($redirect) || !str_starts_with($redirect, '/') || str_starts_with($redirect, '//')) {
    $redirect = '/';
}

if (Auth::isAuthenticated() && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . $redirect);
    exit;
}

$error = '';
$activeTab = $_POST['action'] ?? 'login';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (($_POST['action'] ?? '') === 'register') {
        if (!Captcha::verify('reader_register', $_POST['captcha_answer'] ?? '')) {
            $result = ['success' => false, 'message' => 'Confirme o captcha corretamente'];
        } else {
            $result = Auth::registerReader($_POST);
        }

        if ($result['success']) {
            header('Location: ' . $redirect);
            exit;
        }

        $error = $result['message'] ?? 'Não foi possível criar sua conta';
        $activeTab = 'register';
    } else {
        if (!Captcha::verify('reader_login', $_POST['captcha_answer'] ?? '')) {
            $result = ['success' => false, 'message' => 'Confirme o captcha corretamente'];
        } else {
            $result = Auth::login($_POST['username'] ?? '', $_POST['password'] ?? '');
        }

        if ($result['success']) {
            header('Location: ' . $redirect);
            exit;
        }

        $error = $result['message'];
        $activeTab = 'login';
    }
}
?>
<?php include __DIR__ . '/templates/header.php'; ?>

<main id="primary" class="py-5">
    <section style="border-top: none;">
        <div class="container">
            <div class="reader-auth">
                <div class="reader-auth-header">
                    <h1>Área do Leitor</h1>
                    <p>Entre ou crie sua conta para comentar nos posts.</p>
                </div>

                <?php if ($error): ?>
                    <div class="reader-alert"><?= htmlspecialchars(strip_tags($error)) ?></div>
                <?php endif; ?>

                <div class="reader-tabs" role="tablist">
                    <button type="button" class="reader-tab <?= $activeTab !== 'register' ? 'active' : '' ?>" data-target="login-panel">Entrar</button>
                    <button type="button" class="reader-tab <?= $activeTab === 'register' ? 'active' : '' ?>" data-target="register-panel">Cadastrar</button>
                </div>

                <form id="login-panel" class="reader-form <?= $activeTab === 'register' ? 'is-hidden' : '' ?>" method="POST">
                    <input type="hidden" name="action" value="login">
                    <input type="hidden" name="redirect" value="<?= htmlspecialchars($redirect) ?>">

                    <label for="login-username">Usuário ou email</label>
                    <input id="login-username" type="text" name="username" required autocomplete="username">

                    <label for="login-password">Senha</label>
                    <input id="login-password" type="password" name="password" required autocomplete="current-password">

                    <div class="captcha-field">
                        <label for="login-captcha">Quanto é <?= htmlspecialchars(Captcha::getQuestion('reader_login')) ?>?</label>
                        <input id="login-captcha" type="number" name="captcha_answer" required inputmode="numeric" autocomplete="off">
                    </div>

                    <button type="submit">Entrar</button>
                </form>

                <form id="register-panel" class="reader-form <?= $activeTab === 'register' ? '' : 'is-hidden' ?>" method="POST">
                    <input type="hidden" name="action" value="register">
                    <input type="hidden" name="redirect" value="<?= htmlspecialchars($redirect) ?>">

                    <label for="full_name">Nome</label>
                    <input id="full_name" type="text" name="full_name" required autocomplete="name">

                    <label for="email">Email</label>
                    <input id="email" type="email" name="email" required autocomplete="email">

                    <label for="username">Usuário</label>
                    <input id="username" type="text" name="username" required autocomplete="username">

                    <label for="password">Senha</label>
                    <input id="password" type="password" name="password" required autocomplete="new-password" minlength="6">

                    <label for="password_confirm">Confirmar senha</label>
                    <input id="password_confirm" type="password" name="password_confirm" required autocomplete="new-password" minlength="6">

                    <div class="captcha-field">
                        <label for="register-captcha">Quanto é <?= htmlspecialchars(Captcha::getQuestion('reader_register')) ?>?</label>
                        <input id="register-captcha" type="number" name="captcha_answer" required inputmode="numeric" autocomplete="off">
                    </div>

                    <button type="submit">Criar conta</button>
                </form>
            </div>
        </div>
    </section>
</main>

<style>
    .reader-auth {
        max-width: 460px;
        margin: 0 auto;
        padding: 2rem;
        border-radius: 8px;
        border: 1px solid rgba(0, 217, 163, 0.16);
        background: rgba(0, 217, 163, 0.045);
    }

    .reader-auth-header {
        text-align: center;
        margin-bottom: 1.5rem;
    }

    .reader-auth-header h1 {
        font-size: 2rem;
        margin-bottom: 0.5rem;
    }

    .reader-auth-header p {
        color: var(--text-muted);
        margin: 0;
    }

    .reader-tabs {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 0.5rem;
        margin-bottom: 1.5rem;
    }

    .reader-tab {
        border: 1px solid rgba(0, 217, 163, 0.2);
        border-radius: 6px;
        background: var(--primary);
        color: var(--text);
        padding: 0.75rem;
        font-weight: 700;
    }

    .reader-tab.active {
        background: var(--accent);
        color: #111;
    }

    .reader-form {
        display: grid;
        gap: 0.75rem;
    }

    .reader-form.is-hidden {
        display: none;
    }

    .reader-form label {
        color: var(--text);
        font-weight: 600;
    }

    .reader-form input {
        width: 100%;
        padding: 0.8rem;
        border-radius: 6px;
        border: 1px solid rgba(0, 217, 163, 0.2);
        background: var(--primary);
        color: var(--text);
    }

    .reader-form input:focus {
        outline: none;
        border-color: var(--accent);
        box-shadow: 0 0 0 3px rgba(0, 217, 163, 0.1);
    }

    .captcha-field {
        padding: 0.85rem;
        border: 1px solid rgba(0, 217, 163, 0.18);
        border-radius: 6px;
        background: rgba(0, 217, 163, 0.06);
    }

    .reader-form button {
        border: 0;
        border-radius: 6px;
        background: var(--accent);
        color: #111;
        padding: 0.85rem;
        font-weight: 800;
        margin-top: 0.5rem;
    }

    .reader-alert {
        border: 1px solid rgba(220, 53, 69, 0.28);
        background: rgba(220, 53, 69, 0.12);
        color: #ff9ca5;
        border-radius: 6px;
        padding: 0.8rem 1rem;
        margin-bottom: 1rem;
    }
</style>

<script>
    document.querySelectorAll('.reader-tab').forEach((tab) => {
        tab.addEventListener('click', () => {
            document.querySelectorAll('.reader-tab').forEach((item) => item.classList.remove('active'));
            document.querySelectorAll('.reader-form').forEach((form) => form.classList.add('is-hidden'));
            tab.classList.add('active');
            document.getElementById(tab.dataset.target).classList.remove('is-hidden');
        });
    });
</script>

<?php include __DIR__ . '/templates/footer.php'; ?>
