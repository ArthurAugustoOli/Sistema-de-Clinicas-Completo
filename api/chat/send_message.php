<?php
/* Nome: send_message.php | Caminho: /api/chat/send_message.php */

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

// Verificar se os dados necessários foram fornecidos
if (!isset($_POST['destinatario_id']) || empty($_POST['destinatario_id']) || 
    !isset($_POST['mensagem']) || empty($_POST['mensagem'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Dados incompletos']);
    exit;
}

$remetente_id = $_SESSION['id'];
$destinatario_id = $_POST['destinatario_id'];
$mensagem = $_POST['mensagem'];
$data_envio = date('Y-m-d H:i:s');

// Inserir mensagem no banco de dados
$sql = "INSERT INTO mensagens (remetente_id, destinatario_id, mensagem, data_envio, lida) 
        VALUES (?, ?, ?, ?, 0)";

if ($stmt = $conn->prepare($sql)) {
    $stmt->bind_param("iiss", $remetente_id, $destinatario_id, $mensagem, $data_envio);
    
    if ($stmt->execute()) {
        $message_id = $stmt->insert_id;
        
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true, 
            'message_id' => $message_id,
            'timestamp' => $data_envio
        ]);
    } else {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Erro ao enviar mensagem: ' . $stmt->error]);
    }
    
    $stmt->close();
} else {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Erro ao preparar consulta: ' . $conn->error]);
}

$conn->close();
?>

