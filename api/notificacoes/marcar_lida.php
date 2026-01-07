<?php
/* Nome: marcar_lida.php | Caminho: /api/notificacoes/marcar_lida.php */

// Iniciar sessão
session_start();

// Verificar se o usuário está logado
if (!isset($_SESSION['logado']) || $_SESSION['logado'] !== true) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Não autorizado']);
    exit;
}

// Incluir configuração do banco de dados
require_once '../../config/config.php';

// Verificar se os dados foram enviados via POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Método não permitido']);
    exit;
}

// Verificar se o ID da notificação foi fornecido
if (!isset($_POST['notificacao_id']) || empty($_POST['notificacao_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'ID da notificação não fornecido']);
    exit;
}

$notificacao_id = intval($_POST['notificacao_id']);
$usuario_id = $_SESSION['id'];

// Marcar notificação como lida
$sql = "INSERT INTO notificacoes_usuarios (notificacao_id, usuario_id, lida) 
        VALUES (?, ?, 1) 
        ON DUPLICATE KEY UPDATE lida = 1";

if ($stmt = $conn->prepare($sql)) {
    $stmt->bind_param("ii", $notificacao_id, $usuario_id);
    
    if ($stmt->execute()) {
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'message' => 'Notificação marcada como lida']);
    } else {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Erro ao marcar notificação como lida']);
    }
    
    $stmt->close();
} else {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Erro ao preparar consulta']);
}