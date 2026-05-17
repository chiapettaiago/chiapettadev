<?php
/**
 * Backups locais e envio para Google Drive.
 */

require_once __DIR__ . '/../../db/config.php';

class Backup {
    const VERSION = '1.0.0';
    const BACKUP_DIR = __DIR__ . '/../../backups/';

    public static function ensureSchema() {
        $db = Database::getInstance()->getPDO();

        $db->exec("CREATE TABLE IF NOT EXISTS backup_runs (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            filename TEXT NOT NULL,
            filepath TEXT NOT NULL,
            file_size INTEGER DEFAULT 0,
            status TEXT DEFAULT 'local',
            drive_file_id TEXT,
            message TEXT,
            created_by INTEGER NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (created_by) REFERENCES users(id)
        )");

        @$db->exec("CREATE INDEX IF NOT EXISTS idx_backup_runs_created_at ON backup_runs(created_at)");
    }

    public static function getSettings() {
        self::ensureSchema();
        $settings = Database::getInstance()->select('settings', "key IN ('google_drive_folder_id', 'google_drive_service_account')");
        $indexed = [
            'google_drive_folder_id' => '',
            'google_drive_service_account' => ''
        ];

        foreach ($settings as $setting) {
            $indexed[$setting['key']] = $setting['value'] ?? '';
        }

        return $indexed;
    }

    public static function saveSettings($folderId, $serviceAccountJson) {
        self::ensureSchema();
        self::upsertSetting('google_drive_folder_id', trim($folderId));

        if (trim($serviceAccountJson) !== '') {
            $decoded = json_decode($serviceAccountJson, true);
            if (!is_array($decoded) || empty($decoded['client_email']) || empty($decoded['private_key'])) {
                return ['success' => false, 'message' => 'JSON da Service Account inválido'];
            }

            self::upsertSetting('google_drive_service_account', $serviceAccountJson);
        }

        return ['success' => true, 'message' => 'Configurações de backup salvas com sucesso'];
    }

    public static function create($userId, $uploadToDrive = false) {
        try {
            self::ensureSchema();

            if (!class_exists('ZipArchive')) {
                return ['success' => false, 'message' => 'ZipArchive não está disponível no servidor'];
            }

            $database = self::ensureDatabaseWritable();
            if (!$database['success']) {
                return $database;
            }

            $directory = self::ensureBackupDirectory();
            if (!$directory['success']) {
                return $directory;
            }

            $filename = 'chiapettadev-backup-' . date('Ymd-His') . '.zip';
            $filepath = self::BACKUP_DIR . $filename;
            $zip = new ZipArchive();

            if ($zip->open($filepath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
                return ['success' => false, 'message' => 'Não foi possível criar o arquivo ZIP'];
            }

            self::addFile($zip, __DIR__ . '/../../db/cms.db', 'db/cms.db');
            self::addDirectory($zip, __DIR__ . '/../../images', 'images');
            self::addDirectory($zip, __DIR__ . '/../../admin/uploads', 'admin/uploads');
            self::addDirectory($zip, __DIR__ . '/../../blog', 'blog');
            self::addDirectory($zip, __DIR__ . '/../../slides', 'slides');
            self::addDirectory($zip, __DIR__ . '/../../templates', 'templates');
            self::addDirectory($zip, __DIR__ . '/../../js', 'js');
            self::addFile($zip, __DIR__ . '/../../index.php', 'index.php');
            self::addFile($zip, __DIR__ . '/../../CMS_README.md', 'CMS_README.md');

            $manifest = [
                'created_at' => date('c'),
                'site' => $_SERVER['HTTP_HOST'] ?? 'neverland.chiapetta.dev',
                'cms_backup_version' => self::VERSION,
                'php_version' => PHP_VERSION,
                'database' => 'db/cms.db'
            ];
            $zip->addFromString('backup-manifest.json', json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
            if (!$zip->close()) {
                return ['success' => false, 'message' => 'Não foi possível finalizar o ZIP. Verifique se a pasta backups tem permissão de escrita para o PHP'];
            }

            if (!is_file($filepath)) {
                return ['success' => false, 'message' => 'O ZIP não foi criado no servidor. Verifique as permissões da pasta backups'];
            }

            $fileSize = filesize($filepath) ?: 0;
            $status = 'local';
            $driveFileId = '';
            $message = 'Backup local criado com sucesso';

            if ($uploadToDrive) {
                $upload = self::uploadToDrive($filepath, $filename);
                if (!$upload['success']) {
                    $status = 'drive_failed';
                    $message = 'Backup local criado, mas o envio ao Google Drive falhou: ' . $upload['message'];
                } else {
                    $status = 'drive_uploaded';
                    $driveFileId = $upload['file_id'] ?? '';
                    $message = 'Backup criado e enviado ao Google Drive com sucesso';
                }
            }

            $backupId = Database::getInstance()->insert('backup_runs', [
                'filename' => $filename,
                'filepath' => $filepath,
                'file_size' => $fileSize,
                'status' => $status,
                'drive_file_id' => $driveFileId,
                'message' => $message,
                'created_by' => $userId
            ]);

            return [
                'success' => $status !== 'drive_failed',
                'message' => $message,
                'id' => $backupId,
                'filepath' => $filepath,
                'download_url' => '/admin/pages/backups.php?action=download&id=' . intval($backupId)
            ];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Erro ao gerar backup: ' . $e->getMessage()];
        }
    }

    public static function getList($limit = 50) {
        self::ensureSchema();
        return Database::getInstance()->select('backup_runs', '1=1', 'ORDER BY created_at DESC LIMIT ' . intval($limit));
    }

    public static function getById($id) {
        self::ensureSchema();
        return Database::getInstance()->selectOne('backup_runs', 'id = ?', [intval($id)]);
    }

    public static function restoreFromBackupId($backupId, $userId) {
        $backup = self::getById($backupId);
        if (!$backup || empty($backup['filepath']) || !is_file($backup['filepath'])) {
            return ['success' => false, 'message' => 'Backup selecionado não foi encontrado no servidor'];
        }

        return self::restoreFromFile($backup['filepath'], $userId);
    }

    public static function restoreFromUploadedFile($file, $userId) {
        self::ensureSchema();

        if (empty($file) || ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            return ['success' => false, 'message' => 'Envie um arquivo ZIP de backup válido'];
        }

        $directory = self::ensureBackupDirectory();
        if (!$directory['success']) {
            return $directory;
        }

        $originalName = basename($file['name'] ?? 'backup.zip');
        $filename = 'uploaded-restore-' . date('Ymd-His') . '-' . preg_replace('/[^a-zA-Z0-9._-]/', '-', $originalName);
        $destination = self::BACKUP_DIR . $filename;

        if (!move_uploaded_file($file['tmp_name'], $destination)) {
            if (!copy($file['tmp_name'], $destination)) {
                return ['success' => false, 'message' => 'Não foi possível salvar o backup enviado'];
            }
        }

        Database::getInstance()->insert('backup_runs', [
            'filename' => $filename,
            'filepath' => $destination,
            'file_size' => filesize($destination) ?: 0,
            'status' => 'local',
            'drive_file_id' => '',
            'message' => 'Backup enviado manualmente para restauração',
            'created_by' => $userId
        ]);

        return self::restoreFromFile($destination, $userId);
    }

    private static function restoreFromFile($zipPath, $userId) {
        try {
            self::ensureSchema();

            if (!class_exists('ZipArchive')) {
                return ['success' => false, 'message' => 'ZipArchive não está disponível no servidor'];
            }

            if (!is_file($zipPath)) {
                return ['success' => false, 'message' => 'Arquivo de backup não encontrado'];
            }

            $zip = new ZipArchive();
            if ($zip->open($zipPath) !== true) {
                return ['success' => false, 'message' => 'Não foi possível abrir o ZIP de backup'];
            }

            $validation = self::validateRestoreZip($zip);
            if (!$validation['success']) {
                $zip->close();
                return $validation;
            }

            $preRestore = self::create($userId, false);
            if (!$preRestore['success']) {
                $zip->close();
                return ['success' => false, 'message' => 'Restauração cancelada: não foi possível criar o backup de segurança atual'];
            }

            $tempDir = sys_get_temp_dir() . '/cms-restore-' . bin2hex(random_bytes(8));
            if (!mkdir($tempDir, 0755, true)) {
                $zip->close();
                return ['success' => false, 'message' => 'Não foi possível preparar a pasta temporária de restauração'];
            }

            if (!$zip->extractTo($tempDir)) {
                $zip->close();
                self::removeDirectory($tempDir);
                return ['success' => false, 'message' => 'Não foi possível extrair o backup'];
            }
            $zip->close();

            $root = realpath(__DIR__ . '/../..');
            $restoreMap = [
                'images' => $root . '/images',
                'admin/uploads' => $root . '/admin/uploads',
                'blog' => $root . '/blog',
                'slides' => $root . '/slides',
                'templates' => $root . '/templates',
                'js' => $root . '/js'
            ];

            foreach ($restoreMap as $source => $destination) {
                $sourcePath = $tempDir . '/' . $source;
                if (is_dir($sourcePath)) {
                    self::copyDirectory($sourcePath, $destination);
                }
            }

            foreach (['index.php', 'CMS_README.md'] as $file) {
                $sourcePath = $tempDir . '/' . $file;
                if (is_file($sourcePath)) {
                    copy($sourcePath, $root . '/' . $file);
                }
            }

            $dbSource = $tempDir . '/db/cms.db';
            if (is_file($dbSource)) {
                if (!is_dir($root . '/db')) {
                    mkdir($root . '/db', 0755, true);
                }
                copy($dbSource, $root . '/db/cms.db');
                self::registerPreRestoreBackup($root . '/db/cms.db', $preRestore, $userId);
            }

            self::removeDirectory($tempDir);

            return [
                'success' => true,
                'message' => 'Backup restaurado com sucesso. Um backup do estado anterior foi salvo antes da restauração.'
            ];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Erro ao restaurar backup: ' . $e->getMessage()];
        }
    }

    private static function addFile($zip, $path, $localName) {
        if (is_file($path)) {
            $zip->addFile($path, $localName);
        }
    }

    private static function ensureBackupDirectory() {
        if (!is_dir(self::BACKUP_DIR) && !mkdir(self::BACKUP_DIR, 0777, true)) {
            return ['success' => false, 'message' => 'Não foi possível criar a pasta backups'];
        }

        @chmod(self::BACKUP_DIR, 0777);

        $htaccess = self::BACKUP_DIR . '.htaccess';
        if (!is_file($htaccess) && is_writable(self::BACKUP_DIR)) {
            @file_put_contents($htaccess, "Require all denied\n");
        }

        if (!is_writable(self::BACKUP_DIR)) {
            return ['success' => false, 'message' => 'A pasta backups não está gravável pelo PHP. Ajuste a permissão da pasta no servidor'];
        }

        return ['success' => true];
    }

    private static function ensureDatabaseWritable() {
        if (!is_file(DB_PATH)) {
            return ['success' => false, 'message' => 'Banco de dados não encontrado'];
        }

        @chmod(dirname(DB_PATH), 0777);
        @chmod(DB_PATH, 0666);

        if (!is_writable(dirname(DB_PATH)) || !is_writable(DB_PATH)) {
            return ['success' => false, 'message' => 'O banco SQLite está somente leitura para o PHP. Ajuste as permissões de db/ e db/cms.db'];
        }

        return ['success' => true];
    }

    private static function addDirectory($zip, $path, $localPrefix) {
        if (!is_dir($path)) {
            return;
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $item) {
            if ($item->isDir()) {
                continue;
            }

            $filePath = $item->getPathname();
            $relative = $localPrefix . '/' . ltrim(str_replace($path, '', $filePath), DIRECTORY_SEPARATOR);
            $zip->addFile($filePath, str_replace(DIRECTORY_SEPARATOR, '/', $relative));
        }
    }

    private static function validateRestoreZip($zip) {
        if ($zip->locateName('backup-manifest.json') === false || $zip->locateName('db/cms.db') === false) {
            return ['success' => false, 'message' => 'Este ZIP não parece ser um backup válido deste CMS'];
        }

        $allowedFiles = ['backup-manifest.json', 'db/cms.db', 'index.php', 'CMS_README.md'];
        $allowedPrefixes = ['images/', 'admin/uploads/', 'blog/', 'slides/', 'templates/', 'js/'];

        for ($i = 0; $i < $zip->numFiles; $i++) {
            $entry = str_replace('\\', '/', $zip->getNameIndex($i));

            if ($entry === '' || $entry[0] === '/' || strpos($entry, '../') !== false || strpos($entry, '..') === 0 || substr($entry, -2) === '..') {
                return ['success' => false, 'message' => 'Backup contém caminhos inválidos e foi bloqueado'];
            }

            if (substr($entry, -1) === '/') {
                continue;
            }

            $allowed = in_array($entry, $allowedFiles, true);
            foreach ($allowedPrefixes as $prefix) {
                if (strpos($entry, $prefix) === 0) {
                    $allowed = true;
                    break;
                }
            }

            if (!$allowed) {
                return ['success' => false, 'message' => 'Backup contém arquivos fora da estrutura permitida: ' . $entry];
            }
        }

        return ['success' => true];
    }

    private static function copyDirectory($source, $destination) {
        if (!is_dir($destination)) {
            mkdir($destination, 0755, true);
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($source, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $item) {
            $target = $destination . '/' . ltrim(str_replace($source, '', $item->getPathname()), DIRECTORY_SEPARATOR);

            if ($item->isDir()) {
                if (!is_dir($target)) {
                    mkdir($target, 0755, true);
                }
                continue;
            }

            $targetDir = dirname($target);
            if (!is_dir($targetDir)) {
                mkdir($targetDir, 0755, true);
            }
            copy($item->getPathname(), $target);
        }
    }

    private static function removeDirectory($path) {
        if (!is_dir($path)) {
            return;
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($iterator as $item) {
            if ($item->isDir()) {
                rmdir($item->getPathname());
            } else {
                unlink($item->getPathname());
            }
        }

        rmdir($path);
    }

    private static function registerPreRestoreBackup($dbPath, $preRestore, $userId) {
        if (empty($preRestore['filepath']) || !is_file($preRestore['filepath'])) {
            return;
        }

        try {
            $pdo = new PDO('sqlite:' . $dbPath);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $pdo->exec("CREATE TABLE IF NOT EXISTS backup_runs (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                filename TEXT NOT NULL,
                filepath TEXT NOT NULL,
                file_size INTEGER DEFAULT 0,
                status TEXT DEFAULT 'local',
                drive_file_id TEXT,
                message TEXT,
                created_by INTEGER NOT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )");

            $stmt = $pdo->prepare("INSERT INTO backup_runs (filename, filepath, file_size, status, drive_file_id, message, created_by, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                basename($preRestore['filepath']),
                $preRestore['filepath'],
                filesize($preRestore['filepath']) ?: 0,
                'local',
                '',
                'Backup automático criado antes da restauração',
                $userId,
                date('Y-m-d H:i:s')
            ]);
        } catch (Exception $e) {
            return;
        }
    }

    private static function uploadToDrive($filepath, $filename) {
        $settings = self::getSettings();
        $serviceAccountJson = trim($settings['google_drive_service_account'] ?? '');

        if ($serviceAccountJson === '') {
            return ['success' => false, 'message' => 'Configure a Service Account do Google Drive antes de enviar'];
        }

        if (!extension_loaded('curl') || !extension_loaded('openssl')) {
            return ['success' => false, 'message' => 'Extensões curl e openssl são necessárias'];
        }

        $credentials = json_decode($serviceAccountJson, true);
        if (!is_array($credentials) || empty($credentials['client_email']) || empty($credentials['private_key'])) {
            return ['success' => false, 'message' => 'Credencial da Service Account inválida'];
        }

        $token = self::getGoogleAccessToken($credentials);
        if (!$token['success']) {
            return $token;
        }

        $metadata = [
            'name' => $filename,
            'mimeType' => 'application/zip'
        ];

        if (!empty($settings['google_drive_folder_id'])) {
            $metadata['parents'] = [trim($settings['google_drive_folder_id'])];
        }

        $boundary = 'backup_' . bin2hex(random_bytes(12));
        $body = "--{$boundary}\r\n";
        $body .= "Content-Type: application/json; charset=UTF-8\r\n\r\n";
        $body .= json_encode($metadata, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\r\n";
        $body .= "--{$boundary}\r\n";
        $body .= "Content-Type: application/zip\r\n\r\n";
        $body .= file_get_contents($filepath) . "\r\n";
        $body .= "--{$boundary}--";

        $ch = curl_init('https://www.googleapis.com/upload/drive/v3/files?uploadType=multipart&fields=id,name,webViewLink');
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $token['access_token'],
                'Content-Type: multipart/related; boundary=' . $boundary,
                'Content-Length: ' . strlen($body)
            ],
            CURLOPT_POSTFIELDS => $body,
            CURLOPT_TIMEOUT => 120
        ]);

        $response = curl_exec($ch);
        $error = curl_error($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($response === false || $status < 200 || $status >= 300) {
            return ['success' => false, 'message' => $error ?: ('Google Drive respondeu HTTP ' . $status . ': ' . $response)];
        }

        $decoded = json_decode($response, true);
        return ['success' => true, 'file_id' => $decoded['id'] ?? ''];
    }

    private static function getGoogleAccessToken($credentials) {
        $now = time();
        $header = ['alg' => 'RS256', 'typ' => 'JWT'];
        $claim = [
            'iss' => $credentials['client_email'],
            'scope' => 'https://www.googleapis.com/auth/drive.file',
            'aud' => 'https://oauth2.googleapis.com/token',
            'iat' => $now,
            'exp' => $now + 3600
        ];

        $segments = [
            self::base64UrlEncode(json_encode($header)),
            self::base64UrlEncode(json_encode($claim))
        ];

        $signingInput = implode('.', $segments);
        $signature = '';
        $signed = openssl_sign($signingInput, $signature, $credentials['private_key'], OPENSSL_ALGO_SHA256);

        if (!$signed) {
            return ['success' => false, 'message' => 'Não foi possível assinar o JWT da Service Account'];
        }

        $jwt = $signingInput . '.' . self::base64UrlEncode($signature);

        $ch = curl_init('https://oauth2.googleapis.com/token');
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded'],
            CURLOPT_POSTFIELDS => http_build_query([
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion' => $jwt
            ]),
            CURLOPT_TIMEOUT => 60
        ]);

        $response = curl_exec($ch);
        $error = curl_error($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($response === false || $status < 200 || $status >= 300) {
            return ['success' => false, 'message' => $error ?: ('Falha ao obter token do Google: HTTP ' . $status . ': ' . $response)];
        }

        $decoded = json_decode($response, true);
        if (empty($decoded['access_token'])) {
            return ['success' => false, 'message' => 'Token do Google não retornou access_token'];
        }

        return ['success' => true, 'access_token' => $decoded['access_token']];
    }

    private static function upsertSetting($key, $value) {
        $existing = Database::getInstance()->selectOne('settings', "key = ?", [$key]);
        if ($existing) {
            Database::getInstance()->update('settings', ['value' => $value, 'updated_at' => date('Y-m-d H:i:s')], "key = '" . str_replace("'", "''", $key) . "'");
            return;
        }

        Database::getInstance()->insert('settings', [
            'key' => $key,
            'value' => $value
        ]);
    }

    private static function base64UrlEncode($value) {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }
}
?>
