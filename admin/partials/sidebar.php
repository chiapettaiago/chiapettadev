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
        'match' => ['/admin/pages/posts.php', '/admin/pages/categories.php'],
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
            [
                'label' => 'Categorias',
                'icon' => 'fas fa-folder-tree',
                'href' => '/admin/pages/categories.php',
                'match' => ['/admin/pages/categories.php'],
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
<link rel="stylesheet" href="/admin/assets/admin.css?v=20260805e">
<style id="adminMobileNavigationCritical">
@media (max-width: 768px) {
    html,
    body {
        width: 100% !important;
        max-width: 100% !important;
        overflow-x: clip !important;
    }

    .admin-mobile-bottom-nav {
        position: fixed !important;
        top: auto !important;
        right: 0 !important;
        bottom: 0 !important;
        left: 0 !important;
        width: auto !important;
        min-width: 0 !important;
        max-width: 100% !important;
        height: calc(var(--cms-mobile-nav-height, 72px) + env(safe-area-inset-bottom)) !important;
        min-height: 0 !important;
        max-height: calc(var(--cms-mobile-nav-height, 72px) + env(safe-area-inset-bottom)) !important;
        margin: 0 !important;
        padding: 0.38rem max(0.35rem, env(safe-area-inset-right)) calc(0.38rem + env(safe-area-inset-bottom)) max(0.35rem, env(safe-area-inset-left)) !important;
        box-sizing: border-box !important;
        z-index: 2147483646 !important;
        display: grid !important;
        grid-template-columns: repeat(5, minmax(0, 1fr)) !important;
        grid-template-rows: minmax(0, 1fr) !important;
        align-items: stretch !important;
        overflow: visible !important;
        visibility: visible !important;
        opacity: 1 !important;
        transform: none !important;
        pointer-events: auto !important;
    }

    .admin-mobile-bottom-nav > a,
    .admin-mobile-bottom-nav > button {
        width: auto !important;
        min-width: 0 !important;
        max-width: none !important;
        height: 100% !important;
        min-height: 0 !important;
        margin: 0 !important;
        box-sizing: border-box !important;
    }

    .admin-main,
    body.admin-sidebar-collapsed .admin-main {
        min-width: 0 !important;
        overflow-x: clip !important;
    }

    .admin-mobile-menu-overlay {
        bottom: calc(var(--cms-mobile-nav-height, 72px) + env(safe-area-inset-bottom)) !important;
        z-index: 2147483000 !important;
    }

    .admin-toolbar,
    .admin-sidebar {
        display: none !important;
    }
}

@media (min-width: 769px) {
    .admin-mobile-bottom-nav,
    .admin-mobile-menu-overlay {
        display: none !important;
    }
}
</style>
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

<?php
$mobileMoreActive = !in_array($currentPath, [
    '/admin/',
    '/admin/dashboard.php',
    '/admin/pages/posts.php',
    '/admin/pages/images.php'
], true);
?>
<nav class="admin-mobile-bottom-nav" aria-label="Navegação administrativa móvel">
    <a href="/admin/dashboard.php" class="<?= in_array($currentPath, ['/admin/', '/admin/dashboard.php'], true) ? 'active' : '' ?>">
        <i class="fas fa-gauge-high"></i>
        <span>Painel</span>
    </a>
    <a href="/admin/pages/posts.php" class="<?= $currentPath === '/admin/pages/posts.php' && !isset($currentQuery['new']) ? 'active' : '' ?>">
        <i class="fas fa-pen-to-square"></i>
        <span>Posts</span>
    </a>
    <a href="/admin/pages/posts.php?new=1" class="admin-mobile-create <?= $currentPath === '/admin/pages/posts.php' && (($currentQuery['new'] ?? '') === '1') ? 'active' : '' ?>">
        <i class="fas fa-plus"></i>
        <span>Novo</span>
    </a>
    <a href="/admin/pages/images.php" class="<?= $currentPath === '/admin/pages/images.php' ? 'active' : '' ?>">
        <i class="fas fa-images"></i>
        <span>Mídia</span>
    </a>
    <button type="button" id="adminMobileMoreButton" class="<?= $mobileMoreActive ? 'active' : '' ?>" aria-expanded="false" aria-controls="adminMobileMenuSheet">
        <i class="fas fa-grip"></i>
        <span>Mais</span>
    </button>
</nav>

<div class="admin-mobile-menu-overlay" id="adminMobileMenuOverlay" hidden>
    <section class="admin-mobile-menu-sheet" id="adminMobileMenuSheet" role="dialog" aria-modal="true" aria-labelledby="adminMobileMenuTitle">
        <header>
            <div>
                <span class="admin-mobile-sheet-kicker">ChiapettaDev</span>
                <h2 id="adminMobileMenuTitle">Todas as opções</h2>
            </div>
            <button type="button" class="admin-mobile-sheet-close" id="adminMobileMenuClose" aria-label="Fechar menu">
                <i class="fas fa-times"></i>
            </button>
        </header>

        <div class="admin-mobile-sheet-content">
            <ul class="admin-mobile-all-menu">
                <?php foreach ($adminMenu as $item): ?>
                    <?php $groupActive = cms_menu_group_active($currentPath, $currentQuery, $item); ?>
                    <li class="<?= $groupActive ? 'active' : '' ?>">
                        <a href="<?= htmlspecialchars($item['href']) ?>">
                            <span class="admin-mobile-menu-icon"><i class="<?= htmlspecialchars($item['icon']) ?>"></i></span>
                            <span><?= htmlspecialchars($item['label']) ?></span>
                            <i class="fas fa-chevron-right"></i>
                        </a>
                        <?php if (!empty($item['children'])): ?>
                            <ul>
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
        </div>

        <footer>
            <a href="/" target="_blank" rel="noopener"><i class="fas fa-arrow-up-right-from-square"></i>Ver site</a>
            <a href="/admin/logout.php" class="admin-mobile-logout"><i class="fas fa-right-from-bracket"></i>Sair</a>
        </footer>
    </section>
</div>

<main class="admin-main">
<script>
(function () {
    document.body.classList.add('admin-sidebar-collapsed');

    const desktopSidebar = document.querySelector('.admin-sidebar');
    const desktopToolbar = document.querySelector('.admin-toolbar');
    const mobileBottomNav = document.querySelector('.admin-mobile-bottom-nav');
    const moreButton = document.getElementById('adminMobileMoreButton');
    const overlay = document.getElementById('adminMobileMenuOverlay');
    const closeButton = document.getElementById('adminMobileMenuClose');

    function syncAuthenticatedMobileLayout() {
        const isMobile = window.matchMedia('(max-width: 768px)').matches;
        [desktopSidebar, desktopToolbar].forEach(function (element) {
            if (!element) return;
            if (isMobile) {
                element.setAttribute('aria-hidden', 'true');
                element.style.setProperty('display', 'none', 'important');
            } else {
                element.removeAttribute('aria-hidden');
                element.style.removeProperty('display');
            }
        });

        if (mobileBottomNav) {
            if (isMobile) {
                mobileBottomNav.removeAttribute('aria-hidden');
                mobileBottomNav.style.setProperty('display', 'grid', 'important');
                mobileBottomNav.style.setProperty('position', 'fixed', 'important');
                mobileBottomNav.style.setProperty('bottom', '0', 'important');
                mobileBottomNav.style.setProperty('visibility', 'visible', 'important');
                mobileBottomNav.style.setProperty('opacity', '1', 'important');
            } else {
                mobileBottomNav.setAttribute('aria-hidden', 'true');
                mobileBottomNav.style.setProperty('display', 'none', 'important');
            }
        }

        if (!isMobile && !overlay.hidden) closeMobileMenu();
    }

    function openMobileMenu() {
        overlay.hidden = false;
        requestAnimationFrame(function () {
            overlay.classList.add('active');
            document.body.classList.add('admin-mobile-menu-open');
            moreButton.setAttribute('aria-expanded', 'true');
            closeButton.focus();
        });
    }

    function closeMobileMenu() {
        overlay.classList.remove('active');
        document.body.classList.remove('admin-mobile-menu-open');
        moreButton.setAttribute('aria-expanded', 'false');
        window.setTimeout(function () {
            overlay.hidden = true;
        }, 220);
    }

    moreButton.addEventListener('click', openMobileMenu);
    closeButton.addEventListener('click', closeMobileMenu);
    overlay.addEventListener('click', function (event) {
        if (event.target === overlay) closeMobileMenu();
    });
    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && !overlay.hidden) closeMobileMenu();
    });
    window.addEventListener('resize', syncAuthenticatedMobileLayout, { passive: true });
    syncAuthenticatedMobileLayout();
})();
</script>
