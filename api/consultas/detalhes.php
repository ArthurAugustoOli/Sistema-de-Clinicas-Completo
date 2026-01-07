<?php
// Iniciar sessão para gerenciamento de login
session_start();

// Verificar autenticação
if (!isset($_SESSION['logado']) || $_SESSION['logado'] !== true) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Não autorizado']);
    exit;
}

// Conexão com o banco de dados
require_once '../../config/config.php';

// Parâmetros da requisição
$action = isset($_GET['action']) ? $_GET['action'] : '';
$id = isset($_GET['id']) ? $_GET['id'] : null;

// Definir cabeçalho de resposta
header('Content-Type: application/json');

// Processar ação
if ($action === 'getById' && $id) {
    try {
        $sql = "SELECT c.*, p.nome as paciente_nome, f.nome as profissional_nome, s.nome_servico as nome_servico, s.duracao_minutos 
        FROM consultas c
        LEFT JOIN pacientes p ON c.paciente_cpf = p.cpf
        LEFT JOIN funcionarios f ON c.profissional_id = f.id
        LEFT JOIN servicos s ON c.servico_id = s.id
        WHERE c.id = ?";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param('i', $id); // Corrigido para usar bind_param do mysqli
        $stmt->execute();

        $result = $stmt->get_result(); // Obter o resultado
        $consulta = $result->fetch_assoc(); // Buscar como array associativo

        if ($consulta) {
            echo json_encode($consulta);
        } else {
            echo json_encode(['error' => 'Consulta não encontrada']);
        }
    } catch (Exception $e) {
        echo json_encode(['error' => 'Erro ao buscar consulta: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['error' => 'Ação inválida ou ID não fornecido']);
}
