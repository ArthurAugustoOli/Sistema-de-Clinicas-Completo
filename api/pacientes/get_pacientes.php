<?php
// Iniciar sessão
session_start();

// Conexão com o banco de dados
require_once '../../config/config.php';

// Definir cabeçalho para JSON
header('Content-Type: application/json');

// Buscar todos os pacientes
$query = "SELECT cpf, nome FROM pacientes ORDER BY nome";
$result = $conn->query($query);

if (!$result) {
    echo json_encode(['success' => false, 'message' => 'Erro ao buscar pacientes: ' . $conn->error]);
    exit;
}

$pacientes = [];
while ($row = $result->fetch_assoc()) {
    $pacientes[] = $row;
}

echo json_encode(['success' => true, 'pacientes' => $pacientes]);

$conn->close();

