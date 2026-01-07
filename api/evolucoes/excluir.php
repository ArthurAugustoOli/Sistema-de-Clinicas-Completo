<?php
header('Content-Type: application/json; charset=utf-8');
require_once '../../config/config.php';

// 1) Coleta e validações
$id  = isset($_POST['id'])  ? (int) $_POST['id']  : 0;
$cpf = $_POST['cpf']        ?? '';

if ($id <= 0 || !$cpf) {
    echo json_encode(['success'=>false,'message'=>'Dados incompletos']);
    exit;
}

// 2) Executa DELETE
$stmt = $conn->prepare("
    DELETE FROM evolucoes
     WHERE id = ?
       AND paciente_cpf = ?
");
if (!$stmt) {
    echo json_encode(['success'=>false,'message'=>'Erro no prepare: '.$conn->error]);
    exit;
}
$stmt->bind_param("is", $id, $cpf);
$stmt->execute();

// 3) Verifica sucesso
if ($stmt->affected_rows > 0) {
    echo json_encode(['success'=>true]);
} else {
    echo json_encode(['success'=>false,'message'=>"Nenhuma evolução encontrada (id={$id})"]);
}
