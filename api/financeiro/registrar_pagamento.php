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
$consulta_id = isset($_POST['consulta_id']) ? intval($_POST['consulta_id']) : 0;
$servico_id = isset($_POST['servico_id']) ? intval($_POST['servico_id']) : 0;
$valor = isset($_POST['valor']) ? floatval($_POST['valor']) : 0;
$status = isset($_POST['status']) ? $_POST['status'] : 'PENDENTE';

// Validar dados
if ($consulta_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID de consulta inválido.']);
    exit;
}

if ($servico_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID de serviço inválido.']);
    exit;
}

if ($valor <= 0) {
    echo json_encode(['success' => false, 'message' => 'Valor inválido.']);
    exit;
}

if (!in_array($status, ['PENDENTE', 'PAGO'])) {
    echo json_encode(['success' => false, 'message' => 'Status inválido.']);
    exit;
}

// Verificar se a consulta existe
$sqlCheckConsulta = "SELECT id FROM consultas WHERE id = ?";
$stmtCheckConsulta = $conn->prepare($sqlCheckConsulta);
$stmtCheckConsulta->bind_param("i", $consulta_id);
$stmtCheckConsulta->execute();
$resultCheckConsulta = $stmtCheckConsulta->get_result();

if ($resultCheckConsulta->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Consulta não encontrada.']);
    exit;
}
$stmtCheckConsulta->close();

// Verificar se o serviço existe
$sqlCheckServico = "SELECT id, nome_servico, preco FROM servicos WHERE id = ?";
$stmtCheckServico = $conn->prepare($sqlCheckServico);
$stmtCheckServico->bind_param("i", $servico_id);
$stmtCheckServico->execute();
$resultCheckServico = $stmtCheckServico->get_result();

if ($resultCheckServico->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Serviço não encontrado.']);
    exit;
}

$servico = $resultCheckServico->fetch_assoc();
$stmtCheckServico->close();

// Iniciar transação
$conn->begin_transaction();

try {
    // Registrar pagamento
    $sql = "INSERT INTO financeiro (consulta_id, servico_id, valor, status_pagamento, data_pagamento) VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    
    $dataPagamento = null;
    if ($status === 'PAGO') {
        $dataPagamento = date('Y-m-d H:i:s');
    }
    
    $stmt->bind_param("iidss", $consulta_id, $servico_id, $valor, $status, $dataPagamento);
    $stmt->execute();
    
    $id_inserido = $conn->insert_id;
    
    // Registrar atividade
    if (function_exists('registrarAtividade')) {
        registrarAtividade("Novo pagamento registrado para consulta #$consulta_id: {$servico['nome_servico']} - R$ $valor", $conn);
    }
    
    // Confirmar transação
    $conn->commit();
    
    echo json_encode([
        'success' => true, 
        'message' => 'Pagamento registrado com sucesso.',
        'data' => [
            'id' => $id_inserido,
            'consulta_id' => $consulta_id,
            'servico_id' => $servico_id,
            'servico_nome' => $servico['nome_servico'],
            'valor' => $valor,
            'status' => $status,
            'data_pagamento' => $dataPagamento ? formataData($dataPagamento, true) : null
        ]
    ]);
    
} catch (Exception $e) {
    // Reverter transação em caso de erro
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => 'Erro ao registrar pagamento: ' . $e->getMessage()]);
}

// Fechar conexão
$stmt->close();
$conn->close();

