<?php
/* Nome: get_contact_info.php | Caminho: /api/chat/get_contact_info.php */

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

$contato_id = $_GET['contact_id'];

// Buscar informações do contato
$sql = "SELECT id, nome, email, cargo, status, foto_perfil, ultimo_acesso 
        FROM usuarios 
        WHERE id = ?";

if ($stmt = $conn->prepare($sql)) {
    $stmt->bind_param("i", $contato_id);
    
    if ($stmt->execute()) {
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $contact = $result->fetch_assoc();
            
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'contact' => $contact]);
        } else {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Contato não encontrado']);
        }
    } else {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Erro ao buscar informações do contato: ' . $stmt->error]);
    }
    
    $stmt->close();
} else {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Erro ao preparar consulta: ' . $conn->error]);
}

$conn->close();
?>

