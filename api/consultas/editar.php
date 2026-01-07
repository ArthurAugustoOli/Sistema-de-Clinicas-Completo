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
    
    if (!$id) {
        echo json_encode(['success' => false, 'message' => 'ID da consulta não fornecido']);
        exit;
    }
    
    $pacienteCpf = $_POST['paciente_cpf'];
    $profissionalId = $_POST['profissional_id'];
    $dataConsulta = $_POST['data_consulta'];
    $servicoId = isset($_POST['servico_id']) ? $_POST['servico_id'] : null;
    $procedimento = isset($_POST['procedimento']) ? $_POST['procedimento'] : null;
    $observacoes = isset($_POST['observacoes']) ? $_POST['observacoes'] : null;
    $status = isset($_POST['status']) ? $_POST['status'] : 'Agendada';
    
    // Dados financeiros
    $valor = isset($_POST['valor']) ? $_POST['valor'] : 0;
    $statusPagamento = isset($_POST['status_pagamento']) ? $_POST['status_pagamento'] : 'PENDENTE';
    $dataPagamento = isset($_POST['data_pagamento']) && !empty($_POST['data_pagamento']) ? $_POST['data_pagamento'] : null;
    
    // Iniciar transação
    $conn->begin_transaction();
    
    try {
        // Atualizar consulta
        $sql = "UPDATE consultas SET 
                paciente_cpf = ?, 
                profissional_id = ?, 
                data_consulta = ?, 
                servico_id = ?, 
                procedimento = ?, 
                observacoes = ?, 
                status = ? 
                WHERE id = ?";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('sisssssi', $pacienteCpf, $profissionalId, $dataConsulta, $servicoId, $procedimento, $observacoes, $status, $id);
        $stmt->execute();
        
        // Verificar se já existe registro financeiro
        $sqlCheckFinanceiro = "SELECT id FROM financeiro WHERE consulta_id = ?";
        $stmtCheckFinanceiro = $conn->prepare($sqlCheckFinanceiro);
        $stmtCheckFinanceiro->bind_param('i', $id);
        $stmtCheckFinanceiro->execute();
        
        $financeiroExistente = $stmtCheckFinanceiro->get_result()->fetch_assoc();
        
        if ($financeiroExistente) {
            // Atualizar registro financeiro
            $sqlFinanceiro = "UPDATE financeiro SET 
                             servico_id = ?, 
                             valor = ?, 
                             status_pagamento = ?, 
                             data_pagamento = ? 
                             WHERE consulta_id = ?";
            
            $stmtFinanceiro = $conn->prepare($sqlFinanceiro);
            $stmtFinanceiro->bind_param('idssi', $servicoId, $valor, $statusPagamento, $dataPagamento, $id);
            $stmtFinanceiro->execute();
        } else {
            // Inserir novo registro financeiro
            $sqlFinanceiro = "INSERT INTO financeiro (consulta_id, servico_id, valor, status_pagamento, data_pagamento) 
                             VALUES (?, ?, ?, ?, ?)";
            
            $stmtFinanceiro = $conn->prepare($sqlFinanceiro);
            $stmtFinanceiro->bind_param('iidss', $id, $servicoId, $valor, $statusPagamento, $dataPagamento);
            $stmtFinanceiro->execute();
        }
        
        // Commit da transação
        $conn->commit();
        
        echo json_encode(['success' => true, 'message' => 'Consulta atualizada com sucesso!', 'id' => $id]);
    } catch (Exception $e) {
        // Rollback em caso de erro
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => 'Erro ao atualizar consulta: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Método de requisição inválido']);
}
?>

