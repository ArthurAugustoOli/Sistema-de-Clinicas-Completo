<?php
// Iniciar sessão
session_start();

// Verificar autenticação
if (!isset($_SESSION['logado']) || $_SESSION['logado'] !== true) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Acesso não autorizado.']);
    exit;
}

// Verificar se o usuário tem permissão de administrador
if (!isset($_SESSION['cargo']) || $_SESSION['cargo'] !== 'Administrador') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Você não tem permissão para gerenciar usuários.']);
    exit;
}

// Conexão com o banco de dados
require_once '../../config/config.php';
require_once '../../functions/utils/helpers.php';

// Definir cabeçalho para JSON
header('Content-Type: application/json');

// Verificar se é uma requisição POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método não permitido.']);
    exit;
}

$action = $_POST['action'] ?? '';

try {
    switch ($action) {
        case 'create':
            $username = trim($_POST['username'] ?? '');
            $password = trim($_POST['password'] ?? '');
            $nome = trim($_POST['nome'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $cargo = trim($_POST['cargo'] ?? '');
            $status = trim($_POST['status'] ?? '');

            if (empty($username) || empty($password) || empty($nome) || empty($email) || empty($cargo) || empty($status)) {
                throw new Exception('Todos os campos são obrigatórios.');
            }

            // Verificar duplicidade
            $stmt = $conn->prepare("SELECT id FROM usuarios WHERE username = ? OR email = ?");
            if (!$stmt) {
                throw new Exception("Erro no SQL: " . $conn->error);
            }
            $stmt->bind_param("ss", $username, $email);
            $stmt->execute();
            $stmt->store_result();
            if ($stmt->num_rows > 0) {
                throw new Exception('Nome de usuário ou e-mail já cadastrado.');
            }
            $stmt->close();

            // Inserir novo usuário
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO usuarios (username, password, nome, email, cargo, status) VALUES (?, ?, ?, ?, ?, ?)");
            if (!$stmt) {
                throw new Exception("Erro no SQL: " . $conn->error);
            }
            $stmt->bind_param("ssssss", $username, $hashed_password, $nome, $email, $cargo, $status);
            if (!$stmt->execute()) {
                throw new Exception('Erro ao criar usuário.');
            }
            echo json_encode(['success' => true, 'message' => 'Usuário criado com sucesso!', 'id' => $conn->insert_id]);

            if (function_exists('registrarAtividade')) {
                registrarAtividade("Novo usuário criado: $username", $conn);
            }

            $stmt->close();
            break;

        case 'update':
            $id = intval($_POST['id'] ?? 0);
            $nome = trim($_POST['nome'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $cargo = trim($_POST['cargo'] ?? '');
            $status = trim($_POST['status'] ?? '');

            if (empty($id) || empty($nome) || empty($email) || empty($cargo) || empty($status)) {
                throw new Exception('Todos os campos são obrigatórios.');
            }

            $stmt = $conn->prepare("SELECT id FROM usuarios WHERE email = ? AND id != ?");
            if (!$stmt) {
                throw new Exception("Erro no SQL: " . $conn->error);
            }
            $stmt->bind_param("si", $email, $id);
            $stmt->execute();
            $stmt->store_result();
            if ($stmt->num_rows > 0) {
                throw new Exception('E-mail já está em uso por outro usuário.');
            }
            $stmt->close();

            $stmt = $conn->prepare("UPDATE usuarios SET nome = ?, email = ?, cargo = ?, status = ? WHERE id = ?");
            if (!$stmt) {
                throw new Exception("Erro no SQL: " . $conn->error);
            }
            $stmt->bind_param("ssssi", $nome, $email, $cargo, $status, $id);
            if (!$stmt->execute()) {
                throw new Exception('Erro ao atualizar usuário.');
            }
            echo json_encode(['success' => true, 'message' => 'Usuário atualizado com sucesso!']);

            if (function_exists('registrarAtividade')) {
                registrarAtividade("Usuário #$id atualizado", $conn);
            }

            $stmt->close();
            break;

        case 'updatePassword':
            $id = intval($_POST['id'] ?? 0);
            $password = trim($_POST['password'] ?? '');

            if (empty($id) || empty($password)) {
                throw new Exception('ID e senha são obrigatórios.');
            }

            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE usuarios SET password = ? WHERE id = ?");
            if (!$stmt) {
                throw new Exception("Erro no SQL: " . $conn->error);
            }
            $stmt->bind_param("si", $hashed_password, $id);
            if (!$stmt->execute()) {
                throw new Exception('Erro ao atualizar senha.');
            }
            echo json_encode(['success' => true, 'message' => 'Senha atualizada com sucesso!']);

            if (function_exists('registrarAtividade')) {
                registrarAtividade("Senha do usuário #$id atualizada", $conn);
            }

            $stmt->close();
            break;

        case 'delete':
            $id = intval($_POST['id'] ?? 0);

            if ($id === $_SESSION['id']) {
                throw new Exception('Você não pode excluir sua própria conta.');
            }

            $stmt = $conn->prepare("DELETE FROM usuarios WHERE id = ?");
            if (!$stmt) {
                throw new Exception("Erro no SQL: " . $conn->error);
            }
            $stmt->bind_param("i", $id);
            if (!$stmt->execute()) {
                throw new Exception('Erro ao excluir usuário.');
            }
            echo json_encode(['success' => true, 'message' => 'Usuário excluído com sucesso!']);

            if (function_exists('registrarAtividade')) {
                registrarAtividade("Usuário #$id excluído", $conn);
            }

            $stmt->close();
            break;

        default:
            throw new Exception('Ação não reconhecida.');
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

$conn->close();
