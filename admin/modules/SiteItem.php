<?php
/**
 * Gerenciador de itens editáveis do site
 */

require_once __DIR__ . '/../../db/config.php';

class SiteItem {
    private static $sections = [
        'skill' => 'Habilidade',
        'project' => 'Projeto',
        'blog' => 'Destaque do Blog',
        'nav' => 'Menu da Navbar'
    ];

    public static function ensureSchema() {
        $db = Database::getInstance()->getPDO();

        $db->exec("CREATE TABLE IF NOT EXISTS site_items (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            section TEXT NOT NULL,
            title TEXT NOT NULL,
            description TEXT,
            image TEXT,
            icon TEXT,
            tags TEXT,
            primary_label TEXT,
            primary_url TEXT,
            secondary_label TEXT,
            secondary_url TEXT,
            status TEXT DEFAULT 'published',
            order_num INTEGER DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )");

        @$db->exec("CREATE INDEX IF NOT EXISTS idx_site_items_section ON site_items(section)");
        @$db->exec("CREATE INDEX IF NOT EXISTS idx_site_items_status ON site_items(status)");
        @$db->exec("CREATE INDEX IF NOT EXISTS idx_site_items_order ON site_items(order_num)");

        self::seedExistingItems();
        self::seedNavigationItems();
    }

    public static function getSections() {
        return self::$sections;
    }

    public static function create($data) {
        try {
            self::ensureSchema();
            $errors = self::validate($data);

            if (!empty($errors)) {
                return ['success' => false, 'message' => implode('<br>', $errors)];
            }

            $itemId = Database::getInstance()->insert('site_items', self::prepareData($data));

            return ['success' => true, 'message' => 'Item criado com sucesso', 'id' => $itemId];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Erro ao criar item: ' . $e->getMessage()];
        }
    }

    public static function update($itemId, $data) {
        try {
            self::ensureSchema();
            $item = self::getById($itemId);

            if (!$item) {
                return ['success' => false, 'message' => 'Item não encontrado'];
            }

            $errors = self::validate($data);

            if (!empty($errors)) {
                return ['success' => false, 'message' => implode('<br>', $errors)];
            }

            $updateData = self::prepareData($data);
            $updateData['updated_at'] = date('Y-m-d H:i:s');

            Database::getInstance()->update('site_items', $updateData, "id = " . intval($itemId));

            return ['success' => true, 'message' => 'Item atualizado com sucesso'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Erro ao atualizar item: ' . $e->getMessage()];
        }
    }

    public static function delete($itemId) {
        try {
            self::ensureSchema();
            $item = self::getById($itemId);

            if (!$item) {
                return ['success' => false, 'message' => 'Item não encontrado'];
            }

            Database::getInstance()->delete('site_items', "id = " . intval($itemId));

            return ['success' => true, 'message' => 'Item removido com sucesso'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Erro ao remover item: ' . $e->getMessage()];
        }
    }

    public static function getById($itemId) {
        self::ensureSchema();
        return Database::getInstance()->selectOne('site_items', "id = " . intval($itemId));
    }

    public static function getList($filters = []) {
        self::ensureSchema();
        $where = "1=1";

        if (!empty($filters['section'])) {
            $where .= " AND section = '" . self::escape($filters['section']) . "'";
        }

        if (!empty($filters['status'])) {
            $where .= " AND status = '" . self::escape($filters['status']) . "'";
        }

        if (!empty($filters['search'])) {
            $search = self::escape($filters['search']);
            $where .= " AND (title LIKE '%$search%' OR description LIKE '%$search%' OR tags LIKE '%$search%')";
        }

        $limit = !empty($filters['limit']) ? " LIMIT " . intval($filters['limit']) : "";
        return Database::getInstance()->select('site_items', $where, "ORDER BY order_num ASC, id ASC" . $limit);
    }

    public static function getPublishedBySection($section, $limit = null) {
        $filters = [
            'section' => $section,
            'status' => 'published'
        ];

        if ($limit) {
            $filters['limit'] = $limit;
        }

        return self::getList($filters);
    }

    private static function validate($data) {
        $errors = [];

        if (empty($data['section']) || !array_key_exists($data['section'], self::$sections)) {
            $errors[] = 'Tipo de item inválido';
        }

        if (empty(trim($data['title'] ?? ''))) {
            $errors[] = 'Título é obrigatório';
        }

        if (($data['section'] ?? '') === 'nav' && empty(trim($data['primary_url'] ?? ''))) {
            $errors[] = 'URL principal é obrigatória para itens da navbar';
        }

        if (!empty($data['status']) && !in_array($data['status'], ['draft', 'published'], true)) {
            $errors[] = 'Status inválido';
        }

        return $errors;
    }

    private static function prepareData($data) {
        return [
            'section' => $data['section'],
            'title' => trim($data['title'] ?? ''),
            'description' => trim($data['description'] ?? ''),
            'image' => trim($data['image'] ?? ''),
            'icon' => trim($data['icon'] ?? ''),
            'tags' => trim($data['tags'] ?? ''),
            'primary_label' => trim($data['primary_label'] ?? ''),
            'primary_url' => trim($data['primary_url'] ?? ''),
            'secondary_label' => trim($data['secondary_label'] ?? ''),
            'secondary_url' => trim($data['secondary_url'] ?? ''),
            'status' => $data['status'] ?? 'draft',
            'order_num' => intval($data['order_num'] ?? 0)
        ];
    }

    private static function seedExistingItems() {
        $existing = Database::getInstance()->select('site_items', '1=1', 'LIMIT 1');

        if (!empty($existing)) {
            return;
        }

        $items = [
            ['skill', 'Python', '', '', '', '', '', '', '', '', 'published', 10],
            ['skill', 'Flask', '', '', '', '', '', '', '', '', 'published', 20],
            ['skill', 'HTML5', '', '', '', '', '', '', '', '', 'published', 30],
            ['skill', 'CSS3', '', '', '', '', '', '', '', '', 'published', 40],
            ['skill', 'JavaScript', '', '', '', '', '', '', '', '', 'published', 50],
            ['skill', 'SQL', '', '', '', '', '', '', '', '', 'published', 60],
            ['skill', 'Git', '', '', '', '', '', '', '', '', 'published', 70],
            ['skill', 'Linux', '', '', '', '', '', '', '', '', 'published', 80],
            ['skill', 'WordPress', '', '', '', '', '', '', '', '', 'published', 90],
            ['project', 'Site ISNA', 'Site Institucional que mostra como o instituto Social Novo Amanhecer está mudando a vida de várias pessoas.', '/images/isna.png', '', 'PHP, Linux', 'Ver projeto', 'https://isna.chiapetta.dev', '', '', 'published', 10],
            ['project', 'Donate Hub', 'Sistema de doações internacionais integrado com o Authorize Net', '/images/donatehub.png', '', 'Flask, SQLite, Python', 'Ver projeto', 'https://donatehub.chiapetta.dev/donate', '', '', 'published', 20],
            ['project', 'Portfólio Profissional de Eduarda Chiapetta', 'Portfólio profissional completo', '', '👩‍💼', 'HTML, CSS, Javascript', 'Ver projeto', 'https://eduarda.chiapetta.dev', 'GitHub', 'https://github.com/chiapettaiago/eduarda', 'published', 30],
            ['blog', 'Minha primeira experiência como programador CLT. Como está sendo?', 'Recentemente iniciei minha primeira experiência formal como programador com registro em carteira. No dia 01 de dezembro de 2025, comecei…', '', '', 'Experiência, Carreira', 'Ler artigo', '/blog/minha-primeira-experiencia-como-programador-clt-como-esta-sendo/', '', '', 'published', 10],
            ['blog', 'Testei o GPT-5.3 Codex. Veja o que encontrei.', '"Eu executei uma tarefa real de programação com o GPT‑5.3 Codex e ele foi claramente melhor que as versões anteriores: menos alucinações,…"', '', '', 'IA, GPT, Ferramentas', 'Ler artigo', '/blog/testei-o-gpt-5-3-codex-veja-o-que-encontrei/', '', '', 'published', 20],
            ['blog', 'IA Codifica: Desenvolvedores, Hora de Reimaginarmos Nosso Papel!', '"É 1º de fevereiro de 2026, e a notícia explodiu: a TechGiant X acaba de lançar seu mais novo modelo…"', '', '', 'IA, Desenvolvimento, Reflexão', 'Ler artigo', '/blog/ia-codifica-desenvolvedores-hora-de-reimaginarmos-nosso-papel/', '', '', 'published', 30],
        ];

        foreach ($items as $item) {
            Database::getInstance()->insert('site_items', [
                'section' => $item[0],
                'title' => $item[1],
                'description' => $item[2],
                'image' => $item[3],
                'icon' => $item[4],
                'tags' => $item[5],
                'primary_label' => $item[6],
                'primary_url' => $item[7],
                'secondary_label' => $item[8],
                'secondary_url' => $item[9],
                'status' => $item[10],
                'order_num' => $item[11]
            ]);
        }
    }

    private static function seedNavigationItems() {
        $existing = Database::getInstance()->select('site_items', "section = 'nav'", 'LIMIT 1');

        if (!empty($existing)) {
            return;
        }

        $items = [
            ['Sobre', '#sobre', 10],
            ['Habilidades', '#habilidades', 20],
            ['Projetos', '#projetos', 30],
            ['Blog', '#blog', 40],
        ];

        foreach ($items as $item) {
            Database::getInstance()->insert('site_items', [
                'section' => 'nav',
                'title' => $item[0],
                'description' => '',
                'image' => '',
                'icon' => '',
                'tags' => '',
                'primary_label' => '',
                'primary_url' => $item[1],
                'secondary_label' => '',
                'secondary_url' => '',
                'status' => 'published',
                'order_num' => $item[2]
            ]);
        }
    }

    private static function escape($value) {
        return str_replace("'", "''", trim($value));
    }
}
?>
