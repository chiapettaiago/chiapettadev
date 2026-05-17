<?php
/**
 * Configuracao do Banco de Dados - CMS ChiapettaDev
 */

define('DB_TYPE', 'mysql');
define('DB_HOST', '127.0.0.1');
define('DB_USER', 'neverland');
define('DB_PASS', 'EbATDwkiNkwxHjhJ');
define('DB_NAME', 'neverland');

define('ADMIN_PATH', '/admin/');
define('UPLOADS_PATH', __DIR__ . '/../admin/uploads/');
define('UPLOADS_URL', '/admin/uploads/');

define('SESSION_NAME', 'cms_admin');
define('SESSION_TIMEOUT', 3600);

define('PUBLIC_IMAGES_PATH', __DIR__ . '/../images/');
define('PUBLIC_IMAGES_URL', '/images/');

class Database {
    private static $instance = null;
    private $pdo;

    private function __construct() {
        try {
            $this->pdo = new PDO(
                'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
                DB_USER,
                DB_PASS,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false
                ]
            );
        } catch (PDOException $e) {
            die('Erro de conexao com banco de dados: ' . $e->getMessage());
        }
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public function getPDO() {
        return $this->pdo;
    }

    public function query($sql, $params = []) {
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            throw new Exception('Erro na query: ' . $e->getMessage());
        }
    }

    public function insert($table, $data) {
        $columns = implode(', ', array_map([$this, 'quoteIdentifier'], array_keys($data)));
        $placeholders = implode(', ', array_fill(0, count($data), '?'));

        $sql = "INSERT INTO $table ($columns) VALUES ($placeholders)";
        $stmt = $this->query($sql, array_values($data));

        return $this->pdo->lastInsertId();
    }

    public function update($table, $data, $where) {
        $set = implode(', ', array_map(function($key) {
            return $this->quoteIdentifier($key) . ' = ?';
        }, array_keys($data)));

        $sql = "UPDATE $table SET $set WHERE $where";
        return $this->query($sql, array_values($data));
    }

    public function delete($table, $where) {
        $sql = "DELETE FROM $table WHERE $where";
        return $this->query($sql);
    }

    public function select($table, $where = '', $orderLimit = '') {
        $sql = "SELECT * FROM $table";
        if (!empty($where)) {
            $sql .= ' WHERE ' . $where;
        }
        if (!empty($orderLimit)) {
            $sql .= ' ' . $orderLimit;
        }

        return $this->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    public function selectOne($table, $where, $params = []) {
        $sql = "SELECT * FROM $table WHERE $where LIMIT 1";
        return $this->query($sql, $params)->fetch(PDO::FETCH_ASSOC);
    }

    public function tableExists($table) {
        $stmt = $this->query(
            "SELECT COUNT(*)
             FROM information_schema.tables
             WHERE table_schema = DATABASE() AND table_name = ?",
            [$table]
        );

        return (int) $stmt->fetchColumn() > 0;
    }

    public function indexExists($table, $index) {
        $stmt = $this->query(
            "SELECT COUNT(*)
             FROM information_schema.statistics
             WHERE table_schema = DATABASE() AND table_name = ? AND index_name = ?",
            [$table, $index]
        );

        return (int) $stmt->fetchColumn() > 0;
    }

    public function createIndexIfMissing($table, $index, $columns) {
        if ($this->indexExists($table, $index)) {
            return;
        }

        $this->query("CREATE INDEX `$index` ON `$table` ($columns)");
    }

    private function quoteIdentifier($identifier) {
        return '`' . str_replace('`', '``', $identifier) . '`';
    }
}

if (!defined('CMS_SKIP_AUTO_INIT') || CMS_SKIP_AUTO_INIT !== true) {
    $requiredTables = ['users', 'posts', 'pages', 'images', 'settings', 'site_items', 'comments', 'slide_decks', 'backup_runs'];
    $hasSchema = true;

    foreach ($requiredTables as $requiredTable) {
        if (!Database::getInstance()->tableExists($requiredTable)) {
            $hasSchema = false;
            break;
        }
    }

    if (!$hasSchema) {
        require_once __DIR__ . '/init.php';
    }
}
?>
