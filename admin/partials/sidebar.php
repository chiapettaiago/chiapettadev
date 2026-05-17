<?php
$currentPath = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?: '';
$currentQuery = $_GET;

$adminSections = [
    [
        'label' => 'Visão geral',
        'icon' => 'fas fa-gauge-high',
        'links' => [
            ['href' => '/admin/dashboard.php', 'icon' => 'fas fa-chart-line', 'label' => 'Dashboard', 'match' => ['/admin/dashboard.php', '/admin/']],
        ],
    ],
    [
        'label' => 'Conteúdo',
        'icon' => 'fas fa-newspaper',
        'links' => [
            ['href' => '/admin/pages/posts.php', 'icon' => 'fas fa-file-alt', 'label' => 'Posts', 'match' => ['/admin/pages/posts.php']],
            ['href' => '/admin/pages/pages.php', 'icon' => 'fas fa-file', 'label' => 'Páginas', 'match' => ['/admin/pages/pages.php']],
            ['href' => '/admin/pages/slides.php', 'icon' => 'fas fa-clapperboard', 'label' => 'Slides', 'match' => ['/admin/pages/slides.php']],
            ['href' => '/admin/pages/images.php', 'icon' => 'fas fa-image', 'label' => 'Imagens', 'match' => ['/admin/pages/images.php']],
        ],
    ],
    [
        'label' => 'Aparência',
        'icon' => 'fas fa-palette',
        'links' => [
            ['href' => '/admin/pages/site-items.php', 'icon' => 'fas fa-layer-group', 'label' => 'Itens do Site', 'match' => ['/admin/pages/site-items.php'], 'exclude_section' => 'nav'],
            ['href' => '/admin/pages/site-items.php?section=nav', 'icon' => 'fas fa-bars', 'label' => 'Menu Navbar', 'match' => ['/admin/pages/site-items.php'], 'section' => 'nav'],
        ],
    ],
    [
        'label' => 'Administração',
        'icon' => 'fas fa-sliders',
        'links' => [
            ['href' => '/admin/pages/users.php', 'icon' => 'fas fa-users', 'label' => 'Usuários', 'match' => ['/admin/pages/users.php']],
            ['href' => '/admin/pages/settings.php', 'icon' => 'fas fa-cog', 'label' => 'Configurações', 'match' => ['/admin/pages/settings.php']],
            ['href' => '/admin/pages/backups.php', 'icon' => 'fas fa-cloud-arrow-up', 'label' => 'Backups', 'match' => ['/admin/pages/backups.php']],
            ['href' => '/admin/pages/about.php', 'icon' => 'fas fa-circle-info', 'label' => 'Sobre', 'match' => ['/admin/pages/about.php']],
        ],
    ],
];

if (!function_exists('cms_sidebar_active')) {
    function cms_sidebar_active($currentPath, $currentQuery, $link) {
        if (!in_array($currentPath, $link['match'], true)) {
            return '';
        }

        if (!empty($link['section'])) {
            return (($currentQuery['section'] ?? '') === $link['section']) ? 'active' : '';
        }

        if (!empty($link['exclude_section']) && (($currentQuery['section'] ?? '') === $link['exclude_section'])) {
            return '';
        }

        return 'active';
    }
}

if (!function_exists('cms_sidebar_section_active')) {
    function cms_sidebar_section_active($currentPath, $currentQuery, $links) {
        foreach ($links as $link) {
            if (cms_sidebar_active($currentPath, $currentQuery, $link) === 'active') {
                return true;
            }
        }

        return false;
    }
}
?>
<aside class="admin-sidebar">
    <div class="admin-sidebar-brand">
        <img src="/images/favicon-apple.jpg" alt="ChiapettaDev" class="admin-sidebar-logo">
        <div>
            <h2>CMS</h2>
            <p>ChiapettaDev</p>
        </div>
    </div>

    <ul class="admin-sidebar-menu">
        <?php foreach ($adminSections as $section): ?>
            <?php $sectionActive = cms_sidebar_section_active($currentPath, $currentQuery, $section['links']); ?>
            <li class="admin-sidebar-group">
                <details <?= $sectionActive ? 'open' : '' ?>>
                    <summary class="admin-sidebar-toggle <?= $sectionActive ? 'active' : '' ?>">
                        <i class="<?= htmlspecialchars($section['icon']) ?> admin-sidebar-group-icon"></i>
                        <span><?= htmlspecialchars($section['label']) ?></span>
                        <i class="fas fa-chevron-down admin-sidebar-chevron"></i>
                    </summary>
                    <ul class="admin-sidebar-submenu">
                        <?php foreach ($section['links'] as $link): ?>
                            <li>
                                <a href="<?= htmlspecialchars($link['href']) ?>" class="<?= cms_sidebar_active($currentPath, $currentQuery, $link) ?>">
                                    <i class="<?= htmlspecialchars($link['icon']) ?>"></i>
                                    <span><?= htmlspecialchars($link['label']) ?></span>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </details>
            </li>
        <?php endforeach; ?>
        <li class="admin-sidebar-separator">
            <a href="/admin/logout.php">
                <i class="fas fa-sign-out-alt"></i>
                <span>Sair</span>
            </a>
        </li>
    </ul>
</aside>

<main class="admin-main">
