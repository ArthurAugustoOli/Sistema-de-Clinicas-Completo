<?php
/* Nome: mark_read.php | Caminho: /api/chat/mark_read.php */

// Iniciar sessão
session_start();

// Verificar se o usuário está logado
if (!isset($_SESSION['logado']) || $_SESSION['logado'] !== true) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Usuário não autenticado']);
    exit;
}

// Incluir configuração do banco de dados
require_once __DIR__ . '/../../config/config.php';

// Verificar se o ID do contato foi fornecido
if (!isset($_POST['contact_id']) || empty($_POST['contact_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'ID do contato não fornecido']);
    exit;
}

$usuario_id = $_SESSION['id'];
$contato_id = $_POST['contact_id'];

// Marcar mensagens como lidas
$sql = "UPDATE mensagens 
        SET lida = 1 
        WHERE remetente_id = ? AND destinatario_id = ? AND lida = 0";

if ($stmt = $conn->prepare($sql)) {
    $stmt->bind_param("ii", $contato_id, $usuario_id);
    
    if ($stmt->execute()) {
        $affected_rows = $stmt->affected_rows;
        
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true, 
            'affected_rows' => $affected_rows
        ]);
    } else {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Erro ao marcar mensagens como lidas: ' . $stmt->error]);
    }
    
    $stmt->close();
} else {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Erro ao preparar consulta: ' . $conn->error]);
}

$conn->close();
?>

