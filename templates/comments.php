<?php
require_once __DIR__ . '/../admin/modules/Auth.php';
require_once __DIR__ . '/../admin/modules/Comment.php';

$commentPostSlug = $commentPostSlug ?? basename(dirname($_SERVER['SCRIPT_NAME'] ?? 'post'));
$commentRedirect = $_SERVER['REQUEST_URI'] ?? ('/blog/' . $commentPostSlug . '/');
$commentFlash = $_SESSION['comment_flash'] ?? null;
unset($_SESSION['comment_flash']);
$commentMessage = $commentFlash['message'] ?? '';
$commentMessageType = $commentFlash['type'] ?? '';

$comments = Comment::getByPost($commentPostSlug);
$commentUser = Auth::getCurrentUser();
?>

<section id="comentarios" class="py-5">
    <div class="container">
        <div class="comments-panel">
            <div class="comments-header">
                <h2>Comentários</h2>
                <span><?= count($comments) ?></span>
            </div>

            <?php if ($commentMessage): ?>
                <div class="comment-alert comment-alert-<?= htmlspecialchars($commentMessageType) ?>">
                    <?= htmlspecialchars($commentMessage) ?>
                </div>
            <?php endif; ?>

            <?php if ($commentUser): ?>
                <form method="POST" class="comment-form">
                    <input type="hidden" name="action" value="comment">
                    <label for="comment">Comentar como <?= htmlspecialchars($commentUser['full_name']) ?></label>
                    <textarea id="comment" name="comment" rows="4" maxlength="2000" required placeholder="Escreva seu comentário..."></textarea>
                    <button type="submit">Publicar comentário</button>
                </form>
            <?php else: ?>
                <div class="comment-login-box">
                    <p>Entre ou crie uma conta de leitor para comentar.</p>
                    <a href="/login.php?redirect=<?= urlencode($commentRedirect) ?>">Entrar ou cadastrar</a>
                </div>
            <?php endif; ?>

            <div class="comment-list">
                <?php if (empty($comments)): ?>
                    <p class="comment-empty">Ainda não há comentários neste post.</p>
                <?php else: ?>
                    <?php foreach ($comments as $comment): ?>
                        <article class="comment-item">
                            <div class="comment-meta">
                                <strong><?= htmlspecialchars($comment['full_name'] ?: $comment['username']) ?></strong>
                                <span><?= date('d/m/Y H:i', strtotime($comment['created_at'])) ?></span>
                            </div>
                            <p><?= nl2br(htmlspecialchars($comment['content'])) ?></p>
                        </article>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<style>
    .comments-panel {
        max-width: 800px;
        background: rgba(0, 217, 163, 0.04);
        border: 1px solid rgba(0, 217, 163, 0.14);
        border-radius: 8px;
        padding: 2rem;
    }

    .comments-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        margin-bottom: 1.5rem;
    }

    .comments-header h2 {
        margin-bottom: 0;
    }

    .comments-header span {
        color: var(--accent);
        font-weight: 700;
    }

    .comment-form {
        display: grid;
        gap: 0.85rem;
        margin-bottom: 2rem;
    }

    .comment-form label {
        color: var(--text);
        font-weight: 600;
    }

    .comment-form textarea {
        width: 100%;
        resize: vertical;
        padding: 0.9rem;
        border-radius: 6px;
        border: 1px solid rgba(0, 217, 163, 0.2);
        background: var(--primary);
        color: var(--text);
        line-height: 1.6;
    }

    .comment-form textarea:focus {
        outline: none;
        border-color: var(--accent);
        box-shadow: 0 0 0 3px rgba(0, 217, 163, 0.1);
    }

    .comment-form button,
    .comment-login-box a {
        justify-self: start;
        border: 0;
        border-radius: 6px;
        background: var(--accent);
        color: #111;
        padding: 0.75rem 1.2rem;
        font-weight: 700;
        text-decoration: none;
    }

    .comment-login-box {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        flex-wrap: wrap;
        margin-bottom: 2rem;
        padding: 1rem;
        border: 1px solid rgba(0, 217, 163, 0.14);
        border-radius: 8px;
    }

    .comment-login-box p,
    .comment-empty {
        margin: 0;
        color: var(--text-muted);
    }

    .comment-list {
        display: grid;
        gap: 1rem;
    }

    .comment-item {
        padding: 1rem;
        border-radius: 8px;
        background: rgba(255, 255, 255, 0.035);
        border: 1px solid rgba(255, 255, 255, 0.08);
    }

    .comment-meta {
        display: flex;
        justify-content: space-between;
        gap: 1rem;
        color: var(--text);
        margin-bottom: 0.65rem;
    }

    .comment-meta span {
        color: var(--text-muted);
        font-size: 0.9rem;
    }

    .comment-item p {
        color: var(--text-muted);
        line-height: 1.7;
        margin: 0;
    }

    .comment-alert {
        padding: 0.8rem 1rem;
        border-radius: 6px;
        margin-bottom: 1rem;
    }

    .comment-alert-danger {
        color: #ff9ca5;
        background: rgba(220, 53, 69, 0.12);
        border: 1px solid rgba(220, 53, 69, 0.28);
    }

    .comment-alert-success {
        color: var(--accent);
        background: rgba(0, 217, 163, 0.1);
        border: 1px solid rgba(0, 217, 163, 0.25);
    }
</style>
