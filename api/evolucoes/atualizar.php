<?php
header('Content-Type: application/json; charset=utf-8');
// Para debugar só durante o desenvolvimento:
// ini_set('display_errors', 1);
// error_reporting(E_ALL);

require_once '../../config/config.php';

// 1) Coleta e validações
$id        = isset($_POST['id'])       ? (int) $_POST['id']       : 0;
$cpf       = $_POST['cpf']             ?? '';
$titulo    = trim($_POST['titulo']     ?? '');
$subtitulo = trim($_POST['subtitulo']  ?? '');
$horarioIn = $_POST['horario']         ?? '';

if ($id <= 0 || !$cpf || !$titulo || !$horarioIn) {
    echo json_encode(['success'=>false,'message'=>'Dados incompletos']);
    exit;
}

// 2) Converte d/m/Y H:i → Y-m-d H:i:s
$dt = DateTime::createFromFormat('d/m/Y H:i', $horarioIn);
if (!$dt) {
    echo json_encode(['success'=>false,'message'=>'Formato de data inválido']);
    exit;
}
$horario = $dt->format('Y-m-d H:i:s');

// 3) Executa UPDATE dentro de transação
$conn->begin_transaction();
try {
    $sql = "
      UPDATE evolucoes
         SET titulo       = ?,
             subtitulo    = ?,
             data_horario = ?
       WHERE id = ?
         AND paciente_cpf = ?
    ";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new Exception("Erro no prepare: ".$conn->error);
    }
    // tipos: s = string, i = integer
    $stmt->bind_param("sssis", $titulo, $subtitulo, $horario, $id, $cpf);
    $stmt->execute();

    // 4) Verifica se alguma linha foi efetivamente atualizada
    if ($stmt->affected_rows < 1) {
        throw new Exception("Nenhuma linha atualizada (id={$id}, cpf={$cpf})");
    }

    $conn->commit();
    echo json_encode(['success'=>true]);
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
exit;
