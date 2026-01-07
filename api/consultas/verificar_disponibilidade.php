<?php
// Start session
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
$profissionalId = isset($_GET['profissional_id']) ? intval($_GET['profissional_id']) : (isset($_POST['profissional_id']) ? intval($_POST['profissional_id']) : 0);
$dataInicio = isset($_GET['data_inicio']) ? $_GET['data_inicio'] : (isset($_POST['data_inicio']) ? $_POST['data_inicio'] : '');
$dataFim = isset($_GET['data_fim']) ? $_GET['data_fim'] : (isset($_POST['data_fim']) ? $_POST['data_fim'] : '');
$consultaId = isset($_GET['consulta_id']) ? intval($_GET['consulta_id']) : (isset($_POST['consulta_id']) ? intval($_POST['consulta_id']) : 0);

// Validar parâmetros
if (!$profissionalId || !$dataInicio) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Parâmetros inválidos', 'disponivel' => false]);
    exit;
}

// Se não tiver data fim, calcular baseado na data início (1 hora depois)
if (!$dataFim) {
    $dataInicioObj = new DateTime($dataInicio);
    $dataFimObj = clone $dataInicioObj;
    $dataFimObj->add(new DateInterval('PT1H'));
    $dataFim = $dataFimObj->format('Y-m-d\TH:i:s');
}

// Consultar horários ocupados
$sql = "
    SELECT c.id, c.data_consulta, p.nome as paciente_nome, s.duracao_minutos
    FROM consultas c
    JOIN pacientes p ON c.paciente_cpf = p.cpf
    LEFT JOIN servicos s ON c.servico_id = s.id
    WHERE c.profissional_id = ?
    AND c.status != 'Cancelada'
    AND c.data_consulta < ?
    AND DATE(c.data_consulta) = DATE(?)
";

$params = [$profissionalId, $dataFim, $dataInicio];
$types = "iss";

// Excluir a própria consulta se estiver editando
if ($consultaId) {
    $sql .= " AND c.id != ?";
    $params[] = $consultaId;
    $types .= "i";
}

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

// Verificar disponibilidade
$disponivel = true;
$horariosOcupados = [];

while ($row = $result->fetch_assoc()) {
    // Calcular hora de início e fim da consulta existente
    $dataConsulta = new DateTime($row['data_consulta']);
    $horaInicioExistente = $dataConsulta->format('H:i');
    
    // Calcular hora de fim baseada na duração
    $duracao = $row['duracao_minutos'] ?: 60; // Padrão de 60 minutos se não tiver duração
    $dataFimExistente = clone $dataConsulta;
    $dataFimExistente->add(new DateInterval('PT' . $duracao . 'M'));
    $horaFimExistente = $dataFimExistente->format('H:i');
    
    // Adicionar à lista de horários ocupados
    $horariosOcupados[] = [
        'inicio' => $horaInicioExistente,
        'fim' => $horaFimExistente,
        'paciente' => $row['paciente_nome']
    ];
    
    // Verificar se há sobreposição
    $horaInicioNova = new DateTime($dataInicio);
    $horaFimNova = new DateTime($dataFim);
    
    // Verificar sobreposição
    if (
        ($horaInicioNova >= $dataConsulta && $horaInicioNova < $dataFimExistente) ||
        ($horaFimNova > $dataConsulta && $horaFimNova <= $dataFimExistente) ||
        ($horaInicioNova <= $dataConsulta && $horaFimNova >= $dataFimExistente)
    ) {
        $disponivel = false;
    }
}

// Ordenar horários ocupados por hora de início
usort($horariosOcupados, function($a, $b) {
    return strcmp($a['inicio'], $b['inicio']);
});

// Retornar resultado
header('Content-Type: application/json');
echo json_encode([
    'disponivel' => $disponivel,
    'horarios_ocupados' => $horariosOcupados
]);
?>

