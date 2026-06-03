<?php
$siteBaseUrl = (((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (isset($_SERVER['SERVER_PORT']) && intval($_SERVER['SERVER_PORT']) === 443)) ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost');
$pageTitle = 'Política de Privacidade | ChiapettaDev';
$metaDescription = 'Política de privacidade do site ChiapettaDev, com informações sobre navegação, comentários e dados coletados.';
$metaKeywords = 'política de privacidade, cookies, dados pessoais, ChiapettaDev';
$metaImage = $siteBaseUrl . '/images/favicon-apple.jpg';
$metaCanonical = $siteBaseUrl . '/privacidade/';
$metaUrl = $metaCanonical;
$metaOgTitle = $pageTitle;
?>
<?php include __DIR__ . '/../templates/header.php'; ?>

<main id="primary" class="py-5">
    <section style="border-top: none;">
        <div class="container">
            <div style="max-width: 820px;">
                <h1 style="font-size: clamp(2.2rem, 5vw, 3.5rem); margin-bottom: 1rem;">Política de Privacidade</h1>
                <p style="color: var(--text-muted); font-size: 1.1rem;">
                    Esta página explica como o site trata informações de navegação, comentários e formulários.
                </p>
            </div>
        </div>
    </section>

    <section class="py-5">
        <div class="container">
            <div style="max-width: 820px; line-height: 1.85; color: var(--text-muted);">
                <h2 style="color: var(--text); margin-bottom: 1rem;">1. Coleta de informações</h2>
                <p>
                    O site pode registrar dados técnicos de navegação, como páginas acessadas, data e hora de acesso e informações básicas do navegador, para fins de funcionamento, segurança e estatísticas.
                </p>

                <h2 style="color: var(--text); margin-bottom: 1rem;">2. Comentários e login</h2>
                <p>
                    Quando você comenta em um post, usamos as informações da conta de leitor para publicar o comentário e manter o histórico associado ao conteúdo.
                </p>

                <h2 style="color: var(--text); margin-bottom: 1rem;">3. Cookies e terceiros</h2>
                <p>
                    O site pode usar cookies necessários para navegação e recursos de sessão. Caso anúncios do Google sejam ativados, o Google poderá usar cookies e tecnologias semelhantes conforme suas próprias políticas.
                </p>

                <h2 style="color: var(--text); margin-bottom: 1rem;">4. Uso das informações</h2>
                <p>
                    As informações são usadas para operar o site, melhorar a experiência do usuário, moderar comentários e cumprir obrigações legais quando necessário.
                </p>

                <h2 style="color: var(--text); margin-bottom: 1rem;">5. Contato</h2>
                <p>
                    Se você tiver dúvidas sobre privacidade, entre em contato pelo email <a href="mailto:iagochiapetta@gmail.com" style="color: var(--accent);">iagochiapetta@gmail.com</a>.
                </p>
            </div>
        </div>
    </section>
</main>

<?php include __DIR__ . '/../templates/footer.php'; ?>
