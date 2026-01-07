<?php
// Iniciar sessão
session_start();

// Verificar autenticação
if (!isset($_SESSION['logado']) || $_SESSION['logado'] !== true) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Acesso não autorizado.']);
    exit;
}

// Conexão com o banco de dados
require_once '../../config/config.php';

// Definir cabeçalho para JSON
header('Content-Type: application/json');

// Verificar se é uma requisição GET
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $action = isset($_GET['action']) ? $_GET['action'] : '';
    
    if ($action === 'getAll') {
        // Buscar todos os funcionários
        $sql = "SELECT id, cpf, nome, cargo, telefone, email FROM funcionarios ORDER BY nome ASC";
        $result = $conn->query($sql);
        
        if ($result) {
            $funcionarios = [];
            while ($row = $result->fetch_assoc()) {
                $funcionarios[] = $row;
            }
            
            echo json_encode($funcionarios);
        } else {
            echo json_encode(['success' => false, 'message' => 'Erro ao buscar funcionários: ' . $conn->error]);
        }
    } elseif ($action === 'getById') {
        // Buscar funcionário por ID
        if (!isset($_GET['id'])) {
            echo json_encode(['success' => false, 'message' => 'ID não informado.']);
            exit;
        }
        
        $id = $conn->real_escape_string($_GET['id']);
        
        $sql = "SELECT * FROM funcionarios WHERE id = '$id'";
        $result = $conn->query($sql);
        
        if ($result && $result->num_rows > 0) {
            $funcionario = $result->fetch_assoc();
            echo json_encode($funcionario);
        } else {
            echo json_encode(['success' => false, 'message' => 'Funcionário não encontrado.']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Ação não reconhecida.']);
    }
}

// Fechar conexão
$conn->close();

