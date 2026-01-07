<?php
header('Content-Type: application/json; charset=utf-8');
ini_set('display_errors', 0);
error_reporting(0);

require_once '../../config/config.php';

$cpf       = $_POST['cpf']      ?? '';
$titulo    = trim($_POST['titulo']    ?? '');
$subtitulo = trim($_POST['subtitulo'] ?? '');
$horarioIn = $_POST['horario']  ?? '';

if (!$cpf || !$titulo || !$horarioIn) {
    echo json_encode(['success'=>false,'message'=>'Dados incompletos']);
    exit;
}

// converte de d/m/Y H:i para Y-m-d H:i:s
$dt = DateTime::createFromFormat('d/m/Y H:i', $horarioIn);
if (!$dt) {
    echo json_encode(['success'=>false,'message'=>'Data inválida']);
    exit;
}
$horario = $dt->format('Y-m-d H:i:s');

$stmt = $conn->prepare("
  INSERT INTO evolucoes (paciente_cpf, titulo, subtitulo, data_horario)
  VALUES (?, ?, ?, ?)
");
$stmt->bind_param("ssss", $cpf, $titulo, $subtitulo, $horario);
$ok = $stmt->execute();

echo json_encode([
  'success' => $ok,
  'message' => $ok ? null : $stmt->error
]);
exit;
