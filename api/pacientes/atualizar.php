<?php
session_start();

require_once '../../config/config.php';
require_once '../../functions/utils/helpers.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método não permitido']);
    exit;
}

if (!isset($_POST['cpf']) || empty($_POST['cpf'])) {
    echo json_encode(['success' => false, 'message' => 'CPF não fornecido']);
    exit;
}

$cpf = preg_replace('/[^0-9]/', '', $_POST['cpf']);

// Validar campos obrigatórios
$requiredFields = ['nome', 'telefone'];
foreach ($requiredFields as $field) {
    if (!isset($_POST[$field]) || empty($_POST[$field])) {
        echo json_encode(['success' => false, 'message' => 'Campo obrigatório não preenchido: ' . $field]);
        exit;
    }
}

try {
    // Processar dados do formulário
    $nome = $_POST['nome'];
    $data_nasc = !empty($_POST['data_nasc']) ? DateTime::createFromFormat('d/m/Y', $_POST['data_nasc'])->format('Y-m-d') : null;
    $telefone = preg_replace('/[^0-9]/', '', $_POST['telefone']);
    $email = isset($_POST['email']) ? $_POST['email'] : null;

    // Dados de endereço
    $cep = isset($_POST['cep']) ? preg_replace('/[^0-9]/', '', $_POST['cep']) : null;
    $logradouro = isset($_POST['logradouro']) ? $_POST['logradouro'] : null;
    $numero = isset($_POST['numero']) ? $_POST['numero'] : null;
    $complemento = isset($_POST['complemento']) ? $_POST['complemento'] : null;
    $bairro = isset($_POST['bairro']) ? $_POST['bairro'] : null;
    $cidade = isset($_POST['cidade']) ? $_POST['cidade'] : null;
    $estado = isset($_POST['estado']) ? $_POST['estado'] : null;

    // Dados de saúde
    $doencas       = $_POST['doencas']       ?? null;
    $alergias      = $_POST['alergias']      ?? null;
    $tem_convenio  = isset($_POST['tem_convenio']) ? 1 : 0;
    $convenio      = $_POST['convenio']      ?? null;
    $numero_convenio = $_POST['numero_convenio'] ?? null;
    $tipo_sanguineo   = $_POST['tipo_sanguineo']   ?? null;

    // Contato de emergência
    $nome_contato_emergencia = $_POST['nome_contato_emergencia'] ?? null;
    $numero_contato_emergencia = !empty($_POST['numero_contato_emergencia'])
        ? preg_replace('/\D/', '', $_POST['numero_contato_emergencia'])
        : null;
    $filiacao_contato_emergencia = $_POST['filiacao_contato_emergencia'] ?? null;

    // Condições médicas
    $condicoes_medicas = $_POST['condicoes_medicas'] ?? null;
    $remedios_em_uso   = $_POST['remedios_em_uso']   ?? null;
    
    // Observações
    $observacoes = $_POST['observacoes'] ?? null;

    // Verificar se o paciente existe
    $sqlCheck = "SELECT cpf, foto_perfil FROM pacientes WHERE cpf = ?";
    $stmtCheck = $conn->prepare($sqlCheck);
    $stmtCheck->bind_param("s", $cpf);
    $stmtCheck->execute();
    $resultCheck = $stmtCheck->get_result();

    if ($resultCheck->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Paciente não encontrado']);
        exit;
    }

    $pacienteAtual = $resultCheck->fetch_assoc();
    $foto_perfil = $pacienteAtual['foto_perfil'];
    $stmtCheck->close();

    // Iniciar transação
    $conn->begin_transaction();

    // Upload de nova foto de perfil
    if (!empty($_FILES['foto_perfil']['name']) && $_FILES['foto_perfil']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = '../../uploads/fotos/';
        
        // Criar diretório se não existir
        if (!is_dir($uploadDir) && !mkdir($uploadDir, 0777, true)) {
            throw new Exception('Erro ao criar diretório para upload de fotos');
        }
        
        $ext = pathinfo($_FILES['foto_perfil']['name'], PATHINFO_EXTENSION);
        $nomePaciente = preg_replace('/\s+/', '_', strtolower($nome));
        $dataInclusao = date('Ymd_His'); // ex: 20230322_141500
        $novoNome     = $nomePaciente . '_perfil_' . $dataInclusao . '.' . $ext;

        $uploadFile = $uploadDir . $novoNome;

        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
        if (!in_array($_FILES['foto_perfil']['type'], $allowedTypes)) {
            throw new Exception('Tipo de arquivo não permitido. Apenas JPG, PNG e GIF são aceitos.');
        }
        if ($_FILES['foto_perfil']['size'] > 2 * 1024 * 1024) {
            throw new Exception('Tamanho do arquivo excede o limite de 2MB.');
        }

        if (move_uploaded_file($_FILES['foto_perfil']['tmp_name'], $uploadFile)) {
            // Remover foto antiga se existir
            if (!empty($pacienteAtual['foto_perfil']) && file_exists('../../' . $pacienteAtual['foto_perfil'])) {
                @unlink('../../' . $pacienteAtual['foto_perfil']);
            }
            
            $foto_perfil = 'uploads/fotos/' . $novoNome;
        } else {
            throw new Exception('Erro ao fazer upload da foto de perfil.');
        }
    }

    // Atualizar paciente no banco de dados
    $sql = "UPDATE pacientes SET 
                nome = ?, data_nasc = ?, telefone = ?, email = ?, 
                cep = ?, logradouro = ?, numero = ?, complemento = ?, bairro = ?, cidade = ?, estado = ?,
                doencas = ?, alergias = ?, tem_convenio = ?, convenio = ?, numero_convenio = ?,
                tipo_sanguineo = ?, nome_contato_emergencia = ?, numero_contato_emergencia = ?,
                filiacao_contato_emergencia = ?, condicoes_medicas = ?, remedios_em_uso = ?,
                foto_perfil = ?, observacoes = ?
            WHERE cpf = ?";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new Exception('Erro na preparação da consulta: ' . $conn->error);
    }

    // Corrigido: O tipo de parâmetro para tem_convenio deve ser 'i' (integer) em vez de 's' (string)
    $stmt->bind_param(
        "ssssssssssssissssssssssss", // Agora com 25 caracteres
        $nome,
        $data_nasc,
        $telefone,
        $email,
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
        $observacoes,
        $cpf
    );
    
    if (!$stmt->execute()) {
        throw new Exception('Erro ao atualizar paciente: ' . $stmt->error);
    }

    // Atualizar descrições de documentos existentes
    if (isset($_POST['documento_id_existente']) && is_array($_POST['documento_id_existente'])) {
        $sqlUpdateDoc = "UPDATE paciente_documentos 
                            SET descricao = ? 
                          WHERE id = ? AND paciente_cpf = ?";
        $stmtUpdateDoc = $conn->prepare($sqlUpdateDoc);
        if (!$stmtUpdateDoc) {
            throw new Exception('Erro na preparação de atualização de documentos: ' . $conn->error);
        }

        foreach ($_POST['documento_id_existente'] as $docId) {
            $descricao = isset($_POST['documento_descricao_existente'][$docId]) ? 
                         $_POST['documento_descricao_existente'][$docId] : null;
            $stmtUpdateDoc->bind_param("sis", $descricao, $docId, $cpf);
            if (!$stmtUpdateDoc->execute()) {
                throw new Exception('Erro ao atualizar descrição do documento: ' . $stmtUpdateDoc->error);
            }
        }
        $stmtUpdateDoc->close();
    }

    // Processar novos documentos
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
        $descricoes = isset($_POST['documento_descricao_novo']) ? $_POST['documento_descricao_novo'] : [];

        for ($i = 0; $i < $totalFiles; $i++) {
            if ($_FILES['documentos']['error'][$i] === UPLOAD_ERR_OK) {
                $origName    = $_FILES['documentos']['name'][$i];
                $ext         = pathinfo($origName, PATHINFO_EXTENSION);

                // Renomear => nomePaciente_nomeOriginal_dataInclusao.ext
                $nomePaciente = preg_replace('/\s+/', '_', strtolower($nome));
                $nomeDoc      = pathinfo($origName, PATHINFO_FILENAME);
                $nomeDocSafe  = preg_replace('/\s+/', '_', strtolower($nomeDoc));
                $dataIncl     = date('Ymd_His');
                $novoNome     = "{$nomePaciente}_{$nomeDocSafe}_{$dataIncl}.{$ext}";

                $uploadFile   = $uploadDir . $novoNome;

                if (move_uploaded_file($_FILES['documentos']['tmp_name'][$i], $uploadFile)) {
                    $caminhoArquivo = 'uploads/documentos/' . $novoNome;
                    $descricao      = isset($descricoes[$i]) ? $descricoes[$i] : null;

                    if (!$stmtDoc->bind_param("ssss", $cpf, $origName, $descricao, $caminhoArquivo)) {
                        throw new Exception('Erro ao vincular parâmetros do documento: ' . $stmtDoc->error);
                    }
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
    echo json_encode(['success' => true, 'message' => 'Paciente atualizado com sucesso']);
} catch (Exception $e) {
    if (isset($conn) && $conn->connect_errno === 0) {
        $conn->rollback();
    }
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

if (isset($stmt)) $stmt->close();
if (isset($conn)) $conn->close();