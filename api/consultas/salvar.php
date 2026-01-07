<?php
// Incluir arquivo de configuração
include_once('../../config/config.php');

// Verificar se a conexão foi estabelecida
if (!$conn) {
    die("Falha na conexão com o banco de dados: " . mysqli_connect_error());
}

// Definir o tipo de conteúdo como JSON
header('Content-Type: application/json');

// Verificar método da requisição
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Iniciar transação
    $conn->begin_transaction();
    
    try {
        // Obter dados do formulário
        $id = isset($_POST['id']) && !empty($_POST['id']) ? $_POST['id'] : null;
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
        
        // Verificar se é uma atualização ou inserção
        if ($id) {
            // Atualizar consulta existente
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
            
            $mensagem = 'Consulta atualizada com sucesso!';
        } else {
            // Inserir nova consulta
            $sql = "INSERT INTO consultas (paciente_cpf, profissional_id, data_consulta, servico_id, procedimento, observacoes, status) 
                    VALUES (?, ?, ?, ?, ?, ?, ?)";
            
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('sisssss', $pacienteCpf, $profissionalId, $dataConsulta, $servicoId, $procedimento, $observacoes, $status);
            $stmt->execute();
            
            // Obter ID da consulta inserida
            $id = $stmt->insert_id;
            
            // Inserir registro financeiro
            $sqlFinanceiro = "INSERT INTO financeiro (consulta_id, servico_id, valor, status_pagamento, data_pagamento) 
                             VALUES (?, ?, ?, ?, ?)";
            
            $stmtFinanceiro = $conn->prepare($sqlFinanceiro);
            $stmtFinanceiro->bind_param('iidss', $id, $servicoId, $valor, $statusPagamento, $dataPagamento);
            $stmtFinanceiro->execute();
            
            $mensagem = 'Consulta agendada com sucesso!';
        }
        
        // Commit da transação
        $conn->commit();
        
        echo json_encode(['success' => true, 'message' => $mensagem, 'id' => $id]);
    } catch (Exception $e) {
        // Rollback em caso de erro
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => 'Erro ao salvar consulta: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Método de requisição inválido']);
}

// Fechar a conexão
$conn->close();
?>

