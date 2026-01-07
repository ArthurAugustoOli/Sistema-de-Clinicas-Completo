<?php
// Conexão com o banco de dados
require_once 'config/config.php';

// Funções utilitárias
require_once 'functions/utils/helpers.php';

// Inicializar variáveis para mensagens
$message = '';
$messageType = '';

// Processar o formulário quando enviado
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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

        // Verificar campos obrigatórios
        if (empty($nome) || empty($cpf) || empty($telefone) || empty($data_nasc)) {
            throw new Exception('Por favor, preencha todos os campos obrigatórios.');
        }

        // Verificar se o CPF já existe
        $sqlCheck = "SELECT cpf FROM pacientes WHERE cpf = ?";
        $stmtCheck = $conn->prepare($sqlCheck);
        $stmtCheck->bind_param("s", $cpf);
        $stmtCheck->execute();
        $resultCheck = $stmtCheck->get_result();
        if ($resultCheck->num_rows > 0) {
            throw new Exception('CPF já cadastrado. Por favor, entre em contato com a clínica.');
        }
        $stmtCheck->close();

        // Upload de foto de perfil
        $foto_perfil = null;
        if (!empty($_FILES['foto_perfil']['name']) && $_FILES['foto_perfil']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = 'uploads/fotos/';
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
            $uploadDir = 'uploads/documentos/';
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
        $message = 'Cadastro realizado com sucesso! Em breve entraremos em contato.';
        $messageType = 'success';
        
        // Limpar o formulário após o envio bem-sucedido
        $_POST = array();
        
    } catch (Exception $e) {
        if (isset($conn) && $conn->connect_errno === 0) {
            $conn->rollback();
        }
        $message = $e->getMessage();
        $messageType = 'danger';
    }

    if (isset($stmt)) $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Autocadastro de Pacientes - Clínica</title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <!-- Flatpickr CSS -->
    <link href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css" rel="stylesheet">

    <!-- Toastify CSS -->
    <link href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css" rel="stylesheet">

    <style>
        :root {
            --primary-color: #4e73df;
            --primary-dark: #3a5ccc;
            --secondary-color: #6c757d;
            --success-color: #1cc88a;
            --info-color: #36b9cc;
            --warning-color: #f6c23e;
            --danger-color: #e74a3b;
            --light-color: #f8f9fc;
            --dark-color: #5a5c69;
        }

        body {
            background-color: #f8f9fc;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            padding-bottom: 20px;
        }

        .header {
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            color: white;
            padding: 20px 0;
            margin-bottom: 20px;
            border-radius: 0 0 15px 15px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .form-container {
            background-color: white;
            border-radius: 15px;
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.05);
            padding: 20px;
            margin-bottom: 20px;
        }

        .form-label {
            font-weight: 500;
            color: #333;
        }

        .required-field::after {
            content: "*";
            color: var(--danger-color);
            margin-left: 4px;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            border: none;
        }

        .btn-primary:hover {
            background: linear-gradient(135deg, var(--primary-dark), var(--primary-color));
        }

        .nav-tabs .nav-link {
            color: var(--secondary-color);
            border: none;
            padding: 10px 15px;
            border-radius: 0;
            font-weight: 500;
        }

        .nav-tabs .nav-link.active {
            color: var(--primary-color);
            background-color: transparent;
            border-bottom: 3px solid var(--primary-color);
        }

        .form-control:focus, .form-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.25rem rgba(78, 115, 223, 0.25);
        }

        .photo-upload-container {
            text-align: center;
            margin-bottom: 20px;
        }

        .photo-preview {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            object-fit: cover;
            margin-bottom: 10px;
            border: 3px solid #f0f0f0;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }

        .camera-button {
            position: absolute;
            bottom: 0;
            right: 0;
            width: 35px;
            height: 35px;
            border-radius: 50%;
            background-color: var(--primary-color);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
        }

        .progress-container {
            margin-bottom: 30px;
        }

        .progress {
            height: 8px;
            border-radius: 4px;
            background-color: #e9ecef;
        }

        .progress-bar {
            background-color: var(--primary-color);
        }

        .step-indicator {
            display: flex;
            justify-content: space-between;
            margin-bottom: 5px;
        }

        .step {
            text-align: center;
            flex: 1;
            font-size: 0.8rem;
            color: var(--secondary-color);
        }

        .step.active {
            color: var(--primary-color);
            font-weight: 600;
        }

        .success-message {
            text-align: center;
            padding: 30px 20px;
        }

        .success-icon {
            font-size: 5rem;
            color: var(--success-color);
            margin-bottom: 20px;
        }

        .floating-help {
            position: fixed;
            bottom: 20px;
            right: 20px;
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background-color: var(--primary-color);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
            z-index: 1000;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .floating-help:hover {
            transform: scale(1.1);
        }

        @media (max-width: 767.98px) {
            .header {
                padding: 15px 0;
            }

            .form-container {
                padding: 15px;
            }

            .nav-tabs .nav-link {
                padding: 8px 10px;
                font-size: 0.9rem;
            }

            .photo-preview {
                width: 100px;
                height: 100px;
            }
        }
    </style>
</head>

<body>
    <div class="header">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-auto">
                    <i class="bi bi-clipboard2-pulse fs-1"></i>
                </div>
                <div class="col">
                    <h1 class="h3 mb-0">Clínica Médica</h1>
                    <p class="mb-0">Formulário de Autocadastro</p>
                </div>
            </div>
        </div>
    </div>

    <div class="container">
        <?php if (!empty($message)): ?>
            <div class="alert alert-<?= $messageType ?> alert-dismissible fade show" role="alert">
                <?php if ($messageType === 'success'): ?>
                    <div class="success-message">
                        <div class="success-icon">
                            <i class="bi bi-check-circle"></i>
                        </div>
                        <h4 class="mb-3">Cadastro Realizado com Sucesso!</h4>
                        <p class="mb-4"><?= $message ?></p>
                        <a href="auto-cadastro.php" class="btn btn-primary">Voltar ao Início</a>
                    </div>
                <?php else: ?>
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>
                    <?= $message ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <?php if (empty($message) || $messageType !== 'success'): ?>
            <div class="progress-container">
                <div class="step-indicator">
                    <div class="step active" id="step1-indicator">Dados Pessoais</div>
                    <div class="step" id="step2-indicator">Contato</div>
                    <div class="step" id="step3-indicator">Endereço</div>
                    <div class="step" id="step4-indicator">Saúde</div>
                </div>
                <div class="progress">
                    <div class="progress-bar" role="progressbar" style="width: 25%" id="progress-bar"></div>
                </div>
            </div>

            <form id="cadastroForm" method="POST" enctype="multipart/form-data">
                <!-- Etapa 1: Dados Pessoais -->
                <div class="form-container" id="step1">
                    <h4 class="mb-4">Dados Pessoais</h4>
                    
                    <div class="photo-upload-container mb-4">
                        <div class="position-relative d-inline-block">
                            <img id="preview_foto" src="assets/img/profile-placeholder.png" class="photo-preview">
                            <div class="camera-button" onclick="document.getElementById('foto_perfil').click()">
                                <i class="bi bi-camera"></i>
                            </div>
                        </div>
                        <input type="file" class="form-control d-none" id="foto_perfil" name="foto_perfil" accept="image/*" onchange="previewImage(this, 'preview_foto')">
                        <small class="form-text text-muted">Clique para adicionar uma foto</small>
                    </div>
                    
                    <div class="mb-3">
                        <label for="nome" class="form-label required-field">Nome Completo</label>
                        <input type="text" class="form-control" id="nome" name="nome" required value="<?= $_POST['nome'] ?? '' ?>">
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="cpf" class="form-label required-field">CPF</label>
                            <input type="text" class="form-control" id="cpf" name="cpf" required value="<?= $_POST['cpf'] ?? '' ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="data_nasc" class="form-label required-field">Data de Nascimento</label>
                            <input type="text" class="form-control datepicker" id="data_nasc" name="data_nasc" required value="<?= $_POST['data_nasc'] ?? '' ?>">
                        </div>
                    </div>
                    
                    <div class="d-flex justify-content-end mt-4">
                        <button type="button" class="btn btn-primary" onclick="nextStep(1)">Próximo <i class="bi bi-arrow-right"></i></button>
                    </div>
                </div>

                <!-- Etapa 2: Contato -->
                <div class="form-container" id="step2" style="display: none;">
                    <h4 class="mb-4">Informações de Contato</h4>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="telefone" class="form-label required-field">Telefone</label>
                            <input type="text" class="form-control" id="telefone" name="telefone" required value="<?= $_POST['telefone'] ?? '' ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email" value="<?= $_POST['email'] ?? '' ?>">
                        </div>
                    </div>
                    
                    <h5 class="mt-4 mb-3">Contato de Emergência</h5>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="nome_contato_emergencia" class="form-label">Nome do Contato</label>
                            <input type="text" class="form-control" id="nome_contato_emergencia" name="nome_contato_emergencia" value="<?= $_POST['nome_contato_emergencia'] ?? '' ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="numero_contato_emergencia" class="form-label">Telefone de Emergência</label>
                            <input type="text" class="form-control" id="numero_contato_emergencia" name="numero_contato_emergencia" value="<?= $_POST['numero_contato_emergencia'] ?? '' ?>">
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="filiacao_contato_emergencia" class="form-label">Relação com o Contato</label>
                        <input type="text" class="form-control" id="filiacao_contato_emergencia" name="filiacao_contato_emergencia" placeholder="Ex: Mãe, Pai, Cônjuge, Filho(a)" value="<?= $_POST['filiacao_contato_emergencia'] ?? '' ?>">
                    </div>
                    
                    <div class="d-flex justify-content-between mt-4">
                        <button type="button" class="btn btn-outline-secondary" onclick="prevStep(2)"><i class="bi bi-arrow-left"></i> Anterior</button>
                        <button type="button" class="btn btn-primary" onclick="nextStep(2)">Próximo <i class="bi bi-arrow-right"></i></button>
                    </div>
                </div>

                <!-- Etapa 3: Endereço -->
                <div class="form-container" id="step3" style="display: none;">
                    <h4 class="mb-4">Endereço</h4>
                    
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label for="cep" class="form-label">CEP</label>
                            <div class="input-group">
                                <input type="text" class="form-control" id="cep" name="cep" value="<?= $_POST['cep'] ?? '' ?>">
                                <button class="btn btn-outline-secondary" type="button" id="buscarCep">Buscar</button>
                            </div>
                        </div>
                        <div class="col-md-8 mb-3">
                            <label for="logradouro" class="form-label">Logradouro</label>
                            <input type="text" class="form-control" id="logradouro" name="logradouro" value="<?= $_POST['logradouro'] ?? '' ?>">
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label for="numero" class="form-label">Número</label>
                            <input type="text" class="form-control" id="numero" name="numero" value="<?= $_POST['numero'] ?? '' ?>">
                        </div>
                        <div class="col-md-8 mb-3">
                            <label for="complemento" class="form-label">Complemento</label>
                            <input type="text" class="form-control" id="complemento" name="complemento" value="<?= $_POST['complemento'] ?? '' ?>">
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label for="bairro" class="form-label">Bairro</label>
                            <input type="text" class="form-control" id="bairro" name="bairro" value="<?= $_POST['bairro'] ?? '' ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="cidade" class="form-label">Cidade</label>
                            <input type="text" class="form-control" id="cidade" name="cidade" value="<?= $_POST['cidade'] ?? '' ?>">
                        </div>
                        <div class="col-md-2 mb-3">
                            <label for="estado" class="form-label">UF</label>
                            <input type="text" class="form-control" id="estado" name="estado" maxlength="2" value="<?= $_POST['estado'] ?? '' ?>">
                        </div>
                    </div>
                    
                    <div class="d-flex justify-content-between mt-4">
                        <button type="button" class="btn btn-outline-secondary" onclick="prevStep(3)"><i class="bi bi-arrow-left"></i> Anterior</button>
                        <button type="button" class="btn btn-primary" onclick="nextStep(3)">Próximo <i class="bi bi-arrow-right"></i></button>
                    </div>
                </div>

                <!-- Etapa 4: Saúde -->
                <div class="form-container" id="step4" style="display: none;">
                    <h4 class="mb-4">Informações de Saúde</h4>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="tipo_sanguineo" class="form-label">Tipo Sanguíneo</label>
                            <select class="form-select" id="tipo_sanguineo" name="tipo_sanguineo">
                                <option value="">Selecione</option>
                                <option value="A+" <?= (isset($_POST['tipo_sanguineo']) && $_POST['tipo_sanguineo'] == 'A+') ? 'selected' : '' ?>>A+</option>
                                <option value="A-" <?= (isset($_POST['tipo_sanguineo']) && $_POST['tipo_sanguineo'] == 'A-') ? 'selected' : '' ?>>A-</option>
                                <option value="B+" <?= (isset($_POST['tipo_sanguineo']) && $_POST['tipo_sanguineo'] == 'B+') ? 'selected' : '' ?>>B+</option>
                                <option value="B-" <?= (isset($_POST['tipo_sanguineo']) && $_POST['tipo_sanguineo'] == 'B-') ? 'selected' : '' ?>>B-</option>
                                <option value="AB+" <?= (isset($_POST['tipo_sanguineo']) && $_POST['tipo_sanguineo'] == 'AB+') ? 'selected' : '' ?>>AB+</option>
                                <option value="AB-" <?= (isset($_POST['tipo_sanguineo']) && $_POST['tipo_sanguineo'] == 'AB-') ? 'selected' : '' ?>>AB-</option>
                                <option value="O+" <?= (isset($_POST['tipo_sanguineo']) && $_POST['tipo_sanguineo'] == 'O+') ? 'selected' : '' ?>>O+</option>
                                <option value="O-" <?= (isset($_POST['tipo_sanguineo']) && $_POST['tipo_sanguineo'] == 'O-') ? 'selected' : '' ?>>O-</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Possui Convênio?</label>
                            <div class="form-check mt-2">
                                <input class="form-check-input" type="checkbox" id="tem_convenio" name="tem_convenio" value="1" <?= (isset($_POST['tem_convenio']) && $_POST['tem_convenio'] == '1') ? 'checked' : '' ?>>
                                <label class="form-check-label" for="tem_convenio">
                                    Sim, possuo convênio
                                </label>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row convenio-fields" id="convenio-fields" style="display: none;">
                        <div class="col-md-6 mb-3">
                            <label for="convenio" class="form-label">Convênio</label>
                            <input type="text" class="form-control" id="convenio" name="convenio" value="<?= $_POST['convenio'] ?? '' ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="numero_convenio" class="form-label">Número da Carteirinha</label>
                            <input type="text" class="form-control" id="numero_convenio" name="numero_convenio" value="<?= $_POST['numero_convenio'] ?? '' ?>">
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="alergias" class="form-label">Alergias</label>
                        <textarea class="form-control" id="alergias" name="alergias" rows="2"><?= $_POST['alergias'] ?? '' ?></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label for="doencas" class="form-label">Doenças Crônicas</label>
                        <textarea class="form-control" id="doencas" name="doencas" rows="2"><?= $_POST['doencas'] ?? '' ?></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label for="condicoes_medicas" class="form-label">Condições Médicas</label>
                        <textarea class="form-control" id="condicoes_medicas" name="condicoes_medicas" rows="2"><?= $_POST['condicoes_medicas'] ?? '' ?></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label for="remedios_em_uso" class="form-label">Medicamentos em uso</label>
                        <textarea class="form-control" id="remedios_em_uso" name="remedios_em_uso" rows="2"><?= $_POST['remedios_em_uso'] ?? '' ?></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label for="observacoes" class="form-label">Observações Adicionais</label>
                        <textarea class="form-control" id="observacoes" name="observacoes" rows="3"><?= $_POST['observacoes'] ?? '' ?></textarea>
                    </div>
                    
                    <div class="d-flex justify-content-between mt-4">
                        <button type="button" class="btn btn-outline-secondary" onclick="prevStep(4)"><i class="bi bi-arrow-left"></i> Anterior</button>
                        <button type="submit" class="btn btn-success">Finalizar Cadastro <i class="bi bi-check-lg"></i></button>
                    </div>
                </div>
            </form>
        <?php endif; ?>
    </div>

    <div class="floating-help" onclick="showHelp()">
        <i class="bi bi-question-lg"></i>
    </div>

    <!-- Modal de Ajuda -->
    <div class="modal fade" id="helpModal" tabindex="-1" aria-labelledby="helpModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="helpModalLabel">Precisa de ajuda?</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Este é o formulário de autocadastro para novos pacientes da nossa clínica.</p>
                    <p>Preencha todos os campos marcados com <span class="text-danger">*</span> (obrigatórios) e avance pelas etapas usando os botões "Próximo" e "Anterior".</p>
                    <p>Caso tenha dúvidas ou problemas com o preenchimento, entre em contato conosco:</p>
                    <ul>
                        <li><i class="bi bi-telephone me-2"></i> (XX) XXXX-XXXX</li>
                        <li><i class="bi bi-envelope me-2"></i> contato@clinica.com.br</li>
                        <li><i class="bi bi-whatsapp me-2"></i> (XX) XXXXX-XXXX</li>
                    </ul>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Entendi</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS e dependências -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- jQuery Mask Plugin -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.mask/1.14.16/jquery.mask.min.js"></script>
    <!-- Flatpickr JS -->
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/pt.js"></script>
    <!-- Toastify JS -->
    <script src="https://cdn.jsdelivr.net/npm/toastify-js"></script>

    <script>
        // Inicializar máscaras
        $(document).ready(function() {
            $('#cpf').mask('000.000.000-00');
            $('#telefone').mask('(00) 00000-0000');
            $('#numero_contato_emergencia').mask('(00) 00000-0000');
            $('#cep').mask('00000-000');
            
            // Inicializar datepicker
            flatpickr(".datepicker", {
                locale: "pt",
                dateFormat: "d/m/Y",
                maxDate: "today",
                allowInput: true
            });
            
            // Mostrar/esconder campos de convênio
            $('#tem_convenio').change(function() {
                if ($(this).is(':checked')) {
                    $('#convenio-fields').show();
                } else {
                    $('#convenio-fields').hide();
                }
            });
            
            // Verificar se o checkbox já está marcado ao carregar a página
            if ($('#tem_convenio').is(':checked')) {
                $('#convenio-fields').show();
            }
            
            // Buscar CEP
            $('#buscarCep').click(function() {
                const cep = $('#cep').val().replace(/\D/g, '');
                if (cep.length !== 8) {
                    showToast('CEP inválido', 'error');
                    return;
                }
                
                // Mostrar loading
                $(this).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>');
                $(this).prop('disabled', true);
                
                $.getJSON(`https://viacep.com.br/ws/${cep}/json/`, function(data) {
                    if (!data.erro) {
                        $('#logradouro').val(data.logradouro);
                        $('#bairro').val(data.bairro);
                        $('#cidade').val(data.localidade);
                        $('#estado').val(data.uf);
                        $('#numero').focus();
                    } else {
                        showToast('CEP não encontrado', 'error');
                    }
                }).fail(function() {
                    showToast('Erro ao buscar CEP', 'error');
                }).always(function() {
                    // Restaurar botão
                    $('#buscarCep').html('Buscar');
                    $('#buscarCep').prop('disabled', false);
                });
            });
            
            // Validação do formulário antes de enviar
            $('#cadastroForm').submit(function(e) {
                const requiredFields = ['nome', 'cpf', 'data_nasc', 'telefone'];
                let valid = true;
                
                requiredFields.forEach(field => {
                    if (!$('#' + field).val()) {
                        showToast(`O campo ${field.replace('_', ' ')} é obrigatório`, 'error');
                        valid = false;
                    }
                });
                
                if (!valid) {
                    e.preventDefault();
                }
            });
        });
        
        // Função para mostrar toast
        function showToast(message, type = 'success') {
            Toastify({
                text: message,
                duration: 3000,
                close: true,
                gravity: "top",
                position: "center",
                backgroundColor: type === 'success' ? "#1cc88a" : "#e74a3b",
                stopOnFocus: true
            }).showToast();
        }
        
        // Função para previsualizar imagem
        function previewImage(input, previewId) {
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                
                reader.onload = function(e) {
                    document.getElementById(previewId).src = e.target.result;
                }
                
                reader.readAsDataURL(input.files[0]);
            }
        }
        
        // Navegação entre etapas
        let currentStep = 1;
        
        function nextStep(step) {
            // Validar campos obrigatórios da etapa atual
            let valid = true;
            
            if (step === 1) {
                if (!$('#nome').val() || !$('#cpf').val() || !$('#data_nasc').val()) {
                    showToast('Preencha todos os campos obrigatórios', 'error');
                    valid = false;
                }
            } else if (step === 2) {
                if (!$('#telefone').val()) {
                    showToast('Preencha o telefone', 'error');
                    valid = false;
                }
            }
            
            if (!valid) return;
            
            // Avançar para a próxima etapa
            $(`#step${step}`).hide();
            $(`#step${step+1}`).show();
            currentStep = step + 1;
            
            // Atualizar indicadores de progresso
            updateProgress();
        }
        
        function prevStep(step) {
            $(`#step${step}`).hide();
            $(`#step${step-1}`).show();
            currentStep = step - 1;
            
            // Atualizar indicadores de progresso
            updateProgress();
        }
        
        function updateProgress() {
            // Atualizar barra de progresso
            const progressPercentage = (currentStep / 4) * 100;
            $('#progress-bar').css('width', `${progressPercentage}%`);
            
            // Atualizar indicadores de etapa
            $('.step').removeClass('active');
            for (let i = 1; i <= currentStep; i++) {
                $(`#step${i}-indicator`).addClass('active');
            }
        }
        
        // Função para mostrar modal de ajuda
        function showHelp() {
            const helpModal = new bootstrap.Modal(document.getElementById('helpModal'));
            helpModal.show();
        }
    </script>
</body>
</html>
