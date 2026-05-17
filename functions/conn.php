<?php 

    $host = '127.0.0.1';
    $banco = 'neverland';
    $usuario = 'neverland';
    $senha = 'EbATDwkiNkwxHjhJ';

    try {
        $pdo = new PDO(
            "mysql:host=$host;dbname=$banco;charset=utf8",
            $usuario,
            $senha
        );
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }catch (PDOException $e) {
        die("Erro ao conectar ao banco de dados: " . $e->getMessage());
    }


?>