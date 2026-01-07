<?php
// Iniciar sessão para gerenciamento de login
session_start();

// Conexão com o banco de dados
require_once '../../config/config.php';

// Funções utilitárias
require_once '../../functions/utils/helpers.php';

// Definir cabeçalho JSON
header('Content-Type: application/json');

// Verificar autenticação
if (!isset($_SESSION['logado']) || $_SESSION['logado'] !== true) {
    echo json_encode(['success' => false, 'message' => 'Acesso não autorizado.']);
    exit;
}

// Verificar ação
$action = isset($_GET['action']) ? $_GET['action'] : '';

switch ($action) {
    case 'listar':
        // Listar todos os serviços, agora incluindo duracao_minutos
        $sql = "SELECT id, nome_servico, preco, duracao_minutos FROM servicos ORDER BY nome_servico ASC";
        $result = $conn->query($sql);
        
        $servicos = [];
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $servicos[] = [
                    'id'              => $row['id'],
                    'nome'            => $row['nome_servico'],
                    'preco'           => floatval($row['preco']),
                    'duracao_minutos' => intval($row['duracao_minutos'])
                ];
            }
        }
        
        echo json_encode(['success' => true, 'data' => $servicos]);
        break;
        
    case 'criar':
        // Verificar se é uma requisição POST
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Método não permitido.']);
            exit;
        }
        
        // Obter dados da requisição
        $nome = isset($_POST['nome_servico']) ? trim($_POST['nome_servico']) : '';
        $preco = isset($_POST['preco']) ? floatval($_POST['preco']) : 0;
        $duracao = isset($_POST['duracao_minutos']) ? intval($_POST['duracao_minutos']) : 30;
        
        // Validar dados
        if (empty($nome)) {
            echo json_encode(['success' => false, 'message' => 'Nome do serviço é obrigatório.']);
            exit;
        }
        
        if ($preco <= 0) {
            echo json_encode(['success' => false, 'message' => 'Preço deve ser maior que zero.']);
            exit;
        }
        
        // Inserir serviço
        $sql = "INSERT INTO servicos (nome_servico, preco, duracao_minutos) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            echo json_encode(['success' => false, 'message' => 'Erro na preparação: ' . $conn->error]);
            exit;
        }
        $stmt->bind_param("sdi", $nome, $preco, $duracao);
        
        if ($stmt->execute()) {
            $id_inserido = $conn->insert_id;
            echo json_encode([
                'success' => true, 
                'message' => 'Serviço cadastrado com sucesso.',
                'data' => [
                    'id'              => $id_inserido,
                    'nome'            => $nome,
                    'preco'           => $preco,
                    'duracao_minutos' => $duracao
                ]
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Erro ao cadastrar serviço: ' . $stmt->error]);
        }
        
        $stmt->close();
        break;
        
    case 'atualizar':
        // Verificar se é uma requisição POST
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Método não permitido.']);
            exit;
        }
        
        // Obter dados da requisição
        $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
        $nome = isset($_POST['nome_servico']) ? trim($_POST['nome_servico']) : '';
        $preco = isset($_POST['preco']) ? floatval($_POST['preco']) : 0;
        $duracao = isset($_POST['duracao_minutos']) ? intval($_POST['duracao_minutos']) : 30;
        
        // Validar dados
        if ($id <= 0) {
            echo json_encode(['success' => false, 'message' => 'ID inválido.']);
            exit;
        }
        
        if (empty($nome)) {
            echo json_encode(['success' => false, 'message' => 'Nome do serviço é obrigatório.']);
            exit;
        }
        
        if ($preco <= 0) {
            echo json_encode(['success' => false, 'message' => 'Preço deve ser maior que zero.']);
            exit;
        }
        
        // Atualizar serviço
        $sql = "UPDATE servicos SET nome_servico = ?, preco = ?, duracao_minutos = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            echo json_encode(['success' => false, 'message' => 'Erro na preparação: ' . $conn->error]);
            exit;
        }
        $stmt->bind_param("sdii", $nome, $preco, $duracao, $id);
        
        if ($stmt->execute()) {
            echo json_encode([
                'success' => true, 
                'message' => 'Serviço atualizado com sucesso.',
                'data' => [
                    'id'              => $id,
                    'nome'            => $nome,
                    'preco'           => $preco,
                    'duracao_minutos' => $duracao
                ]
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Erro ao atualizar serviço: ' . $stmt->error]);
        }
        
        $stmt->close();
        break;
        
    case 'excluir':
        // Verificar se é uma requisição POST
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Método não permitido.']);
            exit;
        }
        
        // Obter dados da requisição
        $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
        
        // Validar dados
        if ($id <= 0) {
            echo json_encode(['success' => false, 'message' => 'ID inválido.']);
            exit;
        }
        
        // Verificar se o serviço está sendo usado
        $sqlCheck = "SELECT COUNT(*) as total FROM financeiro WHERE servico_id = ?";
        $stmtCheck = $conn->prepare($sqlCheck);
        $stmtCheck->bind_param("i", $id);
        $stmtCheck->execute();
        $resultCheck = $stmtCheck->get_result();
        $rowCheck = $resultCheck->fetch_assoc();
        
        if ($rowCheck['total'] > 0) {
            echo json_encode(['success' => false, 'message' => 'Este serviço não pode ser excluído pois está sendo usado em registros financeiros.']);
            exit;
        }
        $stmtCheck->close();
        
        // Excluir serviço
        $sql = "DELETE FROM servicos WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $id);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Serviço excluído com sucesso.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Erro ao excluir serviço: ' . $stmt->error]);
        }
        
        $stmt->close();
        break;
        
    default:
        echo json_encode(['success' => false, 'message' => 'Ação não reconhecida.']);
}

$conn->close();
?>
