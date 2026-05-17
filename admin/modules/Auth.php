<?php
/**
 * Sistema de Autenticação - CMS ChiapettaDev
 */

require_once __DIR__ . '/../../db/config.php';

class Auth {
    /**
     * Fazer login de um usuário
     */
    public static function login($username, $password) {
        try {
            $user = Database::getInstance()->selectOne('users', "username = ? OR email = ?", [$username, $username]);
            
            if (!$user) {
                return ['success' => false, 'message' => 'Usuário não encontrado'];
            }

            if ($user['status'] !== 'active') {
                return ['success' => false, 'message' => 'Usuário inativo'];
            }

            if (!password_verify($password, $user['password'])) {
                return ['success' => false, 'message' => 'Senha incorreta'];
            }

            // Iniciar sessão
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }
            
            $_SESSION[SESSION_NAME] = [
                'id' => $user['id'],
                'username' => $user['username'],
                'email' => $user['email'],
                'full_name' => $user['full_name'],
                'role' => $user['role'],
                'login_time' => time()
            ];

            return ['success' => true, 'message' => 'Login realizado com sucesso'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Erro no login: ' . $e->getMessage()];
        }
    }

    /**
     * Verificar se usuário está autenticado
     */
    public static function isAuthenticated() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if (!isset($_SESSION[SESSION_NAME])) {
            return false;
        }

        // Verificar timeout de sessão
        if (time() - $_SESSION[SESSION_NAME]['login_time'] > SESSION_TIMEOUT) {
            self::logout();
            return false;
        }

        // Atualizar tempo de última atividade
        $_SESSION[SESSION_NAME]['login_time'] = time();
        
        return true;
    }

    /**
     * Obter usuário autenticado
     */
    public static function getCurrentUser() {
        if (!self::isAuthenticated()) {
            return null;
        }

        return $_SESSION[SESSION_NAME];
    }

    /**
     * Fazer logout
     */
    public static function logout() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        unset($_SESSION[SESSION_NAME]);
        session_destroy();
    }

    /**
     * Verificar permissão de acesso
     */
    public static function hasPermission($requiredRole) {
        if (!self::isAuthenticated()) {
            return false;
        }

        $user = self::getCurrentUser();
        $roles = ['admin' => 4, 'editor' => 3, 'author' => 2, 'reader' => 1];

        $userLevel = $roles[$user['role']] ?? 0;
        $requiredLevel = $roles[$requiredRole] ?? 0;

        return $userLevel >= $requiredLevel;
    }

    /**
     * Verificar se o usuário atual é apenas leitor.
     */
    public static function isReaderOnly() {
        if (!self::isAuthenticated()) {
            return false;
        }

        $user = self::getCurrentUser();
        return ($user['role'] ?? '') === 'reader';
    }

    /**
     * Criar conta pública de leitor.
     */
    public static function registerReader($data) {
        try {
            $errors = [];
            $username = trim($data['username'] ?? '');
            $email = trim($data['email'] ?? '');
            $fullName = trim($data['full_name'] ?? '');
            $password = $data['password'] ?? '';
            $passwordConfirm = $data['password_confirm'] ?? '';

            if (strlen($username) < 3 || !preg_match('/^[a-zA-Z0-9_.-]+$/', $username)) {
                $errors[] = 'Usuário deve ter no mínimo 3 caracteres e usar apenas letras, números, ponto, hífen ou underline';
            }

            if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors[] = 'Email inválido';
            }

            if (strlen($password) < 6) {
                $errors[] = 'Senha deve ter no mínimo 6 caracteres';
            }

            if ($password !== $passwordConfirm) {
                $errors[] = 'Confirmação de senha não confere';
            }

            if (empty($fullName)) {
                $errors[] = 'Nome é obrigatório';
            }

            $existing = Database::getInstance()->selectOne('users', "username = ? OR email = ?", [$username, $email]);
            if ($existing) {
                $errors[] = 'Usuário ou email já cadastrado';
            }

            if (!empty($errors)) {
                return ['success' => false, 'messages' => $errors, 'message' => implode('<br>', $errors)];
            }

            Database::getInstance()->insert('users', [
                'username' => $username,
                'email' => $email,
                'password' => password_hash($password, PASSWORD_BCRYPT),
                'full_name' => $fullName,
                'role' => 'reader',
                'status' => 'active'
            ]);

            return self::login($username, $password);
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Erro ao criar cadastro: ' . $e->getMessage()];
        }
    }

    /**
     * Alterar senha do usuário
     */
    public static function changePassword($userId, $currentPassword, $newPassword) {
        try {
            $user = Database::getInstance()->selectOne('users', "id = ?", [$userId]);
            
            if (!$user) {
                return ['success' => false, 'message' => 'Usuário não encontrado'];
            }

            if (!password_verify($currentPassword, $user['password'])) {
                return ['success' => false, 'message' => 'Senha atual incorreta'];
            }

            if (strlen($newPassword) < 6) {
                return ['success' => false, 'message' => 'Nova senha deve ter no mínimo 6 caracteres'];
            }

            $newHash = password_hash($newPassword, PASSWORD_BCRYPT);
            Database::getInstance()->update('users', 
                ['password' => $newHash, 'updated_at' => date('Y-m-d H:i:s')],
                "id = $userId"
            );

            return ['success' => true, 'message' => 'Senha alterada com sucesso'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Erro ao alterar senha: ' . $e->getMessage()];
        }
    }

    /**
     * Criar novo usuário (apenas admin)
     */
    public static function createUser($data) {
        try {
            if (!self::hasPermission('admin')) {
                return ['success' => false, 'message' => 'Permissão negada'];
            }

            $errors = [];
            
            if (empty($data['username']) || strlen($data['username']) < 3) {
                $errors[] = 'Nome de usuário deve ter no mínimo 3 caracteres';
            }

            if (empty($data['email']) || !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                $errors[] = 'Email inválido';
            }

            if (empty($data['password']) || strlen($data['password']) < 6) {
                $errors[] = 'Senha deve ter no mínimo 6 caracteres';
            }

            if (empty($data['full_name'])) {
                $errors[] = 'Nome completo é obrigatório';
            }

            $allowedRoles = ['reader', 'author', 'editor', 'admin'];
            if (!in_array($data['role'] ?? 'author', $allowedRoles, true)) {
                $errors[] = 'Papel inválido';
            }

            // Verificar se usuário/email já existe
            $existing = Database::getInstance()->selectOne('users', "username = ? OR email = ?", [$data['username'], $data['email']]);
            if ($existing) {
                $errors[] = 'Usuário ou email já cadastrado';
            }

            if (!empty($errors)) {
                return ['success' => false, 'messages' => $errors];
            }

            $userData = [
                'username' => $data['username'],
                'email' => $data['email'],
                'password' => password_hash($data['password'], PASSWORD_BCRYPT),
                'full_name' => $data['full_name'],
                'role' => $data['role'] ?? 'author',
                'status' => 'active'
            ];

            Database::getInstance()->insert('users', $userData);

            return ['success' => true, 'message' => 'Usuário criado com sucesso'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Erro ao criar usuário: ' . $e->getMessage()];
        }
    }

    /**
     * Excluir usuário (apenas admin)
     */
    public static function deleteUser($userId) {
        try {
            if (!self::hasPermission('admin')) {
                return ['success' => false, 'message' => 'Permissão negada'];
            }

            $userId = intval($userId);
            $currentUser = self::getCurrentUser();
            $currentUserId = intval($currentUser['id'] ?? 0);

            if ($userId <= 0) {
                return ['success' => false, 'message' => 'Usuário inválido'];
            }

            if ($userId === $currentUserId) {
                return ['success' => false, 'message' => 'Você não pode excluir sua própria conta'];
            }

            $targetUser = Database::getInstance()->selectOne('users', "id = ?", [$userId]);
            if (!$targetUser) {
                return ['success' => false, 'message' => 'Usuário não encontrado'];
            }

            if (($targetUser['role'] ?? '') === 'admin') {
                $adminCount = (int) Database::getInstance()
                    ->query("SELECT COUNT(*) FROM users WHERE role = 'admin' AND status = 'active'")
                    ->fetchColumn();

                if ($adminCount <= 1) {
                    return ['success' => false, 'message' => 'Não é possível excluir o último administrador ativo'];
                }
            }

            $pdo = Database::getInstance()->getPDO();
            $pdo->beginTransaction();

            try {
                Database::getInstance()->update('posts', ['author_id' => $currentUserId], "author_id = $userId");
                Database::getInstance()->update('pages', ['author_id' => $currentUserId], "author_id = $userId");
                Database::getInstance()->update('images', ['uploaded_by' => $currentUserId], "uploaded_by = $userId");
                Database::getInstance()->update('slide_decks', ['created_by' => $currentUserId], "created_by = $userId");
                Database::getInstance()->update('backup_runs', ['created_by' => $currentUserId], "created_by = $userId");
                Database::getInstance()->delete('comments', "user_id = $userId");
                Database::getInstance()->delete('users', "id = $userId");

                $pdo->commit();
            } catch (Exception $e) {
                $pdo->rollBack();
                throw $e;
            }

            return ['success' => true, 'message' => 'Usuário excluído com sucesso'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Erro ao excluir usuário: ' . $e->getMessage()];
        }
    }
}

// Inicializar sessão
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
