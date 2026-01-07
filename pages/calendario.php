<?php
// Start session to maintain user state
session_start();

// Verificar autenticação
if (!isset($_SESSION['logado']) || $_SESSION['logado'] !== true) {
    header("Location: ../login.php");
    exit;
}

// Conexão com o banco de dados
require_once '../config/config.php';

// Funções utilitárias
require_once '../functions/utils/helpers.php';

// Buscar funcionários para o select de profissionais
$funcionarios = [];
$sqlFuncionarios = "SELECT id, nome, cargo FROM funcionarios WHERE status = 'Ativo' ORDER BY nome";
try {
    $resultFuncionarios = $conn->query($sqlFuncionarios);
    if ($resultFuncionarios) {
        while ($row = $resultFuncionarios->fetch_assoc()) {
            $funcionarios[] = $row;
        }
    }
} catch (Exception $e) {
    // Erro ao buscar funcionários
}

// Buscar pacientes para o select
$pacientes = [];
$sqlPacientes = "SELECT cpf, nome FROM pacientes ORDER BY nome";
try {
    $resultPacientes = $conn->query($sqlPacientes);
    if ($resultPacientes) {
        while ($row = $resultPacientes->fetch_assoc()) {
            $pacientes[] = $row;
        }
    }
} catch (Exception $e) {
    // Erro ao buscar pacientes
}

// Buscar serviços para o select
$servicos = [];
$sqlServicos = "SELECT id, nome_servico, preco, duracao_minutos FROM servicos ORDER BY nome_servico";
try {
    $resultServicos = $conn->query($sqlServicos);
    if ($resultServicos) {
        while ($row = $resultServicos->fetch_assoc()) {
            $servicos[] = $row;
        }
    }
} catch (Exception $e) {
    // Erro ao buscar serviços
}

// Função para formatar moeda
function formatarMoeda($valor)
{
    return 'R$ ' . number_format($valor, 2, ',', '.');
}
?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Calendário de Consultas - Sistema de Gerenciamento de Clínica</title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <!-- FullCalendar CSS -->
    <link href="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.css" rel="stylesheet">

    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <!-- Estilos personalizados -->
    <link href="../css/styles.css" rel="stylesheet">

    <!-- Estilos responsivos -->
    <link href="../css/responsive.css" rel="stylesheet">

    <!-- Meta tags para dispositivos móveis -->
    <meta name="theme-color" content="#4e73df">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">

    <!-- Ícones para dispositivos móveis -->
    <link rel="apple-touch-icon" href="../img/app-icon.png">
    <link rel="icon" type="image/png" href="../img/favicon.png">

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
            --card-border-radius: 0.75rem;
            --transition-speed: 0.3s;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f8f9fc;
            color: #333;
            overflow-x: hidden;
        }
        
              .form-select {
  font-size: 12px;
  padding: 4px 8px;
 
}

.form-select option {
  font-size: 12px;
}


        .card {
            border-radius: var(--card-border-radius);
            box-shadow: 0 0.25rem 1rem rgba(0, 0, 0, 0.08);
            transition: transform var(--transition-speed), box-shadow var(--transition-speed);
            overflow: hidden;
            border: none;
            height: 100%;
        }

        .card-header {
            background-color: #fff;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
            padding: 1.25rem 1.5rem;
        }

        .card-body {
            padding: 1.5rem;
        }

        /* Estilos do Calendário */
        .calendar-container {
            height: calc(100vh - 250px);
            /* Ajuste conforme necessário */
            overflow-y: auto;
            /* Permitir scroll vertical */
        }

        .fc-header-toolbar {
            margin-bottom: 1.5rem !important;
        }

        .fc-toolbar-title {
            font-size: 1.5rem !important;
            font-weight: 600 !important;
        }

        .fc-button-primary {
            background-color: var(--primary-color) !important;
            border-color: var(--primary-color) !important;
        }

        .fc-button-primary:hover {
            background-color: var(--primary-dark) !important;
            border-color: var(--primary-dark) !important;
        }

        .fc-button-active {
            background-color: var(--primary-dark) !important;
            border-color: var(--primary-dark) !important;
        }

        .fc-event {
            cursor: pointer;
            border-radius: 4px;
            padding: 2px 4px;
            font-size: 0.85rem;
        }

        .fc-event-title {
            font-weight: 500;
        }

        .fc-event-time {
            font-weight: 400;
        }

        .fc-daygrid-day-number {
            font-weight: 500;
        }

        .fc-day-today {
            background-color: rgba(28, 200, 138, 0.1) !important;
        }

        /* Status das consultas */
        .status-agendada {
            background-color: var(--primary-color) !important;
            border-color: var(--primary-color) !important;
        }

        .status-concluida {
            background-color: var(--success-color) !important;
            border-color: var(--success-color) !important;
        }

        .status-cancelada {
            background-color: var(--danger-color) !important;
            border-color: var(--danger-color) !important;
        }

        /* Estilos para o modal */
        .modal-header {
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
            padding: 1.25rem 1.5rem;
        }

        .modal-footer {
            border-top: 1px solid rgba(0, 0, 0, 0.05);
            padding: 1rem 1.5rem;
        }

        .form-label {
            font-weight: 500;
            margin-bottom: 0.5rem;
        }

        /* Responsividade */
        @media (max-width: 767.98px) {
            .calendar-container {
                height: calc(100vh - 200px);
                min-height: 400px;
            }

            .fc-toolbar-title {
                font-size: 1.25rem !important;
            }

            .fc-toolbar {
                flex-direction: column;
                gap: 0.5rem;
            }

            .fc-toolbar-chunk {
                display: flex;
                justify-content: center;
                margin-bottom: 0.5rem;
            }

            .fc-button {
                padding: 0.25rem 0.5rem !important;
                font-size: 0.875rem !important;
            }

            /* Esconder botões desnecessários em mobile */
            .fc-dayGridMonth-button,
            .fc-timeGridWeek-button,
            .fc-timeGridDay-button {
                display: none !important;
            }

            /* Mostrar apenas o botão de lista em mobile */
            .fc-listMonth-button {
                display: inline-block !important;
            }

            /* Ajustes para o modo lista em mobile */
            .fc-list-table td {
                padding: 8px !important;
            }

            .fc-list-day-cushion {
                padding: 8px !important;
            }

            /* Ajustes para o filtro em mobile */
            .mobile-filter-toggle {
                display: block;
                margin-bottom: 1rem;
            }

            .filter-card {
                display: none;
            }

            .filter-card.show {
                display: block;
            }

            /* Ajustes para o modal em mobile */
            .modal-dialog {
                margin: 0.5rem;
            }

            .modal-content {
                border-radius: var(--card-border-radius);
               
            }

            .row>[class*="col-"] {
                margin-bottom: 1rem;
            }
        }

        /* Animações */
        @keyframes fadeIn {
            from {
                opacity: 0;
            }

            to {
                opacity: 1;
            }
        }

        .fade-in {
            animation: fadeIn 0.8s ease-in-out;
        }

        /* Estilos para o filtro */
        .filter-card {
            margin-bottom: 1.5rem;
        }

        .filter-card .card-body {
            padding: 1rem;
        }

        .filter-title {
            font-size: 1rem;
            font-weight: 600;
            margin-bottom: 1rem;
        }

        .filter-group {
            margin-bottom: 0.75rem;
        }

        /* Estilos para o loading */
        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(255, 255, 255, 0.8);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 9999;
        }

        .spinner-border {
            width: 3rem;
            height: 3rem;
        }

        /* Estilos para a visualização em lista */
        .list-view-container {
            display: none;
        }

        @media (max-width: 767.98px) {
            .list-view-container {
                display: block;
            }

            .calendar-view-container {
                display: none;
            }

            .view-toggle-btn {
                margin-bottom: 1rem;
            }

            .list-item {
                border-left: 4px solid var(--primary-color);
                margin-bottom: 0.75rem;
                transition: transform 0.2s;
            }

            .list-item:active {
                transform: scale(0.98);
            }

            .list-item.status-concluida {
                border-left-color: var(--success-color);
            }

            .list-item.status-cancelada {
                border-left-color: var(--danger-color);
            }

            .list-item-header {
                display: flex;
                justify-content: space-between;
                align-items: center;
                margin-bottom: 0.5rem;
            }

            .list-item-time {
                font-weight: 500;
            }

            .list-item-status {
                font-size: 0.75rem;
                padding: 0.25rem 0.5rem;
                border-radius: 50px;
            }

            .mobile-filter-bar {
                background-color: #fff;
                padding: 1rem;
                border-radius: var(--card-border-radius);
                margin-bottom: 1rem;
                box-shadow: 0 0.25rem 1rem rgba(0, 0, 0, 0.08);
            }

            .mobile-filter-bar select {
                width: 100%;
                margin-bottom: 0.5rem;
            }

            .mobile-filter-bar .btn {
                width: 100%;
            }

            .mobile-day-navigation {
                display: flex;
                justify-content: space-between;
                align-items: center;
                margin-bottom: 1rem;
                background-color: #fff;
                padding: 0.75rem 1rem;
                border-radius: var(--card-border-radius);
                box-shadow: 0 0.25rem 0.5rem rgba(0, 0, 0, 0.05);
            }

            .mobile-day-navigation .current-day {
                font-weight: 600;
                font-size: 1.1rem;
            }

            .mobile-day-navigation .nav-btn {
                background: none;
                border: none;
                color: var(--primary-color);
                font-size: 1.25rem;
                padding: 0.25rem 0.5rem;
            }

            .empty-list-message {
                text-align: center;
                padding: 2rem 1rem;
                background-color: #fff;
                border-radius: var(--card-border-radius);
                box-shadow: 0 0.25rem 0.5rem rgba(0, 0, 0, 0.05);
            }

            .empty-list-message i {
                font-size: 2.5rem;
                color: var(--secondary-color);
                margin-bottom: 1rem;
                display: block;
            }
        }
    </style>
</head>

<body>
    <div class="d-flex">
        <div class="d-none d-lg-block">
            <!-- Incluir Sidebar -->
            <?php include '../includes/sidebar.php'; ?>
        </div>

        <!-- Conteúdo Principal -->
        <div class="content-wrapper">
            <!-- Incluir Topbar -->
            <?php include '../includes/topbar.php'; ?>

            <!-- Conteúdo do Calendário -->
            <div class="container-fluid px-4 py-4 mt-3">
                <!-- Header -->
                <div class="d-flex justify-content-between align-items-center mb-4 fade-in mt-5">
                    <div>
                        <h2 class="h3 mb-0 text-gray-800 mt-5">Calendário de Consultas</h2>
                        <p class="mb-0 text-muted">Gerencie todas as consultas da clínica</p>
                    </div>
                    <button type="button" class="btn btn-primary" id="novaConsultaBtn">
                        <i class="bi bi-plus-circle me-1"></i> Nova Consulta
                    </button>
                </div>

                <!-- Botão de alternar visualização (apenas mobile) -->
                <div class="d-md-none mb-3">
                    <button type="button" class="btn btn-outline-primary w-100 view-toggle-btn" id="toggleViewBtn">
                        <i class="bi bi-list me-1"></i> Alternar para Visualização em Lista
                    </button>
                </div>

                <!-- Filtros móveis (apenas mobile) -->
                <div class="d-md-none mb-3">
                    <button class="btn btn-outline-secondary w-100 mobile-filter-toggle" type="button" data-bs-toggle="collapse" data-bs-target="#mobileFilters" aria-expanded="false" aria-controls="mobileFilters">
                        <i class="bi bi-funnel me-1"></i> Filtros
                    </button>

                    <div class="collapse mt-2" id="mobileFilters">
                        <div class="card card-body">
                            <div class="filter-group">
                                <label for="filtroStatusMobile" class="form-label">Status</label>
                                <select class="form-select form-select-sm" id="filtroStatusMobile">
                                    <option value="todos" selected>Todos</option>
                                    <option value="Agendada">Agendadas</option>
                                    <option value="Concluída">Concluídas</option>
                                    <option value="Cancelada">Canceladas</option>
                                </select>
                            </div>
                            <div class="filter-group">
                                <label for="filtroProfissionalMobile" class="form-label">Profissional</label>
                                <select class="form-select form-select-sm" id="filtroProfissionalMobile">
                                    <option value="todos" selected>Todos</option>
                                    <?php foreach ($funcionarios as $funcionario): ?>
                                        <option value="<?= $funcionario['id'] ?>"><?= htmlspecialchars($funcionario['nome']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="filter-group">
                                <label for="filtroPacienteMobile" class="form-label">Paciente</label>
                                <select class="form-select form-select-sm" id="filtroPacienteMobile">
                                    <option value="todos" selected>Todos</option>
                                    <?php foreach ($pacientes as $paciente): ?>
                                        <option value="<?= $paciente['cpf'] ?>"><?= htmlspecialchars($paciente['nome']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <button type="button" class="btn btn-primary btn-sm w-100 mt-3" id="aplicarFiltrosMobile">
                                <i class="bi bi-funnel me-1"></i> Aplicar Filtros
                            </button>
                            <button type="button" class="btn btn-outline-secondary btn-sm w-100 mt-2" id="limparFiltrosMobile">
                                <i class="bi bi-x-circle me-1"></i> Limpar Filtros
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Navegação de dias para visualização em lista (apenas mobile) -->
                <div class="d-md-none mobile-day-navigation" id="mobileDayNavigation">
                    <button class="nav-btn" id="prevDayBtn">
                        <i class="bi bi-chevron-left"></i>
                    </button>
                    <div class="current-day" id="currentDayDisplay">Hoje</div>
                    <button class="nav-btn" id="nextDayBtn">
                        <i class="bi bi-chevron-right"></i>
                    </button>
                </div>

                <!-- Filtros e Calendário (visualização desktop) -->
                <div class="row calendar-view-container">
                    <div class="col-lg-3 mb-4">
                        <!-- Filtros -->
                        <div class="card filter-card fade-in">
                            <div class="card-body">
                                <h5 class="filter-title">Filtros</h5>
                                <div class="filter-group">
                                    <label for="filtroStatus" class="form-label">Status</label>
                                    <select class="form-select form-select-sm" id="filtroStatus">
                                        <option value="todos" selected>Todos</option>
                                        <option value="Agendada">Agendadas</option>
                                        <option value="Concluída">Concluídas</option>
                                        <option value="Cancelada">Canceladas</option>
                                    </select>
                                </div>
                                <div class="filter-group">
                                    <label for="filtroProfissional" class="form-label">Profissional</label>
                                    <select class="form-select form-select-sm" id="filtroProfissional">
                                        <option value="todos" selected>Todos</option>
                                        <?php foreach ($funcionarios as $funcionario): ?>
                                            <option value="<?= $funcionario['id'] ?>"><?= htmlspecialchars($funcionario['nome']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="filter-group">
                                    <label for="filtroPaciente" class="form-label">Paciente</label>
                                    <select class="form-select form-select-sm" id="filtroPaciente">
                                        <option value="todos" selected>Todos</option>
                                        <?php foreach ($pacientes as $paciente): ?>
                                            <option value="<?= $paciente['cpf'] ?>"><?= htmlspecialchars($paciente['nome']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="filter-group">
                                    <label for="filtroServico" class="form-label">Serviço</label>
                                    <select class="form-select form-select-sm" id="filtroServico">
                                        <option value="todos" selected>Todos</option>
                                        <?php foreach ($servicos as $servico): ?>
                                            <option value="<?= $servico['id'] ?>"><?= htmlspecialchars($servico['nome_servico']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="filter-group">
                                    <label for="filtroPeriodo" class="form-label">Período</label>
                                    <select class="form-select form-select-sm" id="filtroPeriodo">
                                        <option value="todos" selected>Todos</option>
                                        <option value="hoje">Hoje</option>
                                        <option value="semana">Esta Semana</option>
                                        <option value="mes">Este Mês</option>
                                    </select>
                                </div>
                                <button type="button" class="btn btn-primary btn-sm w-100 mt-3" id="aplicarFiltros">
                                    <i class="bi bi-funnel me-1"></i> Aplicar Filtros
                                </button>
                                <button type="button" class="btn btn-outline-secondary btn-sm w-100 mt-2" id="limparFiltros">
                                    <i class="bi bi-x-circle me-1"></i> Limpar Filtros
                                </button>
                            </div>
                        </div>


                    </div>
                    <div class="col-lg-9">
                        <!-- Calendário -->
                        <div class="card fade-in">
                            <div class="card-body">
                                <div id="calendar" class="calendar-container"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Visualização em lista (apenas mobile) -->
                <div class="list-view-container">
                    <div id="consultasList" class="fade-in">
                        <!-- Lista de consultas será carregada aqui via JavaScript -->
                        <div class="text-center py-4">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Carregando...</span>
                            </div>
                            <p class="mt-2">Carregando consultas...</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de Nova Consulta -->
    <div class="modal fade" id="consultaModal" tabindex="-1" aria-labelledby="consultaModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-lg modal-dialog-scrollable">

            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="consultaModalLabel">Nova Consulta</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="consultaForm">
                        <input type="hidden" id="consultaId" name="id">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="paciente" class="form-label">Paciente</label>
                                <select class="form-select" id="paciente" name="paciente_cpf" required>
                                    <option value="">Selecione um paciente</option>
                                    <?php foreach ($pacientes as $paciente): ?>
                                        <option value="<?= $paciente['cpf'] ?>"><?= htmlspecialchars($paciente['nome']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="profissional" class="form-label">Profissional</label>
                                <select class="form-select" id="profissional" name="profissional_id" required>
                                    <option value="">Selecione um profissional</option>
                                    <?php foreach ($funcionarios as $funcionario): ?>
                                        <option value="<?= $funcionario['id'] ?>"><?= htmlspecialchars($funcionario['nome']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="data" class="form-label">Data</label>
                                <input type="date" class="form-control" id="data" name="data" required>
                            </div>
                            <div class="col-md-3">
                                <label for="hora_inicio" class="form-label">Hora Início</label>
                                <input type="time" class="form-control" id="hora_inicio" name="hora_inicio" required>
                            </div>
                            <div class="col-md-3">
                                <label for="hora_fim" class="form-label">Hora Fim</label>
                                <input type="time" class="form-control" id="hora_fim" name="hora_fim" required>
                            </div>
                        </div>

                        <!-- Div para mostrar horários ocupados -->
                        <div id="horariosOcupados" class="mb-3"></div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="servico" class="form-label">Serviço</label>
                                <select class="form-select" id="servico" name="servico_id" required>
                                    <option value="">Selecione um serviço</option>
                                    <?php foreach ($servicos as $servico): ?>
                                        <option value="<?= $servico['id'] ?>"
                                            data-preco="<?= $servico['preco'] ?>"
                                            data-duracao="<?= $servico['duracao_minutos'] ?>">
                                            <?= htmlspecialchars($servico['nome_servico']) ?> - <?= formatarMoeda($servico['preco']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="status" class="form-label">Status</label>
                                <select class="form-select" id="status" name="status">
                                    <option value="Agendada">Agendada</option>
                                    <option value="Concluída">Concluída</option>
                                    <option value="Cancelada">Cancelada</option>
                                </select>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="procedimento" class="form-label">Procedimento</label>
                            <input type="text" class="form-control" id="procedimento" name="procedimento" placeholder="Descrição do procedimento">
                        </div>
                        <div class="mb-3">
                            <label for="observacoes" class="form-label">Observações</label>
                            <textarea class="form-control" id="observacoes" name="observacoes" rows="3" placeholder="Observações adicionais"></textarea>
                        </div>

                        <!-- Informações financeiras -->
                        <div class="card mb-3">
                            <div class="card-header bg-light">
                                <h6 class="mb-0">Informações Financeiras</h6>
                            </div>
                            <div class="card-body">
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label for="valor" class="form-label">Valor</label>
                                        <div class="input-group">
                                            <span class="input-group-text">R$</span>
                                            <input type="number" class="form-control" id="valor" name="valor" step="0.01" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="status_pagamento" class="form-label">Status do Pagamento</label>
                                        <select class="form-select" id="status_pagamento" name="status_pagamento">
                                            <option value="PENDENTE">Pendente</option>
                                            <option value="PAGO">Pago</option>
                                        </select>
                                    </div>
                                </div>
                                <div id="data_pagamento_container" class="row mb-3 d-none">
                                    <div class="col-md-6">
                                        <label for="data_pagamento" class="form-label">Data do Pagamento</label>
                                        <input type="datetime-local" class="form-control" id="data_pagamento" name="data_pagamento">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-danger me-2 d-none" id="excluirConsultaBtn">Excluir</button>
                    <button type="button" class="btn btn-primary" id="salvarConsultaBtn">Salvar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de Confirmação de Exclusão -->
    <div class="modal fade" id="confirmacaoExclusaoModal" tabindex="-1" aria-labelledby="confirmacaoExclusaoModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="confirmacaoExclusaoModalLabel">Confirmar Exclusão</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Tem certeza que deseja excluir esta consulta? Esta ação não pode ser desfeita.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-danger" id="confirmarExclusaoBtn">Excluir</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de Verificação de Disponibilidade -->
    <div class="modal fade" id="verificacaoDisponibilidadeModal" tabindex="-1" aria-labelledby="verificacaoDisponibilidadeModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="verificacaoDisponibilidadeModalLabel">Verificação de Disponibilidade</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-warning" role="alert">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i>
                        <span id="mensagemDisponibilidade">O profissional selecionado já possui uma consulta agendada neste horário.</span>
                    </div>
                    <p>Deseja continuar mesmo assim?</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-primary" id="continuarAgendamentoBtn">Continuar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de Horários Ocupados -->
    <div class="modal fade" id="horariosOcupadosModal" tabindex="-1" aria-labelledby="horariosOcupadosModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="horariosOcupadosModalLabel">Horários Ocupados do Profissional</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="horariosOcupadosContainer">
                        <div class="text-center py-3">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Carregando...</span>
                            </div>
                            <p class="mt-2">Carregando horários ocupados...</p>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Loading Overlay -->
    <div class="loading-overlay d-none" id="loadingOverlay">
        <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Carregando...</span>
        </div>
    </div>

    <!-- Incluir navegação móvel -->
    <?php include '../includes/mobile-nav.php'; ?>

    <!-- Bootstrap JS e dependências -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <!-- FullCalendar JS -->
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/locales-all.min.js"></script>

    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <!-- Script Principal -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Inicializar variáveis
            let calendar;
            let consultaAtual = null;
            let consultaIdParaExcluir = null;
            let verificandoDisponibilidade = false;
            let currentView = 'calendar'; // 'calendar' ou 'list'
            let currentDate = new Date();

            // Elementos do DOM
            const calendarEl = document.getElementById('calendar');
            const consultaModal = new bootstrap.Modal(document.getElementById('consultaModal'));
            const confirmacaoExclusaoModal = new bootstrap.Modal(document.getElementById('confirmacaoExclusaoModal'));
            const verificacaoDisponibilidadeModal = new bootstrap.Modal(document.getElementById('verificacaoDisponibilidadeModal'));
            const horariosOcupadosModal = new bootstrap.Modal(document.getElementById('horariosOcupadosModal'));
            const loadingOverlay = document.getElementById('loadingOverlay');
            const consultasList = document.getElementById('consultasList');
            const toggleViewBtn = document.getElementById('toggleViewBtn');
            const calendarViewContainer = document.querySelector('.calendar-view-container');
            const listViewContainer = document.querySelector('.list-view-container');

            // Botões e formulários
            const novaConsultaBtn = document.getElementById('novaConsultaBtn');
            const salvarConsultaBtn = document.getElementById('salvarConsultaBtn');
            const excluirConsultaBtn = document.getElementById('excluirConsultaBtn');
            const confirmarExclusaoBtn = document.getElementById('confirmarExclusaoBtn');
            const continuarAgendamentoBtn = document.getElementById('continuarAgendamentoBtn');
            const consultaForm = document.getElementById('consultaForm');
            const aplicarFiltrosBtn = document.getElementById('aplicarFiltros');
            const limparFiltrosBtn = document.getElementById('limparFiltros');
            const aplicarFiltrosMobileBtn = document.getElementById('aplicarFiltrosMobile');
            const limparFiltrosMobileBtn = document.getElementById('limparFiltrosMobile');
            const prevDayBtn = document.getElementById('prevDayBtn');
            const nextDayBtn = document.getElementById('nextDayBtn');
            const currentDayDisplay = document.getElementById('currentDayDisplay');

            // Campos do formulário
            const consultaIdInput = document.getElementById('consultaId');
            const pacienteSelect = document.getElementById('paciente');
            const profissionalSelect = document.getElementById('profissional');
            const dataInput = document.getElementById('data');
            const horaInicioInput = document.getElementById('hora_inicio');
            const horaFimInput = document.getElementById('hora_fim');
            const servicoSelect = document.getElementById('servico');
            const statusSelect = document.getElementById('status');
            const procedimentoInput = document.getElementById('procedimento');
            const observacoesInput = document.getElementById('observacoes');
            const valorInput = document.getElementById('valor');
            const statusPagamentoSelect = document.getElementById('status_pagamento');
            const dataPagamentoContainer = document.getElementById('data_pagamento_container');
            const dataPagamentoInput = document.getElementById('data_pagamento');

            // Campos de filtro
            const filtroStatus = document.getElementById('filtroStatus');
            const filtroProfissional = document.getElementById('filtroProfissional');
            const filtroPaciente = document.getElementById('filtroPaciente');
            const filtroServico = document.getElementById('filtroServico');
            const filtroPeriodo = document.getElementById('filtroPeriodo');

            // Campos de filtro mobile
            const filtroStatusMobile = document.getElementById('filtroStatusMobile');
            const filtroProfissionalMobile = document.getElementById('filtroProfissionalMobile');
            const filtroPacienteMobile = document.getElementById('filtroPacienteMobile');

            // Inicializar o calendário
            initCalendar();

            // Inicializar a visualização em lista para mobile
            updateListView();
            updateCurrentDayDisplay();

            // Event Listeners
            novaConsultaBtn.addEventListener('click', abrirModalNovaConsulta);
            salvarConsultaBtn.addEventListener('click', salvarConsulta);
            excluirConsultaBtn.addEventListener('click', confirmarExclusaoConsulta);
            confirmarExclusaoBtn.addEventListener('click', excluirConsulta);
            aplicarFiltrosBtn.addEventListener('click', aplicarFiltros);
            limparFiltrosBtn.addEventListener('click', limparFiltros);
            aplicarFiltrosMobileBtn.addEventListener('click', aplicarFiltrosMobile);
            limparFiltrosMobileBtn.addEventListener('click', limparFiltrosMobile);
            prevDayBtn.addEventListener('click', goToPreviousDay);
            nextDayBtn.addEventListener('click', goToNextDay);

            // Event listener para alternar entre visualizações
            toggleViewBtn.addEventListener('click', toggleView);

            // Adicionar botão para visualizar horários ocupados
            profissionalSelect.insertAdjacentHTML('afterend',
                '<button type="button" class="btn btn-outline-info btn-sm mt-2" id="verHorariosBtn" disabled>' +
                '<i class="bi bi-clock me-1"></i>Ver horários ocupados</button>');

            const verHorariosBtn = document.getElementById('verHorariosBtn');
            verHorariosBtn.addEventListener('click', mostrarHorariosOcupados);

            // Habilitar/desabilitar botão de ver horários ocupados
            profissionalSelect.addEventListener('change', function() {
                if (profissionalSelect.value) {
                    verHorariosBtn.disabled = false;
                } else {
                    verHorariosBtn.disabled = true;
                }

                // Verificar disponibilidade quando mudar o profissional
                if (dataInput.value && horaInicioInput.value && horaFimInput.value) {
                    verificarDisponibilidadeHorario();
                }
            });

            // Verificar disponibilidade quando mudar a data ou horários
            dataInput.addEventListener('change', function() {
                if (profissionalSelect.value && horaInicioInput.value && horaFimInput.value) {
                    verificarDisponibilidadeHorario();
                }
            });

            horaInicioInput.addEventListener('change', function() {
                if (profissionalSelect.value && dataInput.value) {
                    // Atualizar hora fim baseado no serviço selecionado
                    atualizarHoraFim();

                    // Verificar disponibilidade
                    if (horaFimInput.value) {
                        verificarDisponibilidadeHorario();
                    }
                }
            });

            // Atualizar hora fim quando serviço é selecionado
            servicoSelect.addEventListener('change', function() {
                const selectedOption = servicoSelect.options[servicoSelect.selectedIndex];
                if (selectedOption.value) {
                    const preco = selectedOption.dataset.preco;
                    const duracao = selectedOption.dataset.duracao;

                    valorInput.value = preco;

                    if (horaInicioInput.value && duracao) {
                        atualizarHoraFim();

                        // Verificar disponibilidade após atualizar hora fim
                        if (profissionalSelect.value && dataInput.value) {
                            verificarDisponibilidadeHorario();
                        }
                    }
                }
            });

            // Mostrar/esconder campo de data de pagamento
            statusPagamentoSelect.addEventListener('change', function() {
                if (statusPagamentoSelect.value === 'PAGO') {
                    dataPagamentoContainer.classList.remove('d-none');
                    const agora = new Date();
                    const dataFormatada = agora.toISOString().slice(0, 16);
                    dataPagamentoInput.value = dataFormatada;
                } else {
                    dataPagamentoContainer.classList.add('d-none');
                    dataPagamentoInput.value = '';
                }
            });

            // Funções
            function initCalendar() {
                calendar = new FullCalendar.Calendar(calendarEl, {
                    locale: 'pt-br',
                    initialView: window.innerWidth < 768 ? 'listMonth' : 'dayGridMonth',
                    headerToolbar: {
                        left: 'prev,next today',
                        center: 'title',
                        right: 'dayGridMonth,timeGridWeek,timeGridDay,listMonth'
                    },
                    buttonText: {
                        today: 'Hoje',
                        month: 'Mês',
                        week: 'Semana',
                        day: 'Dia',
                        list: 'Lista'
                    },
                    themeSystem: 'bootstrap5',
                    height: 'auto', // Permitir que o calendário expanda automaticamente
                    scrollTime: '08:00:00', // Define o horário inicial visível
                    slotMinTime: '06:00:00', // Horário mínimo exibido
                    slotMaxTime: '22:00:00', // Horário máximo exibido
                    selectable: true,
                    selectMirror: true,
                    navLinks: true,
                    editable: true,
                    dayMaxEvents: true,
                    eventTimeFormat: {
                        hour: '2-digit',
                        minute: '2-digit',
                        hour12: false
                    },
                    select: function(info) {
                        abrirModalNovaConsulta(null, info.startStr);
                    },
                    eventClick: function(info) {
                        carregarConsulta(info.event.id);
                    },
                    eventDrop: function(info) {
                        verificarDisponibilidadeAntesDeAtualizar(info.event, function(disponivel) {
                            if (disponivel) {
                                atualizarDataConsulta(info.event);
                            } else {
                                info.revert();
                                mostrarAlerta('Não é possível mover a consulta para este horário. O profissional já possui outra consulta agendada.', 'warning');
                            }
                        });
                    },
                    eventResize: function(info) {
                        verificarDisponibilidadeAntesDeAtualizar(info.event, function(disponivel) {
                            if (disponivel) {
                                atualizarDataConsulta(info.event);
                            } else {
                                info.revert();
                                mostrarAlerta('Não é possível redimensionar a consulta para este horário. O profissional já possui outra consulta agendada.', 'warning');
                            }
                        });
                    },
                    events: function(info, successCallback, failureCallback) {
                        carregarConsultas(info.start, info.end, successCallback);
                    },
                    eventClassNames: function(arg) {
                        if (arg.event.extendedProps.status === 'Concluída') {
                            return ['status-concluida'];
                        } else if (arg.event.extendedProps.status === 'Cancelada') {
                            return ['status-cancelada'];
                        } else {
                            return ['status-agendada'];
                        }
                    }
                });

                calendar.render();
            }

            function carregarConsultas(start, end, successCallback) {
                mostrarLoading();

                const dataInicio = start.toISOString().split('T')[0];
                const dataFim = end.toISOString().split('T')[0];

                // Construir URL com filtros
                let url = `../api/consultas/listar.php?action=getAll&data_inicio=${dataInicio}&data_fim=${dataFim}`;

                // Adicionar filtros se estiverem definidos
                if (filtroStatus.value !== 'todos') {
                    url += `&status=${filtroStatus.value}`;
                }

                if (filtroProfissional.value !== 'todos') {
                    url += `&profissional_id=${filtroProfissional.value}`;
                }

                if (filtroPaciente.value !== 'todos') {
                    url += `&paciente_cpf=${filtroPaciente.value}`;
                }

                if (filtroServico.value !== 'todos') {
                    url += `&servico_id=${filtroServico.value}`;
                }

                fetch(url)
                    .then(response => response.json())
                    .then(data => {
                        const events = data.map(consulta => {
                            const dataInicio = new Date(consulta.data_consulta);

                            // Calcular data fim (1 hora por padrão se não houver duração)
                            let dataFim;
                            if (consulta.duracao_minutos) {
                                dataFim = new Date(dataInicio.getTime() + consulta.duracao_minutos * 60000);
                            } else {
                                dataFim = new Date(dataInicio.getTime() + 60 * 60000);
                            }

                            return {
                                id: consulta.id,
                                title: `${consulta.paciente_nome} - ${consulta.procedimento || 'Consulta'}`,
                                start: consulta.data_consulta,
                                end: dataFim.toISOString(),
                                extendedProps: {
                                    paciente_cpf: consulta.paciente_cpf,
                                    paciente_nome: consulta.paciente_nome,
                                    profissional_id: consulta.profissional_id,
                                    profissional_nome: consulta.profissional_nome,
                                    procedimento: consulta.procedimento,
                                    observacoes: consulta.observacoes,
                                    status: consulta.status,
                                    servico_id: consulta.servico_id
                                }
                            };
                        });

                        successCallback(events);
                        esconderLoading();

                        // Atualizar visualização em lista se estiver no modo lista
                        if (currentView === 'list') {
                            updateListView();
                        }
                    })
                    .catch(error => {
                        console.error('Erro ao carregar consultas:', error);
                        esconderLoading();
                        failureCallback(error);
                    });
            }

            function updateListView() {
                // Formatar data atual para buscar consultas
                const dataFormatada = currentDate.toISOString().split('T')[0];

                // Construir URL com filtros
                let url = `../api/consultas/listar.php?action=getAll&data_inicio=${dataFormatada}&data_fim=${dataFormatada}`;

                // Adicionar filtros mobile se estiverem definidos
                if (filtroStatusMobile.value !== 'todos') {
                    url += `&status=${filtroStatusMobile.value}`;
                }

                if (filtroProfissionalMobile.value !== 'todos') {
                    url += `&profissional_id=${filtroProfissionalMobile.value}`;
                }

                if (filtroPacienteMobile.value !== 'todos') {
                    url += `&paciente_cpf=${filtroPacienteMobile.value}`;
                }

                mostrarLoading();

                fetch(url)
                    .then(response => response.json())
                    .then(data => {
                        // Ordenar consultas por hora
                        data.sort((a, b) => new Date(a.data_consulta) - new Date(b.data_consulta));

                        if (data.length === 0) {
                            // Mostrar mensagem de nenhuma consulta
                            consultasList.innerHTML = `
                                <div class="empty-list-message">
                                    <i class="bi bi-calendar-x"></i>
                                    <h5>Nenhuma consulta encontrada</h5>
                                    <p class="text-muted">Não há consultas agendadas para esta data.</p>
                                    <button class="btn btn-primary mt-3" id="novaConsultaListBtn">
                                        <i class="bi bi-plus-circle me-1"></i> Nova Consulta
                                    </button>
                                </div>
                            `;

                            // Adicionar event listener para o botão de nova consulta
                            document.getElementById('novaConsultaListBtn').addEventListener('click', function() {
                                abrirModalNovaConsulta(null, currentDate.toISOString().split('T')[0]);
                            });
                        } else {
                            // Construir lista de consultas
                            let html = '';

                            data.forEach(consulta => {
                                const dataConsulta = new Date(consulta.data_consulta);
                                const horaInicio = `${String(dataConsulta.getHours()).padStart(2, '0')}:${String(dataConsulta.getMinutes()).padStart(2, '0')}`;

                                let dataFim;
                                if (consulta.duracao_minutos) {
                                    const fimConsulta = new Date(dataConsulta.getTime() + consulta.duracao_minutos * 60000);
                                    dataFim = `${String(fimConsulta.getHours()).padStart(2, '0')}:${String(fimConsulta.getMinutes()).padStart(2, '0')}`;
                                } else {
                                    const fimConsulta = new Date(dataConsulta.getTime() + 60 * 60000);
                                    dataFim = `${String(fimConsulta.getHours()).padStart(2, '0')}:${String(fimConsulta.getMinutes()).padStart(2, '0')}`;
                                }

                                let statusClass = '';
                                let statusBadge = '';

                                if (consulta.status === 'Concluída') {
                                    statusClass = 'status-concluida';
                                    statusBadge = '<span class="badge bg-success list-item-status" style="width:70px;">Concluída</span>';
                                } else if (consulta.status === 'Cancelada') {
                                    statusClass = 'status-cancelada';
                                    statusBadge = '<span class="badge bg-danger list-item-status" style="width:70px;>Cancelada</span>';
                                } else {
                                    statusClass = 'status-agendada';
                                    statusBadge = '<span class="badge bg-primary list-item-status" style="width:70px;>Agendada</span>';
                                }

                                html += `
                                    <div class="card mb-3 list-item ${statusClass}" data-id="${consulta.id}">
                                        <div class="card-body p-3">
                                            <div class="list-item-header">
                                                <span class="list-item-time">${horaInicio} - ${dataFim}</span>
                                                ${statusBadge}
                                            </div>
                                            <h5 class="mb-1">${consulta.paciente_nome}</h5>
                                            <p class="mb-1 text-muted small">
                                                <i class="bi bi-person-badge me-1"></i> ${consulta.profissional_nome}
                                            </p>
                                            <p class="mb-0 small">
                                                <strong>Procedimento:</strong> ${consulta.procedimento || 'Consulta'}
                                            </p>
                                        </div>
                                    </div>
                                `;
                            });

                            consultasList.innerHTML = html;

                            // Adicionar event listeners para os itens da lista
                            document.querySelectorAll('.list-item').forEach(item => {
                                item.addEventListener('click', function() {
                                    const consultaId = this.dataset.id;
                                    carregarConsulta(consultaId);
                                });
                            });
                        }

                        esconderLoading();
                    })
                    .catch(error => {
                        console.error('Erro ao carregar consultas para lista:', error);
                        consultasList.innerHTML = `
                            <div class="alert alert-danger">
                                <i class="bi bi-exclamation-triangle me-2"></i>
                                Erro ao carregar consultas. Por favor, tente novamente.
                            </div>
                        `;
                        esconderLoading();
                    });
            }

            function updateCurrentDayDisplay() {
                const options = {
                    weekday: 'long',
                    day: 'numeric',
                    month: 'long'
                };
                currentDayDisplay.textContent = currentDate.toLocaleDateString('pt-BR', options);
            }

            function goToPreviousDay() {
                currentDate.setDate(currentDate.getDate() - 1);
                updateCurrentDayDisplay();
                updateListView();
            }

            function goToNextDay() {
                currentDate.setDate(currentDate.getDate() + 1);
                updateCurrentDayDisplay();
                updateListView();
            }

            function toggleView() {
                if (currentView === 'calendar') {
                    // Mudar para visualização em lista
                    calendarViewContainer.style.display = 'none';
                    listViewContainer.style.display = 'block';
                    toggleViewBtn.innerHTML = '<i class="bi bi-calendar me-1"></i> Alternar para Calendário';
                    currentView = 'list';
                    updateListView();
                } else {
                    // Mudar para visualização de calendário
                    calendarViewContainer.style.display = 'block';
                    listViewContainer.style.display = 'none';
                    toggleViewBtn.innerHTML = '<i class="bi bi-list me-1"></i> Alternar para Visualização em Lista';
                    currentView = 'calendar';
                    calendar.render();
                }
            }

            function abrirModalNovaConsulta(event, dataStr = null) {
                // Resetar formulário
                consultaForm.reset();
                consultaIdInput.value = '';
                consultaAtual = null;

                // Esconder botão de excluir para novas consultas
                excluirConsultaBtn.classList.add('d-none');

                // Definir título do modal
                document.getElementById('consultaModalLabel').textContent = 'Nova Consulta';

                // Se tiver data selecionada, preencher o campo de data
                if (dataStr) {
                    const data = new Date(dataStr);
                    dataInput.value = data.toISOString().split('T')[0];

                    // Definir hora de início padrão (8:00)
                    horaInicioInput.value = '';

                    // Definir hora de fim padrão (9:00)
                    horaFimInput.value = '';
                } else {
                    // Definir data atual
                    const hoje = new Date();
                    dataInput.value = hoje.toISOString().split('T')[0];

                    // Definir hora de início padrão (8:00)
                    horaInicioInput.value = '08:00';

                    // Definir hora de fim padrão (9:00)
                    horaFimInput.value = '09:00';
                }

                // Definir status padrão
                statusSelect.value = 'Agendada';

                // Definir status de pagamento padrão
                statusPagamentoSelect.value = 'PENDENTE';
                dataPagamentoContainer.classList.add('d-none');

                // Desabilitar botão de ver horários ocupados
                document.getElementById('verHorariosBtn').disabled = true;

                // Limpar horários ocupados
                document.getElementById('horariosOcupados').innerHTML = '';

                // Abrir modal
                consultaModal.show();
            }

            function carregarConsulta(id) {
                mostrarLoading();

                fetch(`../api/consultas/detalhes.php?action=getById&id=${id}`)
                    .then(response => response.json())
                    .then(data => {
                        if (!data.id) {
                            mostrarAlerta('Erro ao carregar consulta. Por favor, tente novamente.', 'danger');
                            esconderLoading();
                            return;
                        }

                        // Armazenar consulta atual
                        consultaAtual = data;

                        // Preencher formulário
                        consultaIdInput.value = data.id;
                        pacienteSelect.value = data.paciente_cpf;
                        profissionalSelect.value = data.profissional_id;

                        // Habilitar botão de ver horários ocupados
                        document.getElementById('verHorariosBtn').disabled = false;

                        // Formatar data e hora
                        const dataConsulta = new Date(data.data_consulta);
                        dataInput.value = dataConsulta.toISOString().split('T')[0];

                        // Formatar hora de início
                        const horaInicio = `${String(dataConsulta.getHours()).padStart(2, '0')}:${String(dataConsulta.getMinutes()).padStart(2, '0')}`;
                        horaInicioInput.value = horaInicio;

                        // Calcular hora de fim (1 hora por padrão se não houver duração)
                        let horaFim;
                        if (data.duracao_minutos) {
                            const dataFim = new Date(dataConsulta.getTime() + data.duracao_minutos * 60000);
                            horaFim = `${String(dataFim.getHours()).padStart(2, '0')}:${String(dataFim.getMinutes()).padStart(2, '0')}`;
                        } else {
                            const dataFim = new Date(dataConsulta.getTime() + 60 * 60000);
                            horaFim = `${String(dataFim.getHours()).padStart(2, '0')}:${String(dataFim.getMinutes()).padStart(2, '0')}`;
                        }
                        horaFimInput.value = horaFim;

                        // Preencher outros campos
                        servicoSelect.value = data.servico_id || '';
                        statusSelect.value = data.status || 'Agendada';
                        procedimentoInput.value = data.procedimento || '';
                        observacoesInput.value = data.observacoes || '';

                        // Carregar dados financeiros
                        carregarDadosFinanceiros(data.id);

                        // Mostrar botão de excluir
                        excluirConsultaBtn.classList.remove('d-none');

                        // Definir título do modal
                        document.getElementById('consultaModalLabel').textContent = 'Editar Consulta';

                        // Verificar disponibilidade para mostrar horários ocupados
                        verificarDisponibilidadeHorario();

                        // Habilitar botão de salvar
                        salvarConsultaBtn.disabled = false;

                        // Abrir modal
                        consultaModal.show();
                        esconderLoading();
                    })
                    .catch(error => {
                        console.error('Erro ao carregar consulta:', error);
                        mostrarAlerta('Erro ao carregar consulta. Por favor, tente novamente.', 'danger');
                        esconderLoading();
                    });
            }

            function carregarDadosFinanceiros(consultaId) {
                fetch(`../api/financeiro/detalhes.php?action=getByConsultaId&consulta_id=${consultaId}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.id) {
                            valorInput.value = data.valor;
                            statusPagamentoSelect.value = data.status_pagamento;

                            if (data.status_pagamento === 'PAGO' && data.data_pagamento) {
                                dataPagamentoContainer.classList.remove('d-none');
                                const dataPagamento = new Date(data.data_pagamento);
                                dataPagamentoInput.value = dataPagamento.toISOString().slice(0, 16);
                            } else {
                                dataPagamentoContainer.classList.add('d-none');
                                dataPagamentoInput.value = '';
                            }
                        } else {
                            // Se não encontrar registro financeiro, usar valor do serviço
                            const servicoOption = servicoSelect.options[servicoSelect.selectedIndex];
                            if (servicoOption && servicoOption.dataset.preco) {
                                valorInput.value = servicoOption.dataset.preco;
                            } else {
                                valorInput.value = '';
                            }

                            statusPagamentoSelect.value = 'PENDENTE';
                            dataPagamentoContainer.classList.add('d-none');
                            dataPagamentoInput.value = '';
                        }
                    })
                    .catch(error => {
                        console.error('Erro ao carregar dados financeiros:', error);

                        // Em caso de erro, usar valor do serviço
                        const servicoOption = servicoSelect.options[servicoSelect.selectedIndex];
                        if (servicoOption && servicoOption.dataset.preco) {
                            valorInput.value = servicoOption.dataset.preco;
                        } else {
                            valorInput.value = '';
                        }

                        statusPagamentoSelect.value = 'PENDENTE';
                        dataPagamentoContainer.classList.add('d-none');
                        dataPagamentoInput.value = '';
                    });
            }

            function verificarDisponibilidadeHorario() {
                const profissionalId = profissionalSelect.value;
                const data = dataInput.value;
                const horaInicio = horaInicioInput.value;
                const horaFim = horaFimInput.value;
                const consultaId = consultaIdInput.value;

                if (!profissionalId || !data || !horaInicio || !horaFim) {
                    return;
                }

                const dataHoraInicio = `${data}T${horaInicio}:00`;
                const dataHoraFim = `${data}T${horaFim}:00`;

                mostrarLoading();

                fetch(`../api/consultas/verificar_disponibilidade.php?profissional_id=${profissionalId}&data_inicio=${dataHoraInicio}&data_fim=${dataHoraFim}&consulta_id=${consultaId}`)
                    .then(response => response.json())
                    .then(data => {
                        esconderLoading();

                        // Mostrar horários ocupados abaixo do campo de horário
                        const horariosOcupadosDiv = document.getElementById('horariosOcupados');

                        if (data.horarios_ocupados && data.horarios_ocupados.length > 0) {
                            let html = '<div class="mt-2 small text-muted"><strong>Horários ocupados:</strong><ul class="mb-0 ps-3">';
                            data.horarios_ocupados.forEach(horario => {
                                html += `<li>${horario.inicio} - ${horario.fim}: ${horario.paciente}</li>`;
                            });
                            html += '</ul></div>';
                            horariosOcupadosDiv.innerHTML = html;
                        } else {
                            horariosOcupadosDiv.innerHTML = '<div class="mt-2 small text-success">Não há outros horários ocupados para este profissional na data selecionada.</div>';
                        }

                        if (!data.disponivel) {
                            // Mostrar alerta de indisponibilidade
                            Swal.fire({
                                icon: 'warning',
                                title: 'Horário Indisponível',
                                text: 'O profissional já possui uma consulta agendada neste horário. Por favor, escolha outro horário.',
                                confirmButtonText: 'OK'
                            });

                            // Destacar campos com problema
                            horaInicioInput.classList.add('is-invalid');
                            horaFimInput.classList.add('is-invalid');

                            // Desabilitar botão de salvar
                            salvarConsultaBtn.disabled = true;
                        } else {
                            // Remover destaque de erro
                            horaInicioInput.classList.remove('is-invalid');
                            horaFimInput.classList.remove('is-invalid');

                            // Habilitar botão de salvar
                            salvarConsultaBtn.disabled = false;
                        }
                    })
                    .catch(error => {
                        console.error('Erro ao verificar disponibilidade:', error);
                        esconderLoading();

                        // Em caso de erro, permitir o agendamento
                        horaInicioInput.classList.remove('is-invalid');
                        horaFimInput.classList.remove('is-invalid');
                        salvarConsultaBtn.disabled = false;
                    });
            }

            function verificarDisponibilidadeAntesDeAtualizar(event, callback) {
                const profissionalId = event.extendedProps.profissional_id;
                const dataInicio = event.start.toISOString();
                const dataFim = event.end ? event.end.toISOString() : new Date(event.start.getTime() + 60 * 60000).toISOString();
                const consultaId = event.id;

                fetch(`../api/consultas/verificar_disponibilidade.php?profissional_id=${profissionalId}&data_inicio=${dataInicio}&data_fim=${dataFim}&consulta_id=${consultaId}`)
                    .then(response => response.json())
                    .then(data => {
                        callback(data.disponivel);
                    })
                    .catch(error => {
                        console.error('Erro ao verificar disponibilidade:', error);
                        callback(true); // Em caso de erro, permitir a atualização
                    });
            }

            function mostrarHorariosOcupados() {
                const profissionalId = profissionalSelect.value;
                const data = dataInput.value;

                if (!profissionalId || !data) {
                    mostrarAlerta('Selecione um profissional e uma data para ver os horários ocupados.', 'warning');
                    return;
                }

                // Mostrar modal de horários ocupados
                horariosOcupadosModal.show();

                // Buscar horários ocupados
                fetch(`../api/consultas/listar_horarios_ocupados.php?profissional_id=${profissionalId}&data=${data}`)
                    .then(response => response.json())
                    .then(data => {
                        const container = document.getElementById('horariosOcupadosContainer');

                        if (data.length === 0) {
                            container.innerHTML = `
                                <div class="alert alert-info">
                                    <i class="bi bi-info-circle me-2"></i>
                                    Não há horários ocupados para este profissional na data selecionada.
                                </div>
                            `;
                            return;
                        }

                        // Ordenar horários
                        data.sort((a, b) => new Date(a.data_consulta) - new Date(b.data_consulta));

                        // Construir tabela de horários
                        let html = `
                            <div class="table-responsive">
                                <table class="table table-striped table-hover">
                                    <thead>
                                        <tr>
                                            <th>Horário</th>
                                            <th>Paciente</th>
                                            <th>Procedimento</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                        `;

                        data.forEach(consulta => {
                            const dataConsulta = new Date(consulta.data_consulta);
                            const horaInicio = `${String(dataConsulta.getHours()).padStart(2, '0')}:${String(dataConsulta.getMinutes()).padStart(2, '0')}`;

                            let dataFim;
                            if (consulta.duracao_minutos) {
                                const fimConsulta = new Date(dataConsulta.getTime() + consulta.duracao_minutos * 60000);
                                dataFim = `${String(fimConsulta.getHours()).padStart(2, '0')}:${String(fimConsulta.getMinutes()).padStart(2, '0')}`;
                            } else {
                                const fimConsulta = new Date(dataConsulta.getTime() + 60 * 60000);
                                dataFim = `${String(fimConsulta.getHours()).padStart(2, '0')}:${String(fimConsulta.getMinutes()).padStart(2, '0')}`;
                            }

                            let statusBadge;
                            if (consulta.status === 'Concluída') {
                                statusBadge = '<span class="badge bg-success">Concluída</span>';
                            } else if (consulta.status === 'Cancelada') {
                                statusBadge = '<span class="badge bg-danger">Cancelada</span>';
                            } else {
                                statusBadge = '<span class="badge bg-primary">Agendada</span>';
                            }

                            html += `
                                <tr>
                                    <td>${horaInicio} - ${dataFim}</td>
                                    <td>${consulta.paciente_nome}</td>
                                    <td>${consulta.procedimento || 'Consulta'}</td>
                                    <td>${statusBadge}</td>
                                </tr>
                            `;
                        });

                        html += `
                                    </tbody>
                                </table>
                            </div>
                        `;

                        container.innerHTML = html;
                    })
                    .catch(error => {
                        console.error('Erro ao buscar horários ocupados:', error);
                        document.getElementById('horariosOcupadosContainer').innerHTML = `
                            <div class="alert alert-danger">
                                <i class="bi bi-exclamation-triangle me-2"></i>
                                Erro ao buscar horários ocupados. Por favor, tente novamente.
                            </div>
                        `;
                    });
            }

            function atualizarHoraFim() {
                const selectedOption = servicoSelect.options[servicoSelect.selectedIndex];
                if (selectedOption && selectedOption.value && horaInicioInput.value) {
                    const duracao = selectedOption.dataset.duracao || 60; // Padrão de 60 minutos
                    const horaInicio = horaInicioInput.value;
                    const [horas, minutos] = horaInicio.split(':');

                    let horaFim = new Date();
                    horaFim.setHours(parseInt(horas));
                    horaFim.setMinutes(parseInt(minutos) + parseInt(duracao));

                    const horaFimFormatada = `${String(horaFim.getHours()).padStart(2, '0')}:${String(horaFim.getMinutes()).padStart(2, '0')}`;
                    horaFimInput.value = horaFimFormatada;
                }
            }

            function salvarConsulta() {
                // Validar formulário
                if (!consultaForm.checkValidity()) {
                    consultaForm.reportValidity();
                    return;
                }

                // Se o botão de salvar estiver desabilitado, não prosseguir
                if (salvarConsultaBtn.disabled) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Erro',
                        text: 'Não é possível agendar esta consulta devido a conflito de horário.',
                    });
                    return;
                }

                mostrarLoading();

                // Construir objeto de consulta
                const consultaId = consultaIdInput.value;
                const pacienteCpf = pacienteSelect.value;
                const profissionalId = profissionalSelect.value;
                const data = dataInput.value;
                const horaInicio = horaInicioInput.value;
                const servicoId = servicoSelect.value;
                const status = statusSelect.value;
                const procedimento = procedimentoInput.value;
                const observacoes = observacoesInput.value;

                // Construir data completa
                const dataConsulta = `${data}T${horaInicio}:00`;

                // Dados financeiros
                const valor = valorInput.value;
                const statusPagamento = statusPagamentoSelect.value;
                const dataPagamento = statusPagamento === 'PAGO' ? dataPagamentoInput.value : null;

                // Construir objeto de dados
                const consultaData = {
                    id: consultaId,
                    paciente_cpf: pacienteCpf,
                    profissional_id: profissionalId,
                    data_consulta: dataConsulta,
                    servico_id: servicoId,
                    status: status,
                    procedimento: procedimento,
                    observacoes: observacoes,
                    // Dados financeiros
                    valor: valor,
                    status_pagamento: statusPagamento,
                    data_pagamento: dataPagamento
                };

                // Determinar URL - usar sempre o mesmo endpoint para salvar/editar
                let url = '../api/consultas/salvar.php';
                let method = 'POST';

                // Converter para URLSearchParams
                const urlParams = new URLSearchParams();
                for (const key in consultaData) {
                    if (consultaData[key] !== null && consultaData[key] !== undefined) {
                        urlParams.append(key, consultaData[key]);
                    }
                }

                // Enviar requisição
                fetch(url, {
                        method: method,
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded'
                        },
                        body: urlParams.toString()
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            // Fechar modal
                            consultaModal.hide();

                            // Atualizar calendário
                            calendar.refetchEvents();

                            // Atualizar visualização em lista se estiver no modo lista
                            if (currentView === 'list') {
                                updateListView();
                            }

                            // Mostrar mensagem de sucesso
                            const mensagem = consultaId ? 'Consulta atualizada com sucesso!' : 'Consulta agendada com sucesso!';
                            mostrarAlerta(mensagem, 'success');
                        } else {
                            mostrarAlerta('Erro ao salvar consulta: ' + data.message, 'danger');
                        }
                        esconderLoading();
                    })
                    .catch(error => {
                        console.error('Erro ao salvar consulta:', error);
                        mostrarAlerta('Erro ao salvar consulta. Por favor, tente novamente.', 'danger');
                        esconderLoading();
                    });
            }

            function confirmarExclusaoConsulta() {
                consultaIdParaExcluir = consultaIdInput.value;
                if (!consultaIdParaExcluir) {
                    return;
                }

                consultaModal.hide();
                confirmacaoExclusaoModal.show();
            }

            function excluirConsulta() {
                if (!consultaIdParaExcluir) {
                    confirmacaoExclusaoModal.hide();
                    return;
                }

                mostrarLoading();

                fetch(`../api/consultas/excluir.php?id=${consultaIdParaExcluir}`, {
                        method: 'DELETE'
                    })
                    .then(response => response.json())
                    .then(data => {
                        confirmacaoExclusaoModal.hide();

                        if (data.success) {
                            // Atualizar calendário
                            calendar.refetchEvents();

                            // Atualizar visualização em lista se estiver no modo lista
                            if (currentView === 'list') {
                                updateListView();
                            }

                            // Mostrar mensagem de sucesso
                            mostrarAlerta('Consulta excluída com sucesso!', 'success');
                        } else {
                            mostrarAlerta('Erro ao excluir consulta: ' + data.message, 'danger');
                        }

                        consultaIdParaExcluir = null;
                        esconderLoading();
                    })
                    .catch(error => {
                        console.error('Erro ao excluir consulta:', error);
                        confirmacaoExclusaoModal.hide();
                        mostrarAlerta('Erro ao excluir consulta. Por favor, tente novamente.', 'danger');
                        consultaIdParaExcluir = null;
                        esconderLoading();
                    });
            }

            function atualizarDataConsulta(event) {
                mostrarLoading();

                const consultaId = event.id;
                const dataInicio = event.start.toISOString();
                const dataFim = event.end ? event.end.toISOString() : null;

                const urlParams = new URLSearchParams({
                    id: consultaId,
                    data_consulta: dataInicio
                });

                fetch('../api/consultas/atualizar_data.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded'
                        },
                        body: urlParams.toString()
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            // Atualizar calendário
                            calendar.refetchEvents();

                            // Atualizar visualização em lista se estiver no modo lista
                            if (currentView === 'list') {
                                updateListView();
                            }

                            // Mostrar mensagem de sucesso
                            mostrarAlerta('Data da consulta atualizada com sucesso!', 'success');
                        } else {
                            // Reverter alteração no calendário
                            calendar.refetchEvents();

                            mostrarAlerta('Erro ao atualizar data da consulta: ' + data.message, 'danger');
                        }
                        esconderLoading();
                    })
                    .catch(error => {
                        console.error('Erro ao atualizar data da consulta:', error);

                        // Reverter alteração no calendário
                        calendar.refetchEvents();

                        mostrarAlerta('Erro ao atualizar data da consulta. Por favor, tente novamente.', 'danger');
                        esconderLoading();
                    });
            }

            function aplicarFiltros() {
                calendar.refetchEvents();
            }

            function limparFiltros() {
                filtroStatus.value = 'todos';
                filtroProfissional.value = 'todos';
                filtroPaciente.value = 'todos';
                filtroServico.value = 'todos';
                filtroPeriodo.value = 'todos';

                calendar.refetchEvents();
            }

            function aplicarFiltrosMobile() {
                updateListView();
            }

            function limparFiltrosMobile() {
                filtroStatusMobile.value = 'todos';
                filtroProfissionalMobile.value = 'todos';
                filtroPacienteMobile.value = 'todos';

                updateListView();
            }

            function mostrarAlerta(mensagem, tipo) {
                const alertDiv = document.createElement('div');
                alertDiv.className = `alert alert-${tipo} alert-dismissible fade show position-fixed top-0 end-0 m-3`;
                alertDiv.setAttribute('role', 'alert');
                alertDiv.innerHTML = `
                    ${tipo === 'success' ? '<i class="bi bi-check-circle-fill me-2"></i>' : '<i class="bi bi-exclamation-triangle-fill me-2"></i>'}
                    ${mensagem}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                `;
                document.body.appendChild(alertDiv);

                // Remover alerta após 5 segundos
                setTimeout(() => {
                    alertDiv.remove();
                }, 5000);
            }

            function mostrarLoading() {
                loadingOverlay.classList.remove('d-none');
            }

            function esconderLoading() {
                loadingOverlay.classList.add('d-none');
            }
        });
    </script>
</body>

</html>