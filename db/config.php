<?php
/**
 * Configuração do Banco de Dados - CMS ChiapettaDev
 */

define('DB_PATH', __DIR__ . '/cms.db');
define('DB_TYPE', 'sqlite'); // sqlite ou mysql

// Configurações MySQL (se necessário mudar futuramente)
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'chiapetta_cms');

// Segurança
define('ADMIN_PATH', '/admin/');
define('UPLOADS_PATH', __DIR__ . '/../admin/uploads/');
define('UPLOADS_URL', '/admin/uploads/');

// Configurações de sessão
define('SESSION_NAME', 'cms_admin');
define('SESSION_TIMEOUT', 3600); // 1 hora

// Pasta de imagens públicas
define('PUBLIC_IMAGES_PATH', __DIR__ . '/../images/');
define('PUBLIC_IMAGES_URL', '/images/');

/**
 * Função de conexão com banco de dados
 */
class Database {
    private static $instance = null;
    private $pdo;

    private function __construct() {
        try {
            if (DB_TYPE === 'sqlite') {
                // Inicializar SQLite
                $this->pdo = new PDO('sqlite:' . DB_PATH);
                $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                
                // Habilitar foreign keys
                $this->pdo->exec("PRAGMA foreign_keys = ON");
            } else {
                // MySQL
                $this->pdo = new PDO(
                    'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
                    DB_USER,
                    DB_PASS,
                    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
                );
            }
        } catch (PDOException $e) {
            die('Erro de conexão com banco de dados: ' . $e->getMessage());
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
        $columns = implode(', ', array_keys($data));
        $placeholders = implode(', ', array_fill(0, count($data), '?'));
        
        $sql = "INSERT INTO $table ($columns) VALUES ($placeholders)";
        $stmt = $this->query($sql, array_values($data));
        
        return $this->pdo->lastInsertId();
    }

    public function update($table, $data, $where) {
        $set = implode(', ', array_map(function($key) {
            return "$key = ?";
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
            $sql .= " WHERE " . $where;
        }
        if (!empty($orderLimit)) {
            $sql .= " " . $orderLimit;
        }
        
        return $this->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    public function selectOne($table, $where, $params = []) {
        $sql = "SELECT * FROM $table WHERE $where LIMIT 1";
        return $this->query($sql, $params)->fetch(PDO::FETCH_ASSOC);
    }
}

// Auto-inicializar banco se não existir
if (!file_exists(DB_PATH) || filesize(DB_PATH) === 0) {
    require_once __DIR__ . '/init.php';
}
?>
