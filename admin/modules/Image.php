<?php
/**
 * Gerenciador de Imagens
 */

require_once __DIR__ . '/../../db/config.php';

class Image {
    // Configurações de upload
    const MAX_FILE_SIZE = 5242880; // 5MB
    const ALLOWED_TYPES = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    const ALLOWED_EXTENSIONS = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

    /**
     * Upload de imagem
     */
    public static function upload($file, $userId, $metadata = []) {
        try {
            // Validar arquivo
            $validation = self::validateFile($file);
            if (!$validation['success']) {
                return $validation;
            }

            // Validar tamanho
            if ($file['size'] > self::MAX_FILE_SIZE) {
                return ['success' => false, 'message' => 'Arquivo muito grande (máximo 5MB)'];
            }

            // Gerar nome único para arquivo
            $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
            $filename = uniqid('img_') . '.' . strtolower($extension);
            $filepath = UPLOADS_PATH . $filename;

            // Criar diretório se não existir
            if (!is_dir(UPLOADS_PATH)) {
                if (!mkdir(UPLOADS_PATH, 0755, true)) {
                    return ['success' => false, 'message' => 'Não foi possível criar a pasta de uploads'];
                }
            }

            if (!is_writable(UPLOADS_PATH)) {
                return ['success' => false, 'message' => 'A pasta de uploads não tem permissão de escrita para o PHP'];
            }

            // Mover arquivo
            if (!move_uploaded_file($file['tmp_name'], $filepath)) {
                return ['success' => false, 'message' => 'Erro ao fazer upload do arquivo'];
            }

            // Processar imagem (redimensionar se necessário)
            self::optimizeImage($filepath, $extension);

            // Salvar informações no banco de dados
            $imageData = [
                'title' => $metadata['title'] ?? pathinfo($file['name'], PATHINFO_FILENAME),
                'filename' => $filename,
                'filepath' => UPLOADS_URL . $filename,
                'mime_type' => $file['type'],
                'file_size' => $file['size'],
                'uploaded_by' => $userId,
                'alt_text' => $metadata['alt_text'] ?? '',
                'description' => $metadata['description'] ?? ''
            ];

            $imageId = Database::getInstance()->insert('images', $imageData);

            return [
                'success' => true, 
                'message' => 'Imagem enviada com sucesso',
                'id' => $imageId,
                'filepath' => $imageData['filepath'],
                'filename' => $filename
            ];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Erro ao fazer upload: ' . $e->getMessage()];
        }
    }

    /**
     * Validar arquivo de imagem
     */
    private static function validateFile($file) {
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $errors = [
                UPLOAD_ERR_INI_SIZE => 'Arquivo excede o limite de tamanho do servidor',
                UPLOAD_ERR_FORM_SIZE => 'Arquivo excede o limite de tamanho do formulário',
                UPLOAD_ERR_PARTIAL => 'Upload foi interrompido',
                UPLOAD_ERR_NO_FILE => 'Nenhum arquivo foi enviado',
                UPLOAD_ERR_NO_TMP_DIR => 'Pasta temporária não existe',
                UPLOAD_ERR_CANT_WRITE => 'Erro ao escrever arquivo',
                UPLOAD_ERR_EXTENSION => 'Upload foi bloqueado por extensão'
            ];

            return ['success' => false, 'message' => $errors[$file['error']] ?? 'Erro desconhecido'];
        }

        // Validar tipo MIME com fallback para servidores sem extensão fileinfo.
        $mimeType = self::detectMimeType($file);

        if (!in_array($mimeType, self::ALLOWED_TYPES)) {
            return ['success' => false, 'message' => 'Tipo de arquivo não permitido. Use: JPG, PNG, GIF, WEBP'];
        }

        // Validar extensão
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($extension, self::ALLOWED_EXTENSIONS)) {
            return ['success' => false, 'message' => 'Extensão de arquivo não permitida'];
        }

        return ['success' => true];
    }

    /**
     * Detectar MIME sem depender exclusivamente da extensão fileinfo.
     */
    private static function detectMimeType($file) {
        if (function_exists('finfo_open')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            if ($finfo) {
                $mimeType = finfo_file($finfo, $file['tmp_name']);
                finfo_close($finfo);

                if ($mimeType) {
                    return $mimeType;
                }
            }
        }

        if (function_exists('mime_content_type')) {
            $mimeType = mime_content_type($file['tmp_name']);
            if ($mimeType) {
                return $mimeType;
            }
        }

        $imageInfo = @getimagesize($file['tmp_name']);
        if (!empty($imageInfo['mime'])) {
            return $imageInfo['mime'];
        }

        return $file['type'] ?? '';
    }

    /**
     * Otimizar imagem (redimensionar, comprimir)
     */
    private static function optimizeImage($filepath, $extension) {
        // Verificar se GD está disponível
        if (!extension_loaded('gd')) {
            return;
        }

        try {
            $image = null;
            switch(strtolower($extension)) {
                case 'jpg':
                case 'jpeg':
                    $image = imagecreatefromjpeg($filepath);
                    break;
                case 'png':
                    $image = imagecreatefrompng($filepath);
                    break;
                case 'gif':
                    $image = imagecreatefromgif($filepath);
                    break;
                case 'webp':
                    $image = imagecreatefromwebp($filepath);
                    break;
            }

            if (!$image) return;

            $width = imagesx($image);
            $height = imagesy($image);

            // Redimensionar se maior que 1920x1080
            if ($width > 1920 || $height > 1920) {
                $scale = min(1920 / $width, 1920 / $height);
                $newWidth = intval($width * $scale);
                $newHeight = intval($height * $scale);

                $resized = imagecreatetruecolor($newWidth, $newHeight);
                imagecopyresampled($resized, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
                
                imagedestroy($image);
                $image = $resized;
            }

            // Salvar com compressão
            switch(strtolower($extension)) {
                case 'jpg':
                case 'jpeg':
                    imagejpeg($image, $filepath, 85);
                    break;
                case 'png':
                    imagepng($image, $filepath, 8);
                    break;
                case 'gif':
                    imagegif($image, $filepath);
                    break;
                case 'webp':
                    imagewebp($image, $filepath, 85);
                    break;
            }

            imagedestroy($image);
        } catch (Exception $e) {
            // Ignorar erros de otimização
        }
    }

    /**
     * Obter imagem por ID
     */
    public static function getById($imageId) {
        return Database::getInstance()->selectOne('images', "id = ?", [$imageId]);
    }

    /**
     * Listar imagens
     */
    public static function getList($filters = []) {
        $where = "1=1";
        
        if (!empty($filters['uploaded_by'])) {
            $where .= " AND uploaded_by = {$filters['uploaded_by']}";
        }

        if (!empty($filters['search'])) {
            $search = addslashes($filters['search']);
            $where .= " AND (title LIKE '%$search%' OR description LIKE '%$search%')";
        }

        $where .= " ORDER BY created_at DESC";

        $limit = !empty($filters['limit']) ? "LIMIT {$filters['limit']}" : '';

        return Database::getInstance()->select('images', $where, $limit);
    }

    /**
     * Atualizar metadados de imagem
     */
    public static function updateMetadata($imageId, $data) {
        try {
            $image = self::getById($imageId);
            if (!$image) {
                return ['success' => false, 'message' => 'Imagem não encontrada'];
            }

            $updateData = [
                'title' => $data['title'] ?? $image['title'],
                'alt_text' => $data['alt_text'] ?? $image['alt_text'],
                'description' => $data['description'] ?? $image['description']
            ];

            Database::getInstance()->update('images', $updateData, "id = $imageId");

            return ['success' => true, 'message' => 'Metadados atualizados com sucesso'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Erro ao atualizar: ' . $e->getMessage()];
        }
    }

    /**
     * Deletar imagem
     */
    public static function delete($imageId) {
        try {
            $image = self::getById($imageId);
            if (!$image) {
                return ['success' => false, 'message' => 'Imagem não encontrada'];
            }

            // Deletar arquivo
            $filepath = UPLOADS_PATH . basename($image['filename']);
            if (file_exists($filepath)) {
                unlink($filepath);
            }

            // Deletar registro no banco
            Database::getInstance()->delete('images', "id = $imageId");

            return ['success' => true, 'message' => 'Imagem deletada com sucesso'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Erro ao deletar: ' . $e->getMessage()];
        }
    }

    /**
     * Copiar imagem para pasta pública
     */
    public static function copyToPublic($imageId, $newName = null) {
        try {
            $image = self::getById($imageId);
            if (!$image) {
                return ['success' => false, 'message' => 'Imagem não encontrada'];
            }

            if (strpos($image['filepath'], PUBLIC_IMAGES_URL) === 0) {
                $publicFile = PUBLIC_IMAGES_PATH . basename($image['filepath']);

                if (file_exists($publicFile)) {
                    return [
                        'success' => true,
                        'message' => 'Imagem já está na pasta pública',
                        'public_path' => $image['filepath']
                    ];
                }
            }

            $source = UPLOADS_PATH . $image['filename'];
            if (!file_exists($source)) {
                return ['success' => false, 'message' => 'Arquivo de origem não encontrado'];
            }

            $filename = $newName ?? basename($image['filename']);
            $destination = PUBLIC_IMAGES_PATH . $filename;

            if (!copy($source, $destination)) {
                return ['success' => false, 'message' => 'Erro ao copiar arquivo'];
            }

            return [
                'success' => true, 
                'message' => 'Imagem copiada para pasta pública',
                'public_path' => PUBLIC_IMAGES_URL . $filename
            ];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Erro: ' . $e->getMessage()];
        }
    }
}
?>
