<?php
// Iniciar sessão para gerenciamento de login
session_start();

// Verificar autenticação
if (!isset($_SESSION['logado']) || $_SESSION['logado'] !== true) {
    header("Location: ../login.php");
    exit;
}

// Conexão com o banco de dados
require_once '../../config/config.php';

// Funções utilitárias
require_once '../../functions/utils/helpers.php';

// Título da página
$titulo = "Dashboard Financeiro";
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title><?= $titulo ?> - Sistema de Gerenciamento de Clínica</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    
    <!-- Flatpickr CSS -->
    <link href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css" rel="stylesheet">
    
    <!-- Estilos personalizados -->
    <link rel="stylesheet" href="../../css/styles.css">
    <link rel="stylesheet" href="../../css/responsive.css">
    
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
  width: 200px;
}

.form-select option {
   
  font-size: 18px;
}






.mobile-bottom-nav-item span {
  font-weight: 500;
     font-size:12px;
       font-family: 'Poppins', sans-serif;
}







        @media (min-width: 992px) {
            body {
                padding-bottom: 0;
            }
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
        
        .card-dashboard {
            transition: all 0.3s ease;
            border-radius: 10px;
            border: none;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        
        .card-dashboard:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 15px rgba(0, 0, 0, 0.1);
        }
        
        .card-icon {
            width: 48px;
            height: 48px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 12px;
            font-size: 24px;
        }
        
        .card-value {
            font-size: 1.8rem;
            font-weight: 700;
        }
        
        .card-title {
            color: #6c757d;
            font-size: 0.9rem;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .card-trend {
            font-size: 0.9rem;
            display: flex;
            align-items: center;
        }
        
        .trend-up {
            color: #28a745;
        }
        
        .trend-down {
            color: #dc3545;
        }
        
        .chart-container {
            position: relative;
            height: 300px;
            width: 100%;
        }
        
        .filter-card {
            border-radius: 10px;
            background-color: #f8f9fa;
            border: none;
        }
        
        .transaction-item {
            border-left: 3px solid transparent;
            transition: all 0.2s ease;
        }
        
        .transaction-item:hover {
            background-color: #f8f9fa;
        }
        
        .transaction-item.status-PAGO {
            border-left-color: #28a745;
        }
        
        .transaction-item.status-PENDENTE {
            border-left-color: #ffc107;
        }
        
        .status-badge {
            font-size: 0.75rem;
            padding: 0.25rem 0.5rem;
            border-radius: 50px;
        }
        
        .status-PAGO {
            background-color: rgba(40, 167, 69, 0.1);
            color: #28a745;
        }
        
        .status-PENDENTE {
            background-color: rgba(255, 193, 7, 0.1);
            color: #ffc107;
        }
        
        .top-service-item {
            border-radius: 8px;
            padding: 10px;
            margin-bottom: 10px;
            background-color: #f8f9fa;
            transition: all 0.2s ease;
        }
        
        .top-service-item:hover {
            background-color: #e9ecef;
        }
        
        .top-service-name {
            font-weight: 600;
            font-size: 0.9rem;
        }
        
        .top-service-value {
            font-weight: 700;
            color: var(--primary-color, #0d6efd);
        }
        
        .top-service-count {
            font-size: 0.8rem;
            color: #6c757d;
        }
        
        .dashboard-section {
            margin-bottom: 30px;
        }
        
        .section-title {
            font-size: 1.2rem;
            font-weight: 600;
            margin-bottom: 20px;
            color: #343a40;
            display: flex;
            align-items: center;
        }
        
        .section-title i {
            margin-right: 10px;
            color: var(--primary-color, #0d6efd);
        }
        
        .skeleton-loading {
            position: relative;
            background-color: #e9ecef;
            border-radius: 4px;
            overflow: hidden;
        }
        
        .skeleton-loading::after {
            content: "";
            position: absolute;
            top: 0;
            right: 0;
            bottom: 0;
            left: 0;
            transform: translateX(-100%);
            background-image: linear-gradient(
                90deg,
                rgba(255, 255, 255, 0) 0,
                rgba(255, 255, 255, 0.2) 20%,
                rgba(255, 255, 255, 0.5) 60%,
                rgba(255, 255, 255, 0)
            );
            animation: shimmer 2s infinite;
        }
        
        @keyframes shimmer {
            100% {
                transform: translateX(100%);
            }
        }
        
        /* Tema escuro */
        .dark-mode {
            background-color: #121212;
            color: #e0e0e0;
        }
        
        .dark-mode .card-dashboard {
            background-color: #1e1e1e;
            color: #e0e0e0;
        }
        
        .dark-mode .card-title {
            color: #adb5bd;
        }
        
        .dark-mode .top-service-item {
            background-color: #2a2a2a;
        }
        
        .dark-mode .top-service-item:hover {
            background-color: #333333;
        }
        
        .dark-mode .top-service-count {
            color: #adb5bd;
        }
        
        .dark-mode .table {
            color: #e0e0e0;
        }
        
        .dark-mode .table-light {
            background-color: #2a2a2a;
            color: #e0e0e0;
        }
        
        .dark-mode .text-muted {
            color: #adb5bd !important;
        }
        
        .dark-mode .skeleton-loading {
            background-color: #2a2a2a;
        }
        
        /* Animações */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .animate-fade-in {
            animation: fadeIn 0.5s ease forwards;
        }
        
        .delay-100 { animation-delay: 0.1s; }
        .delay-200 { animation-delay: 0.2s; }
        .delay-300 { animation-delay: 0.3s; }
        .delay-400 { animation-delay: 0.4s; }
        
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
            to { transform: rotate(360deg); }
        }
        
        /* Ajustes para telas pequenas */
        @media (max-width: 767.98px) {
            
            #exportarRelatorio{
                margin-left: 55%;
            }
            
            .card-dashboard {
                margin-bottom: 1rem;
            }
            
            .chart-container {
                height: 250px;
            }
            
            .date-navigation {
                flex-wrap: wrap;
                justify-content: center;
            }
            
            .date-navigation .current-date {
                order: -1;
                width: 100%;
                text-align: center;
                margin-bottom: 0.5rem;
            }
            
            .card-value {
                font-size: 1.5rem;
            }
            
            .section-title {
                font-size: 1.1rem;
            }
            
            .card-trend {
                
  display: flex;
  align-items: center;
  justify-content: flex-end;
  font-size: 8px;
  min-width: 80px;
}

  .form-select {
  font-size: 12px;
  padding: 5px 8px;
  width: 200px;
}

.form-select option {
   
  font-size: 12px;
    }
}

        
    </style>
</head>

<body>
    <div class="d-flex flex-column flex-lg-row">
        <!-- Sidebar -->
        <div class="d-none d-md-block">
   <?php include '../../includes/sidebar.php'; ?>

        </div>
        
        <!-- Conteúdo Principal -->
        <main class="main-content">
            <!-- Topbar -->
             <div class="mb-5">
            <?php include '../../includes/topbar.php' ?>
            </div>
            <!-- Conteúdo da Página -->
            <div class="container-fluid px-3 px-md-4 py-3 py-md-4 mt-5">
                <div class="d-flex justify-content-between align-items-center mb-4 mt-5 flex-wrap">
                    <div>
                        <h2 class="mb-0"><?= $titulo ?></h2>
                        <p class="text-muted mb-0">Visão geral das finanças da clínica</p>
                    </div>
                    <div class="d-flex flex-wrap mt-3 mt-md-0">
                        <div class="me-2 mb-2 mb-md-0">
                            <select id="periodoSelect" class="form-select">
                                <option value="dia">Hoje</option>
                                <option value="semana">Últimos 7 dias</option>
                                <option value="mes" selected>Este mês</option>
                                <option value="ano">Este ano</option>
                                <option value="personalizado">Período personalizado</option>
                            </select>
                        </div>
                        <div id="dateRangeContainer" style="display: none;" class="mb-2 mb-md-0">
                            <div class="input-group">
                                <input type="text" id="dataInicio" class="form-control datepicker" placeholder="Data inicial">
                                <span class="input-group-text">até</span>
                                <input type="text" id="dataFim" class="form-control datepicker" placeholder="Data final">
                                <button class="btn btn-primary" id="aplicarFiltro">
                                    <i class="bi bi-check-lg"></i>
                                </button>
                            </div>
                        </div>
                        <div class="d-flex" >
                            <button class="btn btn-outline-primary me-2" id="exportarRelatorio" >
                                <i class="bi bi-download me-1 d-none d-sm-inline"></i> Exportar
                            </button>
                        </div>
                    </div>
                </div>
                
                <!-- Cards de Resumo -->
                <div class="row g-4 mb-4">

                    <div class="col-md-6 col-lg-3">
                        <div class="card card-dashboard h-100 animate-fade-in">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start mb-3">
                                    <div class="card-icon bg-primary-subtle text-primary">
                                        <i class="bi bi-cash"></i>
                                    </div>
                                   <div class="card-trend">
                                    <span id="trendReceita" class="badge bg-primary-subtle text-primary rounded-pill" style="width:28px;">
                                     </span>
                                    </div>
                                </div>
                                <h6 class="card-title">Receita Total</h6>
                                <div class="card-value mb-1" id="totalReceita">
                                    <span class="skeleton-loading" style="width: 120px; height: 36px;"></span>
                                </div>
                                <p class="card-text text-muted" id="totalTransacoes">
                                    <span class="skeleton-loading" style="width: 100px; height: 18px;"></span>
                                </p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6 col-lg-3">
                        <div class="card card-dashboard h-100 animate-fade-in delay-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start mb-3">
                                    <div class="card-icon bg-success-subtle text-success">
                                        <i class="bi bi-check-circle"></i>
                                    </div>
                                    <div class="card-trend">
                                        <span class="badge bg-success-subtle text-success rounded-pill" id="percentualPago" style="width:28px;">
                                            <span class="skeleton-loading" style="width: 60px; height: 20px;"></span>
                                        </span>
                                    </div>
                                </div>
                                <h6 class="card-title">Pagamentos Recebidos</h6>
                                <div class="card-value mb-1" id="totalRecebido">
                                    <span class="skeleton-loading" style="width: 120px; height: 36px;"></span>
                                </div>
                                <p class="card-text text-muted" id="transacoesPagas">
                                    <span class="skeleton-loading" style="width: 100px; height: 18px;"></span>
                                </p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6 col-lg-3">
                        <div class="card card-dashboard h-100 animate-fade-in delay-200">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start mb-3">
                                    <div class="card-icon bg-warning-subtle text-warning">
                                        <i class="bi bi-hourglass-split"></i>
                                    </div>
                                    <div class="card-trend">
                                        <span class="badge bg-warning-subtle text-warning rounded-pill" id="percentualPendente" style="width:28px;">
                                            <span class="skeleton-loading" style="width: 60px; height: 20px;"></span>
                                        </span>
                                    </div>
                                </div>
                                <h6 class="card-title">Pagamentos Pendentes</h6>
                                <div class="card-value mb-1" id="totalPendente">
                                    <span class="skeleton-loading" style="width: 120px; height: 36px;"></span>
                                </div>
                                <p class="card-text text-muted" id="transacoesPendentes">
                                    <span class="skeleton-loading" style="width: 100px; height: 18px;"></span>
                                </p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6 col-lg-3">
                        <div class="card card-dashboard h-100 animate-fade-in delay-300">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start mb-3">
                                    <div class="card-icon bg-info-subtle text-info">
                                        <i class="bi bi-graph-up"></i>
                                    </div>
                                   <div class="card-trend">
                                     <span id="trendTransacoes" class="badge bg-info-subtle text-info rounded-pill" style="width:28px;">
                                     </span>
                                    </div>
                                </div>
                                <h6 class="card-title">Ticket Médio</h6>
                                <div class="card-value mb-1" id="ticketMedio">
                                    <span class="skeleton-loading" style="width: 120px; height: 36px;"></span>
                                </div>
                                <p class="card-text text-muted">
                                    Valor médio por transação
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Gráficos e Tabelas -->
                <div class="row">
                    <!-- Coluna da Esquerda -->
                    <div class="col-lg-8">
                        <!-- Gráfico de Receita -->
                        <div class="card card-dashboard mb-4 animate-fade-in delay-400">
                            <div class="card-body">
                                <h5 class="section-title">
                                    <i class="bi bi-bar-chart-line"></i> Evolução da Receita
                                </h5>
                                <div class="chart-container">
                                    <canvas id="receitaChart"></canvas>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Transações Recentes -->
                        <div class="card card-dashboard animate-fade-in delay-400">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <h5 class="section-title mb-0">
                                        <i class="bi bi-clock-history"></i> Transações Recentes
                                    </h5>
                                    <a href="transacoes.php" class="btn btn-sm btn-outline-primary">Ver todas</a>
                                </div>
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Paciente</th>
                                                <th>Serviço</th>
                                                <th class="d-none d-md-table-cell">Data</th>
                                                <th>Valor</th>
                                                <th>Status</th>
                                            </tr>
                                        </thead>
                                        <tbody id="transacoesRecentes">
                                            <tr>
                                                <td colspan="5" class="text-center py-3">
                                                    <div class="spinner-border text-primary" role="status">
                                                        <span class="visually-hidden">Carregando...</span>
                                                    </div>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Coluna da Direita -->
                    <div class="col-lg-4">
                        <!-- Distribuição de Status -->
                        <div class="card card-dashboard mb-4 animate-fade-in delay-400">
                            <div class="card-body">
                                <h5 class="section-title">
                                    <i class="bi bi-pie-chart"></i> Distribuição de Pagamentos
                                </h5>
                                <div class="chart-container" style="height: 220px;">
                                    <canvas id="statusChart"></canvas>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Top Serviços -->
                        <div class="card card-dashboard animate-fade-in delay-400">
                            <div class="card-body">
                                <h5 class="section-title">
                                    <i class="bi bi-trophy"></i> Top Serviços
                                </h5>
                                <div id="topServicos">
                                    <div class="d-flex justify-content-center py-4">
                                        <div class="spinner-border text-primary" role="status">
                                            <span class="visually-hidden">Carregando...</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
    
    <!-- Incluir Mobile Nav -->
    <?php include '../../includes/mobile-nav.php'; ?>
    
    <!-- Bootstrap JS e dependências -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    
    <!-- Flatpickr JS -->
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/pt.js"></script>
    
    <!-- jsPDF para exportação -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.29/jspdf.plugin.autotable.min.js"></script>
    
    <script>
        // Variáveis globais
        let receitaChart = null;
        let statusChart = null;
        let dashboardData = null;
        
        // Inicialização
        document.addEventListener('DOMContentLoaded', function() {
            // Inicializar datepickers
            flatpickr(".datepicker", {
                locale: "pt",
                dateFormat: "d/m/Y",
                allowInput: true
            });
            
            // Carregar dados iniciais
            carregarDashboard('mes');
            
            // Configurar eventos
            document.getElementById('periodoSelect').addEventListener('change', function() {
                const periodo = this.value;
                const dateRangeContainer = document.getElementById('dateRangeContainer');
                
                if (periodo === 'personalizado') {
                    dateRangeContainer.style.display = 'block';
                } else {
                    dateRangeContainer.style.display = 'none';
                    carregarDashboard(periodo);
                }
            });
            
            document.getElementById('aplicarFiltro').addEventListener('click', function() {
                const dataInicio = document.getElementById('dataInicio').value;
                const dataFim = document.getElementById('dataFim').value;
                
                if (dataInicio && dataFim) {
                    carregarDashboard('personalizado', dataInicio, dataFim);
                } else {
                    alert('Por favor, selecione as datas inicial e final.');
                }
            });
            
            document.getElementById('exportarRelatorio').addEventListener('click', function() {
                exportarRelatorio();
            });
            
            // Alternar tema claro/escuro
          /*  document.getElementById('toggleTheme').addEventListener('click', function() {
                document.body.classList.toggle('dark-mode');
                const icon = this.querySelector('i');
                if (document.body.classList.contains('dark-mode')) {
                    icon.classList.remove('bi-moon');
                    icon.classList.add('bi-sun');
                    localStorage.setItem('theme', 'dark');
                } else {
                    icon.classList.remove('bi-sun');
                    icon.classList.add('bi-moon');
                    localStorage.setItem('theme', 'light');
                }
                
                // Atualizar gráficos para o novo tema
                if (receitaChart) {
                    atualizarTemaGraficos();
                }
            });
            
            // Verificar tema salvo
            if (localStorage.getItem('theme') === 'dark') {
                document.body.classList.add('dark-mode');
                const icon = document.querySelector('#toggleTheme i');
                icon.classList.remove('bi-moon');
                icon.classList.add('bi-sun');
            }*/
            
            // Inicializar pull-to-refresh para dispositivos móveis
            let touchStartY = 0;
            let touchEndY = 0;
            
            // Adicionar indicador de pull-to-refresh
            $('body').append('<div class="ptr-indicator"><div class="ptr-spinner"></div></div>');
            
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
            }, { passive: false });
            
            document.addEventListener('touchend', function(e) {
                const distance = touchEndY - touchStartY;
                
                // Se o usuário puxou o suficiente para recarregar
                if (window.scrollY === 0 && distance > 70) {
                    $('.ptr-indicator').css('transform', 'translateY(0)');
                    
                    // Recarregar a página após um pequeno atraso
                    setTimeout(function() {
                        const periodo = document.getElementById('periodoSelect').value;
                        if (periodo === 'personalizado') {
                            const dataInicio = document.getElementById('dataInicio').value;
                            const dataFim = document.getElementById('dataFim').value;
                            if (dataInicio && dataFim) {
                                carregarDashboard('personalizado', dataInicio, dataFim);
                            } else {
                                carregarDashboard('mes');
                            }
                        } else {
                            carregarDashboard(periodo);
                        }
                    }, 1000);
                } else {
                    $('.ptr-indicator').css('transform', 'translateY(-100%)');
                    $('.ptr-indicator').removeClass('visible');
                }
                
                touchStartY = 0;
                touchEndY = 0;
            }, false);
        });
        
        // Função para carregar dados do dashboard
        function carregarDashboard(periodo, dataInicio = null, dataFim = null) {
            // Mostrar indicadores de carregamento
            document.querySelectorAll('.skeleton-loading').forEach(el => {
                el.style.display = 'block';
            });
            
            // Construir URL com parâmetros
            let url = '../../api/financeiro/dashboard.php?periodo=' + periodo;
            
            if (dataInicio && dataFim) {
                url += '&data_inicio=' + dataInicio + '&data_fim=' + dataFim;
            }
            
            // Fazer requisição AJAX
            fetch(url)
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Erro na requisição: ' + response.status);
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        dashboardData = data.data;
                        atualizarDashboard(dashboardData);
                    } else {
                        console.error('Erro ao carregar dados:', data.message);
                        alert('Erro ao carregar dados: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Erro na requisição:', error);
                    alert('Erro ao carregar dados: ' + error.message);
                })
                .finally(() => {
                    // Esconder indicadores de carregamento
                    document.querySelectorAll('.skeleton-loading').forEach(el => {
                        el.style.display = 'none';
                    });
                });
        }
        
        // Função para atualizar o dashboard com os dados recebidos
        function atualizarDashboard(data) {
            // Atualizar cards de resumo
            const resumo = data.resumo;
            const comparacao = data.comparacao;
            
            // Total Receita
            document.getElementById('totalReceita').textContent = formataMoeda(resumo.total_recebido + resumo.total_pendente);
            document.getElementById('totalTransacoes').textContent = `${resumo.total_transacoes} transações`;
            
            // Pagamentos Recebidos
            document.getElementById('totalRecebido').textContent = formataMoeda(resumo.total_recebido);
            document.getElementById('transacoesPagas').textContent = `${resumo.transacoes_pagas} transações pagas`;
            
            // Pagamentos Pendentes
            document.getElementById('totalPendente').textContent = formataMoeda(resumo.total_pendente);
            document.getElementById('transacoesPendentes').textContent = `${resumo.transacoes_pendentes} transações pendentes`;
            
            
            // Percentuais
            const percentualPago = resumo.total_transacoes > 0 ? (resumo.transacoes_pagas / resumo.total_transacoes * 100).toFixed(1) : 0;
            const percentualPendente = resumo.total_transacoes > 0 ? (resumo.transacoes_pendentes / resumo.total_transacoes * 100).toFixed(1) : 0;
            
            document.getElementById('percentualPago').textContent = `${percentualPago}%`;
            document.getElementById('percentualPendente').textContent = `${percentualPendente}%`;
            
            // Ticket Médio
            const ticketMedio = resumo.total_transacoes > 0 ? (resumo.total_recebido + resumo.total_pendente) / resumo.total_transacoes : 0;
            document.getElementById('ticketMedio').textContent = formataMoeda(ticketMedio);
            
            // Tendências
            const variacaoReceita = comparacao.variacao.receita;
            const variacaoTransacoes = comparacao.variacao.transacoes;
            
            let trendReceitaHtml = '';
            if (variacaoReceita > 0) {
                trendReceitaHtml = `<i class="bi bi-arrow-up-right me-1 trend-up"></i><span class="trend-up">+${variacaoReceita}%</span>`;
            } else if (variacaoReceita < 0) {
                trendReceitaHtml = `<i class="bi bi-arrow-down-right me-1 trend-down"></i><span class="trend-down">${variacaoReceita}%</span>`;
            } else {
                trendReceitaHtml = `<i class="bi bi-dash me-1"></i><span>0%</span>`;
            }
            
            let trendTransacoesHtml = '';
            if (variacaoTransacoes > 0) {
                trendTransacoesHtml = `<i class="bi bi-arrow-up-right me-1 trend-up"></i><span class="trend-up">+${variacaoTransacoes}%</span>`;
            } else if (variacaoTransacoes < 0) {
                trendTransacoesHtml = `<i class="bi bi-arrow-down-right me-1 trend-down"></i><span class="trend-down">${variacaoTransacoes}%</span>`;
            } else {
                trendTransacoesHtml = `<i class="bi bi-dash me-1"></i><span>0%</span>`;
            }
            
            document.getElementById('trendReceita').innerHTML = trendReceitaHtml;
            document.getElementById('trendTransacoes').innerHTML = trendTransacoesHtml;
            
            // Atualizar gráfico de receita
            atualizarGraficoReceita(data.grafico_diario);
            
            // Atualizar gráfico de status
            atualizarGraficoStatus(data.distribuicao_status);
            
            // Atualizar top serviços
            atualizarTopServicos(data.top_servicos);
            
            // Atualizar transações recentes
            atualizarTransacoesRecentes(data.transacoes_recentes);
        }
        
        // Função para atualizar o gráfico de receita
        function atualizarGraficoReceita(dados) {
            const ctx = document.getElementById('receitaChart').getContext('2d');
            
            // Preparar dados para o gráfico
            const labels = dados.map(item => item.data);
            const valoresPagos = dados.map(item => parseFloat(item.valor_pago) || 0);
            const valoresPendentes = dados.map(item => parseFloat(item.valor_pendente) || 0);
            
            // Configuração de cores baseada no tema
            const isDarkMode = document.body.classList.contains('dark-mode');
            const gridColor = isDarkMode ? 'rgba(255, 255, 255, 0.1)' : 'rgba(0, 0, 0, 0.1)';
            const textColor = isDarkMode ? '#e0e0e0' : '#666';
            
            // Destruir gráfico existente se houver
            if (receitaChart) {
                receitaChart.destroy();
            }
            
            // Criar novo gráfico
            receitaChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [
                        {
                            label: 'Recebido',
                            data: valoresPagos,
                            backgroundColor: 'rgba(40, 167, 69, 0.7)',
                            borderColor: 'rgba(40, 167, 69, 1)',
                            borderWidth: 1
                        },
                        {
                            label: 'Pendente',
                            data: valoresPendentes,
                            backgroundColor: 'rgba(255, 193, 7, 0.7)',
                            borderColor: 'rgba(255, 193, 7, 1)',
                            borderWidth: 1
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'top',
                            labels: {
                                color: textColor
                            }
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return context.dataset.label + ': ' + formataMoeda(context.raw);
                                }
                            }
                        }
                    },
                    scales: {
                        x: {
                            grid: {
                                display: false
                            },
                            ticks: {
                                color: textColor
                            }
                        },
                        y: {
                            beginAtZero: true,
                            grid: {
                                color: gridColor
                            },
                            ticks: {
                                color: textColor,
                                callback: function(value) {
                                    return formataMoeda(value);
                                }
                            }
                        }
                    },
                    animation: {
                        duration: 1000,
                        easing: 'easeOutQuart'
                    }
                }
            });
        }
        
        // Função para atualizar o gráfico de status
        function atualizarGraficoStatus(dados) {
            const ctx = document.getElementById('statusChart').getContext('2d');
            
            // Preparar dados para o gráfico
            const labels = dados.map(item => item.status == 'PAGO' ? 'Pago' : 'Pendente');
            const valores = dados.map(item => parseFloat(item.valor_total) || 0);
            const cores = [
                'rgba(40, 167, 69, 0.7)',
                'rgba(255, 193, 7, 0.7)'
            ];
            
            // Configuração de cores baseada no tema
            const isDarkMode = document.body.classList.contains('dark-mode');
            const textColor = isDarkMode ? '#e0e0e0' : '#666';
            
            // Destruir gráfico existente se houver
            if (statusChart) {
                statusChart.destroy();
            }
            
            // Criar novo gráfico
            statusChart = new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: labels,
                    datasets: [{
                        data: valores,
                        backgroundColor: cores,
                        borderColor: cores.map(cor => cor.replace('0.7', '1')),
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                color: textColor
                            }
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                    const percentual = Math.round((context.raw / total) * 100);
                                    return `${context.label}: ${formataMoeda(context.raw)} (${percentual}%)`;
                                }
                            }
                        }
                    },
                    animation: {
                        animateRotate: true,
                        animateScale: true,
                        duration: 1000,
                        easing: 'easeOutQuart'
                    }
                }
            });
        }
        
        // Função para atualizar a lista de top serviços
        function atualizarTopServicos(dados) {
            const container = document.getElementById('topServicos');
            
            if (!dados || dados.length === 0) {
                container.innerHTML = '<div class="alert alert-info">Nenhum serviço encontrado no período.</div>';
                return;
            }
            
            let html = '';
            
            dados.forEach((servico, index) => {
                html += `
                <div class="top-service-item">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="top-service-name">${index + 1}. ${servico.nome}</div>
                            <div class="top-service-count">${servico.quantidade} transações</div>
                        </div>
                        <div class="top-service-value">${formataMoeda(servico.valor_total)}</div>
                    </div>
                </div>`;
            });
            
            container.innerHTML = html;
        }
        
        // Função para atualizar a lista de transações recentes
        function atualizarTransacoesRecentes(dados) {
            const tbody = document.getElementById('transacoesRecentes');
            
            if (!dados || dados.length === 0) {
                tbody.innerHTML = `
                <tr>
                    <td colspan="5" class="text-center py-3">
                        Nenhuma transação encontrada no período.
                    </td>
                </tr>`;
                return;
            }
            
            let html = '';
            
            dados.forEach(transacao => {
                html += `
                <tr class="transaction-item status-${transacao.status_pagamento}">
                    <td>
                        <div class="fw-semibold">${transacao.paciente.nome}</div>
                        <small class="text-muted">${formataCPF(transacao.paciente.cpf)}</small>
                    </td>
                    <td>${transacao.servico}</td>
                    <td class="d-none d-md-table-cell">${transacao.data_criacao}</td>
                    <td class="fw-semibold">${formataMoeda(transacao.valor)}</td>
                    <td>
                        <span class="status-badge status-${transacao.status_pagamento}">
                            ${transacao.status_pagamento === 'PAGO' ? 'Pago' : 'Pendente'}
                        </span>
                    </td>
                </tr>`;
            });
            
            tbody.innerHTML = html;
        }
        
        // Função para atualizar tema dos gráficos
        function atualizarTemaGraficos() {
            if (dashboardData) {
                atualizarGraficoReceita(dashboardData.grafico_diario);
                atualizarGraficoStatus(dashboardData.distribuicao_status);
            }
        }
        
        // Função para exportar relatório
        function exportarRelatorio() {
            if (!dashboardData) {
                alert('Nenhum dado disponível para exportar.');
                return;
            }
            
            const { jsPDF } = window.jspdf;
            const doc = new jsPDF();
            
            // Título
            doc.setFontSize(18);
            doc.text('Relatório Financeiro', 105, 15, { align: 'center' });
            
            // Subtítulo com período
            doc.setFontSize(12);
            let periodoTexto = '';
            const filtros = dashboardData.filtros;
            
            switch (filtros.periodo) {
                case 'dia':
                    periodoTexto = `Dia: ${formatarData(filtros.data_inicio)}`;
                    break;
                case 'semana':
                    periodoTexto = `Período: ${formatarData(filtros.data_inicio)} a ${formatarData(filtros.data_fim)}`;
                    break;
                case 'mes':
                    const dataInicio = new Date(filtros.data_inicio);
                    periodoTexto = `Mês: ${dataInicio.toLocaleString('pt-BR', { month: 'long', year: 'numeric' })}`;
                    break;
                case 'ano':
                    periodoTexto = `Ano: ${new Date(filtros.data_inicio).getFullYear()}`;
                    break;
                case 'personalizado':
                    periodoTexto = `Período: ${formatarData(filtros.data_inicio)} a ${formatarData(filtros.data_fim)}`;
                    break;
            }
            
            doc.text(periodoTexto, 105, 25, { align: 'center' });
            
            // Resumo financeiro
            doc.setFontSize(14);
            doc.text('Resumo Financeiro', 14, 40);
            
            const resumo = dashboardData.resumo;
            
            doc.setFontSize(10);
            doc.text(`Receita Total: ${formataMoeda(resumo.total_recebido + resumo.total_pendente)}`, 14, 50);
            doc.text(`Pagamentos Recebidos: ${formataMoeda(resumo.total_recebido)}`, 14, 55);
            doc.text(`Pagamentos Pendentes: ${formataMoeda(resumo.total_pendente)}`, 14, 60);
            doc.text(`Total de Transações: ${resumo.total_transacoes}`, 14, 65);
            doc.text(`Ticket Médio: ${formataMoeda((resumo.total_recebido + resumo.total_pendente) / (resumo.total_transacoes || 1))}`, 14, 70);
            
            // Top Serviços
            doc.setFontSize(14);
            doc.text('Top Serviços', 14, 85);
            
            const topServicos = dashboardData.top_servicos;
            
            if (topServicos && topServicos.length > 0) {
                const servicosData = topServicos.map((servico, index) => [
                    index + 1,
                    servico.nome,
                    servico.quantidade,
                    formataMoeda(servico.valor_total)
                ]);
                
                doc.autoTable({
                    startY: 90,
                    head: [['#', 'Serviço', 'Quantidade', 'Valor Total']],
                    body: servicosData,
                    theme: 'grid',
                    headStyles: { fillColor: [78, 115, 223] }
                });
            } else {
                doc.setFontSize(10);
                doc.text('Nenhum serviço encontrado no período.', 14, 90);
            }
            
            // Transações Recentes
            const finalY = doc.lastAutoTable ? doc.lastAutoTable.finalY + 15 : 120;
            
            doc.setFontSize(14);
            doc.text('Transações Recentes', 14, finalY);
            
            const transacoes = dashboardData.transacoes_recentes;
            
            if (transacoes && transacoes.length > 0) {
                const transacoesData = transacoes.map(t => [
                    t.paciente.nome,
                    t.servico,
                    t.data_criacao,
                    formataMoeda(t.valor),
                    t.status_pagamento === 'PAGO' ? 'Pago' : 'Pendente'
                ]);
                
                doc.autoTable({
                    startY: finalY + 5,
                    head: [['Paciente', 'Serviço', 'Data', 'Valor', 'Status']],
                    body: transacoesData,
                    theme: 'grid',
                    headStyles: { fillColor: [78, 115, 223] }
                });
            } else {
                doc.setFontSize(10);
                doc.text('Nenhuma transação encontrada no período.', 14, finalY + 5);
            }
            
            // Rodapé
            const pageCount = doc.internal.getNumberOfPages();
            doc.setFontSize(8);
            
            for (let i = 1; i <= pageCount; i++) {
                doc.setPage(i);
                doc.text(`Página ${i} de ${pageCount} - Gerado em ${new Date().toLocaleString('pt-BR')}`, 105, doc.internal.pageSize.height - 10, { align: 'center' });
            }
            
            // Salvar PDF
            doc.save('relatorio-financeiro.pdf');
        }
        
        // Funções utilitárias
        function formataMoeda(valor) {
            return 'R$ ' + parseFloat(valor).toFixed(2).replace('.', ',').replace(/(\d)(?=(\d{3})+(?!\d))/g, '$1.');
        }
        
        function formataCPF(cpf) {
            if (!cpf) return '';
            cpf = cpf.replace(/\D/g, '');
            return cpf.replace(/(\d{3})(\d{3})(\d{3})(\d{2})/, '$1.$2.$3-$4');
        }
        
        function formatarData(dataStr) {
            if (!dataStr) return '';
            const data = new Date(dataStr);
            return data.toLocaleDateString('pt-BR');
        }
    </script>
</body>
</html>