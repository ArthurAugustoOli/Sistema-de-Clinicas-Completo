<?php
// Iniciar sessão
session_start();

// Verificar autenticação do usuário
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
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    echo json_encode(['success' => false, 'message' => 'Método não permitido.']);
    exit;
}

// Capturar ação da URL
$action = isset($_GET['action']) ? $_GET['action'] : '';

if ($action === 'getAll') {
    // Buscar todos os pacientes
    $sql = "SELECT cpf, nome, data_nasc, email, telefone FROM pacientes ORDER BY nome ASC";
    $result = $conn->query($sql);

    if ($result) {
        $pacientes = $result->fetch_all(MYSQLI_ASSOC);
        echo json_encode($pacientes); // <-- Retorna apenas o array para ser compatível com JS
    } else {
        echo json_encode([]);
    }
} elseif ($action === 'getByCpf') {
    // Buscar paciente por CPF
    if (!isset($_GET['cpf']) || empty($_GET['cpf'])) {
        echo json_encode(['success' => false, 'message' => 'CPF não informado.']);
        exit;
    }

    $cpf = $conn->real_escape_string($_GET['cpf']);

    // Validar formato do CPF
    if (!preg_match('/^\d{11}$/', $cpf)) {
        echo json_encode(['success' => false, 'message' => 'CPF inválido.']);
        exit;
    }

    $sql = "SELECT cpf, nome, data_nasc, email, telefone FROM pacientes WHERE cpf = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $cpf);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows > 0) {
        $paciente = $result->fetch_assoc();
        echo json_encode($paciente); // <-- Retorna um objeto único
    } else {
        echo json_encode(null);
    }

    $stmt->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Ação não reconhecida.']);
}

// Fechar conexão
$conn->close();
?>
