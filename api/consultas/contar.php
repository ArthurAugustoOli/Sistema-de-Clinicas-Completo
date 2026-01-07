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

// Definir cabeçalho de resposta
header('Content-Type: application/json');

// Parâmetros da requisição
$dataInicio = $_GET['data_inicio'] ?? null;
$dataFim = $_GET['data_fim'] ?? null;
$status = $_GET['status'] ?? null;
$profissionalId = $_GET['profissional_id'] ?? null;
$pacienteCpf = $_GET['paciente_cpf'] ?? null;
$servicoId = $_GET['servico_id'] ?? null;

// Construir consulta SQL base
$sql = "SELECT DATE(data_consulta) as data, COUNT(*) as total 
        FROM consultas c
        WHERE 1=1";

$params = [];

if ($dataInicio && $dataFim) {
    $sql .= " AND DATE(c.data_consulta) BETWEEN ? AND ?";
    $params[] = $dataInicio;
    $params[] = $dataFim;
}
if ($status) {
    $sql .= " AND c.status = ?";
    $params[] = $status;
}
if ($profissionalId) {
    $sql .= " AND c.profissional_id = ?";
    $params[] = $profissionalId;
}
if ($pacienteCpf) {
    $sql .= " AND c.paciente_cpf = ?";
    $params[] = $pacienteCpf;
}
if ($servicoId) {
    $sql .= " AND c.servico_id = ?";
    $params[] = $servicoId;
}

$sql .= " GROUP BY DATE(data_consulta)";

try {
    $stmt = $conn->prepare($sql);
    
    if (count($params) > 0) {
        $types = str_repeat('s', count($params));
        $stmt->bind_param($types, ...$params);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    $contagem = [];
    while ($row = $result->fetch_assoc()) {
        $contagem[$row['data']] = (int)$row['total'];
    }
    
    echo json_encode($contagem);
} catch (Exception $e) {
    echo json_encode(['error' => 'Erro ao contar consultas: ' . $e->getMessage()]);
}
?>

