<?php
require_once __DIR__ . '/../admin/modules/Post.php';
require_once __DIR__ . '/../admin/modules/SiteItem.php';

$blogPostSlug = $blogPostSlug ?? basename(dirname($_SERVER['SCRIPT_NAME'] ?? 'post'));
$post = null;
$dbPost = null;

if (!function_exists('blog_post_base_url')) {
    function blog_post_base_url() {
        $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || (isset($_SERVER['SERVER_PORT']) && intval($_SERVER['SERVER_PORT']) === 443);

        return ($https ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost');
    }
}

if (!function_exists('blog_post_absolute_url')) {
    function blog_post_absolute_url($path) {
        if (preg_match('/^https?:\/\//i', $path)) {
            return $path;
        }

        return blog_post_base_url() . '/' . ltrim($path, '/');
    }
}

if (!empty($blogPostData) && is_array($blogPostData)) {
    $dbPost = Post::getBySlug($blogPostSlug);
    $post = $blogPostData;
    if (is_array($dbPost)) {
        $post = array_merge($dbPost, $post);
    }
    $post['slug'] = $blogPostSlug;
    $post['status'] = $post['status'] ?? 'published';
    $post['author'] = $post['author'] ?? ['full_name' => 'Iago Filgueiras Chiapetta'];
    $post['published_at'] = $post['published_at'] ?? null;
} else {
    $post = Post::getBySlug($blogPostSlug);

    if (!$post || ($post['status'] ?? '') !== 'published') {
        http_response_code(404);
        include __DIR__ . '/../404.html';
        exit;
    }
}

$pageTitle = ($post['title'] ?? 'Blog') . ' | ChiapettaDev';
$metaDescription = $post['excerpt'] ?? blog_post_excerpt($post['content'] ?? '', 160);
$metaImage = !empty($post['featured_image']) ? blog_post_absolute_url($post['featured_image']) : blog_post_absolute_url('/images/favicon-apple.jpg');
$metaCanonical = blog_post_absolute_url('/blog/' . ($post['slug'] ?? $blogPostSlug) . '/');
$metaUrl = $metaCanonical;
$metaType = 'article';
$metaOgTitle = $post['title'] ?? $pageTitle;
$metaKeywords = !empty($post['tags']) && is_array($post['tags']) ? implode(', ', array_map(function ($tag) {
    return $tag['name'] ?? '';
}, $post['tags'])) : '';
$shareTitle = $post['title'];
$shareDescription = $post['excerpt'] ?? '';
$shareUrl = $metaCanonical;

$relatedPosts = [];
if (!empty($blogPostData['related']) && is_array($blogPostData['related'])) {
    $relatedPosts = $blogPostData['related'];
} elseif (empty($blogPostData)) {
    $relatedPosts = array_values(array_filter(
        Post::getList(['status' => 'published', 'limit' => 6]),
        function ($relatedPost) use ($post) {
            return $relatedPost['slug'] !== $post['slug'];
        }
    ));
    $relatedPosts = array_slice($relatedPosts, 0, 2);
}

function blog_post_excerpt($text, $length = 160) {
    $cleanText = trim(html_entity_decode(strip_tags($text ?? ''), ENT_QUOTES, 'UTF-8'));

    if (function_exists('mb_substr')) {
        return mb_substr($cleanText, 0, $length);
    }

    return substr($cleanText, 0, $length);
}
?>
<?php include __DIR__ . '/header.php'; ?>
<script type="application/ld+json">
<?= json_encode([
    '@context' => 'https://schema.org',
    '@type' => 'BlogPosting',
    'headline' => $post['title'] ?? $pageTitle,
    'description' => $metaDescription,
    'image' => [$metaImage],
    'author' => [
        '@type' => 'Person',
        'name' => $post['author']['full_name'] ?? 'Iago Filgueiras Chiapetta'
    ],
    'publisher' => [
        '@type' => 'Organization',
        'name' => 'ChiapettaDev',
        'logo' => [
            '@type' => 'ImageObject',
            'url' => blog_post_absolute_url('/images/favicon-apple.jpg')
        ]
    ],
    'mainEntityOfPage' => $metaCanonical,
    'datePublished' => !empty($post['published_at']) ? date(DATE_W3C, strtotime($post['published_at'])) : null,
    'dateModified' => !empty($post['updated_at']) ? date(DATE_W3C, strtotime($post['updated_at'])) : (!empty($post['published_at']) ? date(DATE_W3C, strtotime($post['published_at'])) : null)
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>
</script>

<div
    class="reading-progress"
    id="readingProgress"
    role="progressbar"
    aria-label="Progresso de leitura"
    aria-valuemin="0"
    aria-valuemax="100"
    aria-valuenow="0"
    aria-valuetext="100% da leitura restante"
>
    <span class="reading-progress-fill" id="readingProgressFill"></span>
</div>

<main id="primary" class="py-5 blog-post-page">
    <section class="blog-post-hero" style="border-top: none;">
        <div class="container">
            <div style="max-width: 820px;">
                <p class="blog-post-kicker">Artigo publicado</p>
                <h1 class="blog-post-title"><?= htmlspecialchars($post['title']) ?></h1>
                <div class="blog-post-meta">
                    <?php if (!empty($post['published_at'])): ?>
                        <span>📅 <?= date('d/m/Y', strtotime($post['published_at'])) ?></span>
                    <?php endif; ?>
                    <?php if (!empty($post['author']['full_name'])): ?>
                        <span>👤 <?= htmlspecialchars($post['author']['full_name']) ?></span>
                    <?php endif; ?>
                </div>
                <?php if (!empty($post['featured_image'])): ?>
                    <img src="<?= htmlspecialchars($post['featured_image']) ?>" alt="<?= htmlspecialchars($post['title']) ?>" class="blog-post-image">
                <?php endif; ?>
            </div>
        </div>
    </section>

    <section class="py-5">
        <div class="container">
            <article class="blog-post-content">
                <div class="blog-post-body">
                    <?= $post['content'] ?>
                </div>
            </article>
        </div>
    </section>

    <?php include __DIR__ . '/share-buttons.php'; ?>

    <?php $commentPostSlug = $post['slug']; include __DIR__ . '/comments.php'; ?>

    <?php if (!empty($relatedPosts)): ?>
        <section class="py-5">
            <div class="container">
                <div style="max-width: 820px;">
                    <h2 style="margin-bottom: 2rem;">Artigos Relacionados</h2>
                    <div class="blog-grid">
                        <?php foreach ($relatedPosts as $relatedPost): ?>
                            <div class="blog-card">
                                <?php if (!empty($relatedPost['featured_image'])): ?>
                                    <a class="blog-card-image" href="/blog/<?= htmlspecialchars($relatedPost['slug']) ?>/">
                                        <img src="<?= htmlspecialchars($relatedPost['featured_image']) ?>" alt="<?= htmlspecialchars($relatedPost['title']) ?>" loading="lazy">
                                    </a>
                                <?php endif; ?>
                                <h3><?= htmlspecialchars($relatedPost['title']) ?></h3>
                                <p><?= htmlspecialchars(blog_post_excerpt($relatedPost['excerpt'] ?? $relatedPost['content'] ?? '', 170)) ?></p>
                                <a href="/blog/<?= htmlspecialchars($relatedPost['slug']) ?>/">Continuar lendo →</a>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </section>
    <?php endif; ?>
</main>

<style>
    .reading-progress {
        position: fixed;
        top: 0;
        right: 0;
        left: 0;
        z-index: 1100;
        height: 5px;
        overflow: hidden;
        background: rgba(255, 255, 255, 0.12);
        pointer-events: none;
    }

    .reading-progress-fill {
        display: block;
        width: 100%;
        height: 100%;
        background: linear-gradient(90deg, var(--accent), #64d2ff);
        box-shadow: 0 0 12px rgba(0, 217, 163, 0.55);
        transform: scaleX(0);
        transform-origin: left center;
        transition: transform 0.1s linear;
        will-change: transform;
    }

    .blog-post-page {
        background: radial-gradient(circle at top left, rgba(0, 217, 163, 0.08), transparent 24rem);
    }

    .blog-post-kicker {
        color: var(--accent);
        font-size: 0.85rem;
        font-weight: 800;
        letter-spacing: 0.1em;
        text-transform: uppercase;
        margin-bottom: 0.8rem;
    }

    .blog-post-title {
        font-size: clamp(2.2rem, 5vw, 4rem);
        line-height: 1.1;
        margin-bottom: 1rem;
    }

    .blog-post-meta {
        display: flex;
        flex-wrap: wrap;
        gap: 1.5rem;
        color: var(--text-muted);
        margin-bottom: 1.5rem;
    }

    .blog-post-image {
        width: 100%;
        aspect-ratio: 16 / 9;
        object-fit: cover;
        border-radius: 0.85rem;
        border: 1px solid rgba(0, 217, 163, 0.12);
        box-shadow: var(--shadow);
        margin-top: 0.5rem;
    }

    .blog-post-content {
        max-width: 820px;
        line-height: 1.85;
        color: var(--text-muted);
    }

    .blog-post-body {
        font-size: 1.05rem;
    }

    .blog-post-body h2,
    .blog-post-body h3,
    .blog-post-body h4 {
        color: var(--text);
        margin-top: 2rem;
        margin-bottom: 1rem;
    }

    .blog-post-body p,
    .blog-post-body ul,
    .blog-post-body ol {
        margin-bottom: 1rem;
    }

    .blog-post-body img {
        max-width: 100%;
        height: auto;
        border-radius: 0.75rem;
    }

    .blog-post-body a {
        color: var(--accent);
    }

    .blog-post-body blockquote {
        margin: 1.5rem 0;
        padding: 1rem 1.25rem;
        border-left: 4px solid var(--accent);
        background: rgba(0, 217, 163, 0.06);
        color: var(--text);
        border-radius: 0 0.75rem 0.75rem 0;
    }

    @media (max-width: 768px) {
        .reading-progress {
            height: 4px;
        }

        .blog-post-meta {
            gap: 0.75rem 1rem;
        }
    }

    @media (prefers-reduced-motion: reduce) {
        .reading-progress-fill {
            transition: none;
        }
    }
</style>

<script>
    (function () {
        const progress = document.getElementById('readingProgress');
        const fill = document.getElementById('readingProgressFill');
        const article = document.querySelector('.blog-post-body');

        if (!progress || !fill || !article) return;

        let frameRequested = false;

        function updateReadingProgress() {
            frameRequested = false;

            const articleTop = article.getBoundingClientRect().top + window.scrollY;
            const articleHeight = Math.max(article.offsetHeight, 1);
            const readingPosition = window.scrollY + (window.innerHeight * 0.28);
            const percentage = Math.min(100, Math.max(0, ((readingPosition - articleTop) / articleHeight) * 100));
            const roundedPercentage = Math.round(percentage);
            const remaining = Math.max(0, 100 - roundedPercentage);

            fill.style.transform = `scaleX(${percentage / 100})`;
            progress.setAttribute('aria-valuenow', String(roundedPercentage));
            progress.setAttribute('aria-valuetext', `${remaining}% da leitura restante`);
        }

        function requestProgressUpdate() {
            if (frameRequested) return;
            frameRequested = true;
            window.requestAnimationFrame(updateReadingProgress);
        }

        window.addEventListener('scroll', requestProgressUpdate, { passive: true });
        window.addEventListener('resize', requestProgressUpdate);

        if ('ResizeObserver' in window) {
            new ResizeObserver(requestProgressUpdate).observe(article);
        }

        updateReadingProgress();
    }());
</script>

<?php include __DIR__ . '/footer.php'; ?>
