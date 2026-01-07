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
$profissionalId = isset($_GET['profissional_id']) ? intval($_GET['profissional_id']) : 0;
$data = isset($_GET['data']) ? $_GET['data'] : '';

// Validar parâmetros
if (!$profissionalId || !$data) {
    header('Content-Type: application/json');
    echo json_encode([]);
    exit;
}

// Consultar horários ocupados
$sql = "
    SELECT c.id, c.data_consulta, c.status, p.nome as paciente_nome, c.procedimento, s.duracao_minutos
    FROM consultas c
    JOIN pacientes p ON c.paciente_cpf = p.cpf
    LEFT JOIN servicos s ON c.servico_id = s.id
    WHERE c.profissional_id = ?
    AND DATE(c.data_consulta) = ?
    ORDER BY c.data_consulta
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("is", $profissionalId, $data);
$stmt->execute();
$result = $stmt->get_result();

$horarios = [];
while ($row = $result->fetch_assoc()) {
    $horarios[] = $row;
}

// Retornar resultado
header('Content-Type: application/json');
echo json_encode($horarios);
?>

