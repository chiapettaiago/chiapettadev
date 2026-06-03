<?php
$siteBaseUrl = (((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (isset($_SERVER['SERVER_PORT']) && intval($_SERVER['SERVER_PORT']) === 443)) ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost');
$pageTitle = 'Termos de Uso | ChiapettaDev';
$metaDescription = 'Termos de uso do site ChiapettaDev, com regras de navegação, comentários e uso do conteúdo.';
$metaKeywords = 'termos de uso, condições, ChiapettaDev';
$metaImage = $siteBaseUrl . '/images/favicon-apple.jpg';
$metaCanonical = $siteBaseUrl . '/termos/';
$metaUrl = $metaCanonical;
$metaOgTitle = $pageTitle;
?>
<?php include __DIR__ . '/../templates/header.php'; ?>

<main id="primary" class="py-5">
    <section style="border-top: none;">
        <div class="container">
            <div style="max-width: 820px;">
                <h1 style="font-size: clamp(2.2rem, 5vw, 3.5rem); margin-bottom: 1rem;">Termos de Uso</h1>
                <p style="color: var(--text-muted); font-size: 1.1rem;">
                    Ao navegar neste site, você concorda com as condições descritas abaixo.
                </p>
            </div>
        </div>
    </section>

    <section class="py-5">
        <div class="container">
            <div style="max-width: 820px; line-height: 1.85; color: var(--text-muted);">
                <h2 style="color: var(--text); margin-bottom: 1rem;">1. Uso do conteúdo</h2>
                <p>
                    O conteúdo publicado neste site é informativo e autoral. Reproduções, citações e compartilhamentos devem preservar a autoria e o link original.
                </p>

                <h2 style="color: var(--text); margin-bottom: 1rem;">2. Comentários</h2>
                <p>
                    Comentários devem ser respeitosos e legais. Conteúdo ofensivo, spam ou que viole direitos de terceiros pode ser removido sem aviso prévio.
                </p>

                <h2 style="color: var(--text); margin-bottom: 1rem;">3. Disponibilidade</h2>
                <p>
                    O site pode passar por manutenções, atualizações ou indisponibilidade temporária sem aviso.
                </p>

                <h2 style="color: var(--text); margin-bottom: 1rem;">4. Serviços de terceiros</h2>
                <p>
                    Recursos externos, como bibliotecas, embeds ou anúncios, podem estar sujeitos às políticas dos respectivos provedores.
                </p>

                <h2 style="color: var(--text); margin-bottom: 1rem;">5. Contato</h2>
                <p>
                    Para questões relacionadas aos termos de uso, escreva para <a href="mailto:iagochiapetta@gmail.com" style="color: var(--accent);">iagochiapetta@gmail.com</a>.
                </p>
            </div>
        </div>
    </section>
</main>

<?php include __DIR__ . '/../templates/footer.php'; ?>
