<?php
/**
 * Métricas simples de acesso às páginas públicas.
 */

require_once __DIR__ . '/../../db/config.php';

class SiteAccess {
    public static function ensureSchema() {
        $db = Database::getInstance()->getPDO();

        $db->exec("CREATE TABLE IF NOT EXISTS site_accesses (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            path TEXT NOT NULL,
            title TEXT,
            referrer TEXT,
            user_agent TEXT,
            ip_hash TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )");

        @$db->exec("CREATE INDEX IF NOT EXISTS idx_site_accesses_created_at ON site_accesses(created_at)");
        @$db->exec("CREATE INDEX IF NOT EXISTS idx_site_accesses_path ON site_accesses(path)");
    }

    public static function trackPublicPage($title = null) {
        if (php_sapi_name() === 'cli' || ($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET') {
            return;
        }

        $path = self::currentPath();

        if (!self::isPublicPage($path)) {
            return;
        }

        try {
            self::ensureSchema();

            Database::getInstance()->insert('site_accesses', [
                'path' => $path,
                'title' => $title,
                'referrer' => substr($_SERVER['HTTP_REFERER'] ?? '', 0, 500),
                'user_agent' => substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 500),
                'ip_hash' => self::hashIp($_SERVER['REMOTE_ADDR'] ?? ''),
                'created_at' => date('Y-m-d H:i:s')
            ]);
        } catch (Exception $e) {
            // Analytics não deve impedir a página pública de carregar.
        }
    }

    public static function getPeriodOptions() {
        return [
            'today' => 'Hoje',
            '7d' => '7 dias',
            '30d' => '30 dias',
            '90d' => '90 dias',
            'all' => 'Todo período'
        ];
    }

    public static function normalizePeriod($period) {
        return array_key_exists($period, self::getPeriodOptions()) ? $period : '30d';
    }

    public static function getTotal($period = '30d') {
        self::ensureSchema();
        $range = self::periodRange($period);
        $where = $range['where'];
        $params = $range['params'];

        return intval(Database::getInstance()
            ->query("SELECT COUNT(*) FROM site_accesses WHERE $where", $params)
            ->fetchColumn());
    }

    public static function getSeries($period = '30d') {
        self::ensureSchema();
        $range = self::periodRange($period);

        $rows = Database::getInstance()
            ->query(
                "SELECT DATE(created_at) AS day, COUNT(*) AS total
                 FROM site_accesses
                 WHERE {$range['where']}
                 GROUP BY DATE(created_at)
                 ORDER BY day ASC",
                $range['params']
            )
            ->fetchAll(PDO::FETCH_ASSOC);

        if ($period === 'all') {
            return [
                'labels' => array_map(fn($row) => date('d/m/Y', strtotime($row['day'])), $rows),
                'values' => array_map(fn($row) => intval($row['total']), $rows)
            ];
        }

        $days = self::daysForPeriod($period);
        $indexed = [];

        foreach ($rows as $row) {
            $indexed[$row['day']] = intval($row['total']);
        }

        $labels = [];
        $values = [];

        for ($i = $days - 1; $i >= 0; $i--) {
            $day = date('Y-m-d', strtotime("-$i days"));
            $labels[] = date('d/m', strtotime($day));
            $values[] = $indexed[$day] ?? 0;
        }

        return ['labels' => $labels, 'values' => $values];
    }

    private static function currentPath() {
        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        $path = parse_url($uri, PHP_URL_PATH) ?: '/';
        return '/' . ltrim($path, '/');
    }

    private static function isPublicPage($path) {
        if (str_starts_with($path, '/admin')) {
            return false;
        }

        return !preg_match('/\.(?:css|js|json|xml|txt|png|jpe?g|gif|webp|svg|ico|pdf|zip|woff2?|ttf|eot)$/i', $path);
    }

    private static function hashIp($ip) {
        if ($ip === '') {
            return null;
        }

        return hash('sha256', $ip . '|' . DB_PATH);
    }

    private static function periodRange($period) {
        $period = self::normalizePeriod($period);

        if ($period === 'all') {
            return ['where' => '1=1', 'params' => []];
        }

        if ($period === 'today') {
            return ['where' => 'created_at >= ?', 'params' => [date('Y-m-d 00:00:00')]];
        }

        $days = self::daysForPeriod($period);
        return ['where' => 'created_at >= ?', 'params' => [date('Y-m-d 00:00:00', strtotime('-' . ($days - 1) . ' days'))]];
    }

    private static function daysForPeriod($period) {
        return match (self::normalizePeriod($period)) {
            'today' => 1,
            '7d' => 7,
            '90d' => 90,
            default => 30
        };
    }
}
?>
