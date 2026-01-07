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

// Funções utilitárias
require_once '../../functions/utils/helpers.php';

// Definir cabeçalho para JSON
header('Content-Type: application/json');

// Verificar se é uma requisição GET
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $action = isset($_GET['action']) ? $_GET['action'] : '';
    
    if ($action === 'getById') {
        // Buscar funcionário por ID
        if (!isset($_GET['id'])) {
            echo json_encode(['success' => false, 'message' => 'ID não informado.']);
            exit;
        }
        
        $id = $conn->real_escape_string($_GET['id']);
        
        $sql = "SELECT * FROM funcionarios WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $funcionario = $result->fetch_assoc();
            echo json_encode($funcionario);
        } else {
            echo json_encode(['success' => false, 'message' => 'Funcionário não encontrado.']);
        }
        
        $stmt->close();
    } elseif ($action === 'getConsultas') {
        // Buscar consultas de um funcionário
        if (!isset($_GET['id'])) {
            echo json_encode(['success' => false, 'message' => 'ID não informado.']);
            exit;
        }
        
        $id = $conn->real_escape_string($_GET['id']);
        $periodo = isset($_GET['periodo']) ? $conn->real_escape_string($_GET['periodo']) : 'futuras';
        
        $sql = "SELECT c.id, c.data_consulta, c.procedimento, c.status, 
                       p.nome as paciente_nome, p.telefone as paciente_telefone
                FROM consultas c
                JOIN pacientes p ON c.paciente_cpf = p.cpf
                WHERE c.profissional_id = ?";
        
        if ($periodo === 'futuras') {
            $sql .= " AND c.data_consulta >= NOW()";
        } elseif ($periodo === 'passadas') {
            $sql .= " AND c.data_consulta < NOW()";
        }
        
        $sql .= " ORDER BY c.data_consulta ASC";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $consultas = [];
        while ($row = $result->fetch_assoc()) {
            $consultas[] = $row;
        }
        
        echo json_encode($consultas);
        
        $stmt->close();
    } else {
        echo json_encode(['success' => false, 'message' => 'Ação não reconhecida.']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Método não permitido.']);
}

// Fechar conexão
$conn->close();

