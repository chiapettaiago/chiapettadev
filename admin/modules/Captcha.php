<?php
/**
 * Captcha simples baseado em sessão.
 */

class Captcha {
    private const SESSION_KEY = 'captcha_challenges';

    private static function ensureSession() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    private static function makeChallenge() {
        $left = random_int(2, 12);
        $right = random_int(1, 9);
        $operator = random_int(0, 1) === 1 ? '+' : '-';

        if ($operator === '-' && $right > $left) {
            [$left, $right] = [$right, $left];
        }

        $answer = $operator === '+' ? $left + $right : $left - $right;

        return [
            'question' => "{$left} {$operator} {$right}",
            'answer' => (string) $answer,
            'created_at' => time(),
        ];
    }

    public static function getQuestion($key) {
        self::ensureSession();

        if (empty($_SESSION[self::SESSION_KEY][$key])) {
            $_SESSION[self::SESSION_KEY][$key] = self::makeChallenge();
        }

        return $_SESSION[self::SESSION_KEY][$key]['question'];
    }

    public static function reset($key) {
        self::ensureSession();
        unset($_SESSION[self::SESSION_KEY][$key]);
    }

    public static function verify($key, $answer) {
        self::ensureSession();

        $challenge = $_SESSION[self::SESSION_KEY][$key] ?? null;
        self::reset($key);

        if (!$challenge || time() - ($challenge['created_at'] ?? 0) > 600) {
            return false;
        }

        return hash_equals($challenge['answer'], trim((string) $answer));
    }
}
?>
