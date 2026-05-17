<?php
require_once __DIR__ . '/../admin/modules/SiteAccess.php';
require_once __DIR__ . '/../admin/modules/Auth.php';
require_once __DIR__ . '/../admin/modules/Comment.php';
require_once __DIR__ . '/../admin/modules/SiteItem.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'comment') {
    $commentRedirect = $_SERVER['REQUEST_URI'] ?? '/';
    $commentPath = parse_url($commentRedirect, PHP_URL_PATH) ?: '/';
    $commentPostSlug = basename(trim($commentPath, '/'));

    if (!Auth::isAuthenticated()) {
        header('Location: /login.php?redirect=' . urlencode($commentRedirect));
        exit;
    }

    $commentUser = Auth::getCurrentUser();
    $commentResult = Comment::create($commentPostSlug, $commentUser['id'], $_POST['comment'] ?? '');

    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    $_SESSION['comment_flash'] = [
        'message' => $commentResult['message'],
        'type' => $commentResult['success'] ? 'success' : 'danger'
    ];

    header('Location: ' . strtok($commentRedirect, '#') . '#comentarios');
    exit;
}

SiteAccess::trackPublicPage('ChiapettaDev');
$headerUser = Auth::getCurrentUser();
$headerRedirect = $_SERVER['REQUEST_URI'] ?? '/';
$navbarItems = SiteItem::getPublishedBySection('nav');

if (empty($navbarItems)) {
    $navbarItems = [
        ['title' => 'Sobre', 'primary_url' => '#sobre'],
        ['title' => 'Habilidades', 'primary_url' => '#habilidades'],
        ['title' => 'Projetos', 'primary_url' => '#projetos'],
        ['title' => 'Blog', 'primary_url' => '#blog'],
    ];
}

if (!function_exists('header_nav_href')) {
    function header_nav_href($url) {
        $url = trim($url ?? '');

        if ($url === '') {
            return '#';
        }

        if ($url[0] === '#' && parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) !== '/') {
            return '/' . $url;
        }

        return $url;
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="icon" href="/images/favicon-apple.jpg" type="image/jpeg">
    <link rel="apple-touch-icon" href="/images/favicon-apple.jpg">
    <title><?= htmlspecialchars($pageTitle ?? 'ChiapettaDev - Desenvolvedor Full Stack') ?></title>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js" integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI" crossorigin="anonymous"></script>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --primary: #1a1a1a;
            --secondary: #2d2d2d;
            --accent: #00d9a3;
            --text: #ffffff;
            --text-muted: #b0b0b0;
            --shadow: 0 24px 70px rgba(0, 0, 0, 0.3);
        }

        html {
            scroll-behavior: smooth;
        }

        body {
            background: #1a1a1a;
            color: var(--text);
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        main {
            flex: 1;
        }

        /* Navigation */
        nav.navbar {
            background: rgba(26, 26, 26, 0.95) !important;
            border-bottom: 1px solid rgba(0, 217, 163, 0.1);
            padding: 1rem 0;
        }

        .navbar-brand {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--accent) !important;
            letter-spacing: -1px;
        }

        .nav-link {
            color: var(--text) !important;
            font-weight: 500;
            margin-left: 2rem;
            position: relative;
            transition: color 0.3s ease;
        }

        .nav-link:hover {
            color: var(--accent) !important;
        }

        .nav-link.active {
            color: var(--accent) !important;
            border-bottom: 2px solid var(--accent);
            padding-bottom: 0.5rem;
        }

        /* Hero Section */
        .hero {
            padding: 6rem 0;
            text-align: center;
        }

        .hero-title {
            font-size: clamp(2.5rem, 6vw, 4rem);
            font-weight: 700;
            line-height: 1.3;
            margin-bottom: 1rem;
        }

        .hero-title .highlight {
            color: var(--accent);
            display: block;
        }

        .hero-subtitle {
            font-size: 1.3rem;
            color: var(--text-muted);
            margin-bottom: 2rem;
        }

        .hero-buttons {
            display: flex;
            gap: 1rem;
            justify-content: center;
            flex-wrap: wrap;
        }

        .btn-primary {
            background: var(--accent);
            border: none;
            color: #1a1a1a;
            font-weight: 600;
            padding: 0.75rem 2rem;
            border-radius: 0.5rem;
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            background: #00c491;
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(0, 217, 163, 0.3);
            color: #1a1a1a;
        }

        .btn-secondary {
            background: transparent;
            border: 2px solid var(--accent);
            color: var(--accent);
            font-weight: 600;
            padding: 0.75rem 2rem;
            border-radius: 0.5rem;
            transition: all 0.3s ease;
        }

        .btn-secondary:hover {
            background: var(--accent);
            color: #1a1a1a;
        }

        /* Sections */
        section {
            padding: 5rem 0;
            border-top: 1px solid rgba(0, 217, 163, 0.1);
        }

        h2 {
            font-size: clamp(2rem, 5vw, 3rem);
            font-weight: 700;
            margin-bottom: 3rem;
            position: relative;
            display: inline-block;
        }

        h2::after {
            content: "";
            position: absolute;
            bottom: -0.5rem;
            left: 0;
            width: 4rem;
            height: 3px;
            background: var(--accent);
            border-radius: 2px;
        }

        h3 {
            font-size: 1.3rem;
            font-weight: 600;
        }

        h4 {
            font-size: 1rem;
            font-weight: 600;
            color: var(--accent);
        }

        /* About Section */
        .about-container {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 3rem;
            align-items: center;
        }

        @media (max-width: 768px) {
            .about-container {
                grid-template-columns: 1fr;
            }
        }

        .about-image {
            text-align: center;
        }

        .about-image img {
            width: 100%;
            max-width: 300px;
            border-radius: 1rem;
        }

        .about-content h4 {
            margin-top: 1.5rem;
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .about-content p {
            color: var(--text-muted);
            line-height: 1.8;
            margin-bottom: 1rem;
        }

        /* Skills Grid */
        .skills-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 1.5rem;
        }

        .skill-card {
            background: rgba(0, 217, 163, 0.05);
            border: 1px solid rgba(0, 217, 163, 0.2);
            padding: 2rem;
            border-radius: 0.5rem;
            text-align: center;
            transition: all 0.3s ease;
        }

        .skill-card:hover {
            background: rgba(0, 217, 163, 0.1);
            border-color: var(--accent);
            transform: translateY(-5px);
        }

        .skill-card h4 {
            margin: 0;
        }

        /* Projects Grid */
        .projects-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
        }

        .project-card {
            background: rgba(45, 45, 45, 0.5);
            border: 1px solid rgba(0, 217, 163, 0.1);
            border-radius: 0.5rem;
            overflow: hidden;
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .project-card:hover {
            border-color: var(--accent);
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0, 217, 163, 0.15);
        }

        .project-image {
            width: 100%;
            height: 200px;
            background: linear-gradient(135deg, rgba(0, 217, 163, 0.2), rgba(0, 217, 163, 0.05));
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3rem;
        }

        .project-content {
            padding: 1.5rem;
        }

        .project-content h3 {
            margin: 0 0 0.5rem 0;
        }

        .project-content p {
            color: var(--text-muted);
            margin-bottom: 1rem;
            font-size: 0.95rem;
        }

        .project-tags {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
            margin-bottom: 1rem;
        }

        .project-tag {
            background: rgba(0, 217, 163, 0.1);
            color: var(--accent);
            padding: 0.25rem 0.75rem;
            border-radius: 1rem;
            font-size: 0.8rem;
        }

        .project-links {
            display: flex;
            gap: 1rem;
        }

        .project-links a {
            color: var(--accent);
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .project-links a:hover {
            color: var(--text);
        }

        /* Blog Section */
        .blog-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 2rem;
        }

        .blog-card {
            background: rgba(45, 45, 45, 0.5);
            border: 1px solid rgba(0, 217, 163, 0.1);
            border-radius: 0.5rem;
            overflow: hidden;
            transition: all 0.3s ease;
        }

        .blog-card:hover {
            border-color: var(--accent);
            transform: translateY(-5px);
        }

        .blog-card h3 {
            color: var(--accent);
            margin: 1.5rem 1.5rem 0.5rem;
        }

        .blog-card p {
            color: var(--text-muted);
            margin: 0 1.5rem 1rem;
        }

        .blog-card a {
            color: var(--accent);
            text-decoration: none;
            font-weight: 600;
        }

        .blog-card > a:not(.blog-card-image),
        .blog-card > div {
            margin-left: 1.5rem;
            margin-right: 1.5rem;
        }

        .blog-card > a:not(.blog-card-image) {
            display: inline-block;
            margin-bottom: 1.5rem;
        }

        .blog-card-image {
            display: block;
            width: 100%;
            aspect-ratio: 16 / 9;
            background: linear-gradient(135deg, rgba(0, 217, 163, 0.14), rgba(255, 255, 255, 0.04));
            overflow: hidden;
        }

        .blog-card-image img {
            width: 100%;
            height: 100%;
            display: block;
            object-fit: cover;
            transition: transform 0.3s ease;
        }

        .blog-card:hover .blog-card-image img {
            transform: scale(1.03);
        }

        .blog-card a:hover {
            text-decoration: underline;
        }

        /* Footer */
        footer {
            background: rgba(0, 0, 0, 0.5);
            border-top: 1px solid rgba(0, 217, 163, 0.1);
            padding: 3rem 0;
            margin-top: 5rem;
        }

        footer p {
            color: var(--text-muted);
            margin: 0.5rem 0;
        }

        footer a {
            color: var(--accent);
            text-decoration: none;
        }

        footer a:hover {
            text-decoration: underline;
        }

        /* WhatsApp Button */
        .whatsapp-btn {
            position: fixed;
            bottom: 2rem;
            right: 2rem;
            width: 60px;
            height: 60px;
            background: #25d366;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            cursor: pointer;
            text-decoration: none;
            box-shadow: 0 4px 12px rgba(37, 211, 102, 0.3);
            transition: all 0.3s ease;
            z-index: 1000;
        }

        .whatsapp-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 20px rgba(37, 211, 102, 0.4);
            color: white;
        }

        .whatsapp-btn svg {
            width: 32px;
            height: 32px;
            fill: #fff;
            display: block;
            flex: 0 0 32px;
        }

        /* Utility Classes */
        .container {
            max-width: 1200px;
        }

        .text-accent {
            color: var(--accent);
        }

        .divider {
            height: 1px;
            background: rgba(0, 217, 163, 0.1);
            margin: 2rem 0;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark sticky-top">
        <div class="container">
            <a class="navbar-brand" href="/#home">ChiapettaDev</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <?php foreach ($navbarItems as $navItem): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="<?= htmlspecialchars(header_nav_href($navItem['primary_url'] ?? '')) ?>">
                                <?= htmlspecialchars($navItem['title']) ?>
                            </a>
                        </li>
                    <?php endforeach; ?>
                    <?php if ($headerUser): ?>
                        <li class="nav-item">
                            <span class="nav-link">Olá, <?= htmlspecialchars(explode(' ', $headerUser['full_name'])[0]) ?></span>
                        </li>
                        <?php if (Auth::hasPermission('author')): ?>
                            <li class="nav-item">
                                <a class="nav-link" href="/admin/dashboard.php">CMS</a>
                            </li>
                        <?php endif; ?>
                        <li class="nav-item">
                            <a class="nav-link" href="/logout.php?redirect=<?= urlencode($headerRedirect) ?>">Sair</a>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link" href="/login.php?redirect=<?= urlencode($headerRedirect) ?>">Entrar</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <a href="https://wa.me/5521972940130?text=Olá" class="whatsapp-btn" title="Enviar mensagem no WhatsApp" aria-label="Enviar mensagem no WhatsApp">
        <svg viewBox="0 0 448 512" aria-hidden="true" focusable="false">
            <path d="M380.9 97.1C339 55.1 283.2 32 223.9 32 101 32 1.1 131.9 1.1 254.8c0 39.3 10.3 77.6 29.9 111.3L0 480l116.5-30.6c32.4 17.7 68.9 27 107.3 27h.1c122.9 0 222.8-99.9 222.8-222.8 0-59.3-23.1-115-65.8-156.5zM223.9 438.9h-.1c-34.2 0-67.7-9.2-96.9-26.7l-6.9-4.1-69.1 18.1 18.4-67.4-4.5-6.9c-18.7-29.7-28.6-63.7-28.6-98.3 0-102.6 83.4-186 186-186 49.7 0 96.4 19.4 131.6 54.5 35.2 35.2 54.6 81.9 54.6 131.6 0 102.6-83.5 185.2-184.5 185.2zm101.9-138.9c-5.6-2.8-33-16.3-38.1-18.1-5.1-1.9-8.8-2.8-12.5 2.8-3.7 5.6-14.4 18.1-17.6 21.8-3.2 3.7-6.5 4.2-12.1 1.4-32.9-16.4-54.5-29.3-76.2-66.3-5.8-10 5.8-9.3 16.4-30.9 1.9-3.7.9-6.9-.5-9.7-1.4-2.8-12.5-30.1-17.1-41.2-4.5-10.8-9.1-9.3-12.5-9.5-3.2-.2-6.9-.2-10.6-.2s-9.7 1.4-14.8 6.9c-5.1 5.6-19.4 19-19.4 46.3s19.9 53.7 22.7 57.4c2.8 3.7 39.1 59.7 94.8 83.8 35.2 15.2 49 16.5 66.6 13.9 10.7-1.6 33-13.5 37.6-26.5 4.6-13 4.6-24.1 3.2-26.5-1.4-2.4-5-3.8-10.6-6.6z"/>
        </svg>
    </a>
