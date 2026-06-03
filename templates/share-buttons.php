<?php
$shareTitle = $shareTitle ?? ($pageTitle ?? 'ChiapettaDev');
$shareUrl = $shareUrl ?? ('https://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . ($_SERVER['REQUEST_URI'] ?? '/'));
$shareDescription = $shareDescription ?? '';
$shareCaption = trim($shareTitle . ($shareDescription !== '' ? "\n" . $shareDescription : '') . "\n" . $shareUrl);

if (!function_exists('share_button_build_url')) {
    function share_button_build_url($baseUrl, $params = []) {
        return $baseUrl . '?' . http_build_query($params);
    }
}

if (!function_exists('share_button_icon')) {
    function share_button_icon($network) {
        $icons = [
            'facebook' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M13.5 22v-8h2.7l.4-3h-3.1V9.1c0-.9.3-1.5 1.6-1.5H16.7V4.8c-.6-.1-1.5-.2-2.7-.2-2.7 0-4.5 1.7-4.5 4.8V11H7v3h2.5v8h4z"/></svg>',
            'linkedin' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6.3 6.5A2.1 2.1 0 1 1 6.3 2.3a2.1 2.1 0 0 1 0 4.2ZM4.4 21.7V8.2h3.8v13.5H4.4ZM9.9 8.2h3.6v1.9h.1c.5-1 1.8-2 3.8-2 4 0 4.8 2.6 4.8 6v7.6h-3.8v-6.8c0-1.6 0-3.6-2.1-3.6s-2.4 1.7-2.4 3.5v7H9.9V8.2Z"/></svg>',
            'x' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M18.9 3H22l-6.8 7.8L23 21h-6.6l-5.2-6.1L6 21H3l7.3-8.4L1 3h6.8l4.7 5.5L18.9 3Zm-1.2 16h1.7L7 4.8H5.2L17.7 19Z"/></svg>',
            'whatsapp' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M20.5 3.5A11.9 11.9 0 0 0 1.8 17.9L1 23l5.2-1.4A11.9 11.9 0 1 0 20.5 3.5Zm-8.6 18a9.8 9.8 0 0 1-5-1.4l-.4-.2-3 .8.8-2.9-.2-.4a9.9 9.9 0 1 1 7.8 4.1Zm5.7-7.3c-.3-.2-1.8-.9-2.1-1s-.5-.2-.7.2-.8 1-.9 1.2-.3.2-.6.1a8 8 0 0 1-2.4-1.5 8.9 8.9 0 0 1-1.7-2.1c-.2-.3 0-.5.1-.7l.4-.4c.1-.1.2-.3.3-.5a.7.7 0 0 0 0-.6c-.1-.2-.7-1.7-1-2.3s-.5-.5-.7-.5h-.6a1.1 1.1 0 0 0-.8.4 3.3 3.3 0 0 0-1 2.5A5.7 5.7 0 0 0 8 15.2a13.2 13.2 0 0 0 5.3 4.5c.9.4 1.6.6 2.1.8a5 5 0 0 0 2.4.1c.7-.1 1.8-.8 2-1.5s.2-1.2.1-1.3-.2-.2-.5-.4Z"/></svg>',
            'instagram' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M7.2 2h9.6A5.2 5.2 0 0 1 22 7.2v9.6a5.2 5.2 0 0 1-5.2 5.2H7.2A5.2 5.2 0 0 1 2 16.8V7.2A5.2 5.2 0 0 1 7.2 2Zm0 2A3.2 3.2 0 0 0 4 7.2v9.6A3.2 3.2 0 0 0 7.2 20h9.6a3.2 3.2 0 0 0 3.2-3.2V7.2A3.2 3.2 0 0 0 16.8 4H7.2Zm4.8 2.6a5.4 5.4 0 1 1 0 10.8 5.4 5.4 0 0 1 0-10.8Zm0 2A3.4 3.4 0 1 0 12 16a3.4 3.4 0 0 0 0-6.8Zm5.7-2.9a1.2 1.2 0 1 1 0 2.4 1.2 1.2 0 0 1 0-2.4Z"/></svg>'
        ];

        return $icons[$network] ?? '';
    }
}
?>

<section class="py-5">
    <div class="container">
        <div style="max-width: 800px;">
            <div style="border-top: 1px solid rgba(0, 217, 163, 0.1); padding-top: 2rem;">
                <h3 style="margin-bottom: 1rem;">Compartilhe este post</h3>
                <p style="color: var(--text-muted); margin-bottom: 1.25rem;">
                    Compartilhe nas redes ou copie um texto pronto para publicar no Instagram.
                </p>

                <div class="share-actions">
                    <a class="share-button share-facebook" href="<?= htmlspecialchars(share_button_build_url('https://www.facebook.com/sharer/sharer.php', ['u' => $shareUrl])) ?>" target="_blank" rel="noopener noreferrer">
                        <span class="share-icon"><?= share_button_icon('facebook') ?></span>
                        <span>Facebook</span>
                    </a>
                    <a class="share-button share-linkedin" href="<?= htmlspecialchars(share_button_build_url('https://www.linkedin.com/sharing/share-offsite/', ['url' => $shareUrl])) ?>" target="_blank" rel="noopener noreferrer">
                        <span class="share-icon"><?= share_button_icon('linkedin') ?></span>
                        <span>LinkedIn</span>
                    </a>
                    <a class="share-button share-x" href="<?= htmlspecialchars(share_button_build_url('https://twitter.com/intent/tweet', ['text' => $shareTitle, 'url' => $shareUrl])) ?>" target="_blank" rel="noopener noreferrer">
                        <span class="share-icon"><?= share_button_icon('x') ?></span>
                        <span>X</span>
                    </a>
                    <a class="share-button share-whatsapp" href="<?= htmlspecialchars(share_button_build_url('https://api.whatsapp.com/send', ['text' => $shareCaption])) ?>" target="_blank" rel="noopener noreferrer">
                        <span class="share-icon"><?= share_button_icon('whatsapp') ?></span>
                        <span>WhatsApp</span>
                    </a>
                    <button class="share-button share-instagram" type="button" data-share-copy="<?= htmlspecialchars($shareCaption) ?>">
                        <span class="share-icon"><?= share_button_icon('instagram') ?></span>
                        <span>Instagram</span>
                    </button>
                </div>

                <p style="color: var(--text-muted); font-size: 0.95rem; margin-top: 1rem; margin-bottom: 0;">
                    <strong>Instagram:</strong> o navegador não oferece compartilhamento direto para post nessa rede, então este botão copia um texto pronto com o link do artigo.
                </p>
                <p id="shareFeedback" style="color: var(--accent); font-size: 0.95rem; margin-top: 0.5rem; margin-bottom: 0; min-height: 1.25rem;" aria-live="polite"></p>
            </div>
        </div>
    </div>
</section>

<style>
    .share-actions {
        display: flex;
        flex-wrap: wrap;
        gap: 0.75rem;
    }

    .share-button {
        display: inline-flex;
        align-items: center;
        gap: 0.7rem;
        min-height: 3rem;
        padding: 0.8rem 1.15rem;
        border-radius: 0.75rem;
        border: 1px solid rgba(0, 217, 163, 0.18);
        background: rgba(0, 217, 163, 0.08);
        color: var(--text);
        text-decoration: none;
        font-weight: 700;
        transition: transform 0.25s ease, background 0.25s ease, border-color 0.25s ease, color 0.25s ease;
        cursor: pointer;
    }

    .share-button:hover {
        background: rgba(0, 217, 163, 0.16);
        border-color: rgba(0, 217, 163, 0.35);
        color: var(--accent);
        transform: translateY(-2px);
    }

    .share-button:focus-visible {
        outline: 2px solid var(--accent);
        outline-offset: 2px;
    }

    .share-icon {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 1.3rem;
        height: 1.3rem;
        flex: 0 0 1.3rem;
    }

    .share-icon svg {
        width: 100%;
        height: 100%;
        fill: currentColor;
    }

    .share-facebook {
        border-color: rgba(24, 119, 242, 0.3);
    }

    .share-linkedin {
        border-color: rgba(10, 102, 194, 0.3);
    }

    .share-x {
        border-color: rgba(255, 255, 255, 0.16);
    }

    .share-whatsapp {
        border-color: rgba(37, 211, 102, 0.3);
    }

    .share-instagram {
        border-color: rgba(225, 48, 108, 0.3);
    }
</style>

<script>
(function () {
    const button = document.querySelector('[data-share-copy]');
    const feedback = document.getElementById('shareFeedback');

    if (!button || !feedback) {
        return;
    }

    const setFeedback = function (message) {
        feedback.textContent = message;
    };

    button.addEventListener('click', async function () {
        const text = button.getAttribute('data-share-copy') || '';

        try {
            await navigator.clipboard.writeText(text);
            setFeedback('Texto copiado. Agora é só colar no Instagram.');
        } catch (error) {
            const fallbackInput = document.createElement('textarea');
            fallbackInput.value = text;
            fallbackInput.setAttribute('readonly', '');
            fallbackInput.style.position = 'absolute';
            fallbackInput.style.left = '-9999px';
            document.body.appendChild(fallbackInput);
            fallbackInput.select();
            document.execCommand('copy');
            document.body.removeChild(fallbackInput);
            setFeedback('Texto copiado. Agora é só colar no Instagram.');
        }
    });
})();
</script>
