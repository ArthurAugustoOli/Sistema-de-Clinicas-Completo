<?php
// Iniciar sessão para gerenciamento de login
session_start();

// Conexão com o banco de dados
require_once '../../config/config.php';

// Funções utilitárias
require_once '../../functions/utils/helpers.php';

// Definir cabeçalho JSON
header('Content-Type: application/json');

// Verificar se é uma requisição POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método não permitido']);
    exit;
}

// Verificar se o ID do documento foi fornecido
if (!isset($_POST['documento_id']) || empty($_POST['documento_id'])) {
    echo json_encode(['success' => false, 'message' => 'ID do documento não fornecido']);
    exit;
}

$documentoId = (int)$_POST['documento_id'];

// Buscar informações do documento antes de excluir (para remover o arquivo físico)
$sqlSelect = "SELECT caminho_arquivo FROM paciente_documentos WHERE id = ?";
$stmtSelect = $conn->prepare($sqlSelect);
$stmtSelect->bind_param("i", $documentoId);
$stmtSelect->execute();
$resultSelect = $stmtSelect->get_result();

if ($resultSelect->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Documento não encontrado']);
    exit;
}

$documento = $resultSelect->fetch_assoc();
$caminhoArquivo = '../../' . $documento['caminho_arquivo'];

// Excluir o documento do banco de dados
$sql = "DELETE FROM paciente_documentos WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $documentoId);

if ($stmt->execute()) {
    // Tentar remover o arquivo físico
    if (file_exists($caminhoArquivo)) {
        unlink($caminhoArquivo);
    }
    
    echo json_encode(['success' => true, 'message' => 'Documento removido com sucesso']);
} else {
    echo json_encode(['success' => false, 'message' => 'Erro ao remover documento: ' . $stmt->error]);
}

$stmtSelect->close();
$stmt->close();
$conn->close();

