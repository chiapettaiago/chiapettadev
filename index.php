<?php
require_once __DIR__ . '/admin/modules/SiteItem.php';

$skills = SiteItem::getPublishedBySection('skill');
$projects = SiteItem::getPublishedBySection('project');
$blogHighlights = SiteItem::getPublishedBySection('blog', 3);

function render_tags($tags) {
    $tagList = array_filter(array_map('trim', explode(',', $tags ?? '')));

    foreach ($tagList as $tag) {
        echo '<span class="project-tag">' . htmlspecialchars($tag) . '</span>';
    }
}

function link_target($url) {
    return preg_match('/^https?:\/\//i', $url ?? '') ? ' target="_blank" rel="noopener"' : '';
}
?>
<?php include 'templates/header.php'; ?>
<?php require 'functions/conn.php'?>

<main id="primary" class="py-5">
    <!-- Hero Section -->
    <section class="hero" id="home">
        <div class="container text-center">
            <h1 class="hero-title">
                Olá, eu sou <span class="highlight">Iago Filgueiras<br>Chiapetta</span>
            </h1>
            <p class="hero-subtitle">Desenvolvedor Full Stack | Especialista em Python e Linux</p>
            <div class="hero-buttons">
                <button class="btn btn-primary" onclick="document.getElementById('sobre').scrollIntoView({behavior: 'smooth'})">
                    Sobre mim
                </button>
                <button class="btn btn-secondary" onclick="document.getElementById('projetos').scrollIntoView({behavior: 'smooth'})">
                    Meus projetos
                </button>
            </div>
        </div>
    </section>

    <!-- About Section -->
    <section id="sobre">
        <div class="container">
            <h2>Sobre mim</h2>
            <div class="about-container">
                <div class="about-image">
                    <img src="/images/profile.jpg" alt="Foto de perfil">
                </div>
                <div class="about-content">
                    <h3>Desenvolvedor Full Stack</h3>
                    <p>Programador full stack apaixonado por tecnologia e inovação, com foco em transformar ideias em soluções eficientes e intuitivas.</p>
                    
                    <div>
                        <h4>💼 Experiência</h4>
                        <p>Programador Web na UNIFESO e desenvolvedor Python freelancer, criando soluções personalizadas e explorando novas tecnologias.</p>
                    </div>
                    
                    <div>
                        <h4>⚡ Especialidades</h4>
                        <p>Especializado em Flask, SQLAlchemy, Bootstrap e bancos de dados MySQL/Oracle. Experiência em APIs, automação e desenvolvimento web responsivo.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Skills Section -->
    <section id="habilidades">
        <div class="container">
            <h2>Habilidades Técnicas</h2>
            <div class="skills-grid">
                <?php foreach ($skills as $skill): ?>
                    <div class="skill-card">
                        <h4><?= htmlspecialchars($skill['title']) ?></h4>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- Projects Section -->
    <section id="projetos">
        <div class="container">
            <h2>Meus projetos</h2>
            <div class="projects-grid">
                <?php foreach ($projects as $project): ?>
                    <div class="project-card">
                        <div class="project-image">
                            <?php if (!empty($project['image'])): ?>
                                <img src="<?= htmlspecialchars($project['image']) ?>" alt="<?= htmlspecialchars($project['title']) ?>" style="width: 100%; height: 100%; object-fit: cover;">
                            <?php else: ?>
                                <?= htmlspecialchars($project['icon'] ?: '💻') ?>
                            <?php endif; ?>
                        </div>
                        <div class="project-content">
                            <h3><?= htmlspecialchars($project['title']) ?></h3>
                            <p><?= htmlspecialchars($project['description']) ?></p>
                            <?php if (!empty($project['tags'])): ?>
                                <div class="project-tags">
                                    <?php render_tags($project['tags']); ?>
                                </div>
                            <?php endif; ?>
                            <div class="project-links">
                                <?php if (!empty($project['primary_url'])): ?>
                                    <a href="<?= htmlspecialchars($project['primary_url']) ?>"<?= link_target($project['primary_url']) ?>>→ <?= htmlspecialchars($project['primary_label'] ?: 'Ver projeto') ?></a>
                                <?php endif; ?>
                                <?php if (!empty($project['secondary_url'])): ?>
                                    <a href="<?= htmlspecialchars($project['secondary_url']) ?>"<?= link_target($project['secondary_url']) ?>>→ <?= htmlspecialchars($project['secondary_label'] ?: 'Link') ?></a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- Blog Section -->
    <section id="blog">
        <div class="container">
            <h2>Blog</h2>
            <div class="blog-grid">
                <?php foreach ($blogHighlights as $post): ?>
                    <div class="blog-card">
                        <?php if (!empty($post['image'])): ?>
                            <a class="blog-card-image" href="<?= htmlspecialchars($post['primary_url'] ?: '#') ?>">
                                <img src="<?= htmlspecialchars($post['image']) ?>" alt="<?= htmlspecialchars($post['title']) ?>" loading="lazy">
                            </a>
                        <?php endif; ?>
                        <h3><?= htmlspecialchars($post['title']) ?></h3>
                        <p><?= htmlspecialchars($post['description']) ?></p>
                        <?php if (!empty($post['primary_url'])): ?>
                            <a href="<?= htmlspecialchars($post['primary_url']) ?>"><?= htmlspecialchars($post['primary_label'] ?: 'Ler artigo') ?> →</a>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
            <div style="text-align: center; margin-top: 2rem;">
                <a href="/blog/" class="btn btn-primary">Ver todos os posts</a>
            </div>
        </div>
    </section>
</main>

<script src="js/script.js"></script>
<?php include 'templates/footer.php'; ?>
    
