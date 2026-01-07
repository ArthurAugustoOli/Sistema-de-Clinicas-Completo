<?php
session_start();

// Verificar autenticação
if (!isset($_SESSION['logado']) || $_SESSION['logado'] !== true) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Usuário não autenticado']);
    exit;
}

// Conexão com o banco de dados
require_once '../../config/config.php';

// Obter parâmetros
$periodo = isset($_GET['periodo']) ? $_GET['periodo'] : 'dia';
$profissional_id = isset($_GET['profissional_id']) ? $_GET['profissional_id'] : '';
$servico_id = isset($_GET['servico_id']) ? $_GET['servico_id'] : '';

// Definir datas com base no período
$data_atual = date('Y-m-d');
$data_inicio = $data_atual;
$data_fim = $data_atual;

switch ($periodo) {
    case 'semana':
        $data_inicio = date('Y-m-d', strtotime('-7 days'));
        break;
    case 'mes':
        $data_inicio = date('Y-m-d', strtotime('-30 days'));
        break;
}

// Definir datas do período anterior para comparação
$data_inicio_anterior = date('Y-m-d', strtotime($data_inicio . ' -' . (strtotime($data_fim) - strtotime($data_inicio) + 86400) . ' seconds'));
$data_fim_anterior = date('Y-m-d', strtotime($data_inicio . ' -1 day'));

// Construir condições SQL
$condicoes = "WHERE data_consulta BETWEEN '$data_inicio' AND '$data_fim 23:59:59'";
$condicoes_anterior = "WHERE data_consulta BETWEEN '$data_inicio_anterior' AND '$data_fim_anterior 23:59:59'";

if (!empty($profissional_id)) {
    $condicoes .= " AND profissional_id = $profissional_id";
    $condicoes_anterior .= " AND profissional_id = $profissional_id";
}

if (!empty($servico_id)) {
    $condicoes .= " AND servico_id = $servico_id";
    $condicoes_anterior .= " AND servico_id = $servico_id";
}

// Consultas para o período atual
$sql_total = "SELECT COUNT(*) as total FROM consultas $condicoes";
$sql_por_status = "SELECT status, COUNT(*) as total FROM consultas $condicoes GROUP BY status";
$sql_concluidas = "SELECT COUNT(*) as total FROM consultas $condicoes AND status = 'Concluída'";
$sql_canceladas = "SELECT COUNT(*) as total FROM consultas $condicoes AND status = 'Cancelada'";
$sql_valor_total = "SELECT COALESCE(SUM(s.preco), 0) as total FROM consultas c LEFT JOIN servicos s ON c.servico_id = s.id $condicoes AND c.status != 'Cancelada'";
$sql_por_profissional = "SELECT f.id, f.nome, COUNT(*) as total FROM consultas c JOIN funcionarios f ON c.profissional_id = f.id $condicoes GROUP BY f.id ORDER BY total DESC";

// Consultas para o período anterior
$sql_total_anterior = "SELECT COUNT(*) as total FROM consultas $condicoes_anterior";
$sql_concluidas_anterior = "SELECT COUNT(*) as total FROM consultas $condicoes_anterior AND status = 'Concluída'";
$sql_canceladas_anterior = "SELECT COUNT(*) as total FROM consultas $condicoes_anterior AND status = 'Cancelada'";
$sql_valor_total_anterior = "SELECT COALESCE(SUM(s.preco), 0) as total FROM consultas c LEFT JOIN servicos s ON c.servico_id = s.id $condicoes_anterior AND c.status != 'Cancelada'";

// Executar consultas para o período atual
$result_total = $conn->query($sql_total);
$result_por_status = $conn->query($sql_por_status);
$result_concluidas = $conn->query($sql_concluidas);
$result_canceladas = $conn->query($sql_canceladas);
$result_valor_total = $conn->query($sql_valor_total);
$result_por_profissional = $conn->query($sql_por_profissional);

// Executar consultas para o período anterior
$result_total_anterior = $conn->query($sql_total_anterior);
$result_concluidas_anterior = $conn->query($sql_concluidas_anterior);
$result_canceladas_anterior = $conn->query($sql_canceladas_anterior);
$result_valor_total_anterior = $conn->query($sql_valor_total_anterior);

// Processar resultados
$total_consultas = $result_total->fetch_assoc()['total'];
$consultas_concluidas = $result_concluidas->fetch_assoc()['total'];
$consultas_canceladas = $result_canceladas->fetch_assoc()['total'];
$valor_total = $result_valor_total->fetch_assoc()['total'] ?? 0;

$total_consultas_anterior = $result_total_anterior->fetch_assoc()['total'];
$consultas_concluidas_anterior = $result_concluidas_anterior->fetch_assoc()['total'];
$consultas_canceladas_anterior = $result_canceladas_anterior->fetch_assoc()['total'];
$valor_total_anterior = $result_valor_total_anterior->fetch_assoc()['total'] ?? 0;

// Calcular taxas e comparações
$taxa_cancelamento = $total_consultas > 0 ? round(($consultas_canceladas / $total_consultas) * 100, 1) : 0;
$taxa_cancelamento_anterior = $total_consultas_anterior > 0 ? round(($consultas_canceladas_anterior / $total_consultas_anterior) * 100, 1) : 0;

$comparacao_total = $total_consultas_anterior > 0 ?
    round((($total_consultas - $total_consultas_anterior) / $total_consultas_anterior) * 100, 1) : 0;
$comparacao_concluidas = $consultas_concluidas_anterior > 0 ?
    round((($consultas_concluidas - $consultas_concluidas_anterior) / $consultas_concluidas_anterior) * 100, 1) : 0;
$comparacao_cancelamento = $taxa_cancelamento_anterior > 0 ?
    round((($taxa_cancelamento - $taxa_cancelamento_anterior) / $taxa_cancelamento_anterior) * 100, 1) : 0;
$comparacao_valor = $valor_total_anterior > 0 ?
    round((($valor_total - $valor_total_anterior) / $valor_total_anterior) * 100, 1) : 0;

// Processar consultas por status
$consultas_por_status = [
    'Agendada' => 0,
    'Confirmada' => 0,
    'Cancelada' => 0,
    'Concluída' => 0
];

if ($result_por_status) {
    while ($row = $result_por_status->fetch_assoc()) {
        $consultas_por_status[$row['status']] = (int)$row['total'];
    }
}

// Processar consultas por profissional
$consultas_por_profissional = [];
if ($result_por_profissional) {
    while ($row = $result_por_profissional->fetch_assoc()) {
        $consultas_por_profissional[] = [
            'id' => $row['id'],
            'nome' => $row['nome'],
            'total' => (int)$row['total']
        ];
    }
}

// Obter desempenho detalhado por profissional
$desempenho_profissionais = [];
$sql_desempenho = "
    SELECT 
      f.id,
      f.nome,
      COUNT(*) as total,
      SUM(CASE WHEN c.status = 'Concluída' THEN 1 ELSE 0 END) as concluidas,
      SUM(CASE WHEN c.status = 'Cancelada' THEN 1 ELSE 0 END) as canceladas
    FROM consultas c
    JOIN funcionarios f ON c.profissional_id = f.id
    $condicoes
    GROUP BY f.id
    ORDER BY total DESC
";

$result_desempenho = $conn->query($sql_desempenho);
if ($result_desempenho) {
    while ($row = $result_desempenho->fetch_assoc()) {
        $sql_anterior = "
            SELECT COUNT(*) as total
            FROM consultas
            WHERE profissional_id = {$row['id']}
            AND data_consulta BETWEEN '$data_inicio_anterior' AND '$data_fim_anterior 23:59:59'
        ";
        $result_anterior = $conn->query($sql_anterior);
        $total_anterior = $result_anterior ? $result_anterior->fetch_assoc()['total'] : 0;
        $comparacao = $total_anterior > 0 ? round((($row['total'] - $total_anterior) / $total_anterior) * 100, 1) : 0;
        $desempenho_profissionais[] = [
            'id' => $row['id'],
            'nome' => $row['nome'],
            'total' => (int)$row['total'],
            'concluidas' => (int)$row['concluidas'],
            'canceladas' => (int)$row['canceladas'],
            'comparacao' => $comparacao
        ];
    }
}

// Preparar resposta
$response = [
    'total_consultas' => (int)$total_consultas,
    'consultas_concluidas' => (int)$consultas_concluidas,
    'consultas_canceladas' => (int)$consultas_canceladas,
    'taxa_cancelamento' => $taxa_cancelamento,
    'valor_total' => (float)$valor_total,
    'comparacao_total' => $comparacao_total,
    'comparacao_concluidas' => $comparacao_concluidas,
    'comparacao_cancelamento' => $comparacao_cancelamento,
    'comparacao_valor' => $comparacao_valor,
    'consultas_por_status' => $consultas_por_status,
    'consultas_por_profissional' => $consultas_por_profissional,
    'desempenho_profissionais' => $desempenho_profissionais,
    'periodo' => [
        'inicio' => $data_inicio,
        'fim' => $data_fim
    ]
];

header('Content-Type: application/json');
echo json_encode($response);
?>
