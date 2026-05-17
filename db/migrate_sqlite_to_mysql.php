<?php
/**
 * Migra o conteudo de db/cms.db para o schema MySQL atual.
 *
 * Uso:
 * php db/migrate_sqlite_to_mysql.php
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('Este script deve ser executado via CLI.');
}

define('CMS_SKIP_AUTO_INIT', true);
require_once __DIR__ . '/config.php';

$sqlitePath = __DIR__ . '/cms.db';
if (!is_file($sqlitePath)) {
    exit("Arquivo SQLite nao encontrado em {$sqlitePath}\n");
}

$tables = [
    'users',
    'posts',
    'pages',
    'images',
    'tags',
    'categories',
    'settings',
    'site_items',
    'site_accesses',
    'comments',
    'slide_decks',
    'slide_items',
    'backup_runs',
    'post_tags',
    'post_categories'
];

$sqlite = new PDO('sqlite:' . $sqlitePath);
$sqlite->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$mysql = Database::getInstance()->getPDO();
$database = Database::getInstance();
$suffix = date('YmdHis');

try {
    $mysql->exec('SET FOREIGN_KEY_CHECKS=0');

    foreach (array_reverse($tables) as $table) {
        if (!$database->tableExists($table)) {
            continue;
        }

        $backupName = $table . '_pre_mysql_migration_' . $suffix;
        $mysql->exec("RENAME TABLE `{$table}` TO `{$backupName}`");
    }

    require __DIR__ . '/init.php';
    $mysql->exec('SET FOREIGN_KEY_CHECKS=0');

    foreach (array_reverse($tables) as $table) {
        if ($database->tableExists($table)) {
            $mysql->exec("DELETE FROM `{$table}`");
        }
    }

    foreach ($tables as $table) {
        $existsInSqlite = $sqlite
            ->prepare("SELECT COUNT(*) FROM sqlite_master WHERE type = 'table' AND name = ?");
        $existsInSqlite->execute([$table]);

        if ((int) $existsInSqlite->fetchColumn() === 0) {
            continue;
        }

        $rows = $sqlite->query("SELECT * FROM {$table}")->fetchAll(PDO::FETCH_ASSOC);
        foreach ($rows as $row) {
            if (empty($row)) {
                continue;
            }

            $columns = array_keys($row);
            $quotedColumns = array_map(fn($column) => '`' . str_replace('`', '``', $column) . '`', $columns);
            $placeholders = implode(', ', array_fill(0, count($columns), '?'));
            $sql = "INSERT INTO `{$table}` (" . implode(', ', $quotedColumns) . ") VALUES ({$placeholders})";
            $stmt = $mysql->prepare($sql);
            $stmt->execute(array_values($row));
        }

        echo "Migrado: {$table} (" . count($rows) . " registros)\n";
    }

    $mysql->exec('SET FOREIGN_KEY_CHECKS=1');
    echo "Migracao SQLite -> MySQL concluida com sucesso.\n";
    echo "Tabelas MySQL anteriores foram renomeadas com sufixo _pre_mysql_migration_{$suffix}.\n";
} catch (Exception $e) {
    try {
        $mysql->exec('SET FOREIGN_KEY_CHECKS=1');
    } catch (Exception $ignored) {
    }

    fwrite(STDERR, "Erro na migracao: " . $e->getMessage() . "\n");
    exit(1);
}
?>
