<?php
// Iniciar sessão para gerenciamento de login
session_start();

// Conexão com o banco de dados
require_once '../../config/config.php';

// Funções utilitárias
require_once '../../functions/utils/helpers.php';

// Definir cabeçalho JSON
header('Content-Type: application/json');

// Verificar autenticação
if (!isset($_SESSION['logado']) || $_SESSION['logado'] !== true) {
    echo json_encode(['success' => false, 'message' => 'Acesso não autorizado.']);
    exit;
}

// Verificar se é uma requisição POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método não permitido.']);
    exit;
}

// Obter dados da requisição
$id = isset($_POST['id']) ? intval($_POST['id']) : 0;
$status = isset($_POST['status']) ? $_POST['status'] : '';

// Validar dados
if ($id <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID inválido.']);
    exit;
}

if (!in_array($status, ['PENDENTE', 'PAGO'])) {
    echo json_encode(['success' => false, 'message' => 'Status inválido.']);
    exit;
}

// Iniciar transação
$conn->begin_transaction();

try {
    // Atualizar status do pagamento
    $sql = "UPDATE financeiro SET status_pagamento = ?, data_pagamento = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    
    $dataPagamento = null;
    if ($status === 'PAGO') {
        $dataPagamento = date('Y-m-d H:i:s');
    }
    
    $stmt->bind_param("ssi", $status, $dataPagamento, $id);
    $stmt->execute();
    
    if ($stmt->affected_rows === 0) {
        throw new Exception('Registro não encontrado ou nenhuma alteração realizada.');
    }
    
    // Registrar atividade
    if (function_exists('registrarAtividade')) {
        $acao = $status === 'PAGO' ? 'Pagamento confirmado' : 'Pagamento marcado como pendente';
        registrarAtividade("$acao para o registro financeiro #$id", $conn);
    }
    
    // Confirmar transação
    $conn->commit();
    
    echo json_encode([
        'success' => true, 
        'message' => 'Status atualizado com sucesso.',
        'data' => [
            'id' => $id,
            'status' => $status,
            'data_pagamento' => $dataPagamento ? formataData($dataPagamento, true) : null
        ]
    ]);
    
} catch (Exception $e) {
    // Reverter transação em caso de erro
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => 'Erro ao atualizar status: ' . $e->getMessage()]);
}

// Fechar conexão
$stmt->close();
$conn->close();
