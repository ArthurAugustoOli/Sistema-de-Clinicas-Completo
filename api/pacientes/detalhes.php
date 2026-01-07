<?php
session_start();

require_once '../../config/config.php';
require_once '../../functions/utils/helpers.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    echo json_encode(['success' => false, 'message' => 'Método não permitido']);
    exit;
}

if (!isset($_GET['cpf']) || empty($_GET['cpf'])) {
    echo json_encode(['success' => false, 'message' => 'CPF não fornecido']);
    exit;
}

$cpf = preg_replace('/[^0-9]/', '', $_GET['cpf']);

try {
    // Buscar dados do paciente
    $sql = "SELECT * FROM pacientes WHERE cpf = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $cpf);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Paciente não encontrado']);
        exit;
    }
    
    $paciente = $result->fetch_assoc();
    $stmt->close();
    
    // Buscar documentos do paciente
   $sqlDocs = "
  SELECT 
    id,
    nome_documento,
    descricao,
    caminho_arquivo
  FROM paciente_documentos
  WHERE paciente_cpf = ?
  ORDER BY data_upload DESC
";

    $stmtDocs = $conn->prepare($sqlDocs);
    $stmtDocs->bind_param("s", $cpf);
    $stmtDocs->execute();
    $resultDocs = $stmtDocs->get_result();
    
    $documentos = [];
    while ($doc = $resultDocs->fetch_assoc()) {
        $documentos[] = $doc;
    }
    $stmtDocs->close();
    
    // Verificar se a tabela consultas existe
    $tableExists = false;
    $checkTable = $conn->query("SHOW TABLES LIKE 'consultas'");
    if ($checkTable->num_rows > 0) {
        $tableExists = true;
    }
    
    $consultas = [];
    
    // Buscar histórico de consultas apenas se a tabela existir
    if ($tableExists) {
        // Verificar se a tabela profissionais existe
        $profTableExists = false;
        $checkProfTable = $conn->query("SHOW TABLES LIKE 'profissionais'");
        if ($checkProfTable->num_rows > 0) {
            $profTableExists = true;
            
            // Se ambas as tabelas existirem, fazer a consulta com JOIN
            $sqlConsultas = "SELECT c.*, p.nome as profissional_nome 
                             FROM consultas c 
                             LEFT JOIN profissionais p ON c.profissional_id = p.id
                             WHERE c.paciente_cpf = ? 
                             ORDER BY c.data_consulta DESC";
        } else {
            // Se apenas a tabela consultas existir, fazer a consulta sem JOIN
            $sqlConsultas = "SELECT c.*, 'Não informado' as profissional_nome 
                             FROM consultas c 
                             WHERE c.paciente_cpf = ? 
                             ORDER BY c.data_consulta DESC";
        }
        
        $stmtConsultas = $conn->prepare($sqlConsultas);
        $stmtConsultas->bind_param("s", $cpf);
        $stmtConsultas->execute();
        $resultConsultas = $stmtConsultas->get_result();
        
        while ($consulta = $resultConsultas->fetch_assoc()) {
            $consultas[] = $consulta;
        }
        $stmtConsultas->close();
    }
    
    // Retornar dados
    echo json_encode([
        'success' => true, 
        'data' => [
            'paciente' => $paciente,
            'documentos' => $documentos,
            'consultas' => $consultas
        ]
    ]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

if (isset($conn)) $conn->close();