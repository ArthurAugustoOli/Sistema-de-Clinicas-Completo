<?php
// Iniciar sessão para gerenciamento de login
session_start();

// Conexão com o banco de dados
require_once '../config/config.php';

// Funções utilitárias
require_once '../functions/utils/helpers.php';

// Verificar se o usuário está logado
// if (!isset($_SESSION['usuario_id'])) {
//     header('Location: ../login.php');
//     exit;
// }

// Parâmetros de busca/paginação
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$page   = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit  = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
$offset = ($page - 1) * $limit;

// Consulta base
$sqlBase = "SELECT p.*,
                   (SELECT MAX(c.data_consulta) 
                      FROM consultas c 
                     WHERE c.paciente_cpf = p.cpf) AS ultima_visita,
                   (SELECT COUNT(*) 
                      FROM consultas c 
                     WHERE c.paciente_cpf = p.cpf) AS total_consultas
              FROM pacientes p";

// Verificar se a tabela consultas existe
$tableExists = false;
$checkTable = $conn->query("SHOW TABLES LIKE 'consultas'");
if ($checkTable->num_rows > 0) {
    $tableExists = true;
} else {
    // Se a tabela não existir, modificar a consulta base
    $sqlBase = "SELECT p.*, 
                       NULL AS ultima_visita,
                       0 AS total_consultas
                  FROM pacientes p";
}

// Filtro de busca
$whereClause = "";
if (!empty($search)) {
    $search = $conn->real_escape_string($search);
    $searchClean = preg_replace('/[^0-9]/', '', $search);
    // Filtro de busca
    $whereClause = "";
    if (!empty($search)) {
        $search = $conn->real_escape_string($search);
        $searchClean = preg_replace('/[^0-9]/', '', $search);

        $whereClause = " WHERE (p.nome LIKE '%$search%' 
                     OR p.email LIKE '%$search%'";

        if (!empty($searchClean)) {
            $whereClause .= " OR p.cpf LIKE '%$searchClean%'
                          OR p.telefone LIKE '%$searchClean%'";
        }

        $whereClause .= ")";
    }
}

// Contar total de registros
$sqlCount = "SELECT COUNT(*) as total FROM pacientes p $whereClause";
$resultCount = $conn->query($sqlCount);
$rowCount = $resultCount->fetch_assoc();
$totalRecords = $rowCount['total'];
$totalPages   = ceil($totalRecords / $limit);

// Buscar pacientes com paginação
$sql = $sqlBase . $whereClause . " ORDER BY p.nome ASC LIMIT $offset, $limit";
$result = $conn->query($sql);

// Lista de convênios (se necessário)
$sqlConvenios = "SELECT DISTINCT convenio 
                   FROM pacientes 
                  WHERE convenio IS NOT NULL 
               ORDER BY convenio ASC";
$resultConvenios = $conn->query($sqlConvenios);
$convenios = [];
if ($resultConvenios) {
    while ($row = $resultConvenios->fetch_assoc()) {
        $convenios[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Pacientes - Sistema de Gerenciamento de Clínica</title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <!-- Estilos personalizados -->
    <link href="../css/styles.css" rel="stylesheet">

    <!-- DataTables CSS (opcional) -->
    <link href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css" rel="stylesheet">

    <!-- Toastify CSS -->
    <link href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css" rel="stylesheet">

    <!-- Flatpickr CSS -->
    <link href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css" rel="stylesheet">

    <style>
        :root {
            --header-height: 60px;
            --mobile-nav-height: 60px;
        }

        body {
            overflow-x: hidden;
            padding-bottom: var(--mobile-nav-height);
        }

        .form-select {
            font-size: 18px;
            padding: 4px 8px;
            width: 150px;
        }

        .form-select option {
            font-size: 12px;
        }

        @media (min-width: 992px) {
            body {
                padding-bottom: 0;
            }
        }

        a {
            text-decoration: none;
        }

        .avatar-circle {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: #e9ecef;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            color: #6c757d;
            font-size: 16px;
            margin-right: 10px;
        }

        .patient-card {
            transition: all 0.2s ease;
            border-left: 4px solid transparent;
        }

        .patient-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            border-left: 4px solid var(--primary-color);
        }

        .badge-consultas {
            position: absolute;
            top: 10px;
            right: 10px;
        }

        .search-container {
            position: relative;
        }

        .search-container .clear-search {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: #6c757d;
        }

        .btn-action {
            width: 36px;
            height: 36px;
            padding: 0;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            margin-right: 5px;
            transition: all 0.2s;
        }

        .btn-action:hover {
            transform: translateY(-2px);
        }

        .modal-header {
            background-color: var(--primary-color);
            color: white;
        }

        .modal-header .btn-close {
            color: white;
            filter: brightness(0) invert(1);
        }

        .nav-tabs .nav-link.active {
            border-bottom: 3px solid var(--primary-color);
            font-weight: bold;
        }

        .patient-info-item {
            margin-bottom: 15px;
        }

        .patient-info-item .label {
            font-weight: bold;
            color: #6c757d;
            font-size: 0.9rem;
        }

        .patient-info-item .value {
            font-size: 1rem;
        }

        .timeline {
            position: relative;
            padding-left: 30px;
        }

        .timeline::before {
            content: '';
            position: absolute;
            left: 10px;
            top: 0;
            bottom: 0;
            width: 2px;
            background-color: #e9ecef;
        }

        .timeline-item {
            position: relative;
            margin-bottom: 20px;
        }

        .timeline-item::before {
            content: '';
            position: absolute;
            left: -30px;
            top: 0;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background-color: var(--primary-color);
        }

        .flatpickr-input {
            background-color: white !important;
        }

        .pagination-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 20px;
        }

        .view-toggle-btn.active {
            background-color: var(--primary-color);
            color: white;
        }

        /* Modal de visualização de documentos */
        .modal-doc-preview .modal-body {
            text-align: center;
        }

        .modal-doc-preview iframe,
        .modal-doc-preview img {
            width: 100%;
            max-height: 70vh;
        }

        /* Melhorias de responsividade */
        .main-content {
            transition: all 0.3s ease;
            width: 100%;
        }

        @media (min-width: 992px) {
            .main-content {
                width: calc(100% - var(--sidebar-width));
                margin-left: var(--sidebar-width);
            }

            body:has(.sidebar.collapsed) .main-content {
                width: calc(100% - var(--sidebar-collapsed-width));
                margin-left: var(--sidebar-collapsed-width);
            }
        }

        /* Ajustes para telas pequenas */
        @media (max-width: 767.98px) {
            .card-body {
                padding: 1rem;
            }

            .btn-action {
                width: 32px;
                height: 32px;
                margin-right: 3px;
            }

            .pagination .page-link {
                padding: 0.25rem 0.5rem;
                font-size: 0.875rem;
            }

            .modal-dialog {
                margin: 0.5rem;
            }

            .nav-tabs .nav-link {
                padding: 0.5rem 0.75rem;
                font-size: 0.875rem;
            }

            .filter-row {
                flex-direction: column;
            }

            .filter-row>div {
                margin-bottom: 0.5rem;
            }
        }

        /* Pull to refresh */
        .ptr-indicator {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            height: 50px;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: #f8f9fa;
            transform: translateY(-100%);
            transition: transform 0.3s ease;
            z-index: 1000;
        }

        .ptr-indicator.visible {
            transform: translateY(0);
        }

        .ptr-spinner {
            width: 24px;
            height: 24px;
            border: 3px solid rgba(0, 0, 0, 0.1);
            border-top-color: var(--primary-color);
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            to {
                transform: rotate(360deg);
            }
        }

        /* Ajustes para o mobile-nav */
        .has-mobile-nav {
            padding-bottom: var(--mobile-nav-height);
        }

        .mobile-bottom-nav {
            display: flex;
            flex-direction: row;
            /* garante que os items fiquem em linha */
            background: white;
            box-shadow: 0 -2px 10px rgba(0, 0, 0, 0.1);
            border-radius: 1.5rem 1.5rem 0 0;
            padding: 0.5rem 0;
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            z-index: 1030;
            gap: 0.8rem;
        }

        .mobile-bottom-nav-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            margin-left: 7px;
            padding: 0.5rem 0;
            color: rgb(108, 117, 125);
            transition: all 0.3s;
        }

        .mobile-bottom-nav-item i {
            font-size: 1.25rem;
            margin-bottom: 0.25rem;
        }

        .mobile-bottom-nav-item span {
            font-weight: 500;
            font-size: 12px;
            font-family: 'Poppins', sans-serif;
        }

        .mobile-bottom-nav-item.active {
            color: var(--primary-color);
        }


        /* Ajustes para o filtro responsivo */
        .filter-container {
            margin-bottom: 1rem;
        }

        @media (max-width: 767.98px) {
            .filter-actions {
                margin-top: 1rem;
            }
        }

        /*ESTOU USANDO PARA TESTE*/
        @media (max-width: 767.98px) {
            #exportBtn {
                margin-top: 8px;
            }
        }


        /* Ajustes para os cards de pacientes */
        @media (max-width: 767.98px) {
            .patient-card .card-body {
                padding: 0.75rem;
            }

            .patient-actions {
                flex-wrap: wrap;
            }

            .patient-actions .btn-group:last-child {
                margin-top: 0.5rem;
            }
        }

        .modal-body {
            flex: auto !important;
        }
    </style>
</head>

<body class="has-mobile-nav">
    <div class="d-flex flex-column flex-lg-row">
        <div class="d-none d-md-block">

            <?php include '../includes/sidebar.php'; ?>

        </div>
        <!-- Conteúdo Principal -->
        <main class="main-content">
            <!-- Incluir Topbar -->
            <?php include '../includes/topbar.php'; ?>

            <div class="container-fluid px-3 px-md-4 py-3 py-md-4 mt-5">
                <div class="d-flex justify-content-between align-items-center mb-4 mt-3 flex-wrap">
                    <h2 class="mb-0 mb-2 mb-md-0">Pacientes</h2>
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal"
                        data-bs-target="#addPacienteModal">
                        <i class="bi bi-plus-lg me-1"></i> Novo Paciente
                    </button>
                </div>

                <!-- Filtros e Pesquisa -->
                <div class="card shadow-sm mb-4">
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-12 col-md-6">
                                <div class="search-container">
                                    <input type="text" id="searchInput" class="form-control"
                                        placeholder="Buscar por nome, CPF, email ou telefone..."
                                        value="<?= htmlspecialchars($search); ?>">
                                    <?php if (!empty($search)): ?>
                                        <span class="clear-search" id="clearSearch"><i class="bi bi-x-circle"></i></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="col-6 col-md-2">
                                <select id="limitSelect" class="form-select">
                                    <option value="10" <?= $limit == 10  ? 'selected' : '' ?>>10 por página</option>
                                    <option value="25" <?= $limit == 25  ? 'selected' : '' ?>>25 por página</option>
                                    <option value="50" <?= $limit == 50  ? 'selected' : '' ?>>50 por página</option>
                                    <option value="100" <?= $limit == 100 ? 'selected' : '' ?>>100 por página</option>
                                </select>
                            </div>
                            <div class="col-6 col-md-4 text-end">
                                <div class="btn-group" role="group">
                                    <button type="button" class="btn btn-outline-secondary view-toggle-btn active"
                                        data-view="card">
                                        <i class="bi bi-grid-3x3-gap"></i>
                                    </button>
                                    <button type="button" class="btn btn-outline-secondary view-toggle-btn"
                                        data-view="list">
                                        <i class="bi bi-list-ul"></i>
                                    </button>
                                </div>
                                <button class="btn btn-outline-primary ms-2" id="exportBtn">
                                    <i class="bi bi-download me-1 d-none d-sm-inline"></i> Exportar
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Contador de Resultados -->
                <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap">
                    <p class="text-muted mb-2 mb-md-0">
                        Mostrando <?= min($totalRecords, $limit) ?> de <?= $totalRecords ?> pacientes
                    </p>
                    <?php if (!empty($search)): ?>
                        <div class="d-flex align-items-center">
                            <span class="badge bg-primary me-2">Filtro ativo</span>
                            <a href="?page=1&limit=<?= $limit ?>" class="text-decoration-none">Limpar filtro</a>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Visualização em Cards -->
                <div id="cardView" class="row g-3">
                    <?php if ($result && $result->num_rows > 0): ?>
                        <?php while ($paciente = $result->fetch_assoc()): ?>
                            <?php
                            // Iniciais para avatar
                            $nameParts = explode(' ', $paciente['nome']);
                            $initials = '';
                            if (count($nameParts) >= 2) {
                                $initials = mb_substr($nameParts[0], 0, 1) . mb_substr($nameParts[count($nameParts) - 1], 0, 1);
                            } else {
                                $initials = mb_substr($paciente['nome'], 0, 2);
                            }
                            $initials = strtoupper($initials);

                            // Telefone e última visita
                            $telefone     = !empty($paciente['telefone']) ? formataTelefone($paciente['telefone']) : 'Não informado';
                            $ultimaVisita = !empty($paciente['ultima_visita']) ? formataData($paciente['ultima_visita']) : 'Sem consultas';
                            $whatsappNumber = !empty($paciente['telefone']) ? preg_replace('/[^0-9]/', '', $paciente['telefone']) : '';
                            ?>
                            <div class="col-12 col-md-6 col-lg-4">
                                <div class="card patient-card h-100 shadow-sm">
                                    <div class="card-body position-relative">
                                        <span
                                            class="badge bg-<?= $paciente['total_consultas'] > 0 ? 'primary' : 'secondary' ?> badge-consultas"
                                            style="width:60px; padding:15px;">
                                            <?= $paciente['total_consultas'] ?>
                                            consulta<?= ($paciente['total_consultas'] != 1 ? 's' : '') ?>
                                        </span>
                                        <div class="d-flex align-items-center mb-3">
                                            <?php if (!empty($paciente['foto_perfil']) && file_exists('../' . $paciente['foto_perfil'])): ?>
                                                <img src="../<?= $paciente['foto_perfil'] ?>" alt="Foto de perfil"
                                                    class="rounded-circle me-2" width="40" height="40">
                                            <?php else: ?>
                                                <div class="avatar-circle"><?= $initials ?></div>
                                            <?php endif; ?>
                                            <div>
                                                <h5 class="card-title mb-0 fs-6"><?= htmlspecialchars($paciente['nome']) ?></h5>
                                                <small class="text-muted"><?= formataCPF($paciente['cpf']) ?></small>
                                            </div>
                                        </div>
                                        <div class="mb-2 text-truncate">
                                            <i class="bi bi-envelope text-muted me-2"></i>
                                            <?= !empty($paciente['email']) ? htmlspecialchars($paciente['email']) : 'Email não informado' ?>
                                        </div>
                                        <div class="mb-2">
                                            <i class="bi bi-telephone text-muted me-2"></i>
                                            <?= $telefone ?>
                                        </div>
                                        <div class="mb-3">
                                            <i class="bi bi-calendar-check text-muted me-2"></i>
                                            Última visita: <?= $ultimaVisita ?>
                                        </div>

                                        <div class="d-flex justify-content-between align-items-center mt-3 flex-wrap">
                                            <div class="mb-2 mb-sm-0">
                                                <button class="btn btn-action btn-primary"
                                                    onclick="viewPaciente('<?= $paciente['cpf'] ?>')" title="Ver detalhes">
                                                    <i class="bi bi-eye-fill"></i>
                                                </button>
                                                <button class="btn btn-action btn-warning"
                                                    onclick="editPaciente('<?= $paciente['cpf'] ?>')" title="Editar">
                                                    <i class="bi bi-pencil-fill  text-white"></i>
                                                </button>
                                                <button class="btn btn-action btn-danger"
                                                    onclick="confirmDelete('<?= $paciente['cpf'] ?>', '<?= htmlspecialchars($paciente['nome']) ?>')"
                                                    title="Excluir">
                                                    <i class="bi bi-trash-fill"></i>
                                                </button>
                                                <button class="btn btn-action btn-info"
                                                    onclick="openEvolucaoModal({ mode:'add', cpf:'<?= $paciente['cpf'] ?>' })"
                                                    title="Adicionar Evolução">
                                                    <i class="bi bi-journal text-white"></i>
                                                </button>

                                                <i class="bi bi-journal text-white"></i>
                                                </button>
                                            </div>
                                            <div>
                                                <?php if (!empty($whatsappNumber)): ?>
                                                    <a href="https://wa.me/55<?= $whatsappNumber ?>" target="_blank"
                                                        class="btn btn-action btn-success" title="WhatsApp">
                                                        <i class="bi bi-whatsapp"></i>
                                                    </a>
                                                <?php endif; ?>
                                                <!-- Botão PDF -->
                                                <a href="../api/pacientes/gerar_pdf.php?cpf=<?= $paciente['cpf'] ?>"
                                                    target="_blank" class="btn btn-action btn-secondary" title="Gerar PDF">
                                                    <i class="bi bi-file-earmark-pdf"></i>
                                                </a>
                                            </div>
                                        </div>

                                    </div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="col-12">
                            <div class="alert alert-info text-center">
                                <i class="bi bi-info-circle me-2"></i>
                                Nenhum paciente encontrado.
                                <?php if (!empty($search)): ?>
                                    <a href="?page=1&limit=<?= $limit ?>" class="alert-link">Limpar busca</a>
                                <?php else: ?>
                                    <a href="#" data-bs-toggle="modal" data-bs-target="#addPacienteModal"
                                        class="alert-link">Cadastrar novo paciente</a>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Visualização em Lista -->
                <div id="listView" class="row" style="display: none;">
                    <div class="col-12">
                        <div class="card shadow-sm">
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Paciente</th>
                                            <th>Contato</th>
                                            <th class="d-none d-md-table-cell">Última Visita</th>
                                            <th class="d-none d-md-table-cell">Consultas</th>
                                            <th class="text-center">Ações</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        if ($result && $totalRecords > 0):
                                            $result->data_seek(0);
                                            while ($paciente = $result->fetch_assoc()):
                                                $nameParts = explode(' ', $paciente['nome']);
                                                $initials = (count($nameParts) >= 2)
                                                    ? mb_substr($nameParts[0], 0, 1) . mb_substr($nameParts[count($nameParts) - 1], 0, 1)
                                                    : mb_substr($paciente['nome'], 0, 2);
                                                $initials = strtoupper($initials);

                                                $telefone     = !empty($paciente['telefone']) ? formataTelefone($paciente['telefone']) : 'Não informado';
                                                $ultimaVisita = !empty($paciente['ultima_visita']) ? formataData($paciente['ultima_visita']) : 'Sem consultas';
                                                $whatsappNumber = !empty($paciente['telefone']) ? preg_replace('/[^0-9]/', '', $paciente['telefone']) : '';
                                        ?>
                                                <tr>
                                                    <td>
                                                        <div class="d-flex align-items-center">
                                                            <?php if (!empty($paciente['foto_perfil']) && file_exists('../' . $paciente['foto_perfil'])): ?>
                                                                <img src="../<?= $paciente['foto_perfil'] ?>" alt="Foto de perfil"
                                                                    class="rounded-circle me-2" width="40" height="40">
                                                            <?php else: ?>
                                                                <div class="avatar-circle"><?= $initials ?></div>
                                                            <?php endif; ?>
                                                            <div>
                                                                <div class="fw-medium">
                                                                    <?= htmlspecialchars($paciente['nome']) ?></div>
                                                                <small
                                                                    class="text-muted"><?= formataCPF($paciente['cpf']) ?></small>
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <div class="text-truncate" style="max-width: 150px;">
                                                            <?= !empty($paciente['email']) ? htmlspecialchars($paciente['email']) : 'Email não informado' ?>
                                                        </div>
                                                        <small class="text-muted"><?= $telefone ?></small>
                                                    </td>
                                                    <td class="d-none d-md-table-cell"><?= $ultimaVisita ?></td>
                                                    <td class="d-none d-md-table-cell">
                                                        <span
                                                            class="badge bg-<?= $paciente['total_consultas'] > 0 ? 'primary' : 'secondary' ?>">
                                                            <?= $paciente['total_consultas'] ?>
                                                            consulta<?= ($paciente['total_consultas'] != 1 ? 's' : '') ?>
                                                        </span>
                                                    </td>
                                                    <td class="text-center">
                                                        <div class="d-flex justify-content-center flex-wrap">
                                                            <button class="btn btn-sm btn-primary me-1 mb-1"
                                                                onclick="viewPaciente('<?= $paciente['cpf'] ?>')"
                                                                title="Ver detalhes">
                                                                <i class="bi bi-eye-fill"></i>
                                                            </button>
                                                            <button class="btn btn-sm btn-warning me-1 mb-1"
                                                                onclick="editPaciente('<?= $paciente['cpf'] ?>')"
                                                                title="Editar">
                                                                <i class="bi bi-pencil-fill"></i>
                                                            </button>
                                                            <button class="btn btn-sm btn-danger me-1 mb-1"
                                                                onclick="confirmDelete('<?= $paciente['cpf'] ?>', '<?= htmlspecialchars($paciente['nome']) ?>')"
                                                                title="Excluir">
                                                                <i class="bi bi-trash-fill"></i>
                                                            </button>
                                                            <?php if (!empty($whatsappNumber)): ?>
                                                                <a href="https://wa.me/55<?= $whatsappNumber ?>" target="_blank"
                                                                    class="btn btn-sm btn-success me-1 mb-1" title="WhatsApp">
                                                                    <i class="bi bi-whatsapp"></i>
                                                                </a>
                                                            <?php endif; ?>
                                                            <button class="btn btn-sm btn-info text-white me-1 mb-1"
                                                                onclick="agendarConsulta('<?= $paciente['cpf'] ?>')"
                                                                title="Agendar consulta">
                                                                <i class="bi bi-calendar-plus"></i>
                                                            </button>
                                                            <a href="../api/pacientes/gerar_pdf.php?cpf=<?= $paciente['cpf'] ?>"
                                                                target="_blank" class="btn btn-sm btn-secondary mb-1"
                                                                title="Gerar PDF">
                                                                <i class="bi bi-file-earmark-pdf"></i>
                                                            </a>
                                                        </div>
                                                    </td>
                                                </tr>
                                            <?php
                                            endwhile;
                                        else:
                                            ?>
                                            <tr>
                                                <td colspan="5" class="text-center py-3">
                                                    Nenhum paciente encontrado.
                                                    <?php if (!empty($search)): ?>
                                                        <a href="?page=1&limit=<?= $limit ?>" class="alert-link">Limpar
                                                            busca</a>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Paginação -->
                <?php if ($totalPages > 1): ?>
                    <div class="pagination-container mt-4">
                        <div>
                            <span class="text-muted d-none d-sm-inline">Página <?= $page ?> de <?= $totalPages ?></span>
                        </div>
                        <nav aria-label="Navegação de páginas">
                            <ul class="pagination pagination-sm flex-wrap justify-content-center">
                                <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
                                    <a class="page-link" href="?page=1&limit=<?= $limit ?>&search=<?= urlencode($search) ?>"
                                        aria-label="Primeira">
                                        <span aria-hidden="true">&laquo;&laquo;</span>
                                    </a>
                                </li>
                                <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
                                    <a class="page-link"
                                        href="?page=<?= $page - 1 ?>&limit=<?= $limit ?>&search=<?= urlencode($search) ?>"
                                        aria-label="Anterior">
                                        <span aria-hidden="true">&laquo;</span>
                                    </a>
                                </li>

                                <?php
                                $startPage = max(1, $page - 2);
                                $endPage   = min($totalPages, $page + 2);

                                if ($startPage > 1) {
                                    echo '<li class="page-item d-none d-sm-block"><span class="page-link">...</span></li>';
                                }

                                for ($i = $startPage; $i <= $endPage; $i++):
                                ?>
                                    <li class="page-item <?= ($i == $page) ? 'active' : '' ?>">
                                        <a class="page-link"
                                            href="?page=<?= $i ?>&limit=<?= $limit ?>&search=<?= urlencode($search) ?>"><?= $i ?></a>
                                    </li>
                                <?php endfor; ?>

                                <?php if ($endPage < $totalPages) {
                                    echo '<li class="page-item d-none d-sm-block"><span class="page-link">...</span></li>';
                                } ?>

                                <li class="page-item <?= ($page >= $totalPages) ? 'disabled' : '' ?>">
                                    <a class="page-link"
                                        href="?page=<?= $page + 1 ?>&limit=<?= $limit ?>&search=<?= urlencode($search) ?>"
                                        aria-label="Próxima">
                                        <span aria-hidden="true">&raquo;</span>
                                    </a>
                                </li>
                                <li class="page-item <?= ($page >= $totalPages) ? 'disabled' : '' ?>">
                                    <a class="page-link"
                                        href="?page=<?= $totalPages ?>&limit=<?= $limit ?>&search=<?= urlencode($search) ?>"
                                        aria-label="Última">
                                        <span aria-hidden="true">&raquo;&raquo;</span>
                                    </a>
                                </li>
                            </ul>
                        </nav>
                    </div>
                <?php endif; ?>

            </div>
        </main>
    </div>

    <!-- Ações Rápidas 
                <div class="row mb-4 slide-in-up" style="animation-delay: 0.1s;">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title mb-3">Ações Rápidas</h5>
                                <div class="row g-3">
                                    <div class="col-6 col-md-3">
                                        <a href="pages/pacientes.php" class="btn btn-primary w-100 d-flex flex-column align-items-center py-3 quick-action">
                                            <i class="bi bi-person-plus-fill"></i>
                                            <span>Novo Paciente</span>
                                        </a>
                                    </div>
                                    <div class="col-6 col-md-3">
                                        <a href="pages/calendario.php" class="btn btn-success w-100 d-flex flex-column align-items-center py-3 quick-action">
                                            <i class="bi bi-calendar-plus"></i>
                                            <span>Agendar Consulta</span>
                                        </a>
                                    </div>
                                    <div class="col-6 col-md-3">
                                        <a href="pages/consulta_dia.php" class="btn btn-info w-100 d-flex flex-column align-items-center py-3 quick-action text-white">
                                            <i class="bi bi-calendar-check"></i>
                                            <span>Consultas do Dia</span>
                                        </a>
                                    </div>
                                    <div class="col-6 col-md-3">
                                        <a href="pages/financeiro/dashboard.php" class="btn btn-warning w-100 d-flex flex-column align-items-center py-3 quick-action">
                                            <i class="bi bi-cash-coin"></i>
                                            <span>Financeiro</span>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>-->

    <!-- Modal Adicionar Paciente -->
    <div class="modal fade" id="addPacienteModal" tabindex="-1" aria-labelledby="addPacienteModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addPacienteModalLabel">Novo Paciente</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>
                <div class="modal-body">
                    <form id="addPacienteForm" enctype="multipart/form-data">
                        <div class="row mb-3">
                            <div class="col-md-8">
                                <div class="mb-3">
                                    <label for="nome" class="form-label">Nome Completo*</label>
                                    <input type="text" class="form-control" id="nome" name="nome" required>
                                </div>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="cpf" class="form-label">CPF*</label>
                                            <input type="text" class="form-control" id="cpf" name="cpf" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="data_nasc" class="form-label">Data de Nascimento*</label>
                                            <input type="text" class="form-control datepicker" id="data_nasc"
                                                name="data_nasc" required>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4 text-center">
                                <div class="mb-3">
                                    <label for="foto_perfil" class="form-label">Foto de Perfil</label>
                                    <div class="d-flex flex-column align-items-center">
                                        <div class="position-relative mb-2">
                                            <img id="preview_foto" src="../assets/img/profile-placeholder.png"
                                                class="img-thumbnail rounded-circle"
                                                style="width: 120px; height: 120px; object-fit: cover;">
                                            <button type="button"
                                                class="btn btn-sm btn-primary position-absolute bottom-0 end-0 rounded-circle"
                                                style="width: 30px; height: 30px; padding: 0;"
                                                onclick="document.getElementById('foto_perfil').click()">
                                                <i class="bi bi-camera"></i>
                                            </button>
                                        </div>
                                        <input type="file" class="form-control d-none" id="foto_perfil"
                                            name="foto_perfil" accept="image/*"
                                            onchange="previewImage(this, 'preview_foto')">
                                        <small class="form-text text-muted">Clique para adicionar uma foto</small>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <ul class="nav nav-tabs mb-3" id="pacienteTab" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active" id="contato-tab" data-bs-toggle="tab"
                                    data-bs-target="#contato" type="button" role="tab" aria-controls="contato"
                                    aria-selected="true">Contato</button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="endereco-tab" data-bs-toggle="tab"
                                    data-bs-target="#endereco" type="button" role="tab" aria-controls="endereco"
                                    aria-selected="false">Endereço</button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="saude-tab" data-bs-toggle="tab" data-bs-target="#saude"
                                    type="button" role="tab" aria-controls="saude" aria-selected="false">Saúde</button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="observacoes-tab" data-bs-toggle="tab"
                                    data-bs-target="#observacoes" type="button" role="tab" aria-controls="observacoes"
                                    aria-selected="false">Obs</button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="documentos-tab" data-bs-toggle="tab"
                                    data-bs-target="#documentos" type="button" role="tab" aria-controls="documentos"
                                    aria-selected="false">Docs</button>
                            </li>
                        </ul>

                        <div class="tab-content" id="pacienteTabContent">
                            <!-- Aba de Contato -->
                            <div class="tab-pane fade show active" id="contato" role="tabpanel"
                                aria-labelledby="contato-tab">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="telefone" class="form-label">Telefone*</label>
                                            <input type="text" class="form-control" id="telefone" name="telefone"
                                                required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="email" class="form-label">Email</label>
                                            <input type="email" class="form-control" id="email" name="email">
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="nome_contato_emergencia" class="form-label">Contato de
                                                Emergência</label>
                                            <input type="text" class="form-control" id="nome_contato_emergencia"
                                                name="nome_contato_emergencia">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="numero_contato_emergencia" class="form-label">Telefone de
                                                Emergência</label>
                                            <input type="text" class="form-control" id="numero_contato_emergencia"
                                                name="numero_contato_emergencia">
                                        </div>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label for="filiacao_contato_emergencia" class="form-label">Filiação do Contato de
                                        Emergência</label>
                                    <input type="text" class="form-control" id="filiacao_contato_emergencia"
                                        name="filiacao_contato_emergencia">
                                </div>
                            </div>

                            <!-- Aba de Endereço -->
                            <div class="tab-pane fade" id="endereco" role="tabpanel" aria-labelledby="endereco-tab">
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label for="cep" class="form-label">CEP</label>
                                            <div class="input-group">
                                                <input type="text" class="form-control" id="cep" name="cep">
                                                <button class="btn btn-outline-secondary" type="button"
                                                    id="buscarCep">Buscar</button>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-8">
                                        <div class="mb-3">
                                            <label for="logradouro" class="form-label">Logradouro</label>
                                            <input type="text" class="form-control" id="logradouro" name="logradouro">
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label for="numero" class="form-label">Número</label>
                                            <input type="text" class="form-control" id="numero" name="numero">
                                        </div>
                                    </div>
                                    <div class="col-md-8">
                                        <div class="mb-3">
                                            <label for="complemento" class="form-label">Complemento</label>
                                            <input type="text" class="form-control" id="complemento" name="complemento">
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label for="bairro" class="form-label">Bairro</label>
                                            <input type="text" class="form-control" id="bairro" name="bairro">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="cidade" class="form-label">Cidade</label>
                                            <input type="text" class="form-control" id="cidade" name="cidade">
                                        </div>
                                    </div>
                                    <div class="col-md-2">
                                        <div class="mb-3">
                                            <label for="estado" class="form-label">UF</label>
                                            <input type="text" class="form-control" id="estado" name="estado"
                                                maxlength="2">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Aba de Saúde -->
                            <div class="tab-pane fade" id="saude" role="tabpanel" aria-labelledby="saude-tab">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="tipo_sanguineo" class="form-label">Tipo Sanguíneo</label>
                                            <select class="form-select" id="tipo_sanguineo" name="tipo_sanguineo">
                                                <option value="">Selecione</option>
                                                <option value="A+">A+</option>
                                                <option value="A-">A-</option>
                                                <option value="B+">B+</option>
                                                <option value="B-">B-</option>
                                                <option value="AB+">AB+</option>
                                                <option value="AB-">AB-</option>
                                                <option value="O+">O+</option>
                                                <option value="O-">O-</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="tem_convenio" class="form-label">Possui Convênio?</label>
                                            <div class="form-check mt-2">
                                                <input class="form-check-input" type="checkbox" id="tem_convenio"
                                                    name="tem_convenio" value="1">
                                                <label class="form-check-label" for="tem_convenio">
                                                    Sim, possui convênio
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="row convenio-fields" style="display: none;">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="convenio" class="form-label">Convênio</label>
                                            <input type="text" class="form-control" id="convenio" name="convenio">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="numero_convenio" class="form-label">Número da
                                                Carteirinha</label>
                                            <input type="text" class="form-control" id="numero_convenio"
                                                name="numero_convenio">
                                        </div>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label for="alergias" class="form-label">Alergias</label>
                                    <textarea class="form-control" id="alergias" name="alergias" rows="2"></textarea>
                                </div>
                                <div class="mb-3">
                                    <label for="doencas" class="form-label">Doenças Crônicas</label>
                                    <textarea class="form-control" id="doencas" name="doencas" rows="2"></textarea>
                                </div>
                                <div class="mb-3">
                                    <label for="condicoes_medicas" class="form-label">Condições Médicas</label>
                                    <textarea class="form-control" id="condicoes_medicas" name="condicoes_medicas"
                                        rows="2"></textarea>
                                </div>
                                <div class="mb-3">
                                    <label for="remedios_em_uso" class="form-label">Medicamentos em uso</label>
                                    <textarea class="form-control" id="remedios_em_uso" name="remedios_em_uso"
                                        rows="2"></textarea>
                                </div>
                            </div>

                            <!-- Aba de Observações -->
                            <div class="tab-pane fade" id="observacoes" role="tabpanel"
                                aria-labelledby="observacoes-tab">
                                <div class="mb-3">
                                    <label for="observacoes" class="form-label">Observações Gerais</label>
                                    <textarea class="form-control" id="observacoes" name="observacoes"
                                        rows="5"></textarea>
                                </div>
                            </div>

                            <!-- Aba de Documentos -->
                            <div class="tab-pane fade" id="documentos" role="tabpanel" aria-labelledby="documentos-tab">
                                <div class="mb-3">
                                    <label for="documentos" class="form-label">Documentos do Paciente</label>
                                    <input type="file" class="form-control" id="documentos" name="documentos[]"
                                        multiple>
                                    <small class="form-text text-muted">Você pode selecionar múltiplos arquivos (PDF,
                                        imagens, etc.)</small>
                                </div>
                                <div id="documentos-container">
                                    <!-- Campos de descrição serão adicionados dinamicamente -->
                                </div>
                                <div id="documentos-preview" class="row mt-3">
                                    <!-- Prévia dos documentos será exibida aqui -->
                                </div>
                            </div>
                        </div>

                        <div class="text-end mt-4">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                            <button type="submit" class="btn btn-primary">Salvar Paciente</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Visualizar Paciente -->
    <div class="modal fade" id="viewPacienteModal" tabindex="-1" aria-labelledby="viewPacienteModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="viewPacienteModalLabel">Detalhes do Paciente</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>
                <div class="modal-body">
                    <div class="text-center mb-4" id="pacienteHeader">
                        <!-- Preenchido via JS -->
                    </div>

                    <ul class="nav nav-tabs mb-3" id="viewPacienteTab" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="view-info-tab" data-bs-toggle="tab"
                                data-bs-target="#view-info" type="button" role="tab" aria-controls="view-info"
                                aria-selected="true">Informações</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="view-consultas-tab" data-bs-toggle="tab"
                                data-bs-target="#view-consultas" type="button" role="tab" aria-controls="view-consultas"
                                aria-selected="false">Histórico</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="view-documentos-tab" data-bs-toggle="tab"
                                data-bs-target="#view-documentos" type="button" role="tab"
                                aria-controls="view-documentos" aria-selected="false">Documentos</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="view-evolucao-tab" data-bs-toggle="tab"
                                data-bs-target="#view-evolucao" type="button">Evolução</button>
                        </li>
                    </ul>

                    <div class="tab-content" id="viewPacienteTabContent">
                        <div class="tab-pane fade show active" id="view-info" role="tabpanel"
                            aria-labelledby="view-info-tab">
                            <div class="row">
                                <div class="col-md-6">
                                    <h6 class="border-bottom pb-2 mb-3">Dados Pessoais</h6>
                                    <div id="dadosPessoais"></div>
                                </div>
                                <div class="col-md-6">
                                    <h6 class="border-bottom pb-2 mb-3">Contato</h6>
                                    <div id="dadosContato"></div>
                                </div>
                            </div>
                            <div class="row mt-4">
                                <div class="col-md-6">
                                    <h6 class="border-bottom pb-2 mb-3">Endereço</h6>
                                    <div id="dadosEndereco"></div>
                                </div>
                                <div class="col-md-6">
                                    <h6 class="border-bottom pb-2 mb-3">Saúde</h6>
                                    <div id="dadosSaude"></div>
                                </div>
                            </div>
                            <div class="row mt-4">
                                <div class="col-12">
                                    <h6 class="border-bottom pb-2 mb-3">Observações</h6>
                                    <div id="dadosObservacoes"></div>
                                </div>
                            </div>
                        </div>
                        <div class="tab-pane fade" id="view-consultas" role="tabpanel"
                            aria-labelledby="view-consultas-tab">
                            <div class="timeline" id="historicoConsultas">
                                <!-- Preenchido via JS -->
                            </div>
                        </div>
                        <div class="tab-pane fade" id="view-documentos" role="tabpanel"
                            aria-labelledby="view-documentos-tab">
                            <div id="viewDocumentosContainer" class="row">
                                <!-- Preenchido via JS -->
                            </div>
                        </div>
                        <div class="tab-pane fade" id="view-evolucao" role="tabpanel">
                            <div class="timeline" id="evolucaoTimeline">
                                <!-- será preenchido por JS -->
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
                    <button type="button" class="btn btn-warning" id="btnEditarPaciente">Editar</button>
                    <button type="button" class="btn btn-info text-white" id="btnAgendarConsulta">Agendar</button>
                </div>
            </div>
        </div>
    </div>

    /* <?php  /*
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../config/config.php';

$cpf      = $_POST['cpf'] ?? '';
$titulo   = trim($_POST['titulo'] ?? '');
$subtitulo = trim($_POST['subtitulo'] ?? '');
$horarioInput  = $_POST['horario'] ?? '';

if (!$cpf || !$titulo || !$horarioInput) {
    echo json_encode(['success' => false, 'message' => 'Dados incompletos']);
    exit;
}

// Formatar horário (de dd/mm/yyyy HH:ii para Y-m-d H:i:s)
$horarioObj = DateTime::createFromFormat('d/m/Y H:i', $horarioInput);
if (!$horarioObj) {
    echo json_encode(['success' => false, 'message' => 'Formato de horário inválido']);
    exit;
}
$horario = $horarioObj->format('Y-m-d H:i:s');

$stmt = $conn->prepare("INSERT INTO comentarios (paciente_cpf, titulo, subtitulo, horario) VALUES (?, ?, ?, ?)");
if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'Erro no prepare(): ' . $conn->error]);
    exit;
}

$stmt->bind_param("ssss", $cpf, $titulo, $subtitulo, $horario);

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Erro ao salvar: ' . $stmt->error]);
}*/
        ?>*/



    <!-- Modal de Evolução (add & edit) -->
    <div class="modal fade" id="evolucaoModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-sm modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="evolucaoModalLabel">Adicionar Evolução</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form id="evolucaoForm" novalidate>
                    <input type="hidden" name="cpf" id="evoCpf">
                    <input type="hidden" name="id"  id="evoId">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Título*</label>
                            <input type="text" class="form-control" name="titulo" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Subtítulo</label>
                            <input type="text" class="form-control" name="subtitulo">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Data e Hora*</label>
                            <input type="text" class="form-control datetimepicker" name="horario" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary" id="evoSubmitBtn">Salvar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>




    <script>
        document.getElementById("formComentario").addEventListener("submit", function(e) {
            e.preventDefault();

            const dados = new FormData(this);
            dados.append('cpf', window.currentCpf); // define esse valor no botão

            fetch('../api/comentarios/adicionar.php', {
                    method: 'POST',
                    body: dados
                })
                .then(r => r.json())
                .then(resp => {
                    if (resp.success) {
                        showToast('Comentário adicionado com sucesso!', 'success');
                        bootstrap.Modal.getInstance(document.getElementById("addComentarioModal")).hide();
                        viewPaciente(window.currentCpf); // atualiza a visualização do paciente
                    } else {
                        showToast(resp.message || 'Erro ao salvar', 'error');
                    }
                });
        });
    </script>




    <!-- Outros modais (Editar, Excluir, Agendar, etc.) permanecem iguais -->
    <!-- Modal Editar Paciente -->
    <div class="modal fade" id="editPacienteModal" tabindex="-1" aria-labelledby="editPacienteModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editPacienteModalLabel">Editar Paciente</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>
                <div class="modal-body">
                    <form id="editPacienteForm" enctype="multipart/form-data">
                        <!-- Conteúdo do formulário preenchido dinamicamente pelo JS (função editPaciente) -->
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" form="editPacienteForm" class="btn btn-primary">Salvar Alterações</button>
                </div>
            </div>
        </div>
    </div>


    <!-- Modal Confirmar Exclusão -->
    <div class="modal fade" id="deleteConfirmModal" tabindex="-1" aria-labelledby="deleteConfirmModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title" id="deleteConfirmModalLabel">Confirmar Exclusão</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>
                <div class="modal-body">
                    <p>Tem certeza que deseja excluir o paciente <strong id="pacienteNomeDelete"></strong>?</p>
                    <p class="text-danger"><i class="bi bi-exclamation-triangle-fill me-2"></i>Esta ação não pode ser
                        desfeita.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-danger" id="confirmDeleteBtn">Excluir</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Agendar Consulta -->
    <div class="modal fade" id="agendarConsultaModal" tabindex="-1" aria-labelledby="agendarConsultaModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="agendarConsultaModalLabel">Agendar Consulta</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>
                <div class="modal-body">
                    <form id="agendarConsultaForm">
                        <input type="hidden" id="paciente_cpf" name="paciente_cpf">
                        <div class="mb-3">
                            <label for="data_consulta" class="form-label">Data e Hora*</label>
                            <input type="text" class="form-control datetimepicker" id="data_consulta"
                                name="data_consulta" required>
                        </div>
                        <div class="mb-3">
                            <label for="procedimento" class="form-label">Procedimento*</label>
                            <input type="text" class="form-control" id="procedimento" name="procedimento" required>
                        </div>
                        <div class="mb-3">
                            <label for="observacoes_consulta" class="form-label">Observações</label>
                            <textarea class="form-control" id="observacoes_consulta" name="observacoes_consulta"
                                rows="3"></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-primary" id="salvarConsultaBtn">Agendar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para Visualizar Documento (Preview) -->
    <div class="modal fade modal-doc-preview" id="previewDocumentoModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Visualizar Documento</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>
                <div class="modal-body">
                    <h5 id="docPreviewTitle" class="mb-3"></h5>
                    <div id="docPreviewContainer">
                        <!-- Iframe ou Img -->
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Sair</button>
                    <button type="button" class="btn btn-primary" onclick="window.print();">Imprimir</button>
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
    <!-- DataTables JS (opcional) -->
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
    <!-- Toastify JS -->
    <script src="https://cdn.jsdelivr.net/npm/toastify-js"></script>
    <!-- Flatpickr JS -->
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/pt.js"></script>

    <script>
        const evoModal = new bootstrap.Modal(document.getElementById('evolucaoModal'));
        const formEvo = document.getElementById('evolucaoForm');
        const lblTitle = document.getElementById('evolucaoModalLabel');
        const btnSubmit = document.getElementById('evoSubmitBtn');
        const inpCpf = document.getElementById('evoCpf');
        const inpId = document.getElementById('evoId');

        // inicia o flatpickr
        flatpickr(formEvo.querySelector('.datetimepicker'), {
            locale: "pt",
            enableTime: true,
            time_24hr: true,

            // o usuário vê este formato:
            altInput: true,
            altFormat: "d/m/Y H:i",

            // mas o valor real do <input> (o que cai no POST) será este:
            dateFormat: "Y-m-d H:i:S",

            allowInput: true
        });



        function renderDocumentos(documentos, containerId, isEditable) {
            let html = '';

            // Verifica se há documentos, se não houver, exibe a mensagem de "Nenhum documento cadastrado"
            if (!documentos || documentos.length === 0) {
                html = '<div class="col-12"><p class="text-muted">Nenhum documento cadastrado.</p></div>';
            } else {
                documentos.forEach(doc => {
                    if (isEditable) {
                        // Exibição para o modo de edição
                        html += `
                <div class="col-md-4 documento-edit mb-3">
                    <div class="card">
                        <div class="card-body">
                            <h6 class="card-title">${doc.nome_documento}</h6>
                            <div class="mb-2">
                                <button type="button" class="btn btn-sm btn-primary"
                                        onclick="abrirPreviewDocumento('${doc.nome_documento}', '../${doc.caminho_arquivo}')">
                                    Visualizar
                                </button>
                                <button type="button" class="btn btn-sm btn-danger"
                                        onclick="excluirDocumento(${doc.id}, '${containerId}')">
                                    Excluir
                                </button>
                            </div>
                            <input type="hidden" name="documento_id_existente[]" value="${doc.id}">
                            <label class="form-label">Descrição</label>
                            <input type="text" class="form-control"
                                   name="documento_descricao_existente[${doc.id}]"
                                   value="${doc.descricao ? doc.descricao : ''}">
                        </div>
                    </div>
                </div>`;
                    } else {
                        // Exibição para o modo de visualização (sem edição)
                        html += `
                <div class="col-md-4 mb-3">
                    <div class="card">
                        <div class="card-body">
                            <h6 class="card-title">${doc.nome_documento}</h6>
                            <p class="card-text">${doc.descricao ? doc.descricao : 'Sem descrição'}</p>
                            <button type="button" class="btn btn-sm btn-primary"
                                    onclick="abrirPreviewDocumento('${doc.nome_documento}', '../${doc.caminho_arquivo}')">
                                Visualizar
                            </button>
                        </div>
                    </div>
                </div>`;
                    }
                });
            }

            // Atualiza o conteúdo do contêiner com os documentos
            $('#' + containerId).html(html);
        }


        /**
         * Abre um modal de preview do documento (imagem ou PDF) com botões de Imprimir e Sair.
         */
        function abrirPreviewDocumento(nomeDocumento, caminho) {
            $('#docPreviewTitle').text(nomeDocumento);
            let ext = caminho.split('.').pop().toLowerCase();
            let previewHtml = '';
            if (['pdf'].includes(ext)) {
                // Exibir PDF em iframe
                previewHtml = `<iframe src="${caminho}" style="border:none;" height="600" width="100%"></iframe>`;
            } else {
                // Tratar como imagem
                previewHtml = `<img src="${caminho}" alt="${nomeDocumento}" class="img-fluid" />`;
            }
            $('#docPreviewContainer').html(previewHtml);
            $('#previewDocumentoModal').modal('show');
        }

        /**
         * Excluir documento existente via AJAX.
         */
        function excluirDocumento(documentId, containerId) {
            if (!confirm('Tem certeza que deseja excluir este documento?')) return;
            $.ajax({
                url: '../api/pacientes/excluir_documento.php',
                type: 'POST',
                data: {
                    id: documentId
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        showToast('Documento excluído com sucesso!', 'success');
                        // Remover o card do documento no DOM
                        // (ou recarregar a lista se preferir)
                        $('input[value="' + documentId + '"][name="documento_id_existente[]"]').closest(
                            '.documento-edit').remove();
                    } else {
                        showToast(response.message || 'Erro ao excluir documento', 'error');
                    }
                },
                error: function() {
                    showToast('Erro ao excluir documento', 'error');
                }
            });
        }

        /**
         * Função para mostrar preview da imagem
         */
        function previewImage(input, previewId) {
            if (input.files && input.files[0]) {
                const reader = new FileReader();

                reader.onload = function(e) {
                    document.getElementById(previewId).src = e.target.result;
                }

                reader.readAsDataURL(input.files[0]);
            }
        }

        /**
         * Ao carregar a página, inicializa máscaras, datepickers e eventos.
         */
        $(document).ready(function() {
            // Máscaras
            $('#cpf').mask('000.000.000-00');
            $('#telefone').mask('(00) 00000-0000');
            $('#numero_contato_emergencia').mask('(00) 00000-0000');
            $('#cep').mask('00000-000');

            // Datepickers
            flatpickr(".datepicker", {
                locale: "pt",
                dateFormat: "d/m/Y",
                allowInput: true
            });
            flatpickr(".datetimepicker", {
                locale: "pt",
                dateFormat: "d/m/Y H:i",
                enableTime: true,
                time_24hr: true,
                allowInput: true
            });

            // Trocar visualização (card/list)
            $('.view-toggle-btn').click(function() {
                $('.view-toggle-btn').removeClass('active');
                $(this).addClass('active');
                const view = $(this).data('view');
                if (view === 'card') {
                    $('#cardView').show();
                    $('#listView').hide();
                } else {
                    $('#cardView').hide();
                    $('#listView').show();
                }
            });

            // Limpar pesquisa
            $('#clearSearch').click(function() {
                window.location.href = '?page=1&limit=<?= $limit ?>';
            });

            // Pesquisar ao pressionar Enter
            $('#searchInput').on('keyup', function(e) {
                if (e.key === 'Enter') {
                    const searchTerm = $(this).val().trim();
                    window.location.href = '?page=1&limit=<?= $limit ?>&search=' + encodeURIComponent(
                        searchTerm);
                }
            });

            // Mudar quantidade de itens por página
            $('#limitSelect').change(function() {
                const limit = $(this).val();
                window.location.href = '?page=1&limit=' + limit + '&search=<?= urlencode($search) ?>';
            });

            // Evento para mostrar/esconder campos de convênio
            $('#tem_convenio').change(function() {
                if ($(this).is(':checked')) {
                    $('.convenio-fields').show();
                } else {
                    $('.convenio-fields').hide();
                }
            });

            // Buscar CEP
            $('#buscarCep').click(function() {
                const cep = $('#cep').val().replace(/\D/g, '');
                if (cep.length !== 8) {
                    showToast('CEP inválido', 'error');
                    return;
                }
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
                });
            });

            // Adicionar campos de descrição para documentos
            $('#documentos').on('change', function() {
                const files = this.files;
                if (!files || files.length === 0) return;

                // Limpar campos de descrição anteriores
                $('#documentos-container').html('');

                // Adicionar campos de descrição para cada arquivo
                for (let i = 0; i < files.length; i++) {
                    $('#documentos-container').append(`
                        <div class="documento-item mb-3">
                            <div class="row">
                                <div class="col-md-12">
                                    <label class="form-label">Descrição do Documento ${i+1}</label>
                                    <input type="text" class="form-control documento-descricao" 
                                           name="documento_descricao[]" 
                                           placeholder="Ex: Exame de sangue, Raio-X, Receita médica...">
                                </div>
                            </div>
                        </div>
                    `);
                }

                // Mostrar prévia dos arquivos
                let previewHtml = '';
                for (let i = 0; i < files.length; i++) {
                    const file = files[i];
                    previewHtml += `
                        <div class="col-md-4 mb-3">
                            <div class="card">
                                <div class="card-body">
                                    <h6 class="card-title">${file.name}</h6>
                                    <p class="card-text">Tamanho: ${(file.size / 1024).toFixed(2)} KB</p>
                                </div>
                            </div>
                        </div>
                    `;
                }
                $('#documentos-preview').html(previewHtml);
            });

            // Submeter formulário de novo paciente
            $(document).ready(function() {
                $("#addPacienteForm").off("submit").on("submit", function(e) {
                    e.preventDefault();

                    const formData = new FormData(this);

                    // Remove qualquer alerta anterior
                    $(".toast").remove();

                    $.ajax({
                        url: "../api/pacientes/criar.php",
                        type: "POST",
                        data: formData,
                        processData: false,
                        contentType: false,
                        success: (response) => {
                            // Remove toasts existentes antes de mostrar outro
                            $(".toast").remove();

                            if (response.success) {
                                showToast("Paciente cadastrado com sucesso!",
                                    "success");
                                $("#addPacienteModal").modal("hide");
                                this.reset();
                                setTimeout(() => {
                                    window.location.reload();
                                }, 1000);
                            } else {
                                showToast(response.message ||
                                    "Erro ao cadastrar paciente", "error");
                            }
                        },
                        error: () => {
                            $(".toast").remove(); // remove duplicados
                            showToast("Erro ao cadastrar paciente", "error");
                        },
                    });
                });
            });


            // Inicializar pull-to-refresh para dispositivos móveis
            let touchStartY = 0;
            let touchEndY = 0;

            document.addEventListener('touchstart', function(e) {
                touchStartY = e.touches[0].clientY;
            }, false);

            document.addEventListener('touchmove', function(e) {
                touchEndY = e.touches[0].clientY;
                const distance = touchEndY - touchStartY;

                // Se o usuário estiver no topo da página e puxar para baixo
                if (window.scrollY === 0 && distance > 0 && distance < 100) {
                    $('.ptr-indicator').addClass('visible');
                    $('.ptr-indicator').css('transform', `translateY(${distance - 50}px)`);
                    e.preventDefault();
                }
            }, {
                passive: false
            });

            document.addEventListener('touchend', function(e) {
                const distance = touchEndY - touchStartY;

                // Se o usuário puxou o suficiente para recarregar
                if (window.scrollY === 0 && distance > 70) {
                    $('.ptr-indicator').css('transform', 'translateY(0)');

                    // Recarregar a página após um pequeno atraso
                    setTimeout(function() {
                        window.location.reload();
                    }, 1000);
                } else {
                    $('.ptr-indicator').css('transform', 'translateY(-100%)');
                    $('.ptr-indicator').removeClass('visible');
                }

                touchStartY = 0;
                touchEndY = 0;
            }, false);

            // Adicionar indicador de pull-to-refresh
            $('body').append('<div class="ptr-indicator"><div class="ptr-spinner"></div></div>');
        });

        /**
         * Exibir modal de visualização de um paciente
         */
        function viewPaciente(cpf) {
            window.currentCpf = cpf;
            $.ajax({
                url: '../api/pacientes/detalhes.php',
                type: 'GET',
                data: {
                    cpf
                },
                dataType: 'json',
                success: function(response) {
                    if (!response.success) {
                        showToast(response.message || 'Erro ao carregar dados do paciente', 'error');
                        return;
                    }

                    const paciente = response.data.paciente || {};
                    const consultas = response.data.consultas || [];
                    const documentos = response.data.documentos || [];

                    // — Cabeçalho —
                    let headerHtml = '';
                    if (paciente.foto_perfil) {
                        headerHtml +=
                            `<img src="../${paciente.foto_perfil}" class="rounded-circle mb-2" width="100" height="100" style="object-fit:cover;">`;
                    } else {
                        const parts = (paciente.nome || '').split(' ');
                        let initials = parts.length >= 2 ?
                            parts[0][0] + parts[parts.length - 1][0] :
                            (paciente.nome || '').substr(0, 2);
                        initials = initials.toUpperCase();
                        headerHtml +=
                            `<div class="avatar-circle mx-auto mb-2" style="width:100px;height:100px;font-size:36px;">${initials}</div>`;
                    }
                    headerHtml += `<h4>${paciente.nome||'Nome não informado'}</h4>`;
                    headerHtml += `<p class="text-muted mb-0">${formatCPF(paciente.cpf)}</p>`;
                    $('#pacienteHeader').html(headerHtml);

                    // — Dados Pessoais —
                    let pessoaisHtml = '';
                    pessoaisHtml += createInfoItem('CPF', formatCPF(paciente.cpf));
                    pessoaisHtml += createInfoItem('Data de Nascimento', formatDate(paciente.data_nasc));
                    pessoaisHtml += createInfoItem('Idade', calcularIdade(paciente.data_nasc) + ' anos');
                    $('#dadosPessoais').html(pessoaisHtml);

                    // — Contato —
                    let contatoHtml = '';
                    contatoHtml += createInfoItem('Telefone', formataTelefone(paciente.telefone));
                    contatoHtml += createInfoItem('Email', paciente.email);
                    contatoHtml += createInfoItem('Contato Emergência', paciente.nome_contato_emergencia);
                    contatoHtml += createInfoItem('Telefone Emergência', formataTelefone(paciente.numero_contato_emergencia));
                    contatoHtml += createInfoItem('Filiação Contato', paciente.filiacao_contato_emergencia);
                    $('#dadosContato').html(contatoHtml);

                    // — Endereço —
                    let endHtml = '';
                    if (paciente.logradouro) {
                        endHtml += createInfoItem('Endereço',
                            `${paciente.logradouro}, ${paciente.numero}` +
                            (paciente.complemento ? `, ${paciente.complemento}` : ''));
                        endHtml += createInfoItem('Bairro', paciente.bairro);
                        endHtml += createInfoItem('Cidade/UF',
                            `${paciente.cidade}/${paciente.estado}`);
                        endHtml += createInfoItem('CEP',
                            paciente.cep.replace(/(\d{5})(\d{3})/, '$1-$2'));
                    } else {
                        endHtml = '<p class="text-muted">Não informado</p>';
                    }
                    $('#dadosEndereco').html(endHtml);

                    // — Saúde —
                    let saudeHtml = '';
                    saudeHtml += createInfoItem('Tipo Sanguíneo', paciente.tipo_sanguineo);
                    saudeHtml += createInfoItem('Convênio', paciente.convenio || 'Não possui');
                    saudeHtml += createInfoItem('Nº Carteirinha', paciente.numero_convenio);
                    saudeHtml += createInfoItem('Doenças', paciente.doencas);
                    saudeHtml += createInfoItem('Alergias', paciente.alergias);
                    saudeHtml += createInfoItem('Condições Médicas', paciente.condicoes_medicas);
                    saudeHtml += createInfoItem('Medicamentos em Uso', paciente.remedios_em_uso);
                    $('#dadosSaude').html(saudeHtml);

                    // — Observações —
                    $('#dadosObservacoes').html(
                        paciente.observacoes || 'Nenhuma observação registrada.'
                    );

                    // — Consultas —
                    let consultasHtml = '';
                    if (consultas.length) {
                        consultasHtml = consultas.map(c => `
                  <div class="timeline-item">
                    <div class="card mb-2">
                      <div class="card-body">
                        <h6 class="card-title">${formatDateTime(c.data_consulta)}</h6>
                        <p class="card-text mb-1"><strong>Procedimento:</strong> ${c.procedimento}</p>
                        <p class="card-text mb-1"><strong>Profissional:</strong> ${c.profissional_nome}</p>
                        ${c.observacoes?`<p class="card-text"><strong>Observações:</strong> ${c.observacoes}</p>`:''}
                      </div>
                    </div>
                  </div>
                `).join('');
                    } else {
                        consultasHtml = '<div class="alert alert-info">Nenhuma consulta registrada.</div>';
                    }
                    $('#historicoConsultas').html(consultasHtml);

                    // — Documentos —
                    renderDocumentos(documentos, 'viewDocumentosContainer', false);

                    // — EVOLUÇÕES —
$.ajax({
  url: '../api/evolucoes/listar.php',
  type: 'GET',
  data: { cpf },
  dataType: 'json',
  success: function(resEvo) {
    let evoHtml = '';

    if (resEvo.success && resEvo.evolucoes.length) {
      resEvo.evolucoes.forEach(ev => {
  evoHtml += `
    <div class="timeline-item d-flex justify-content-between align-items-start">
      <div class="flex-grow-1">
        <div class="card mb-2">
          <div class="card-body">
            <h6 class="card-title">${formatDateTime(ev.data_horario)}</h6>
            <p class="card-text mb-1"><strong>${ev.titulo}</strong></p>
            ${ev.subtitulo ? `<p class="card-text">${ev.subtitulo}</p>` : ''}
          </div>
        </div>
      </div>
      <div class="ms-2">
        <!-- Botão Editar -->
        <button
          class="btn btn-sm btn-warning"
          title="Editar evolução"
          onclick="abrirModalEditEvolucao(
            ${ev.id},
            '${ev.titulo.replace(/'/g, "\\'")}',
            '${(ev.subtitulo||'').replace(/'/g, "\\'")}',
            '${ev.data_horario}'
          )"
        >
          <i class="bi bi-pencil-fill"></i>
        </button>
        <!-- NOVO Botão Excluir -->
        <button
          class="btn btn-sm btn-danger ms-1"
          title="Excluir evolução"
          onclick="deleteEvolucao(${ev.id})"
        >
          <i class="bi bi-trash-fill"></i>
        </button>
      </div>
    </div>
  `;
});
    } else {
      evoHtml = '<div class="alert alert-info">Nenhuma evolução registrada.</div>';
    }

    $('#evolucaoTimeline').html(evoHtml);
  },
  error: function() {
    $('#evolucaoTimeline').html(
      '<div class="alert alert-danger">Erro ao carregar evoluções.</div>'
    );
  }
});

                    // — Footer buttons —
                    $('#btnEditarPaciente').off('click').on('click', function() {
                        $('#viewPacienteModal').modal('hide');
                        editPaciente(paciente.cpf);
                    });
                    $('#btnAgendarConsulta').off('click').on('click', function() {
                        $('#viewPacienteModal').modal('hide');
                        agendarConsulta(paciente.cpf);
                    });

                    // — Mostrar modal —
                    $('#viewPacienteModal').modal('show');
                },
                error: function() {
                    showToast('Erro ao carregar dados do paciente', 'error');
                }
            });
        }


        /**
         * Confirmar exclusão do paciente
         */
        function confirmDelete(cpf, nome) {
            $('#pacienteNomeDelete').text(nome);
            $('#confirmDeleteBtn').attr('onclick', `deletePaciente('${cpf}')`);
            $('#deleteConfirmModal').modal('show');
        }

        /**
         * Excluir paciente via AJAX
         */
        function deletePaciente(cpf) {
            $.ajax({
                url: '../api/pacientes/excluir.php',
                type: 'POST',
                data: {
                    cpf: cpf
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        showToast('Paciente excluído com sucesso!', 'success');
                        $('#deleteConfirmModal').modal('hide');
                        setTimeout(() => {
                            window.location.reload();
                        }, 1000);
                    } else {
                        showToast(response.message || 'Erro ao excluir paciente', 'error');
                    }
                },
                error: function() {
                    showToast('Erro ao excluir paciente', 'error');
                }
            });
        }

        /**
         * Abrir modal de agendar consulta
         */
        function agendarConsulta(cpf) {
            $('#viewPacienteModal').modal('hide');
            $('#paciente_cpf').val(cpf); // Set the patient CPF in the form

            // Configurar datepicker
            flatpickr("#data_consulta", {
                locale: "pt",
                dateFormat: "d/m/Y H:i",
                enableTime: true,
                time_24hr: true,
                allowInput: true,
                minDate: "today"
            });

            // Configurar evento para salvar consulta
            $('#salvarConsultaBtn').off('click').on('click', function() {
                const formData = new FormData(document.getElementById('agendarConsultaForm'));

                $.ajax({
                    url: '../api/consultas/agendar.php',
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(resp) {
                        if (resp.success) {
                            showToast('Consulta agendada com sucesso!', 'success');
                            $('#agendarConsultaModal').modal('hide');
                            setTimeout(() => {
                                window.location.reload();
                            }, 1000);
                        } else {
                            showToast(resp.message || 'Erro ao agendar consulta', 'error');
                        }
                    },
                    error: function() {
                        showToast('Erro ao agendar consulta', 'error');
                    }
                });
            });

            // Exibir modal
            $('#agendarConsultaModal').modal('show');
        }

        /**
         * Funções auxiliares
         */
        function showToast(message, type = 'success') {
            Toastify({
                text: message,
                duration: 3000,
                close: true,
                gravity: "top",
                position: "right",
                backgroundColor: type === 'success' ? "#4e73df" : "#e74a3b",
                stopOnFocus: true
            }).showToast();
        }

        function createInfoItem(label, value) {
            if (!value) value = 'Não informado';
            return `
                <div class="patient-info-item">
                    <div class="label">${label}</div>
                    <div class="value">${value}</div>
                </div>`;
        }

        function formatCPF(cpf) {
            if (!cpf) return 'Não informado';
            cpf = cpf.replace(/\D/g, '');
            if (cpf.length !== 11) return cpf;
            return cpf.replace(/(\d{3})(\d{3})(\d{3})(\d{2})/, '$1.$2.$3-$4');
        }

        function formataTelefone(telefone) {
            if (!telefone) return 'Não informado';
            telefone = telefone.replace(/\D/g, '');
            if (telefone.length === 11) {
                return telefone.replace(/(\d{2})(\d{5})(\d{4})/, '($1) $2-$3');
            } else if (telefone.length === 10) {
                return telefone.replace(/(\d{2})(\d{4})(\d{4})/, '($1) $2-$3');
            }
            return telefone;
        }

        function formatDate(dateStr) {
            if (!dateStr) return 'Não informada';
            const date = new Date(dateStr);
            return date.toLocaleDateString('pt-BR');
        }

        function formatDateTime(dateTimeStr) {
            if (!dateTimeStr) return 'Não informada';
            const date = new Date(dateTimeStr);
            return date.toLocaleDateString('pt-BR') + ' ' + date.toLocaleTimeString('pt-BR', {
                hour: '2-digit',
                minute: '2-digit'
            });
        }

        function calcularIdade(dateStr) {
            if (!dateStr) return 0;
            const birth = new Date(dateStr);
            const today = new Date();
            let age = today.getFullYear() - birth.getFullYear();
            const m = today.getMonth() - birth.getMonth();
            if (m < 0 || (m === 0 && today.getDate() < birth.getDate())) {
                age--;
            }
            return age;
        }

        function openEvolucaoModal(o) {
            inpCpf.value = o.cpf;
            inpId.value = o.mode === 'edit' ? o.id : '';

            lblTitle.textContent = o.mode === 'edit' ? 'Editar Evolução' : 'Adicionar Evolução';
            btnSubmit.textContent = o.mode === 'edit' ? 'Atualizar' : 'Salvar';

            formEvo.titulo.value = o.titulo || '';
            formEvo.subtitulo.value = o.subtitulo || '';

            if (o.mode === 'edit' && o.dataHor) {
                // converte ISO → d/m/Y H:i
                const d = new Date(o.dataHor);
                const pad = n => String(n).padStart(2, '0');
                const s = `${pad(d.getDate())}/${pad(d.getMonth()+1)}/${d.getFullYear()} ` +
                    `${pad(d.getHours())}:${pad(d.getMinutes())}`;
                formEvo.horario.value = s;
                formEvo.horario._flatpickr.setDate(s, false);
            } else {
                formEvo.horario.value = '';
            }
            evoModal.show();
        }

        // submit genérico para criar ou editar
        formEvo.addEventListener('submit', async e => {
            e.preventDefault();
            const fd = new FormData(formEvo);
            for (let [k,v] of fd.entries()) {
    console.log(k, '→', v);
  }
            const isEdit = !!inpId.value;
            const url = isEdit ?
                '../api/evolucoes/atualizar.php' :
                '../api/evolucoes/adicionar.php';

            // pega o texto bruto (pode vir HTML de erro!)
            const txt = await fetch(url, {
                method: 'POST',
                body: fd
            }).then(r => r.text());
            let res;
            try {
                res = JSON.parse(txt);
            } catch {
                console.error('Resposta inválida:', txt);
                return showToast('Erro no servidor (JSON inválido)', 'error');
            }

            if (res.success) {
                showToast(isEdit ? 'Evolução atualizada!' : 'Evolução adicionada!', 'success');
                evoModal.hide();
                viewPaciente(inpCpf.value);
            } else {
                showToast(res.message || 'Erro ao salvar evolução', 'error');
            }
        });

        function abrirModalEditEvolucao(id, titulo, subtitulo, dataHor) {
            // 1) Popula os hidden
            inpCpf.value = window.currentCpf;
            inpId.value  = id;

            // 2) Atualiza título e botão
            lblTitle.textContent = 'Editar Evolução';
            btnSubmit.textContent = 'Atualizar';

            // 3) Preenche título e subtítulo
            formEvo.titulo.value = titulo || '';
            formEvo.subtitulo.value = subtitulo || '';

            // 4) Converte ISO → 'dd/mm/YYYY HH:mm'
            const d = new Date(dataHor);
            const pad = n => String(n).padStart(2, '0');
            const s = `${pad(d.getDate())}/${pad(d.getMonth()+1)}/${d.getFullYear()} ` +
                `${pad(d.getHours())}:${pad(d.getMinutes())}`;

            // 5) Preenche o campo de data e sincroniza o flatpickr
            formEvo.horario.value = s;
            formEvo.horario._flatpickr.setDate(s, false);

            // 6) Por fim, exibe o modal
            evoModal.show();
        }
        async function deleteEvolucao(id) {
  if (!confirm('Deseja realmente excluir esta evolução?')) return;
  // prepara os dados
  const fd = new FormData();
  fd.append('id', id);
  fd.append('cpf', window.currentCpf);

  // chama o endpoint de exclusão
  const txt = await fetch('../api/evolucoes/excluir.php', {
    method: 'POST',
    body: fd
  }).then(r => r.text());

  let res;
  try {
    res = JSON.parse(txt);
  } catch {
    return showToast('Resposta inválida do servidor', 'error');
  }

  if (res.success) {
    showToast('Evolução excluída!', 'success');
    // Recarrega apenas as evoluções, sem fechar o modal
    // Se estiver dentro de viewPaciente, basta chamar de novo seu AJAX:
    // por comodidade, vamos reabrir a aba de evolução:
    // (reexeuta o AJAX que popula #evolucaoTimeline)
    viewPaciente(window.currentCpf);
  } else {
    showToast(res.message || 'Erro ao excluir evolução', 'error');
  }
}
    </script>
    <script src="../js/pacientes.js"></script>
    <?php include '../includes/mobile-nav.php'; ?>
</body>

</html>