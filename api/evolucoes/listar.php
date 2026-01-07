<?php
require_once __DIR__ . '/../../config/config.php';

$cpf = $_GET['cpf'] ?? '';
if (!$cpf) {
    echo json_encode(['success'=>false,'message'=>'CPF não informado']);
    exit;
}

$sql = "SELECT id, titulo, subtitulo, data_horario
        FROM evolucoes
       WHERE paciente_cpf = ?
       ORDER BY data_horario DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $cpf);
$stmt->execute();
$res = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

echo json_encode(['success'=>true, 'evolucoes'=>$res]);
?>