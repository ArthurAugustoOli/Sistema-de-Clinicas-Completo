<?php
// Iniciar sessão para gerenciamento de login
session_start();

// Verificar autenticação
if (!isset($_SESSION['logado']) || $_SESSION['logado'] !== true) {
    header('Content-Type: application/json');
    echo json_encode([]);
    exit;
}

// Conexão com o banco de dados
require_once '../../config/config.php';

// Definir cabeçalho para JSON
header('Content-Type: application/json');

// Verificar se é uma requisição GET
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    echo json_encode([]);
    exit;
}

$action = $_GET['action'] ?? '';

if ($action === 'getAll') {
    // Verificar se a tabela servicos existe
    $tableExists = $conn->query("SHOW TABLES LIKE 'servicos'");
    
    if ($tableExists->num_rows == 0) {
        // Criar a tabela servicos se não existir
        $conn->query("CREATE TABLE servicos (
            id INT AUTO_INCREMENT PRIMARY KEY,
            nome_servico VARCHAR(255) NOT NULL,
            descricao TEXT,
            preco DECIMAL(10,2) NOT NULL,
            duracao_minutos INT DEFAULT 30,
            status VARCHAR(50) DEFAULT 'Ativo',
            data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )");
        
        // Inserir alguns serviços padrão
        $conn->query("INSERT INTO servicos (nome_servico, descricao, preco, duracao_minutos) VALUES 
            ('Consulta de Rotina', 'Consulta médica de rotina', 100.00, 30),
            ('Consulta de Retorno', 'Consulta de retorno para acompanhamento', 80.00, 20),
            ('Exame Clínico', 'Exame clínico completo', 150.00, 45),
            ('Avaliação', 'Avaliação médica especializada', 120.00, 30),
            ('Procedimento Cirúrgico', 'Procedimento cirúrgico simples', 500.00, 90)
        ");
    }
    
    // Buscar todos os serviços
    $sql = "SELECT id, nome_servico, descricao, preco, duracao_minutos, status FROM servicos ORDER BY nome_servico ASC";
    $result = $conn->query($sql);
    
    $servicos = [];
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $servicos[] = $row;
        }
    }
    
    echo json_encode($servicos);
} elseif ($action === 'getById') {
    $id = intval($_GET['id'] ?? 0);
    
    if ($id <= 0) {
        echo json_encode(['success' => false, 'message' => 'ID inválido.']);
        exit;
    }
    
    // Buscar serviço pelo ID
    $sql = "SELECT id, nome_servico, descricao, preco, duracao_minutos, status FROM servicos WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Serviço não encontrado.']);
        exit;
    }
    
    $servico = $result->fetch_assoc();
    echo json_encode($servico);
    
    $stmt->close();
} else {
    echo json_encode([]);
}

$conn->close();

