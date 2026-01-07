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
$action = $_GET['action'] ?? '';
$dataInicio = $_GET['data_inicio'] ?? null;
$dataFim = $_GET['data_fim'] ?? null;
$status = $_GET['status'] ?? null;
$profissionalId = $_GET['profissional_id'] ?? null;
$pacienteCpf = $_GET['paciente_cpf'] ?? null;
$servicoId = $_GET['servico_id'] ?? null;

if ($action === 'getAll') {
    // Construir consulta SQL base
    $sql = "SELECT c.*, p.nome AS paciente_nome, f.nome AS profissional_nome, s.duracao_minutos 
            FROM consultas c
            LEFT JOIN pacientes p ON c.paciente_cpf = p.cpf
            LEFT JOIN funcionarios f ON c.profissional_id = f.id
            LEFT JOIN servicos s ON c.servico_id = s.id
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

    $sql .= " ORDER BY c.data_consulta ASC";

    try {
        $stmt = $conn->prepare($sql);
        $stmt->bind_param(str_repeat('s', count($params)), ...$params); // Bind parameters dinamicamente
        $stmt->execute();
    
        // Vincular os resultados manualmente
        $meta = $stmt->result_metadata();
        $fields = [];
        $row = [];
    
        while ($field = $meta->fetch_field()) {
            $fields[] = &$row[$field->name];
        }
    
        call_user_func_array([$stmt, 'bind_result'], $fields);
    
        $consultas = [];
        while ($stmt->fetch()) {
            $consulta = [];
            foreach ($row as $key => $val) {
                $consulta[$key] = $val;
            }
            $consultas[] = $consulta;
        }
    
        echo json_encode($consultas);
    } catch (Exception $e) {
        echo json_encode(['error' => 'Erro ao buscar consultas: ' . $e->getMessage()]);
    }
    
    } else {
        echo json_encode(['error' => 'Ação inválida']);
    }
?>