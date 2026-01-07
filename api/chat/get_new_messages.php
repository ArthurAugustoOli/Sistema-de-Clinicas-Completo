<?php
/* Nome: get_new_messages.php | Caminho: /api/chat/get_new_messages.php */

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
if (!isset($_GET['contact_id']) || empty($_GET['contact_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'ID do contato não fornecido']);
    exit;
}

$usuario_id = $_SESSION['id'];
$contato_id = $_GET['contact_id'];
$last_check = isset($_GET['last_check']) ? $_GET['last_check'] : date('Y-m-d H:i:s', strtotime('-30 seconds'));

// Buscar novas mensagens
$sql = "SELECT * FROM mensagens 
        WHERE ((remetente_id = ? AND destinatario_id = ?) 
           OR (remetente_id = ? AND destinatario_id = ?))
           AND data_envio > ? 
        ORDER BY data_envio ASC";

$messages = [];

if ($stmt = $conn->prepare($sql)) {
    $stmt->bind_param("iiiss", $usuario_id, $contato_id, $contato_id, $usuario_id, $last_check);
    
    if ($stmt->execute()) {
        $result = $stmt->get_result();
        
        while ($row = $result->fetch_assoc()) {
            $messages[] = $row;
        }
        
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'messages' => $messages]);
    } else {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Erro ao buscar novas mensagens: ' . $stmt->error]);
    }
    
    $stmt->close();
} else {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Erro ao preparar consulta: ' . $conn->error]);
}

$conn->close();
?>

