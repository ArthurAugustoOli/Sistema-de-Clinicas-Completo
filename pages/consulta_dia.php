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

// Get date parameter
$data = isset($_GET['data']) ? $_GET['data'] : date('Y-m-d');

// Format date for display
$dataFormatada = date('d/m/Y', strtotime($data));

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

// Função para formatar data e hora
function formatarDataHora($dataHora)
{
    return date('H:i', strtotime($dataHora));
}

// Função para calcular hora de fim
function calcularHoraFim($dataInicio, $duracao = 60)
{
    $inicio = new DateTime($dataInicio);
    $fim = clone $inicio;
    $fim->add(new DateInterval('PT' . $duracao . 'M'));
    return $fim->format('H:i');
}

// Buscar estatísticas para os gráficos
$estatisticas = [];

// Estatísticas por status
$sqlStatusCount = "SELECT status, COUNT(*) as total FROM consultas 
                  WHERE DATE(data_consulta) = ? 
                  GROUP BY status";
$stmtStatusCount = $conn->prepare($sqlStatusCount);
$stmtStatusCount->bind_param('s', $data);
$stmtStatusCount->execute();
$resultStatusCount = $stmtStatusCount->get_result();
$estatisticasStatus = [];
while ($row = $resultStatusCount->fetch_assoc()) {
    $estatisticasStatus[$row['status']] = $row['total'];
}

// Estatísticas por profissional
$sqlProfissionalCount = "SELECT f.nome, COUNT(*) as total FROM consultas c
                        JOIN funcionarios f ON c.profissional_id = f.id
                        WHERE DATE(c.data_consulta) = ?
                        GROUP BY c.profissional_id";
$stmtProfissionalCount = $conn->prepare($sqlProfissionalCount);
$stmtProfissionalCount->bind_param('s', $data);
$stmtProfissionalCount->execute();
$resultProfissionalCount = $stmtProfissionalCount->get_result();
$estatisticasProfissional = [];
while ($row = $resultProfissionalCount->fetch_assoc()) {
    $estatisticasProfissional[$row['nome']] = $row['total'];
}

// Estatísticas de valor por profissional
$sqlValorProfissional = "SELECT f.nome, SUM(fin.valor) as total_valor FROM consultas c
                        JOIN funcionarios f ON c.profissional_id = f.id
                        JOIN financeiro fin ON c.id = fin.consulta_id
                        WHERE DATE(c.data_consulta) = ?
                        GROUP BY c.profissional_id";
$stmtValorProfissional = $conn->prepare($sqlValorProfissional);
$stmtValorProfissional->bind_param('s', $data);
$stmtValorProfissional->execute();
$resultValorProfissional = $stmtValorProfissional->get_result();
$estatisticasValorProfissional = [];
while ($row = $resultValorProfissional->fetch_assoc()) {
    $estatisticasValorProfissional[$row['nome']] = $row['total_valor'];
}

// Buscar consultas do dia
$consultas = [];
$sql = "SELECT c.*, p.nome as paciente_nome, f.nome as profissional_nome, 
               s.nome_servico, s.duracao_minutos, fin.valor, fin.status_pagamento
        FROM consultas c
        LEFT JOIN pacientes p ON c.paciente_cpf = p.cpf
        LEFT JOIN funcionarios f ON c.profissional_id = f.id
        LEFT JOIN servicos s ON c.servico_id = s.id
        LEFT JOIN financeiro fin ON c.id = fin.consulta_id
        WHERE DATE(c.data_consulta) = ?
        ORDER BY c.data_consulta ASC";

try {
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('s', $data);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $consultas[] = $row;
    }
} catch (Exception $e) {
    // Erro ao buscar consultas
    $erro = $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Consultas do Dia - Sistema de Gerenciamento de Clínica</title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <!-- Flatpickr (Calendário) -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">

    <!-- Chart.js -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.min.css">

    <!-- Estilos personalizados -->
    <link href="../css/styles.css" rel="stylesheet">

    <!-- Estilos responsivos -->
    <link href="../css/responsive.css" rel="stylesheet">

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
  font-size: 12px

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

        .consulta-card {
            transition: transform 0.2s, box-shadow 0.2s;
            cursor: pointer;
        }

        .consulta-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 0.5rem 1.5rem rgba(0, 0, 0, 0.15);
        }

        .status-agendada {
            border-left: 4px solid var(--primary-color);
        }

        .status-concluida {
            border-left: 4px solid var(--success-color);
        }

        .status-cancelada {
            border-left: 4px solid var(--danger-color);
        }

        .consulta-hora {
            font-weight: 600;
            font-size: 1.1rem;
        }

        .consulta-paciente {
            font-weight: 500;
            font-size: 1rem;
        }

        .consulta-procedimento {
            color: var(--secondary-color);
        }

        .consulta-profissional {
            font-size: 0.9rem;
        }

        .consulta-status {
            font-size: 0.8rem;
            padding: 0.25rem 0.5rem;
            border-radius: 50px;
        }

        .status-badge-agendada {
            background-color: rgba(78, 115, 223, 0.1);
            color: var(--primary-color);
        }

        .status-badge-concluida {
            background-color: rgba(28, 200, 138, 0.1);
            color: var(--success-color);
        }

        .status-badge-cancelada {
            background-color: rgba(231, 74, 59, 0.1);
            color: var(--danger-color);
        }

        .day-navigation {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .day-navigation .btn {
            padding: 0.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            width: 38px;
            height: 38px;
        }

        .current-date {
            font-weight: 600;
            font-size: 1.1rem;
        }

        .empty-state {
            text-align: center;
            padding: 3rem 1rem;
        }

        .empty-state-icon {
            font-size: 3rem;
            color: var(--secondary-color);
            opacity: 0.5;
            margin-bottom: 1rem;
        }

        .empty-state-text {
            font-size: 1.1rem;
            color: var(--secondary-color);
            margin-bottom: 1.5rem;
        }

        .calendar-container {
            position: relative;
        }

        .calendar-input {
            padding-left: 2.5rem;
            cursor: pointer;
        }

        .calendar-icon {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--primary-color);
            pointer-events: none;
        }

        .quick-actions {
            display: flex;
            gap: 0.5rem;
            margin-top: 0.5rem;
        }

        .quick-actions .btn {
            padding: 0.25rem 0.5rem;
            font-size: 0.8rem;
        }

        .chart-container {
            position: relative;
            height: 300px;
            margin-bottom: 1.5rem;
        }

        .filter-container {
            margin-bottom: 1.5rem;
        }

        .filter-label {
            font-weight: 500;
            margin-bottom: 0.5rem;
        }

        .status-summary {
            display: flex;
            justify-content: space-between;
            margin-bottom: 1.5rem;
        }

        .status-item {
            text-align: center;
            padding: 1rem;
            border-radius: var(--card-border-radius);
            flex: 1;
            margin: 0 0.5rem;
        }

        .status-item-agendada {
            background-color: rgba(78, 115, 223, 0.1);
            color: var(--primary-color);
        }

        .status-item-concluida {
            background-color: rgba(28, 200, 138, 0.1);
            color: var(--success-color);
        }

        .status-item-cancelada {
            background-color: rgba(231, 74, 59, 0.1);
            color: var(--danger-color);
        }

        .status-count {
            font-size: 2rem;
            font-weight: 700;
            line-height: 1;
            margin-bottom: 0.5rem;
        }

        .status-label {
            font-size: 0.9rem;
            font-weight: 500;
        }

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

/*teste*/
@media (max-width: 767.98px) {
  .day-navigation {
    display: flex;
    flex-direction: row;
    gap: 1rem;
    justify-content: center;
    align-items: center;
  }
  .d-flex .gap-2{flex-direction:column;}
  
  #novaConsultaBtn{
     height:50px;
     font-size:14px;
  }
  #calendarioCompleto{
       height:50px;
     font-size:14px;
     margin-top: 1rem;
  }
     .calendar-icon {
            position: absolute;
            left: 1.5rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--primary-color);
            pointer-events: none;
        }
        
        .day-navigation .btn {
    padding: 0.5rem;
    display: flex;
    align-items: center;
    justify-content: center;
    width: 45px;
}

#chartTypeDropdown{
    margin: 0;
    width: 135px;
    font-size: 11px;
    padding: 0;
}
#estatisticasMenu{
     width: 200px;
     font-size:12px;
}

}


        @media (max-width: 767.98px) {
        

            .status-summary {
                flex-direction: column;
                gap: 1rem;
            }

            .status-item {
                margin: 0;
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


            <!-- Conteúdo da Página -->
            <div class="container-fluid px-4 py-4 mt-3">
                <!-- Header -->
                <div class="d-flex justify-content-between align-items-center mb-4 fade-in mt-5">
                    <div>
                        <h2 class="h3 mb-0 text-gray-800" style="margin-top:1rem;font-weight: 700;">Consultas do Dia</h2>
                        <p class="mb-0 text-muted">Gerenciamento de consultas diárias</p>
                    </div>
                    <div class="d-flex gap-2" >
                        <a href="calendario.php" class="btn btn-outline-primary" id="calendarioCompleto">
                            <i class="bi bi-calendar3 me-1"></i> Calendário Completo
                        </a>
                        <button type="button" class="btn btn-primary" id="novaConsultaBtn">
                            <i class="bi bi-plus-circle me-1"></i> Nova Consulta
                        </button>
                    </div>
                </div>

                <!-- Navegação de Dias e Calendário -->
                <div class="card mb-4 fade-in">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-md-6 mb-3 mb-md-0">
                                <div class="day-navigation" >
                                    <a href="?data=<?= date('Y-m-d', strtotime($data . ' -1 day')) ?>" class="btn btn-outline-secondary rounded-circle">
                                        <i class="bi bi-chevron-left"></i>
                                    </a>
                                    <span class="current-date" style="margin-top:6px"><?= $dataFormatada ?></span>
                                    <a href="?data=<?= date('Y-m-d', strtotime($data . ' +1 day')) ?>" class="btn btn-outline-secondary rounded-circle">
                                        <i class="bi bi-chevron-right"></i>
                                    </a>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="d-flex gap-2 justify-content-md-end">
                                    <div class="calendar-container">
                                        <i class="bi bi-calendar3 calendar-icon"></i>
                                        <input type="text" id="datepicker" class="form-control calendar-input" value="<?= $dataFormatada ?>" readonly>
                                    </div>
                                    <a href="?data=<?= date('Y-m-d') ?>" class="btn btn-outline-primary">
                                        <i class="bi bi-calendar-check me-1"></i> Hoje
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Resumo de Status -->
                <div class="status-summary fade-in">
                    <div class="status-item status-item-agendada">
                        <div class="status-count"><?= $estatisticasStatus['Agendada'] ?? 0 ?></div>
                        <div class="status-label">Agendadas</div>
                    </div>
                    <div class="status-item status-item-concluida">
                        <div class="status-count"><?= $estatisticasStatus['Concluída'] ?? 0 ?></div>
                        <div class="status-label">Concluídas</div>
                    </div>
                    <div class="status-item status-item-cancelada">
                        <div class="status-count"><?= $estatisticasStatus['Cancelada'] ?? 0 ?></div>
                        <div class="status-label">Canceladas</div>
                    </div>
                </div>

                <!-- Gráficos e Filtros -->
                <div class="row fade-in">
                    <div class="col-lg-8 mb-4">
                        <div class="card h-100">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">Estatísticas do Dia</h5>
                                <div class="dropdown">
                                    <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" id="chartTypeDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                                        <i class="bi bi-bar-chart me-1"></i> Tipo de Gráfico
                                    </button>
                                    <ul class="dropdown-menu" aria-labelledby="chartTypeDropdown" id="estatisticasMenu">
                                        <li><a class="dropdown-item chart-type" data-type="consultas" href="#">Consultas por Profissional</a></li>
                                        <li><a class="dropdown-item chart-type" data-type="valores" href="#">Valores por Profissional</a></li>
                                        <li><a class="dropdown-item chart-type" data-type="status" href="#">Consultas por Status</a></li>
                                    </ul>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="chart-container">
                                    <canvas id="consultasChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-4 mb-4">
                        <div class="card h-100">
                            <div class="card-header">
                                <h5 class="mb-0">Filtros</h5>
                            </div>
                            <div class="card-body">
                                <div class="filter-container">
                                    <label for="filtroProfissional" class="filter-label">Profissional</label>
                                    <select class="form-select" id="filtroProfissional">
                                        <option value="todos" selected>Todos</option>
                                        <?php foreach ($funcionarios as $funcionario): ?>
                                            <option value="<?= $funcionario['id'] ?>"><?= htmlspecialchars($funcionario['nome']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="filter-container">
                                    <label for="filtroStatus" class="filter-label">Status</label>
                                    <select class="form-select" id="filtroStatus">
                                        <option value="todos" selected>Todos</option>
                                        <option value="Agendada">Agendadas</option>
                                        <option value="Concluída">Concluídas</option>
                                        <option value="Cancelada">Canceladas</option>
                                    </select>
                                </div>
                                <div class="filter-container">
                                    <label for="filtroServico" class="filter-label">Serviço</label>
                                    <select class="form-select" id="filtroServico">
                                        <option value="todos" selected>Todos</option>
                                        <?php foreach ($servicos as $servico): ?>
                                            <option value="<?= $servico['id'] ?>"><?= htmlspecialchars($servico['nome_servico']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <button type="button" class="btn btn-primary w-100 mt-3" id="aplicarFiltrosBtn">
                                    <i class="bi bi-funnel me-1"></i> Aplicar Filtros
                                </button>
                                <button type="button" class="btn btn-outline-secondary w-100 mt-2" id="limparFiltrosBtn">
                                    <i class="bi bi-x-circle me-1"></i> Limpar Filtros
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Lista de Consultas -->
                <div class="row fade-in" id="consultasList">
                    <?php if (empty($consultas)): ?>
                        <div class="col-12">
                            <div class="card">
                                <div class="card-body empty-state ">
                                    <div class="empty-state-icon">
                                        <i class="bi bi-calendar-x"></i>
                                    </div>
                                    <div class="empty-state-text">
                                        Não há consultas agendadas para esta data.
                                    </div>
                                    <button type="button" class="btn btn-primary" id="novaConsultaVazioBtn">
                                        <i class="bi bi-plus-circle me-1"></i> Agendar Consulta
                                    </button>
                                </div>
                            </div>
                        </div>
                    <?php else: ?>
                        <?php foreach ($consultas as $consulta): ?>
                            <?php
                            $horaInicio = formatarDataHora($consulta['data_consulta']);
                            $horaFim = calcularHoraFim($consulta['data_consulta'], $consulta['duracao_minutos'] ?: 60);

                            $statusClass = '';
                            $statusBadgeClass = '';

                            if ($consulta['status'] === 'Agendada') {
                                $statusClass = 'status-agendada';
                                $statusBadgeClass = 'status-badge-agendada';
                            } elseif ($consulta['status'] === 'Concluída') {
                                $statusClass = 'status-concluida';
                                $statusBadgeClass = 'status-badge-concluida';
                            } elseif ($consulta['status'] === 'Cancelada') {
                                $statusClass = 'status-cancelada';
                                $statusBadgeClass = 'status-badge-cancelada';
                            }
                            ?>
                            <div class="col-md-6 col-lg-4 mb-4 consulta-item"
                                data-id="<?= $consulta['id'] ?>"
                                data-profissional="<?= $consulta['profissional_id'] ?>"
                                data-status="<?= $consulta['status'] ?>"
                                data-servico="<?= $consulta['servico_id'] ?>">
                                <div class="card consulta-card <?= $statusClass ?>">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-start mb-2">
                                            <div class="consulta-hora"><?= $horaInicio ?> - <?= $horaFim ?></div>
                                            <div class="dropdown">
                                                <button class="btn btn-sm btn-outline-secondary dropdown-toggle status-dropdown" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                                    <span class="consulta-status <?= $statusBadgeClass ?>"><?= $consulta['status'] ?></span>
                                                </button>
                                                <ul class="dropdown-menu">
                                                    <li><a class="dropdown-item status-option" data-status="Agendada" data-id="<?= $consulta['id'] ?>" href="#">Agendada</a></li>
                                                    <li><a class="dropdown-item status-option" data-status="Concluída" data-id="<?= $consulta['id'] ?>" href="#">Concluída</a></li>
                                                    <li><a class="dropdown-item status-option" data-status="Cancelada" data-id="<?= $consulta['id'] ?>" href="#">Cancelada</a></li>
                                                </ul>
                                            </div>
                                        </div>
                                        <div class="consulta-paciente mb-1"><?= htmlspecialchars($consulta['paciente_nome']) ?></div>
                                        <div class="consulta-procedimento mb-2"><?= htmlspecialchars($consulta['procedimento'] ?: $consulta['nome_servico'] ?: 'Consulta') ?></div>
                                        <div class="consulta-profissional text-muted">
                                            <i class="bi bi-person me-1"></i> <?= htmlspecialchars($consulta['profissional_nome']) ?>
                                        </div>
                                        <?php if (isset($consulta['valor']) && $consulta['valor'] > 0): ?>
                                            <div class="consulta-valor mt-2 d-flex justify-content-between">
                                                <span><?= formatarMoeda($consulta['valor']) ?></span>
                                                <span class="badge <?= $consulta['status_pagamento'] === 'PAGO' ? 'bg-success' : 'bg-warning text-dark' ?>" style="width:50px;">
                                                    <?= $consulta['status_pagamento'] === 'PAGO' ? 'Pago' : 'Pendente' ?>
                                                </span>
                                            </div>
                                        <?php endif; ?>
                                        <div class="quick-actions">
                                            <button type="button" class="btn btn-sm btn-outline-primary editar-consulta" data-id="<?= $consulta['id'] ?>">
                                                <i class="bi bi-pencil"></i> Editar
                                            </button>
                                            <button type="button" class="btn btn-sm btn-outline-danger excluir-consulta" data-id="<?= $consulta['id'] ?>">
                                                <i class="bi bi-trash"></i> Excluir
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de Edição de Consulta -->
    <div class="modal fade" id="consultaModal" tabindex="-1" aria-labelledby="consultaModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="consultaModalLabel">Editar Consulta</h5>
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
                    <button type="button" class="btn btn-danger me-2" id="excluirConsultaBtn">Excluir</button>
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
                </form>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-danger" id="confirmarExclusaoBtn">Excluir</button>
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

    <!-- Flatpickr (Calendário) -->
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/pt.js"></script>

    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <!-- Script Principal -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Inicializar variáveis
            let consultaAtual = null;
            let consultaIdParaExcluir = null;
            let chartAtual = null;
            let tipoGraficoAtual = 'consultas';

            // Elementos do DOM
            const consultaModal = new bootstrap.Modal(document.getElementById('consultaModal'));
            const confirmacaoExclusaoModal = new bootstrap.Modal(document.getElementById('confirmacaoExclusaoModal'));
            const loadingOverlay = document.getElementById('loadingOverlay');
            const consultasList = document.getElementById('consultasList');

            // Botões e formulários
            const novaConsultaBtn = document.getElementById('novaConsultaBtn');
            const novaConsultaVazioBtn = document.getElementById('novaConsultaVazioBtn');
            const salvarConsultaBtn = document.getElementById('salvarConsultaBtn');
            const excluirConsultaBtn = document.getElementById('excluirConsultaBtn');
            const confirmarExclusaoBtn = document.getElementById('confirmarExclusaoBtn');
            const aplicarFiltrosBtn = document.getElementById('aplicarFiltrosBtn');
            const limparFiltrosBtn = document.getElementById('limparFiltrosBtn');

            // Campos do formulário
            const consultaForm = document.getElementById('consultaForm');
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
            const filtroProfissional = document.getElementById('filtroProfissional');
            const filtroStatus = document.getElementById('filtroStatus');
            const filtroServico = document.getElementById('filtroServico');

            // Inicializar Flatpickr (calendário)
            const datepicker = flatpickr("#datepicker", {
                dateFormat: "d/m/Y",
                locale: "pt",
                onChange: function(selectedDates, dateStr, instance) {
                    // Converter para formato YYYY-MM-DD
                    const data = selectedDates[0].toISOString().split('T')[0];
                    window.location.href = `?data=${data}`;
                }
            });

            // Inicializar gráficos
            inicializarGraficos();

            // Event Listeners
            if (novaConsultaBtn) {
                novaConsultaBtn.addEventListener('click', abrirModalNovaConsulta);
            }

            if (novaConsultaVazioBtn) {
                novaConsultaVazioBtn.addEventListener('click', abrirModalNovaConsulta);
            }

            salvarConsultaBtn.addEventListener('click', salvarConsulta);
            excluirConsultaBtn.addEventListener('click', confirmarExclusaoConsulta);
            confirmarExclusaoBtn.addEventListener('click', excluirConsulta);
            aplicarFiltrosBtn.addEventListener('click', aplicarFiltros);
            limparFiltrosBtn.addEventListener('click', limparFiltros);

            // Event listener para os botões de editar consulta
            document.querySelectorAll('.editar-consulta').forEach(btn => {
                btn.addEventListener('click', function(e) {
                    e.stopPropagation();
                    const consultaId = this.dataset.id;
                    carregarConsulta(consultaId);
                });
            });

            // Event listener para os botões de excluir consulta
            document.querySelectorAll('.excluir-consulta').forEach(btn => {
                btn.addEventListener('click', function(e) {
                    e.stopPropagation();
                    consultaIdParaExcluir = this.dataset.id;
                    confirmacaoExclusaoModal.show();
                });
            });

            // Event listener para os cards de consulta
            document.querySelectorAll('.consulta-card').forEach(card => {
                card.addEventListener('click', function(e) {
                    // Verificar se o clique foi em um elemento interno (como o botão de status ou dropdown)
                    if (e.target.closest('.status-option') || e.target.closest('.status-dropdown')) {
                        return; // Não executar o evento do card
                    }

                    const consultaId = this.closest('.consulta-item').dataset.id;
                    carregarConsulta(consultaId);
                });
            });

            // Event listener para as opções de status
            document.querySelectorAll('.status-option').forEach(option => {
                option.addEventListener('click', function(e) {
                    e.preventDefault(); // Impede o comportamento padrão do link
                    e.stopPropagation(); // Impede a propagação do evento para o card

                    const status = this.dataset.status;
                    const consultaId = this.dataset.id;

                    // Atualizar o status da consulta
                    atualizarStatusConsulta(consultaId, status);
                });
            });
            // Event listener para os tipos de gráfico
            document.querySelectorAll('.chart-type').forEach(option => {
                option.addEventListener('click', function(e) {
                    e.preventDefault();
                    tipoGraficoAtual = this.dataset.type;
                    atualizarGrafico();
                });
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

            // Atualizar hora fim quando serviço é selecionado
            servicoSelect.addEventListener('change', function() {
                const selectedOption = servicoSelect.options[servicoSelect.selectedIndex];
                if (selectedOption.value) {
                    const preco = selectedOption.dataset.preco;
                    const duracao = selectedOption.dataset.duracao;

                    valorInput.value = preco;

                    if (horaInicioInput.value && duracao) {
                        atualizarHoraFim();
                    }
                }
            });

            // Atualizar hora fim quando hora início é alterada
            horaInicioInput.addEventListener('change', function() {
                if (servicoSelect.value) {
                    atualizarHoraFim();
                }
            });

            // Funções
            function inicializarGraficos() {
                const ctx = document.getElementById('consultasChart').getContext('2d');

                // Dados para o gráfico de consultas por profissional
                const dadosConsultas = {
                    labels: <?= json_encode(array_keys($estatisticasProfissional)) ?>,
                    datasets: [{
                        label: 'Consultas',
                        data: <?= json_encode(array_values($estatisticasProfissional)) ?>,
                        backgroundColor: [
                            'rgba(78, 115, 223, 0.8)',
                            'rgba(28, 200, 138, 0.8)',
                            'rgba(54, 185, 204, 0.8)',
                            'rgba(246, 194, 62, 0.8)',
                            'rgba(231, 74, 59, 0.8)'
                        ],
                        borderWidth: 1
                    }]
                };

                // Configurações do gráfico
                const config = {
                    type: 'bar',
                    data: dadosConsultas,
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                display: false
                            },
                            title: {
                                display: true,
                                text: 'Consultas por Profissional'
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    precision: 0
                                }
                            }
                        }
                    }
                };

                // Criar gráfico
                chartAtual = new Chart(ctx, config);
            }

            function atualizarGrafico() {
                // Destruir gráfico atual
                if (chartAtual) {
                    chartAtual.destroy();
                }

                const ctx = document.getElementById('consultasChart').getContext('2d');
                let dados, titulo, tipo;

                if (tipoGraficoAtual === 'consultas') {
                    dados = {
                        labels: <?= json_encode(array_keys($estatisticasProfissional)) ?>,
                        datasets: [{
                            label: 'Consultas',
                            data: <?= json_encode(array_values($estatisticasProfissional)) ?>,
                            backgroundColor: [
                                'rgba(78, 115, 223, 0.8)',
                                'rgba(28, 200, 138, 0.8)',
                                'rgba(54, 185, 204, 0.8)',
                                'rgba(246, 194, 62, 0.8)',
                                'rgba(231, 74, 59, 0.8)'
                            ],
                            borderWidth: 1
                        }]
                    };
                    titulo = 'Consultas por Profissional';
                    tipo = 'bar';
                } else if (tipoGraficoAtual === 'valores') {
                    dados = {
                        labels: <?= json_encode(array_keys($estatisticasValorProfissional)) ?>,
                        datasets: [{
                            label: 'Valores (R$)',
                            data: <?= json_encode(array_values($estatisticasValorProfissional)) ?>,
                            backgroundColor: [
                                'rgba(28, 200, 138, 0.8)',
                                'rgba(78, 115, 223, 0.8)',
                                'rgba(54, 185, 204, 0.8)',
                                'rgba(246, 194, 62, 0.8)',
                                'rgba(231, 74, 59, 0.8)'
                            ],
                            borderWidth: 1
                        }]
                    };
                    titulo = 'Valores por Profissional (R$)';
                    tipo = 'bar';
                } else if (tipoGraficoAtual === 'status') {
                    dados = {
                        labels: ['Agendada', 'Concluída', 'Cancelada'],
                        datasets: [{
                            label: 'Consultas',
                            data: [
                                <?= $estatisticasStatus['Agendada'] ?? 0 ?>,
                                <?= $estatisticasStatus['Concluída'] ?? 0 ?>,
                                <?= $estatisticasStatus['Cancelada'] ?? 0 ?>
                            ],
                            backgroundColor: [
                                'rgba(78, 115, 223, 0.8)',
                                'rgba(28, 200, 138, 0.8)',
                                'rgba(231, 74, 59, 0.8)'
                            ],
                            borderWidth: 1
                        }]
                    };
                    titulo = 'Consultas por Status';
                    tipo = 'pie';
                }

                // Configurações do gráfico
                const config = {
                    type: tipo,
                    data: dados,
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                display: tipo === 'pie'
                            },
                            title: {
                                display: true,
                                text: titulo
                            }
                        },
                        scales: tipo !== 'pie' ? {
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    precision: 0
                                }
                            }
                        } : {}
                    }
                };

                // Criar novo gráfico
                chartAtual = new Chart(ctx, config);
            }

            function abrirModalNovaConsulta() {
                // Resetar formulário
                consultaForm.reset();
                consultaIdInput.value = '';
                consultaAtual = null;

                // Definir título do modal
                document.getElementById('consultaModalLabel').textContent = 'Nova Consulta';

                // Definir data atual
                const hoje = new Date();
                dataInput.value = '<?= $data ?>';

                // Definir hora de início padrão (8:00)
                horaInicioInput.value = '08:00';

                // Definir hora de fim padrão (9:00)
                horaFimInput.value = '09:00';

                // Definir status padrão
                statusSelect.value = 'Agendada';

                // Definir status de pagamento padrão
                statusPagamentoSelect.value = 'PENDENTE';
                dataPagamentoContainer.classList.add('d-none');

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

                        // Definir título do modal
                        document.getElementById('consultaModalLabel').textContent = 'Editar Consulta';

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

                            // Recarregar a página para mostrar as alterações
                            window.location.reload();

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
                            // Recarregar a página para mostrar as alterações
                            window.location.reload();

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

            function atualizarStatusConsulta(consultaId, status) {
                mostrarLoading();

                const urlParams = new URLSearchParams({
                    id: consultaId,
                    status: status
                });

                fetch('../api/consultas/atualizar_status.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded'
                        },
                        body: urlParams.toString()
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            // Recarregar a página para mostrar as alterações
                            window.location.reload();

                            // Mostrar mensagem de sucesso
                            mostrarAlerta('Status da consulta atualizado com sucesso!', 'success');
                        } else {
                            mostrarAlerta('Erro ao atualizar status da consulta: ' + data.message, 'danger');
                        }
                        esconderLoading();
                    })
                    .catch(error => {
                        console.error('Erro ao atualizar status da consulta:', error);
                        mostrarAlerta('Erro ao atualizar status da consulta. Por favor, tente novamente.', 'danger');
                        esconderLoading();
                    });
            }

            function aplicarFiltros() {
                const profissionalId = filtroProfissional.value;
                const status = filtroStatus.value;
                const servicoId = filtroServico.value;

                // Filtrar os itens de consulta
                document.querySelectorAll('.consulta-item').forEach(item => {
                    let mostrar = true;

                    if (profissionalId !== 'todos' && item.dataset.profissional !== profissionalId) {
                        mostrar = false;
                    }

                    if (status !== 'todos' && item.dataset.status !== status) {
                        mostrar = false;
                    }

                    if (servicoId !== 'todos' && item.dataset.servico !== servicoId) {
                        mostrar = false;
                    }

                    if (mostrar) {
                        item.style.display = '';
                    } else {
                        item.style.display = 'none';
                    }
                });

                // Verificar se há itens visíveis
                const itensVisiveis = document.querySelectorAll('.consulta-item[style=""]').length;

                if (itensVisiveis === 0) {
                    // Mostrar mensagem de nenhum resultado
                    if (!document.getElementById('nenhumResultado')) {
                        const mensagem = document.createElement('div');
                        mensagem.id = 'nenhumResultado';
                        mensagem.className = 'col-12';
                        mensagem.innerHTML = `
                            <div class="card">
                                <div class="card-body empty-state">
                                    <div class="empty-state-icon">
                                        <i class="bi bi-search"></i>
                                    </div>
                                    <div class="empty-state-text">
                                        Nenhuma consulta encontrada com os filtros selecionados.
                                    </div>
                                    <button type="button" class="btn btn-outline-primary" id="limparFiltrosBtn2">
                                        <i class="bi bi-x-circle me-1"></i> Limpar Filtros
                                    </button>
                                </div>
                            </div>
                        `;
                        consultasList.appendChild(mensagem);

                        // Adicionar event listener para o botão de limpar filtros
                        document.getElementById('limparFiltrosBtn2').addEventListener('click', limparFiltros);
                    }
                } else {
                    // Remover mensagem de nenhum resultado se existir
                    const mensagem = document.getElementById('nenhumResultado');
                    if (mensagem) {
                        mensagem.remove();
                    }
                }
            }

            function limparFiltros() {
                filtroProfissional.value = 'todos';
                filtroStatus.value = 'todos';
                filtroServico.value = 'todos';

                // Mostrar todos os itens
                document.querySelectorAll('.consulta-item').forEach(item => {
                    item.style.display = '';
                });

                // Remover mensagem de nenhum resultado se existir
                const mensagem = document.getElementById('nenhumResultado');
                if (mensagem) {
                    mensagem.remove();
                }
            }

            function mostrarAlerta(mensagem, tipo) {
                const alertDiv = document.createElement('div');
                alertDiv.className = `alert alert-${tipo} alert-dismissible fade show fixed-top mx-3 mt-3`;
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