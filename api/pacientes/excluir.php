<?php
session_start();

require_once '../../config/config.php';
require_once '../../functions/utils/helpers.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método não permitido']);
    exit;
}

if (!isset($_POST['cpf']) || empty($_POST['cpf'])) {
    echo json_encode(['success' => false, 'message' => 'CPF não fornecido']);
    exit;
}

$cpf = $_POST['cpf'];

// Verificar se o paciente existe
$sqlCheck = "SELECT nome, foto_perfil FROM pacientes WHERE cpf = ?";
$stmtCheck = $conn->prepare($sqlCheck);
$stmtCheck->bind_param("s", $cpf);
$stmtCheck->execute();
$resultCheck = $stmtCheck->get_result();

if ($resultCheck->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Paciente não encontrado']);
    exit;
}

$paciente = $resultCheck->fetch_assoc();

// Verificar se o paciente possui consultas
$sqlConsultas = "SELECT COUNT(*) as total FROM consultas WHERE paciente_cpf = ?";
$stmtConsultas = $conn->prepare($sqlConsultas);
$stmtConsultas->bind_param("s", $cpf);
$stmtConsultas->execute();
$resultConsultas = $stmtConsultas->get_result();
$rowConsultas = $resultConsultas->fetch_assoc();

// Se não quiser bloquear exclusão, comente este bloco
if ($rowConsultas['total'] > 0) {
    echo json_encode([
        'success' => false,
        'message' => 'Não é possível excluir o paciente pois ele possui consultas registradas'
    ]);
    exit;
}

// Iniciar transação
$conn->begin_transaction();

try {
    // Buscar documentos
    $sqlDocs = "SELECT caminho_arquivo FROM paciente_documentos WHERE paciente_cpf = ?";
    $stmtDocs = $conn->prepare($sqlDocs);
    $stmtDocs->bind_param("s", $cpf);
    $stmtDocs->execute();
    $resultDocs = $stmtDocs->get_result();

    // Excluir arquivos físicos
    while ($doc = $resultDocs->fetch_assoc()) {
        $caminhoArquivo = '../../' . $doc['caminho_arquivo'];
        if (file_exists($caminhoArquivo)) {
            @unlink($caminhoArquivo);
        }
    }
    $resultDocs->close();
    $stmtDocs->close();

    // Excluir documentos
    $sqlDeleteDocs = "DELETE FROM paciente_documentos WHERE paciente_cpf = ?";
    $stmtDeleteDocs = $conn->prepare($sqlDeleteDocs);
    $stmtDeleteDocs->bind_param("s", $cpf);
    $stmtDeleteDocs->execute();
    $stmtDeleteDocs->close();

    // Excluir paciente
    $sqlDelete = "DELETE FROM pacientes WHERE cpf = ?";
    $stmtDelete = $conn->prepare($sqlDelete);
    $stmtDelete->bind_param("s", $cpf);
    $stmtDelete->execute();
    $stmtDelete->close();

    // Commit
    $conn->commit();

    // Remover foto de perfil se existir
    if (!empty($paciente['foto_perfil']) && file_exists('../../' . $paciente['foto_perfil'])) {
        @unlink('../../' . $paciente['foto_perfil']);
    }

    // Desativar ou comentar a função de log
    /*
    if (function_exists('geraLog')) {
        geraLog(
            'Exclusão de Paciente',
            "Paciente {$paciente['nome']} (CPF: {$cpf}) excluído com sucesso",
            isset($_SESSION['usuario']) ? $_SESSION['usuario'] : 'Sistema'
        );
    }
    */

    echo json_encode(['success' => true, 'message' => 'Paciente excluído com sucesso']);
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => 'Erro ao excluir paciente: ' . $e->getMessage()]);
}

// Encerrar statements e conexão
$resultCheck->close();
$stmtCheck->close();
$stmtConsultas->close();
$resultConsultas->close();
$conn->close();
