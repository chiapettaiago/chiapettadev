<?php
/**
 * Dashboard Admin - CMS ChiapettaDev
 */

require_once __DIR__ . '/../db/config.php';
require_once __DIR__ . '/modules/Auth.php';
require_once __DIR__ . '/modules/Post.php';
require_once __DIR__ . '/modules/Page.php';
require_once __DIR__ . '/modules/Image.php';
require_once __DIR__ . '/modules/SiteItem.php';
require_once __DIR__ . '/modules/SiteAccess.php';
require_once __DIR__ . '/modules/ExistingContentImporter.php';

// Verificar autenticação
if (!Auth::hasPermission('author')) {
    header('Location: /admin/login.php');
    exit;
}

$user = Auth::getCurrentUser();

ExistingContentImporter::ensureImported();

// Obter estatísticas
$totalPosts = count(Database::getInstance()->select('posts', "1=1"));
$publishedPosts = count(Database::getInstance()->select('posts', "status = 'published'"));
$totalPages = count(Database::getInstance()->select('pages', "1=1"));
$totalImages = count(Database::getInstance()->select('images', "1=1"));
$totalUsers = count(Database::getInstance()->select('users', "1=1"));
$totalSiteItems = count(SiteItem::getList());
$accessPeriod = SiteAccess::normalizePeriod($_GET['access_period'] ?? '30d');
$accessPeriodOptions = SiteAccess::getPeriodOptions();
$accessTotal = SiteAccess::getTotal($accessPeriod);
$accessSeries = SiteAccess::getSeries($accessPeriod);

// Obter posts recentes
$recentPosts = Post::getList(['limit' => 5]);

// Obter páginas recentes
$recentPages = Page::getList(['limit' => 5]);

// Obter imagens recentes
$recentImages = Image::getList(['limit' => 8]);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard | CMS ChiapettaDev</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="icon" href="/images/favicon-apple.jpg" type="image/jpeg">
    <style>
        :root {
            --primary: #1a1a1a;
            --secondary: #2d2d2d;
            --accent: #00d9a3;
            --text: #ffffff;
            --text-muted: #b0b0b0;
            --danger: #dc3545;
            --warning: #ffc107;
            --info: #0dcaf0;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background: var(--primary);
            color: var(--text);
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
        }

        .topbar {
            background: var(--secondary);
            padding: 1rem 2rem;
            border-radius: 12px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            border: 1px solid rgba(0, 217, 163, 0.1);
        }

        .topbar-left h1 {
            font-size: 1.8rem;
            margin: 0;
        }

        .topbar-right {
            display: flex;
            gap: 1rem;
            align-items: center;
        }

        .user-profile {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.5rem 1rem;
            background: rgba(0, 217, 163, 0.05);
            border-radius: 6px;
            font-size: 0.9rem;
        }

        .user-profile strong {
            color: var(--accent);
        }

        .btn-logout {
            padding: 0.5rem 1rem;
            background: var(--danger);
            border: none;
            border-radius: 6px;
            color: white;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-logout:hover {
            background: #bb2d3b;
            transform: translateY(-2px);
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: var(--secondary);
            border: 1px solid rgba(0, 217, 163, 0.1);
            border-radius: 12px;
            padding: 1.5rem;
            transition: all 0.3s ease;
        }

        .stat-card:hover {
            border-color: var(--accent);
            transform: translateY(-5px);
        }

        .stat-icon {
            font-size: 2rem;
            color: var(--accent);
            margin-bottom: 1rem;
        }

        .stat-label {
            color: var(--text-muted);
            font-size: 0.9rem;
            margin-bottom: 0.5rem;
        }

        .stat-number {
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--text);
        }

        .section {
            background: var(--secondary);
            border: 1px solid rgba(0, 217, 163, 0.1);
            border-radius: 12px;
            padding: 2rem;
            margin-bottom: 2rem;
        }

        .section-title {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 1.5rem;
            color: var(--text);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .section-actions {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .period-filter {
            display: flex;
            align-items: center;
            gap: 0.6rem;
            margin: 0;
        }

        .period-filter label {
            color: var(--text-muted);
            font-size: 0.9rem;
            font-weight: 500;
        }

        .period-filter select {
            min-width: 150px;
            background: var(--primary);
            color: var(--text);
            border: 1px solid rgba(0, 217, 163, 0.2);
            border-radius: 6px;
            padding: 0.5rem 0.75rem;
            font-weight: 600;
        }

        .chart-wrap {
            height: 320px;
            position: relative;
        }

        .btn-primary {
            background: var(--accent);
            color: #000;
            border: none;
            border-radius: 6px;
            padding: 0.6rem 1.2rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(0, 217, 163, 0.3);
        }

        .table {
            color: var(--text);
        }

        .table thead {
            border-bottom: 2px solid rgba(0, 217, 163, 0.2);
        }

        .table th {
            color: var(--accent);
            font-weight: 600;
            border-bottom: 2px solid rgba(0, 217, 163, 0.2);
        }

        .table td {
            border-bottom: 1px solid rgba(0, 217, 163, 0.1);
            padding: 1rem;
        }

        .table tbody tr:hover {
            background: rgba(0, 217, 163, 0.05);
        }

        .badge {
            padding: 0.35rem 0.75rem;
            border-radius: 4px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .badge-published {
            background: rgba(0, 217, 163, 0.2);
            color: var(--accent);
        }

        .badge-draft {
            background: rgba(255, 193, 7, 0.2);
            color: #ffc107;
        }

        .gallery {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            gap: 1rem;
        }

        .gallery-item {
            background: var(--primary);
            border-radius: 8px;
            overflow: hidden;
            cursor: pointer;
            transition: all 0.3s ease;
            border: 1px solid rgba(0, 217, 163, 0.1);
        }

        .gallery-item:hover {
            border-color: var(--accent);
            transform: scale(1.05);
        }

        .gallery-item img {
            width: 100%;
            height: 150px;
            object-fit: cover;
            display: block;
        }

        .gallery-item-title {
            padding: 0.75rem;
            font-size: 0.85rem;
            color: var(--text-muted);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        @media (max-width: 768px) {
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 1rem;
            }

            .topbar {
                flex-direction: column;
                align-items: flex-start;
                gap: 1rem;
            }

            .topbar-right {
                width: 100%;
                justify-content: space-between;
            }

            .section-title {
                align-items: flex-start;
                flex-direction: column;
                gap: 1rem;
            }

            .section-actions,
            .period-filter {
                width: 100%;
            }

            .period-filter select {
                flex: 1;
            }
        }

        @media (max-width: 480px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
    <link rel="stylesheet" href="/admin/assets/admin.css?v=20260516">
</head>
<body>
    <?php include __DIR__ . '/partials/sidebar.php'; ?>

    <div class="container">
        <!-- Topbar -->
        <div class="topbar">
            <div class="topbar-left">
                <h1>Dashboard</h1>
            </div>
            <div class="topbar-right">
                <div class="user-profile">
                    <i class="fas fa-user-circle"></i>
                    <span><strong><?= htmlspecialchars($user['full_name']) ?></strong> (<?= $user['role'] ?>)</span>
                </div>
                <a href="/admin/logout.php" class="btn-logout">
                    <i class="fas fa-sign-out-alt"></i>Sair
                </a>
            </div>
        </div>

        <!-- Stats -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-file-alt"></i></div>
                <div class="stat-label">Posts Publicados</div>
                <div class="stat-number"><?= $publishedPosts ?></div>
            </div>

            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-file-lines"></i></div>
                <div class="stat-label">Total de Posts</div>
                <div class="stat-number"><?= $totalPosts ?></div>
            </div>

            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-file"></i></div>
                <div class="stat-label">Páginas</div>
                <div class="stat-number"><?= $totalPages ?></div>
            </div>

            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-layer-group"></i></div>
                <div class="stat-label">Itens do Site</div>
                <div class="stat-number"><?= $totalSiteItems ?></div>
            </div>

            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-image"></i></div>
                <div class="stat-label">Imagens</div>
                <div class="stat-number"><?= $totalImages ?></div>
            </div>

            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-users"></i></div>
                <div class="stat-label">Usuários</div>
                <div class="stat-number"><?= $totalUsers ?></div>
            </div>

            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-eye"></i></div>
                <div class="stat-label">Acessos <?= htmlspecialchars($accessPeriodOptions[$accessPeriod]) ?></div>
                <div class="stat-number"><?= $accessTotal ?></div>
            </div>
        </div>

        <!-- Acessos ao Site -->
        <div class="section">
            <div class="section-title">
                <span>Acessos ao Site</span>
                <div class="section-actions">
                    <form method="GET" class="period-filter">
                        <label for="access_period">Período</label>
                        <select id="access_period" name="access_period" onchange="this.form.submit()">
                            <?php foreach ($accessPeriodOptions as $value => $label): ?>
                                <option value="<?= htmlspecialchars($value) ?>" <?= $accessPeriod === $value ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($label) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </form>
                </div>
            </div>

            <div class="chart-wrap">
                <canvas id="accessChart"></canvas>
            </div>
        </div>

        <!-- Posts Recentes -->
        <div class="section">
            <div class="section-title">
                <span>Posts Recentes</span>
                <a href="/admin/pages/posts.php" class="btn-primary">
                    <i class="fas fa-plus"></i>Novo Post
                </a>
            </div>

            <?php if (!empty($recentPosts)): ?>
                <table class="table">
                    <thead>
                        <tr>
                            <th>Título</th>
                            <th>Status</th>
                            <th>Autor</th>
                            <th>Data</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentPosts as $post): ?>
                            <tr>
                                <td><?= htmlspecialchars($post['title']) ?></td>
                                <td>
                                    <span class="badge badge-<?= $post['status'] ?>">
                                        <?= ucfirst($post['status']) ?>
                                    </span>
                                </td>
                                <td>
                                    <?php
                                    $author = Database::getInstance()->selectOne('users', "id = {$post['author_id']}");
                                    echo htmlspecialchars($author['full_name'] ?? 'Desconhecido');
                                    ?>
                                </td>
                                <td><?= date('d/m/Y', strtotime($post['created_at'])) ?></td>
                                <td>
                                    <a href="/admin/pages/posts.php?edit=<?= $post['id'] ?>" class="btn-primary" style="font-size: 0.85rem;">
                                        <i class="fas fa-edit"></i>Editar
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p style="color: var(--text-muted); text-align: center; padding: 2rem;">
                    Nenhum post criado ainda. <a href="/admin/pages/posts.php" style="color: var(--accent);">Criar primeiro post</a>
                </p>
            <?php endif; ?>
        </div>

        <!-- Imagens Recentes -->
        <div class="section">
            <div class="section-title">
                <span>Imagens Recentes</span>
                <a href="/admin/pages/images.php" class="btn-primary">
                    <i class="fas fa-plus"></i>Upload
                </a>
            </div>

            <?php if (!empty($recentImages)): ?>
                <div class="gallery">
                    <?php foreach ($recentImages as $image): ?>
                        <div class="gallery-item" title="<?= htmlspecialchars($image['title']) ?>">
                            <img src="<?= htmlspecialchars($image['filepath']) ?>" alt="<?= htmlspecialchars($image['alt_text']) ?>">
                            <div class="gallery-item-title"><?= htmlspecialchars($image['title']) ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p style="color: var(--text-muted); text-align: center; padding: 2rem;">
                    Nenhuma imagem enviada ainda. <a href="/admin/pages/images.php" style="color: var(--accent);">Fazer upload</a>
                </p>
            <?php endif; ?>
        </div>
    </div>
    <?php include __DIR__ . '/partials/sidebar-close.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>
    <script>
        const accessChartElement = document.getElementById('accessChart');

        if (accessChartElement && typeof Chart !== 'undefined') {
            new Chart(accessChartElement, {
                type: 'line',
                data: {
                    labels: <?= json_encode($accessSeries['labels'], JSON_UNESCAPED_UNICODE) ?>,
                    datasets: [{
                        label: 'Acessos',
                        data: <?= json_encode($accessSeries['values'], JSON_UNESCAPED_UNICODE) ?>,
                        borderColor: '#00d9a3',
                        backgroundColor: 'rgba(0, 217, 163, 0.14)',
                        pointBackgroundColor: '#00d9a3',
                        pointBorderColor: '#10141b',
                        pointHoverRadius: 6,
                        borderWidth: 3,
                        fill: true,
                        tension: 0.35
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            labels: { color: '#f4f7fb' }
                        },
                        tooltip: {
                            backgroundColor: '#10141b',
                            borderColor: 'rgba(0, 217, 163, 0.35)',
                            borderWidth: 1,
                            titleColor: '#f4f7fb',
                            bodyColor: '#f4f7fb'
                        }
                    },
                    scales: {
                        x: {
                            ticks: { color: '#9aa6b6' },
                            grid: { color: 'rgba(255, 255, 255, 0.06)' }
                        },
                        y: {
                            beginAtZero: true,
                            ticks: {
                                color: '#9aa6b6',
                                precision: 0
                            },
                            grid: { color: 'rgba(255, 255, 255, 0.06)' }
                        }
                    }
                }
            });
        }
    </script>
</body>
</html>
