<?php
require_once __DIR__ . '/../admin/modules/Post.php';

$searchQuery = trim((string) ($_GET['q'] ?? ''));
if (function_exists('mb_substr')) {
    $searchQuery = mb_substr($searchQuery, 0, 100);
} else {
    $searchQuery = substr($searchQuery, 0, 100);
}

$blogPostRows = Post::getList([
    'status' => 'published',
    'search' => $searchQuery,
    'limit' => 100
]);
$blogPosts = array_values(array_filter(array_map(function ($postRow) {
    return Post::getById($postRow['id']);
}, $blogPostRows)));
$siteBaseUrl = (((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (isset($_SERVER['SERVER_PORT']) && intval($_SERVER['SERVER_PORT']) === 443)) ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost');
$pageTitle = 'Blog | ChiapettaDev';
$metaDescription = 'Artigos e reflexões de Iago Filgueiras Chiapetta sobre desenvolvimento web, tecnologia, carreira e inteligência artificial.';
$metaKeywords = 'blog de tecnologia, desenvolvimento web, Python, IA, carreira de desenvolvedor';
$metaImage = $siteBaseUrl . '/images/perplexity-ai.webp';
$metaCanonical = $siteBaseUrl . '/blog/';
$metaUrl = $metaCanonical;
$metaOgTitle = $pageTitle;

function render_blog_taxonomies($post) {
    $items = [];
    foreach ($post['categories'] ?? [] as $category) {
        $items[] = $category['name'] ?? '';
    }
    foreach ($post['tags'] ?? [] as $tag) {
        $items[] = $tag['name'] ?? '';
    }

    foreach (array_unique(array_filter($items)) as $item) {
        echo '<span class="project-tag">' . htmlspecialchars($item) . '</span>';
    }
}
?>
<?php include __DIR__ . '/../templates/header.php'; ?>

<main id="primary" class="py-5">
    <!-- Blog Header -->
    <section class="py-5" style="border-top: none;">
        <div class="container">
            <h1 style="font-size: 3rem; margin-bottom: 1rem;">Blog</h1>
            <p style="color: var(--text-muted); font-size: 1.1rem;">Artigos e reflexões sobre desenvolvimento web e tecnologia</p>
            <form class="blog-search" method="GET" action="/blog/" role="search">
                <label for="blogSearch">Pesquisar nos artigos</label>
                <div class="blog-search-control">
                    <span class="blog-search-icon" aria-hidden="true">⌕</span>
                    <input
                        type="search"
                        id="blogSearch"
                        name="q"
                        value="<?= htmlspecialchars($searchQuery) ?>"
                        placeholder="Busque por título, assunto, tag ou categoria"
                        autocomplete="off"
                    >
                    <?php if ($searchQuery !== ''): ?>
                        <a href="/blog/" class="blog-search-clear" aria-label="Limpar pesquisa">Limpar</a>
                    <?php endif; ?>
                    <button type="submit">Pesquisar</button>
                </div>
            </form>
        </div>
    </section>

    <!-- Blog Posts Grid -->
    <section id="blog" class="py-5">
        <div class="container">
            <div class="blog-results-summary" aria-live="polite">
                <?php if ($searchQuery !== ''): ?>
                    <?= count($blogPosts) ?> <?= count($blogPosts) === 1 ? 'artigo encontrado' : 'artigos encontrados' ?> para “<?= htmlspecialchars($searchQuery) ?>”
                <?php else: ?>
                    <?= count($blogPosts) ?> <?= count($blogPosts) === 1 ? 'artigo publicado' : 'artigos publicados' ?>
                <?php endif; ?>
            </div>

            <?php if ($blogPosts): ?>
                <div class="blog-grid">
                <?php foreach ($blogPosts as $post): ?>
                    <div class="blog-card">
                        <?php if (!empty($post['featured_image'])): ?>
                            <a class="blog-card-image" href="/blog/<?= rawurlencode($post['slug']) ?>/">
                                <img src="<?= htmlspecialchars($post['featured_image']) ?>" alt="<?= htmlspecialchars($post['title']) ?>" loading="lazy">
                            </a>
                        <?php endif; ?>
                        <h3><?= htmlspecialchars($post['title']) ?></h3>
                        <p><?= htmlspecialchars($post['excerpt'] ?: mb_substr(trim(strip_tags($post['content'])), 0, 180) . '…') ?></p>
                        <?php if (!empty($post['tags']) || !empty($post['categories'])): ?>
                            <div class="blog-taxonomies">
                                <?php render_blog_taxonomies($post); ?>
                            </div>
                        <?php endif; ?>
                        <a href="/blog/<?= rawurlencode($post['slug']) ?>/">Continuar lendo →</a>
                    </div>
                <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="blog-search-empty">
                    <span aria-hidden="true">⌕</span>
                    <h2>Nenhum artigo encontrado</h2>
                    <p>Tente outro termo ou consulte todos os posts publicados.</p>
                    <a href="/blog/" class="btn btn-primary">Ver todos os artigos</a>
                </div>
            <?php endif; ?>
        </div>
    </section>
</main>

<style>
    .blog-search { max-width: 820px; margin-top: 2rem; }
    .blog-search > label { display: block; margin-bottom: 0.55rem; color: var(--text); font-weight: 700; }
    .blog-search-control { display: grid; grid-template-columns: auto minmax(0, 1fr) auto auto; align-items: center; gap: 0.55rem; padding: 0.45rem; border: 1px solid rgba(0, 217, 163, 0.3); border-radius: 14px; background: #242424; box-shadow: 0 15px 40px rgba(0, 0, 0, 0.18); }
    .blog-search-icon { padding-left: 0.65rem; color: var(--accent); font-size: 1.35rem; }
    .blog-search input { min-width: 0; padding: 0.75rem 0.2rem; border: 0; outline: 0; background: transparent; color: var(--text); font: inherit; }
    .blog-search input::placeholder { color: #8f8f8f; }
    .blog-search button { min-height: 44px; padding: 0.65rem 1rem; border: 0; border-radius: 10px; background: var(--accent); color: #10221d; font-weight: 800; }
    .blog-search-clear { color: var(--text-muted); font-size: 0.85rem; text-decoration: none; }
    .blog-results-summary { margin-bottom: 1.25rem; color: var(--text-muted); }
    .blog-taxonomies { display: flex; flex-wrap: wrap; gap: 0.45rem; margin-bottom: 1rem; }
    .blog-search-empty { max-width: 680px; margin: 0 auto; padding: 3rem 1.25rem; text-align: center; border: 1px solid rgba(0, 217, 163, 0.14); border-radius: 16px; background: #242424; }
    .blog-search-empty > span { display: block; color: var(--accent); font-size: 2.5rem; }
    .blog-search-empty h2 { margin: 0.7rem 0 1rem; }
    .blog-search-empty h2::after { display: none; }
    .blog-search-empty p { color: var(--text-muted); }
    @media (max-width: 640px) {
        .blog-search-control { grid-template-columns: auto minmax(0, 1fr) auto; }
        .blog-search button { grid-column: 1 / -1; width: 100%; }
        .blog-search-clear { padding-right: 0.45rem; }
    }
</style>

<?php include __DIR__ . '/../templates/footer.php'; ?>
