<?php
// Iniciar sessão para gerenciamento de login
session_start();

// Verificar autenticação
if (!isset($_SESSION['logado']) || $_SESSION['logado'] !== true) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Não autorizado']);
    exit;
}

// Conexão com o banco de dados
require_once '../../config/config.php';

// Definir cabeçalho de resposta
header('Content-Type: application/json');

// Verificar método da requisição
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Obter dados do formulário
    $id = isset($_POST['id']) ? $_POST['id'] : null;
    $dataConsulta = isset($_POST['data_consulta']) ? $_POST['data_consulta'] : null;
    
    if (!$id || !$dataConsulta) {
        echo json_encode(['success' => false, 'message' => 'Parâmetros incompletos']);
        exit;
    }
    
    try {
        // Atualizar data da consulta
        $sql = "UPDATE consultas SET data_consulta = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('si', $dataConsulta, $id); // Substituído bindParam por bind_param
        $stmt->execute();
        
        if ($stmt->affected_rows > 0) {
            echo json_encode(['success' => true, 'message' => 'Data da consulta atualizada com sucesso']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Nenhuma consulta foi atualizada. Verifique o ID.']);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Erro ao atualizar data da consulta: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Método de requisição inválido']);
}
?>