<?php
/**
 * Gerenciador de Páginas
 */

require_once __DIR__ . '/../../db/config.php';

class Page {
    /**
     * Criar nova página
     */
    public static function create($data, $userId) {
        try {
            $slugSource = !empty(trim($data['slug'] ?? '')) ? $data['slug'] : $data['title'];
            $slug = self::generateSlug($slugSource);
            
            // Verificar se slug já existe
            $existing = Database::getInstance()->selectOne('pages', "slug = ?", [$slug]);
            if ($existing) {
                return ['success' => false, 'message' => 'Já existe uma página com este título'];
            }

            $pageData = [
                'title' => $data['title'],
                'slug' => $slug,
                'content' => $data['content'],
                'featured_image' => $data['featured_image'] ?? null,
                'author_id' => $userId,
                'status' => $data['status'] ?? 'draft',
                'parent_id' => !empty($data['parent_id']) ? intval($data['parent_id']) : null,
                'order_num' => $data['order_num'] ?? 0,
                'published_at' => ($data['status'] === 'published') ? date('Y-m-d H:i:s') : null
            ];

            $pageId = Database::getInstance()->insert('pages', $pageData);

            return ['success' => true, 'message' => 'Página criada com sucesso', 'id' => $pageId];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Erro ao criar página: ' . $e->getMessage()];
        }
    }

    /**
     * Atualizar página
     */
    public static function update($pageId, $data, $userId) {
        try {
            $page = self::getById($pageId);
            if (!$page) {
                return ['success' => false, 'message' => 'Página não encontrada'];
            }

            if ($page['author_id'] !== $userId && !Auth::hasPermission('admin')) {
                return ['success' => false, 'message' => 'Permissão negada'];
            }

            $slugSource = !empty(trim($data['slug'] ?? '')) ? $data['slug'] : $data['title'];
            $slug = self::generateSlug($slugSource);
            
            // Verificar se novo slug já existe
            if ($slug !== $page['slug']) {
                $existing = Database::getInstance()->selectOne('pages', "slug = ?", [$slug]);
                if ($existing) {
                    return ['success' => false, 'message' => 'Já existe uma página com este título'];
                }
            }

            $updateData = [
                'title' => $data['title'],
                'slug' => $slug,
                'content' => $data['content'],
                'featured_image' => $data['featured_image'] ?? $page['featured_image'],
                'status' => $data['status'] ?? $page['status'],
                'parent_id' => !empty($data['parent_id']) ? intval($data['parent_id']) : null,
                'order_num' => $data['order_num'] ?? $page['order_num'],
                'updated_at' => date('Y-m-d H:i:s'),
                'published_at' => ($data['status'] === 'published' && !$page['published_at']) 
                    ? date('Y-m-d H:i:s') 
                    : $page['published_at']
            ];

            Database::getInstance()->update('pages', $updateData, "id = $pageId");

            return ['success' => true, 'message' => 'Página atualizada com sucesso'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Erro ao atualizar página: ' . $e->getMessage()];
        }
    }

    /**
     * Obter página por ID
     */
    public static function getById($pageId) {
        $page = Database::getInstance()->selectOne('pages', "id = ?", [$pageId]);
        if (!$page) return null;

        $page['author'] = Database::getInstance()->selectOne('users', "id = {$page['author_id']}");
        if ($page['parent_id']) {
            $page['parent'] = self::getById($page['parent_id']);
        }

        return $page;
    }

    /**
     * Obter página por slug
     */
    public static function getBySlug($slug) {
        $page = Database::getInstance()->selectOne('pages', "slug = ?", [$slug]);
        if (!$page) return null;

        $page['author'] = Database::getInstance()->selectOne('users', "id = {$page['author_id']}");
        if ($page['parent_id']) {
            $page['parent'] = self::getById($page['parent_id']);
        }

        return $page;
    }

    /**
     * Listar páginas
     */
    public static function getList($filters = []) {
        $where = "1=1";
        
        if (!empty($filters['status'])) {
            $where .= " AND status = '{$filters['status']}'";
        }

        if (!empty($filters['author_id'])) {
            $where .= " AND author_id = {$filters['author_id']}";
        }

        if (!empty($filters['parent_id'])) {
            $where .= " AND parent_id = {$filters['parent_id']}";
        } else if (isset($filters['parent_id']) && $filters['parent_id'] === false) {
            $where .= " AND parent_id IS NULL";
        }

        if (!empty($filters['search'])) {
            $search = addslashes($filters['search']);
            $where .= " AND (title LIKE '%$search%' OR content LIKE '%$search%')";
        }

        $orderLimit = !empty($filters['limit'])
            ? "ORDER BY order_num ASC, published_at DESC, created_at DESC LIMIT {$filters['limit']}"
            : "ORDER BY order_num ASC, published_at DESC, created_at DESC";

        return Database::getInstance()->select('pages', $where, $orderLimit);
    }

    /**
     * Obter hierarquia de páginas
     */
    public static function getHierarchy($parentId = null) {
        $where = $parentId ? "parent_id = $parentId" : "parent_id IS NULL";
        $where .= " AND status = 'published'";
        
        $pages = Database::getInstance()->select('pages', $where, 'ORDER BY order_num ASC');
        
        foreach ($pages as &$page) {
            $page['children'] = self::getHierarchy($page['id']);
        }

        return $pages;
    }

    /**
     * Deletar página
     */
    public static function delete($pageId, $userId) {
        try {
            $page = self::getById($pageId);
            if (!$page) {
                return ['success' => false, 'message' => 'Página não encontrada'];
            }

            if ($page['author_id'] !== $userId && !Auth::hasPermission('admin')) {
                return ['success' => false, 'message' => 'Permissão negada'];
            }

            // Deletar arquivo de imagem se existir
            if ($page['featured_image']) {
                $imagePath = PUBLIC_IMAGES_PATH . basename($page['featured_image']);
                if (file_exists($imagePath)) {
                    unlink($imagePath);
                }
            }

            // Deletar subpáginas
            $subpages = Database::getInstance()->select('pages', "parent_id = $pageId");
            foreach ($subpages as $subpage) {
                self::delete($subpage['id'], $userId);
            }

            Database::getInstance()->delete('pages', "id = $pageId");

            return ['success' => true, 'message' => 'Página deletada com sucesso'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Erro ao deletar página: ' . $e->getMessage()];
        }
    }

    /**
     * Gerar slug a partir do título
     */
    private static function generateSlug($title) {
        $slug = iconv('UTF-8', 'ASCII//TRANSLIT', $title);
        $slug = preg_replace('/[^a-z0-9]+/i', '-', $slug);
        $slug = trim($slug, '-');
        $slug = strtolower($slug);
        return $slug;
    }
}
?>
