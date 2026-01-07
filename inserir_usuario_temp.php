<?php
// inserir_usuario_temp.php

// Incluir configuração do banco de dados
require_once 'config/config.php';

// Definir os dados do usuário
$username = 'admin';
$password = 'admin';
$nome     = 'Admaster';
$email    = 'gustavo@clinica.com';
$cargo    = 'Administrador';

// Gerar o hash da senha utilizando bcrypt
$hashed_password = password_hash($password, PASSWORD_DEFAULT);

// Preparar a query para inserir o usuário (ou ignorar se já existir)
$sql = "INSERT INTO usuarios (username, password, nome, email, cargo) 
        VALUES (?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE 
            password = VALUES(password),
            nome = VALUES(nome),
            email = VALUES(email),
            cargo = VALUES(cargo)";


if ($stmt = $conn->prepare($sql)) {
    $stmt->bind_param("sssss", $username, $hashed_password, $nome, $email, $cargo);
    
    if ($stmt->execute()) {
        echo "Usuário '{$username}' inserido com sucesso.";
    } else {
        echo "Erro ao inserir o usuário: " . $stmt->error;
    }
    
    $stmt->close();
} else {
    echo "Erro na preparação da query: " . $conn->error;
}

$conn->close();
?>
