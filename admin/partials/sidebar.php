<?php
$currentPath = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?: '';
$currentQuery = $_GET;
$toolbarUser = Auth::getCurrentUser();

if (!function_exists('cms_menu_item_active')) {
    function cms_menu_item_active($currentPath, $currentQuery, $item) {
        if (!in_array($currentPath, $item['match'] ?? [], true)) {
            return false;
        }

        if (!empty($item['section']) && (($currentQuery['section'] ?? '') !== $item['section'])) {
            return false;
        }

        if (!empty($item['exclude_section']) && (($currentQuery['section'] ?? '') === $item['exclude_section'])) {
            return false;
        }

        if (!empty($item['query'])) {
            foreach ($item['query'] as $key => $value) {
                if (($currentQuery[$key] ?? null) !== (string) $value) {
                    return false;
                }
            }
        }

        if (!empty($item['exclude_query'])) {
            foreach ($item['exclude_query'] as $key => $value) {
                if (($currentQuery[$key] ?? null) === (string) $value) {
                    return false;
                }
            }
        }

        return true;
    }
}

if (!function_exists('cms_menu_group_active')) {
    function cms_menu_group_active($currentPath, $currentQuery, $item) {
        if (cms_menu_item_active($currentPath, $currentQuery, $item)) {
            return true;
        }

        foreach ($item['children'] ?? [] as $child) {
            if (cms_menu_item_active($currentPath, $currentQuery, $child)) {
                return true;
            }
        }

        return false;
    }
}

if (!function_exists('cms_toolbar_initial')) {
    function cms_toolbar_initial($toolbarUser) {
        $toolbarInitial = 'U';

        if ($toolbarUser && !empty($toolbarUser['full_name'])) {
            $toolbarName = trim($toolbarUser['full_name']);
            $toolbarInitial = function_exists('mb_strtoupper')
                ? mb_strtoupper(function_exists('mb_substr') ? mb_substr($toolbarName, 0, 1) : substr($toolbarName, 0, 1))
                : strtoupper(substr($toolbarName, 0, 1));
        }

        return $toolbarInitial;
    }
}

$adminMenu = [
    [
        'label' => 'Painel',
        'icon' => 'fas fa-gauge-high',
        'href' => '/admin/dashboard.php',
        'match' => ['/admin/dashboard.php', '/admin/'],
    ],
    [
        'label' => 'Posts',
        'icon' => 'fas fa-pen-to-square',
        'href' => '/admin/pages/posts.php',
        'match' => ['/admin/pages/posts.php'],
        'children' => [
            [
                'label' => 'Todos os posts',
                'icon' => 'fas fa-list',
                'href' => '/admin/pages/posts.php',
                'match' => ['/admin/pages/posts.php'],
                'exclude_query' => ['new' => '1'],
            ],
            [
                'label' => 'Adicionar novo',
                'icon' => 'fas fa-plus',
                'href' => '/admin/pages/posts.php?new=1',
                'match' => ['/admin/pages/posts.php'],
                'query' => ['new' => '1'],
            ],
        ],
    ],
    [
        'label' => 'Mídia',
        'icon' => 'fas fa-images',
        'href' => '/admin/pages/images.php',
        'match' => ['/admin/pages/images.php'],
    ],
    [
        'label' => 'Páginas',
        'icon' => 'fas fa-file-lines',
        'href' => '/admin/pages/pages.php',
        'match' => ['/admin/pages/pages.php'],
        'children' => [
            [
                'label' => 'Todas as páginas',
                'icon' => 'fas fa-list',
                'href' => '/admin/pages/pages.php',
                'match' => ['/admin/pages/pages.php'],
                'exclude_query' => ['new' => '1'],
            ],
            [
                'label' => 'Adicionar nova',
                'icon' => 'fas fa-plus',
                'href' => '/admin/pages/pages.php?new=1',
                'match' => ['/admin/pages/pages.php'],
                'query' => ['new' => '1'],
            ],
        ],
    ],
    [
        'label' => 'Slides',
        'icon' => 'fas fa-clapperboard',
        'href' => '/admin/pages/slides.php',
        'match' => ['/admin/pages/slides.php'],
        'children' => [
            [
                'label' => 'Todos os slides',
                'icon' => 'fas fa-list',
                'href' => '/admin/pages/slides.php',
                'match' => ['/admin/pages/slides.php'],
                'exclude_query' => ['new' => '1'],
            ],
            [
                'label' => 'Adicionar novo',
                'icon' => 'fas fa-plus',
                'href' => '/admin/pages/slides.php?new=1',
                'match' => ['/admin/pages/slides.php'],
                'query' => ['new' => '1'],
            ],
        ],
    ],
    [
        'label' => 'Aparência',
        'icon' => 'fas fa-palette',
        'href' => '/admin/pages/site-items.php',
        'match' => ['/admin/pages/site-items.php'],
        'children' => [
            [
                'label' => 'Itens do site',
                'icon' => 'fas fa-layer-group',
                'href' => '/admin/pages/site-items.php',
                'match' => ['/admin/pages/site-items.php'],
                'exclude_section' => 'nav',
            ],
            [
                'label' => 'Menu da navegação',
                'icon' => 'fas fa-bars',
                'href' => '/admin/pages/site-items.php?section=nav',
                'match' => ['/admin/pages/site-items.php'],
                'section' => 'nav',
            ],
        ],
    ],
    [
        'label' => 'Usuários',
        'icon' => 'fas fa-users',
        'href' => '/admin/pages/users.php',
        'match' => ['/admin/pages/users.php'],
    ],
    [
        'label' => 'Ferramentas',
        'icon' => 'fas fa-screwdriver-wrench',
        'href' => '/admin/pages/backups.php',
        'match' => ['/admin/pages/backups.php'],
        'children' => [
            [
                'label' => 'Cópias de segurança',
                'icon' => 'fas fa-cloud-arrow-up',
                'href' => '/admin/pages/backups.php',
                'match' => ['/admin/pages/backups.php'],
            ],
            [
                'label' => 'Sobre',
                'icon' => 'fas fa-circle-info',
                'href' => '/admin/pages/about.php',
                'match' => ['/admin/pages/about.php'],
            ],
        ],
    ],
    [
        'label' => 'Configurações',
        'icon' => 'fas fa-gear',
        'href' => '/admin/pages/settings.php',
        'match' => ['/admin/pages/settings.php'],
    ],
];
?>
<?php $toolbarInitial = cms_toolbar_initial($toolbarUser); ?>
<header class="admin-toolbar" role="banner">
    <div class="admin-toolbar-left">
        <a href="/admin/dashboard.php" class="admin-toolbar-brand" aria-label="Ir para o painel">
            <span class="admin-toolbar-logo">
                <i class="fas fa-sitemap"></i>
            </span>
            <span class="admin-toolbar-title">ChiapettaDev</span>
        </a>
        <a href="/" class="admin-toolbar-link" target="_blank" rel="noopener noreferrer">
            <i class="fas fa-external-link-alt"></i>
            Ver site
        </a>
    </div>

    <div class="admin-toolbar-right">
        <a href="/admin/pages/posts.php?new=1" class="admin-toolbar-action">
            <i class="fas fa-plus"></i>
            Novo post
        </a>
        <a href="/admin/pages/pages.php?new=1" class="admin-toolbar-action">
            <i class="fas fa-file-circle-plus"></i>
            Nova página
        </a>
        <?php if ($toolbarUser): ?>
            <div class="admin-toolbar-user" title="<?= htmlspecialchars($toolbarUser['full_name']) ?>">
                <span class="admin-toolbar-avatar"><?= htmlspecialchars($toolbarInitial) ?></span>
                <span class="admin-toolbar-user-name"><?= htmlspecialchars($toolbarUser['full_name']) ?></span>
            </div>
        <?php endif; ?>
    </div>
</header>

<aside class="admin-sidebar">
    <div class="admin-sidebar-brand">
        <img src="/images/favicon-apple.jpg" alt="ChiapettaDev" class="admin-sidebar-logo">
        <div>
            <h2>ChiapettaDev</h2>
            <p>Painel administrativo</p>
        </div>
    </div>

    <nav class="admin-sidebar-nav" aria-label="Menu administrativo">
        <ul class="admin-sidebar-menu">
            <?php foreach ($adminMenu as $item): ?>
                <?php $groupActive = cms_menu_group_active($currentPath, $currentQuery, $item); ?>
                <li class="admin-sidebar-item <?= $groupActive ? 'active' : '' ?>">
                    <a href="<?= htmlspecialchars($item['href']) ?>" class="admin-sidebar-link <?= cms_menu_item_active($currentPath, $currentQuery, $item) ? 'active' : '' ?>">
                        <i class="<?= htmlspecialchars($item['icon']) ?>"></i>
                        <span><?= htmlspecialchars($item['label']) ?></span>
                        <?php if (!empty($item['children'])): ?>
                            <i class="fas fa-chevron-down admin-sidebar-chevron"></i>
                        <?php endif; ?>
                    </a>

                    <?php if (!empty($item['children'])): ?>
                        <ul class="admin-sidebar-submenu">
                            <?php foreach ($item['children'] as $child): ?>
                                <li>
                                    <a href="<?= htmlspecialchars($child['href']) ?>" class="<?= cms_menu_item_active($currentPath, $currentQuery, $child) ? 'active' : '' ?>">
                                        <i class="<?= htmlspecialchars($child['icon']) ?>"></i>
                                        <span><?= htmlspecialchars($child['label']) ?></span>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </li>
            <?php endforeach; ?>
        </ul>
    </nav>

    <div class="admin-sidebar-footer">
        <a href="/admin/logout.php" class="admin-sidebar-logout">
            <i class="fas fa-right-from-bracket"></i>
            <span>Sair</span>
        </a>
    </div>
</aside>

<main class="admin-main">
<script>
(function () {
    document.body.classList.add('admin-sidebar-collapsed');
})();
</script>
