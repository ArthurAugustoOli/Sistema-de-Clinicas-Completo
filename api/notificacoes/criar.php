<?php
/* Nome: criar.php | Caminho: /api/notificacoes/criar.php */

// Iniciar sessão
session_start();

// Verificar se o usuário está logado e é administrador
if (!isset($_SESSION['logado']) || $_SESSION['logado'] !== true || $_SESSION['cargo'] !== 'Administrador') {
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

// Verificar se os dados necessários foram fornecidos
if (!isset($_POST['titulo']) || empty($_POST['titulo']) || !isset($_POST['mensagem']) || empty($_POST['mensagem'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Dados incompletos']);
    exit;
}

$titulo = trim($_POST['titulo']);
$mensagem = trim($_POST['mensagem']);
$tipo = isset($_POST['tipo']) ? trim($_POST['tipo']) : 'info';
$para_todos = isset($_POST['para_todos']) ? intval($_POST['para_todos']) : 1;
$expira_em = isset($_POST['expira_em']) && !empty($_POST['expira_em']) ? $_POST['expira_em'] : null;
$criador_id = $_SESSION['id'];

// Inserir notificação no banco de dados
$sql = "INSERT INTO notificacoes (titulo, mensagem, tipo, criador_id, data_criacao, expira_em, para_todos) 
        VALUES (?, ?, ?, ?, NOW(), ?, ?)";

if ($stmt = $conn->prepare($sql)) {
    $stmt->bind_param("sssisi", $titulo, $mensagem, $tipo, $criador_id, $expira_em, $para_todos);
    
    if ($stmt->execute()) {
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'message' => 'Notificação criada com sucesso']);
    } else {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Erro ao criar notificação']);
    }
    
    $stmt->close();
} else {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Erro ao preparar consulta']);
}