<?php
require_once __DIR__ . '/../admin/modules/SiteItem.php';

$blogPosts = SiteItem::getPublishedBySection('blog');

function render_blog_tags($tags) {
    $tagList = array_filter(array_map('trim', explode(',', $tags ?? '')));

    foreach ($tagList as $tag) {
        echo '<span class="project-tag">' . htmlspecialchars($tag) . '</span>';
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
        </div>
    </section>

    <!-- Blog Posts Grid -->
    <section id="blog" class="py-5">
        <div class="container">
            <div class="blog-grid">
                <?php foreach ($blogPosts as $post): ?>
                    <div class="blog-card">
                        <?php if (!empty($post['image'])): ?>
                            <a class="blog-card-image" href="<?= htmlspecialchars($post['primary_url'] ?: '#') ?>">
                                <img src="<?= htmlspecialchars($post['image']) ?>" alt="<?= htmlspecialchars($post['title']) ?>" loading="lazy">
                            </a>
                        <?php endif; ?>
                        <h3><?= htmlspecialchars($post['title']) ?></h3>
                        <p><?= htmlspecialchars($post['description']) ?></p>
                        <?php if (!empty($post['tags'])): ?>
                            <div style="display: flex; gap: 0.5rem; flex-wrap: wrap; margin-bottom: 1rem;">
                                <?php render_blog_tags($post['tags']); ?>
                            </div>
                        <?php endif; ?>
                        <?php if (!empty($post['primary_url'])): ?>
                            <a href="<?= htmlspecialchars($post['primary_url']) ?>"><?= htmlspecialchars($post['primary_label'] ?: 'Continuar lendo') ?> →</a>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
</main>

<?php include __DIR__ . '/../templates/footer.php'; ?>
