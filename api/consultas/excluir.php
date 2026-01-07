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
if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    // Obter ID da consulta
    $id = isset($_GET['id']) ? $_GET['id'] : null;
    
    if (!$id) {
        echo json_encode(['success' => false, 'message' => 'ID da consulta não fornecido']);
        exit;
    }
    
    // Iniciar transação
    $conn->begin_transaction();
    
    try {
        // Excluir registros financeiros relacionados
        $sqlFinanceiro = "DELETE FROM financeiro WHERE consulta_id = ?";
        $stmtFinanceiro = $conn->prepare($sqlFinanceiro);
        $stmtFinanceiro->bind_param('i', $id); // Substituído bindParam por bind_param
        $stmtFinanceiro->execute();
        
        // Excluir consulta
        $sqlConsulta = "DELETE FROM consultas WHERE id = ?";
        $stmtConsulta = $conn->prepare($sqlConsulta);
        $stmtConsulta->bind_param('i', $id); // Substituído bindParam por bind_param
        $stmtConsulta->execute();
        
        // Commit da transação
        $conn->commit();
        
        echo json_encode(['success' => true, 'message' => 'Consulta excluída com sucesso']);
    } catch (Exception $e) {
        // Rollback em caso de erro
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => 'Erro ao excluir consulta: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Método de requisição inválido']);
}
?>