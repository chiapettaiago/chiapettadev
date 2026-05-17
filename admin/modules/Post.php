<?php
/**
 * Gerenciador de Posts do Blog
 */

require_once __DIR__ . '/../../db/config.php';

class Post {
    /**
     * Criar novo post
     */
    public static function create($data, $userId) {
        try {
            $slugSource = !empty(trim($data['slug'] ?? '')) ? $data['slug'] : $data['title'];
            $slug = self::generateSlug($slugSource);
            
            // Verificar se slug já existe
            $existing = Database::getInstance()->selectOne('posts', "slug = ?", [$slug]);
            if ($existing) {
                return ['success' => false, 'message' => 'Já existe um post com este título'];
            }

            $postData = [
                'title' => $data['title'],
                'slug' => $slug,
                'content' => $data['content'],
                'excerpt' => $data['excerpt'] ?? substr(strip_tags($data['content']), 0, 160),
                'featured_image' => $data['featured_image'] ?? null,
                'author_id' => $userId,
                'status' => $data['status'] ?? 'draft',
                'published_at' => ($data['status'] === 'published') ? date('Y-m-d H:i:s') : null
            ];

            $postId = Database::getInstance()->insert('posts', $postData);

            // Adicionar tags se fornecidas
            if (!empty($data['tags'])) {
                self::addTags($postId, $data['tags']);
            }

            // Adicionar categorias se fornecidas
            if (!empty($data['categories'])) {
                self::addCategories($postId, $data['categories']);
            }

            return ['success' => true, 'message' => 'Post criado com sucesso', 'id' => $postId];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Erro ao criar post: ' . $e->getMessage()];
        }
    }

    /**
     * Atualizar post
     */
    public static function update($postId, $data, $userId) {
        try {
            $post = self::getById($postId);
            if (!$post) {
                return ['success' => false, 'message' => 'Post não encontrado'];
            }

            // Verificar permissão
            if ($post['author_id'] !== $userId && !Auth::hasPermission('admin')) {
                return ['success' => false, 'message' => 'Permissão negada'];
            }

            $slugSource = !empty(trim($data['slug'] ?? '')) ? $data['slug'] : $data['title'];
            $slug = self::generateSlug($slugSource);
            
            // Verificar se novo slug já existe
            if ($slug !== $post['slug']) {
                $existing = Database::getInstance()->selectOne('posts', "slug = ?", [$slug]);
                if ($existing) {
                    return ['success' => false, 'message' => 'Já existe um post com este título'];
                }
            }

            $updateData = [
                'title' => $data['title'],
                'slug' => $slug,
                'content' => $data['content'],
                'excerpt' => $data['excerpt'] ?? substr(strip_tags($data['content']), 0, 160),
                'featured_image' => $data['featured_image'] ?? $post['featured_image'],
                'status' => $data['status'] ?? $post['status'],
                'updated_at' => date('Y-m-d H:i:s'),
                'published_at' => ($data['status'] === 'published' && !$post['published_at']) 
                    ? date('Y-m-d H:i:s') 
                    : $post['published_at']
            ];

            Database::getInstance()->update('posts', $updateData, "id = $postId");

            // Atualizar tags
            if (isset($data['tags'])) {
                Database::getInstance()->delete('post_tags', "post_id = $postId");
                if (!empty($data['tags'])) {
                    self::addTags($postId, $data['tags']);
                }
            }

            // Atualizar categorias
            if (isset($data['categories'])) {
                Database::getInstance()->delete('post_categories', "post_id = $postId");
                if (!empty($data['categories'])) {
                    self::addCategories($postId, $data['categories']);
                }
            }

            return ['success' => true, 'message' => 'Post atualizado com sucesso'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Erro ao atualizar post: ' . $e->getMessage()];
        }
    }

    /**
     * Obter post por ID
     */
    public static function getById($postId) {
        $post = Database::getInstance()->selectOne('posts', "id = ?", [$postId]);
        if (!$post) return null;

        $post['tags'] = self::getTags($postId);
        $post['categories'] = self::getCategories($postId);
        $post['author'] = Database::getInstance()->selectOne('users', "id = {$post['author_id']}");

        return $post;
    }

    /**
     * Obter post por slug
     */
    public static function getBySlug($slug) {
        $post = Database::getInstance()->selectOne('posts', "slug = ?", [$slug]);
        if (!$post) return null;

        // Incrementar visualizações
        Database::getInstance()->update('posts', 
            ['views' => $post['views'] + 1],
            "id = {$post['id']}"
        );

        $post['tags'] = self::getTags($post['id']);
        $post['categories'] = self::getCategories($post['id']);
        $post['author'] = Database::getInstance()->selectOne('users', "id = {$post['author_id']}");

        return $post;
    }

    /**
     * Listar posts
     */
    public static function getList($filters = []) {
        $where = "1=1";
        
        if (!empty($filters['status'])) {
            $where .= " AND status = '{$filters['status']}'";
        }

        if (!empty($filters['author_id'])) {
            $where .= " AND author_id = {$filters['author_id']}";
        }

        if (!empty($filters['search'])) {
            $search = addslashes($filters['search']);
            $where .= " AND (title LIKE '%$search%' OR content LIKE '%$search%')";
        }

        $orderLimit = !empty($filters['limit'])
            ? "ORDER BY published_at DESC, created_at DESC LIMIT {$filters['limit']}"
            : "ORDER BY published_at DESC, created_at DESC";

        return Database::getInstance()->select('posts', $where, $orderLimit);
    }

    /**
     * Deletar post
     */
    public static function delete($postId, $userId) {
        try {
            $post = self::getById($postId);
            if (!$post) {
                return ['success' => false, 'message' => 'Post não encontrado'];
            }

            if ($post['author_id'] !== $userId && !Auth::hasPermission('admin')) {
                return ['success' => false, 'message' => 'Permissão negada'];
            }

            // Deletar arquivo de imagem se existir
            if ($post['featured_image']) {
                $imagePath = PUBLIC_IMAGES_PATH . basename($post['featured_image']);
                if (file_exists($imagePath)) {
                    unlink($imagePath);
                }
            }

            Database::getInstance()->delete('posts', "id = $postId");

            return ['success' => true, 'message' => 'Post deletado com sucesso'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Erro ao deletar post: ' . $e->getMessage()];
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

    /**
     * Adicionar tags a um post
     */
    private static function addTags($postId, $tags) {
        if (is_string($tags)) {
            $tags = explode(',', $tags);
        }

        foreach ($tags as $tagName) {
            $tagName = trim($tagName);
            if (empty($tagName)) continue;

            // Verificar ou criar tag
            $tag = Database::getInstance()->selectOne('tags', "name = ?", [$tagName]);
            if (!$tag) {
                $tagSlug = self::generateSlug($tagName);
                $tagId = Database::getInstance()->insert('tags', [
                    'name' => $tagName,
                    'slug' => $tagSlug
                ]);
            } else {
                $tagId = $tag['id'];
            }

            // Adicionar relacionamento
            Database::getInstance()->insert('post_tags', [
                'post_id' => $postId,
                'tag_id' => $tagId
            ]);
        }
    }

    /**
     * Obter tags de um post
     */
    private static function getTags($postId) {
        $result = Database::getInstance()->query(
            "SELECT t.* FROM tags t 
             JOIN post_tags pt ON t.id = pt.tag_id 
             WHERE pt.post_id = ?",
            [$postId]
        );
        return $result->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Adicionar categorias a um post
     */
    private static function addCategories($postId, $categories) {
        if (is_string($categories)) {
            $categories = explode(',', $categories);
        }

        foreach ($categories as $categoryId) {
            Database::getInstance()->insert('post_categories', [
                'post_id' => $postId,
                'category_id' => intval(trim($categoryId))
            ]);
        }
    }

    /**
     * Obter categorias de um post
     */
    private static function getCategories($postId) {
        $result = Database::getInstance()->query(
            "SELECT c.* FROM categories c 
             JOIN post_categories pc ON c.id = pc.category_id 
             WHERE pc.post_id = ?",
            [$postId]
        );
        return $result->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>
