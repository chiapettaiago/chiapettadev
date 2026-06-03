<?php
header('Content-Type: application/xml; charset=UTF-8');

function sitemap_base_url() {
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (isset($_SERVER['SERVER_PORT']) && intval($_SERVER['SERVER_PORT']) === 443);

    return ($https ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost');
}

function sitemap_entry($loc, $lastmod = null, $changefreq = 'weekly', $priority = '0.5') {
    $xml = "  <url>\n";
    $xml .= '    <loc>' . htmlspecialchars($loc, ENT_XML1 | ENT_QUOTES, 'UTF-8') . "</loc>\n";

    if ($lastmod) {
        $xml .= '    <lastmod>' . htmlspecialchars($lastmod, ENT_XML1 | ENT_QUOTES, 'UTF-8') . "</lastmod>\n";
    }

    $xml .= '    <changefreq>' . htmlspecialchars($changefreq, ENT_XML1 | ENT_QUOTES, 'UTF-8') . "</changefreq>\n";
    $xml .= '    <priority>' . htmlspecialchars($priority, ENT_XML1 | ENT_QUOTES, 'UTF-8') . "</priority>\n";
    $xml .= "  </url>\n";

    return $xml;
}

$baseUrl = sitemap_base_url();
$entries = [
    ['loc' => $baseUrl . '/', 'file' => __DIR__ . '/index.php', 'changefreq' => 'weekly', 'priority' => '1.0'],
    ['loc' => $baseUrl . '/blog/', 'file' => __DIR__ . '/blog/index.php', 'changefreq' => 'daily', 'priority' => '0.9'],
    ['loc' => $baseUrl . '/slides/', 'file' => __DIR__ . '/slides/index.php', 'changefreq' => 'weekly', 'priority' => '0.7'],
    ['loc' => $baseUrl . '/privacidade/', 'file' => __DIR__ . '/privacidade/index.php', 'changefreq' => 'yearly', 'priority' => '0.4'],
    ['loc' => $baseUrl . '/termos/', 'file' => __DIR__ . '/termos/index.php', 'changefreq' => 'yearly', 'priority' => '0.4'],
];

foreach (glob(__DIR__ . '/blog/*/index.php') ?: [] as $file) {
    $slug = basename(dirname($file));
    $entries[] = [
        'loc' => $baseUrl . '/blog/' . $slug . '/',
        'file' => $file,
        'changefreq' => 'weekly',
        'priority' => '0.8'
    ];
}

foreach (glob(__DIR__ . '/slides/*/index.php') ?: [] as $file) {
    $slug = basename(dirname($file));
    $entries[] = [
        'loc' => $baseUrl . '/slides/' . $slug . '/',
        'file' => $file,
        'changefreq' => 'weekly',
        'priority' => '0.7'
    ];
}

echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
echo "<urlset xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\">\n";

foreach ($entries as $entry) {
    $lastmod = is_file($entry['file']) ? date('c', filemtime($entry['file'])) : null;
    echo sitemap_entry($entry['loc'], $lastmod, $entry['changefreq'], $entry['priority']);
}

echo "</urlset>\n";
