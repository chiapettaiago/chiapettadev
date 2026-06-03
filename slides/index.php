<?php
require_once __DIR__ . '/../admin/modules/SlideDeck.php';

$pageTitle = 'Slides | ChiapettaDev';
$siteBaseUrl = (((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (isset($_SERVER['SERVER_PORT']) && intval($_SERVER['SERVER_PORT']) === 443)) ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost');
$metaDescription = 'Apresentações navegáveis e conteúdos visuais publicados no site ChiapettaDev.';
$metaKeywords = 'slides, apresentações, tecnologia, desenvolvimento web';
$metaImage = $siteBaseUrl . '/images/favicon-apple.jpg';
$metaCanonical = $siteBaseUrl . '/slides/';
$metaUrl = $metaCanonical;
$metaOgTitle = $pageTitle;
$decks = SlideDeck::getList(['status' => 'published']);
?>
<?php include __DIR__ . '/../templates/header.php'; ?>

<main id="primary" class="py-5">
    <section style="border-top: none;">
        <div class="container">
            <h1 style="font-size: 3rem; margin-bottom: 1rem;">Slides</h1>
            <p style="color: var(--text-muted); font-size: 1.1rem;">Apresentações navegáveis publicadas no site.</p>
        </div>
    </section>

    <section class="py-5">
        <div class="container">
            <?php if (!empty($decks)): ?>
                <div class="blog-grid">
                    <?php foreach ($decks as $deck): ?>
                        <div class="blog-card">
                            <h3><?= htmlspecialchars($deck['title']) ?></h3>
                            <p><?= htmlspecialchars($deck['description']) ?></p>
                            <a href="<?= htmlspecialchars(SlideDeck::publicUrl($deck['slug'])) ?>">Abrir slides →</a>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p style="color: var(--text-muted);">Nenhuma apresentação publicada ainda.</p>
            <?php endif; ?>
        </div>
    </section>
</main>

<?php include __DIR__ . '/../templates/footer.php'; ?>
