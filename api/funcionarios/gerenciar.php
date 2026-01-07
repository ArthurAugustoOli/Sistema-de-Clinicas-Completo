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
require_once '../../functions/utils/helpers.php';

// Definir cabeçalho para JSON
header('Content-Type: application/json');

// Verificar se é uma requisição POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método não permitido.']);
    exit;
}

$action = $_POST['action'] ?? '';

if ($action === 'create') {
    // Criar novo funcionário
    $cpf = preg_replace('/[^0-9]/', '', $_POST['cpf'] ?? '');
    $nome = trim($_POST['nome'] ?? '');
    $cargo = trim($_POST['cargo'] ?? '');
    $telefone = preg_replace('/[^0-9]/', '', $_POST['telefone'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $data_nasc = !empty($_POST['data_nascimento']) ? $_POST['data_nascimento'] : null;
    $whatsapp = preg_replace('/[^0-9]/', '', $_POST['whatsapp'] ?? $telefone); // Usar telefone como padrão se whatsapp não for fornecido
    $turno = trim($_POST['turno'] ?? '');
    $horario_trabalho = trim($_POST['horario_trabalho'] ?? '');
    
    // Validações básicas
    if (empty($cpf) || strlen($cpf) !== 11) {
        echo json_encode(['success' => false, 'message' => 'CPF inválido.']);
        exit;
    }
    
    if (empty($nome)) {
        echo json_encode(['success' => false, 'message' => 'Nome é obrigatório.']);
        exit;
    }
    
    if (empty($cargo)) {
        echo json_encode(['success' => false, 'message' => 'Cargo é obrigatório.']);
        exit;
    }

    // Verificar se o CPF já existe
    $check_stmt = $conn->prepare("SELECT id FROM funcionarios WHERE cpf = ?");
    $check_stmt->bind_param("s", $cpf);
    $check_stmt->execute();
    $check_stmt->store_result();

    if ($check_stmt->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => 'Este CPF já está cadastrado.']);
        exit;
    }
    $check_stmt->close();

    // Inserir novo funcionário
    $sql = "INSERT INTO funcionarios (cpf, nome, data_nasc, email, telefone, whatsapp, cargo, turno, horario_trabalho) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssssssss", $cpf, $nome, $data_nasc, $email, $telefone, $whatsapp, $cargo, $turno, $horario_trabalho);

    if ($stmt->execute()) {
    $id_inserido = $conn->insert_id;

    // Criar usuário correspondente ao funcionário
    $username = $nome;
    $password_hash = password_hash($cpf, PASSWORD_DEFAULT); // senha padrão: CPF
    $status = 'ativo'; // ou 'pendente', dependendo da lógica do seu sistema
    $data_criacao = date('Y-m-d H:i:s');
    $ultimo_acesso = null;
    $tentativas_login = 0;
    $reset_token = null;
    $reset_expira = null;
    $foto_perfil = null;

    // Verificar se já existe um usuário com esse username
    $check_user = $conn->prepare("SELECT id FROM usuarios WHERE username = ?");
    $check_user->bind_param("s", $username);
    $check_user->execute();
    $check_user->store_result();

    if ($check_user->num_rows === 0) {
        $check_user->close();

        // Inserir novo usuário
        $insert_user = $conn->prepare("INSERT INTO usuarios 
            (username, password, nome, email, cargo, status, data_criacao, ultimo_acesso, tentativas_login, reset_token, reset_expira, foto_perfil)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

        $insert_user->bind_param(
            "ssssssssisss",
            $username,
            $password_hash,
            $nome,
            $email,
            $cargo,
            $status,
            $data_criacao,
            $ultimo_acesso,
            $tentativas_login,
            $reset_token,
            $reset_expira,
            $foto_perfil
        );

        if (!$insert_user->execute()) {
            echo json_encode(['success' => false, 'message' => 'Funcionário cadastrado, mas erro ao criar usuário: ' . $insert_user->error]);
            exit;
        }
        $insert_user->close();

    } else {
        $check_user->close();
        echo json_encode(['success' => false, 'message' => 'Funcionário cadastrado, mas o usuário já existe.']);
        exit;
    }

    // Registrar atividade
    if (function_exists('registrarAtividade')) {
        registrarAtividade("Novo funcionário e usuário criados: $nome", $conn);
    }

    echo json_encode(['success' => true, 'message' => 'Funcionário e usuário criados com sucesso!', 'id' => $id_inserido]);
} else {
    echo json_encode(['success' => false, 'message' => 'Erro ao cadastrar funcionário: ' . $stmt->error]);
}

    


    $stmt->close();

} elseif ($action === 'update') {
    // Atualizar funcionário existente
    $id = intval($_POST['id'] ?? 0);
    $nome = trim($_POST['nome'] ?? '');
    $cargo = trim($_POST['cargo'] ?? '');
    $telefone = preg_replace('/[^0-9]/', '', $_POST['telefone'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $data_nasc = !empty($_POST['data_nascimento']) ? $_POST['data_nascimento'] : null;
    $whatsapp = preg_replace('/[^0-9]/', '', $_POST['whatsapp'] ?? $telefone); // Usar telefone como padrão se whatsapp não for fornecido
    $turno = trim($_POST['turno'] ?? '');
    $horario_trabalho = trim($_POST['horario_trabalho'] ?? '');

    if ($id <= 0) {
        echo json_encode(['success' => false, 'message' => 'ID inválido.']);
        exit;
    }

    // Validações básicas
    if (empty($nome)) {
        echo json_encode(['success' => false, 'message' => 'Nome é obrigatório.']);
        exit;
    }
    
    if (empty($cargo)) {
        echo json_encode(['success' => false, 'message' => 'Cargo é obrigatório.']);
        exit;
    }

    $sql = "UPDATE funcionarios SET 
            nome = ?, data_nasc = ?, email = ?, telefone = ?, 
            whatsapp = ?, cargo = ?, turno = ?, horario_trabalho = ? 
            WHERE id = ?";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssssssssi", $nome, $data_nasc, $email, $telefone, $whatsapp, $cargo, $turno, $horario_trabalho, $id);

    if ($stmt->execute()) {
        // Registrar atividade se a função existir
        if (function_exists('registrarAtividade')) {
            registrarAtividade("Funcionário #$id atualizado: $nome", $conn);
        }
        echo json_encode(['success' => true, 'message' => 'Funcionário atualizado com sucesso!']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Erro ao atualizar funcionário: ' . $stmt->error]);
    }

    $stmt->close();

} elseif ($action === 'delete') {
    // Excluir funcionário
    $id = intval($_POST['id'] ?? 0);

    if ($id <= 0) {
        echo json_encode(['success' => false, 'message' => 'ID inválido.']);
        exit;
    }

    // Verificar se o funcionário tem consultas associadas
    $check_stmt = $conn->prepare("SELECT COUNT(*) as total FROM consultas WHERE profissional_id = ?");
    $check_stmt->bind_param("i", $id);
    $check_stmt->execute();
    $result = $check_stmt->get_result();
    $row = $result->fetch_assoc();
    $check_stmt->close();

    if ($row['total'] > 0) {
        echo json_encode(['success' => false, 'message' => 'Este funcionário possui consultas associadas e não pode ser excluído. Considere marcá-lo como inativo.']);
        exit;
    }

    // Excluir funcionário
    $stmt = $conn->prepare("DELETE FROM funcionarios WHERE id = ?");
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        // Registrar atividade se a função existir
        if (function_exists('registrarAtividade')) {
            registrarAtividade("Funcionário #$id excluído", $conn);
        }
        echo json_encode(['success' => true, 'message' => 'Funcionário excluído com sucesso!']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Erro ao excluir funcionário: ' . $stmt->error]);
    }

    $stmt->close();

} else {
    echo json_encode(['success' => false, 'message' => 'Ação não reconhecida.']);
}

$conn->close();

