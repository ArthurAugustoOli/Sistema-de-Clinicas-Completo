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

// Obter parâmetros de paginação e filtro
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$limit = isset($_GET['limit']) ? intval($_GET['limit']) : 10;
$offset = ($page - 1) * $limit;

$search = isset($_GET['search']) ? $_GET['search'] : '';
$status = isset($_GET['status']) ? $_GET['status'] : '';
$servico_id = isset($_GET['servico_id']) ? intval($_GET['servico_id']) : 0;
$data_inicio = isset($_GET['data_inicio']) ? $_GET['data_inicio'] : '';
$data_fim = isset($_GET['data_fim']) ? $_GET['data_fim'] : '';

// Preparar condições de filtro
$whereConditions = [];
$params = [];
$types = "";

if (!empty($search)) {
    $whereConditions[] = "(p.nome LIKE ? OR p.cpf LIKE ? OR s.nome_servico LIKE ?)";
    $searchParam = "%$search%";
    $params[] = $searchParam;
    $params[] = $searchParam;
    $params[] = $searchParam;
    $types .= "sss";
}

if (!empty($status)) {
    $whereConditions[] = "f.status_pagamento = ?";
    $params[] = $status;
    $types .= "s";
}

if ($servico_id > 0) {
    $whereConditions[] = "f.servico_id = ?";
    $params[] = $servico_id;
    $types .= "i";
}

if (!empty($data_inicio)) {
    $data_inicio_formatada = date('Y-m-d', strtotime($data_inicio));
    $whereConditions[] = "f.data_criacao >= ?";
    $params[] = $data_inicio_formatada . " 00:00:00";
    $types .= "s";
}

if (!empty($data_fim)) {
    $data_fim_formatada = date('Y-m-d', strtotime($data_fim));
    $whereConditions[] = "f.data_criacao <= ?";
    $params[] = $data_fim_formatada . " 23:59:59";
    $types .= "s";
}

$whereClause = !empty($whereConditions) ? "WHERE " . implode(" AND ", $whereConditions) : "";

// Consulta para contar o total de registros
$sqlCount = "
    SELECT COUNT(*) as total 
    FROM financeiro f
    JOIN servicos s ON f.servico_id = s.id
    JOIN consultas c ON f.consulta_id = c.id
    JOIN pacientes p ON c.paciente_cpf = p.cpf
    $whereClause
";

$stmtCount = $conn->prepare($sqlCount);
if (!empty($params)) {
    $stmtCount->bind_param($types, ...$params);
}
$stmtCount->execute();
$resultCount = $stmtCount->get_result();
$rowCount = $resultCount->fetch_assoc();
$totalRecords = $rowCount['total'];

// Calcular o número total de páginas
$totalPages = ceil($totalRecords / $limit);

// Consulta para obter os registros financeiros com paginação
$sql = "
    SELECT 
        f.id,
        f.consulta_id,
        f.servico_id,
        f.valor,
        f.status_pagamento,
        f.data_criacao,
        f.data_pagamento,
        s.nome_servico,
        p.nome as nome_paciente,
        p.cpf as cpf_paciente
    FROM financeiro f
    JOIN servicos s ON f.servico_id = s.id
    JOIN consultas c ON f.consulta_id = c.id
    JOIN pacientes p ON c.paciente_cpf = p.cpf
    $whereClause
    ORDER BY f.data_criacao DESC
    LIMIT ?, ?
";

$stmt = $conn->prepare($sql);
$bindParams = $params;
$bindParams[] = $offset;
$bindParams[] = $limit;
$types .= "ii";

$stmt->bind_param($types, ...$bindParams);
$stmt->execute();
$result = $stmt->get_result();

$registros = [];
while ($row = $result->fetch_assoc()) {
    $registros[] = [
        'id' => $row['id'],
        'consulta_id' => $row['consulta_id'],
        'servico_id' => $row['servico_id'],
        'valor' => floatval($row['valor']),
        'status_pagamento' => $row['status_pagamento'],
        'data_criacao' => formataData($row['data_criacao'], true),
        'data_pagamento' => $row['data_pagamento'] ? formataData($row['data_pagamento'], true) : null,
        'servico' => $row['nome_servico'],
        'paciente' => [
            'nome' => $row['nome_paciente'],
            'cpf' => $row['cpf_paciente']
        ]
    ];
}

// Obter lista de serviços para filtro
$sqlServicos = "SELECT id, nome_servico FROM servicos ORDER BY nome_servico ASC";
$resultServicos = $conn->query($sqlServicos);
$servicos = [];
while ($row = $resultServicos->fetch_assoc()) {
    $servicos[] = [
        'id' => $row['id'],
        'nome' => $row['nome_servico']
    ];
}

// Retornar dados
echo json_encode([
    'success' => true,
    'data' => [
        'registros' => $registros,
        'pagination' => [
            'total' => $totalRecords,
            'per_page' => $limit,
            'current_page' => $page,
            'last_page' => $totalPages
        ],
        'filtros' => [
            'servicos' => $servicos
        ]
    ]
]);

// Fechar conexões
$stmtCount->close();
$stmt->close();
$conn->close();

