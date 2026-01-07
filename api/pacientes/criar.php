<?php
// Iniciar sessão para gerenciamento de login
session_start();

// Conexão com o banco de dados
require_once '../../config/config.php';

// Funções utilitárias
require_once '../../functions/utils/helpers.php';

// Definir cabeçalho JSON
header('Content-Type: application/json');

// Verificar se é uma requisição POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método não permitido']);
    exit;
}

// Validar campos obrigatórios
$requiredFields = ['nome', 'cpf', 'telefone'];
foreach ($requiredFields as $field) {
    if (empty($_POST[$field])) {
        echo json_encode(['success' => false, 'message' => 'Campo obrigatório não preenchido: ' . $field]);
        exit;
    }
}

try {
    // Processar dados do formulário
    $nome = trim($_POST['nome']);
    $cpf  = preg_replace('/\D/', '', $_POST['cpf']); // Remover não numéricos
    $data_nasc = !empty($_POST['data_nasc'])
        ? DateTime::createFromFormat('d/m/Y', $_POST['data_nasc'])->format('Y-m-d')
        : null;
    $telefone = preg_replace('/\D/', '', $_POST['telefone']);
    $email    = $_POST['email'] ?? null;

    // Dados de endereço
    $cep = isset($_POST['cep']) ? preg_replace('/\D/', '', $_POST['cep']) : null;
    $logradouro = isset($_POST['logradouro']) ? $_POST['logradouro'] : null;
    $numero = isset($_POST['numero']) ? $_POST['numero'] : null;
    $complemento = isset($_POST['complemento']) ? $_POST['complemento'] : null;
    $bairro = isset($_POST['bairro']) ? $_POST['bairro'] : null;
    $cidade = isset($_POST['cidade']) ? $_POST['cidade'] : null;
    $estado = isset($_POST['estado']) ? $_POST['estado'] : null;

    // Dados de saúde
    $doencas        = $_POST['doencas']        ?? null;
    $alergias       = $_POST['alergias']       ?? null;
    $tem_convenio   = isset($_POST['tem_convenio']) ? 1 : 0;
    $convenio       = $_POST['convenio']       ?? null;
    $numero_convenio = $_POST['numero_convenio'] ?? null;
    $tipo_sanguineo  = $_POST['tipo_sanguineo']  ?? null;

    // Contato de emergência
    $nome_contato_emergencia    = $_POST['nome_contato_emergencia']    ?? null;
    $numero_contato_emergencia  = !empty($_POST['numero_contato_emergencia'])
        ? preg_replace('/\D/', '', $_POST['numero_contato_emergencia'])
        : null;
    $filiacao_contato_emergencia = $_POST['filiacao_contato_emergencia'] ?? null;

    // Condições médicas
    $condicoes_medicas = $_POST['condicoes_medicas'] ?? null;
    $remedios_em_uso   = $_POST['remedios_em_uso']   ?? null;
    
    // Observações
    $observacoes = $_POST['observacoes'] ?? null;

    // Verificar se o CPF já existe
    $sqlCheck = "SELECT cpf FROM pacientes WHERE cpf = ?";
    $stmtCheck = $conn->prepare($sqlCheck);
    $stmtCheck->bind_param("s", $cpf);
    $stmtCheck->execute();
    $resultCheck = $stmtCheck->get_result();
    if ($resultCheck->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => 'CPF já cadastrado']);
        exit;
    }
    $stmtCheck->close();

    // Upload de foto de perfil
    $foto_perfil = null;
    if (!empty($_FILES['foto_perfil']['name']) && $_FILES['foto_perfil']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = '../../uploads/fotos/';
        if (!is_dir($uploadDir) && !mkdir($uploadDir, 0777, true)) {
            throw new Exception('Erro ao criar diretório para upload de fotos');
        }

        $ext = pathinfo($_FILES['foto_perfil']['name'], PATHINFO_EXTENSION);
        $nomePaciente = preg_replace('/\s+/', '_', strtolower($nome));
        $dataIncl     = date('Ymd_His');
        $novoNome     = $nomePaciente . '_perfil_' . $dataIncl . '.' . $ext;

        $uploadFile = $uploadDir . $novoNome;

        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
        if (!in_array($_FILES['foto_perfil']['type'], $allowedTypes) || $_FILES['foto_perfil']['size'] > 2 * 1024 * 1024) {
            throw new Exception('Arquivo inválido ou excede 2MB.');
        }

        if (move_uploaded_file($_FILES['foto_perfil']['tmp_name'], $uploadFile)) {
            $foto_perfil = 'uploads/fotos/' . $novoNome;
        } else {
            throw new Exception('Erro ao fazer upload da foto.');
        }
    }

    // Iniciar transação
    $conn->begin_transaction();

    // Inserir paciente no banco
    $sql = "INSERT INTO pacientes (
                cpf, nome, data_nasc, email, telefone,
                cep, logradouro, numero, complemento, bairro, cidade, estado,
                doencas, alergias, tem_convenio, convenio, numero_convenio, 
                tipo_sanguineo, nome_contato_emergencia, numero_contato_emergencia, 
                filiacao_contato_emergencia, condicoes_medicas, remedios_em_uso, 
                foto_perfil, observacoes
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new Exception('Erro na preparação da consulta: ' . $conn->error);
    }

    $stmt->bind_param(
        "ssssssssssssssissssssssss",
        $cpf,
        $nome,
        $data_nasc,
        $email,
        $telefone,
        $cep,
        $logradouro,
        $numero,
        $complemento,
        $bairro,
        $cidade,
        $estado,
        $doencas,
        $alergias,
        $tem_convenio,
        $convenio,
        $numero_convenio,
        $tipo_sanguineo,
        $nome_contato_emergencia,
        $numero_contato_emergencia,
        $filiacao_contato_emergencia,
        $condicoes_medicas,
        $remedios_em_uso,
        $foto_perfil,
        $observacoes
    );

    if (!$stmt->execute()) {
        throw new Exception('Erro ao cadastrar paciente: ' . $stmt->error);
    }

    // Processar documentos
    if (!empty($_FILES['documentos']['name'][0])) {
        $uploadDir = '../../uploads/documentos/';
        if (!is_dir($uploadDir) && !mkdir($uploadDir, 0777, true)) {
            throw new Exception('Erro ao criar diretório para upload de documentos');
        }

        $sqlDoc = "INSERT INTO paciente_documentos (paciente_cpf, nome_documento, descricao, caminho_arquivo, data_upload) 
                   VALUES (?, ?, ?, ?, NOW())";
        $stmtDoc = $conn->prepare($sqlDoc);
        if (!$stmtDoc) {
            throw new Exception('Erro na preparação de documentos: ' . $conn->error);
        }

        $totalFiles = count($_FILES['documentos']['name']);
        $descricoes = $_POST['documento_descricao'] ?? [];

        for ($i = 0; $i < $totalFiles; $i++) {
            if ($_FILES['documentos']['error'][$i] === UPLOAD_ERR_OK) {
                $origName = $_FILES['documentos']['name'][$i];
                $ext      = pathinfo($origName, PATHINFO_EXTENSION);

                // Renomear => nomePaciente_nomeDoc_dataInclusao.ext
                $nomePaciente = preg_replace('/\s+/', '_', strtolower($nome));
                $nomeDocSafe  = preg_replace('/\s+/', '_', strtolower(pathinfo($origName, PATHINFO_FILENAME)));
                $dataIncl     = date('Ymd_His');
                $novoNome     = "{$nomePaciente}_{$nomeDocSafe}_{$dataIncl}.{$ext}";

                $uploadFile   = $uploadDir . $novoNome;

                if (move_uploaded_file($_FILES['documentos']['tmp_name'][$i], $uploadFile)) {
                    $caminhoArquivo = 'uploads/documentos/' . $novoNome;
                    $descricao      = isset($descricoes[$i]) ? $descricoes[$i] : null;

                    $stmtDoc->bind_param("ssss", $cpf, $origName, $descricao, $caminhoArquivo);
                    if (!$stmtDoc->execute()) {
                        throw new Exception('Erro ao cadastrar documento: ' . $stmtDoc->error);
                    }
                } else {
                    throw new Exception('Erro ao fazer upload do documento: ' . $origName);
                }
            }
        }
        $stmtDoc->close();
    }

    $conn->commit();
    echo json_encode(['success' => true, 'message' => 'Paciente cadastrado com sucesso']);
} catch (Exception $e) {
    if (isset($conn) && $conn->connect_errno === 0) {
        $conn->rollback();
    }
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

if (isset($stmt)) $stmt->close();
if (isset($conn)) $conn->close();

