<?php 

    require 'functions/conn.php';

    $sql = "
    
    CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nome VARCHAR(100),
        email VARCHAR(100),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )
    ";

    $pdo->exec($sql);

    echo "Tabela criada com sucesso"

?>