<?php
/* Nome: check_unread.php | Caminho: /api/chat/check_unread.php */

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

$usuario_id = $_SESSION['id'];

// Contar mensagens não lidas
$sql = "SELECT COUNT(*) as count FROM mensagens 
        WHERE destinatario_id = ? AND lida = 0";

if ($stmt = $conn->prepare($sql)) {
    $stmt->bind_param("i", $usuario_id);
    
    if ($stmt->execute()) {
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true, 
            'count' => (int)$row['count']
        ]);
    } else {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Erro ao contar mensagens não lidas: ' . $stmt->error]);
    }
    
    $stmt->close();
} else {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Erro ao preparar consulta: ' . $conn->error]);
}

$conn->close();
?>

