<?php
session_start();

require_once '../../config/config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método não permitido']);
    exit;
}

if (!isset($_POST['id']) || empty($_POST['id'])) {
    echo json_encode(['success' => false, 'message' => 'ID do documento não fornecido']);
    exit;
}

$documentoId = (int)$_POST['id'];

try {
    // Buscar informações do documento
    $sqlSelect = "SELECT caminho_arquivo, paciente_cpf FROM paciente_documentos WHERE id = ?";
    $stmtSelect = $conn->prepare($sqlSelect);
    $stmtSelect->bind_param("i", $documentoId);
    $stmtSelect->execute();
    $result = $stmtSelect->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Documento não encontrado']);
        exit;
    }
    
    $documento = $result->fetch_assoc();
    $stmtSelect->close();
    
    // Iniciar transação
    $conn->begin_transaction();
    
    // Excluir registro do banco de dados
    $sqlDelete = "DELETE FROM paciente_documentos WHERE id = ?";
    $stmtDelete = $conn->prepare($sqlDelete);
    $stmtDelete->bind_param("i", $documentoId);
    
    if (!$stmtDelete->execute()) {
        throw new Exception('Erro ao excluir documento do banco de dados: ' . $stmtDelete->error);
    }
    
    $stmtDelete->close();
    
    // Excluir arquivo físico
    $caminhoCompleto = '../../' . $documento['caminho_arquivo'];
    if (file_exists($caminhoCompleto)) {
        if (!unlink($caminhoCompleto)) {
            // Não vamos interromper a operação se o arquivo não puder ser excluído
            // Apenas registramos o erro
            error_log('Não foi possível excluir o arquivo: ' . $caminhoCompleto);
        }
    }
    
    $conn->commit();
    echo json_encode(['success' => true, 'message' => 'Documento excluído com sucesso']);
    
} catch (Exception $e) {
    if (isset($conn) && $conn->connect_errno === 0) {
        $conn->rollback();
    }
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

if (isset($conn)) $conn->close();

