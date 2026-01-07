<?php
// Iniciar sessão para gerenciamento de login
session_start();

// Verificar autenticação
if (!isset($_SESSION['logado']) || $_SESSION['logado'] !== true) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Não autorizado']);
    exit;
}

// Conexão com o banco de dados
require_once '../../config/config.php';

// Parâmetros da requisição
$action = isset($_GET['action']) ? $_GET['action'] : '';
$consultaId = isset($_GET['consulta_id']) ? $_GET['consulta_id'] : null;

// Definir cabeçalho de resposta
header('Content-Type: application/json');

// Processar ação
if ($action === 'getByConsultaId' && $consultaId) {
    try {
        $sql = "SELECT * FROM financeiro WHERE consulta_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('i', $consultaId); // Substituído bindParam por bind_param
        $stmt->execute();
        
        $result = $stmt->get_result(); // Obter o resultado
        $financeiro = $result->fetch_assoc(); // Buscar como array associativo
        
        if ($financeiro) {
            echo json_encode($financeiro);
        } else {
            echo json_encode(['error' => 'Registro financeiro não encontrado']);
        }
    } catch (Exception $e) {
        echo json_encode(['error' => 'Erro ao buscar registro financeiro: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['error' => 'Ação inválida ou ID da consulta não fornecido']);
}
?>