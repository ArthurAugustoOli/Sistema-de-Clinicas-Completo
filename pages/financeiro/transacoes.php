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
$titulo = "Transações Financeiras";
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
    
    <!-- DataTables CSS -->
    <link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap5.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.bootstrap5.min.css" rel="stylesheet">
    
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
 
}

.form-select option {
  font-size: 12px;
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
        
        .filter-card {
            border-radius: 10px;
            background-color: #f8f9fa;
            border: none;
        }
        
        /* Transação card para visualização mobile */
        .transacao-card {
            transition: all 0.2s ease;
            border-left: 4px solid transparent;
            margin-bottom: 1rem;
        }

        .transacao-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }
        
        .transacao-card.status-PAGO {
            border-left-color: #28a745;
        }
        
        .transacao-card.status-PENDENTE {
            border-left-color: #ffc107;
        }
        
        /* Botões de ação */
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
        
        /* Animações */
        .fade-in {
            animation: fadeIn 0.5s ease-in-out;
        }

        .slide-in-up {
            animation: slideInUp 0.5s ease-in-out;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
            }

            to {
                opacity: 1;
            }
        }

        @keyframes slideInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        /* Ajustes para telas pequenas */
        @media (max-width: 767.98px) {
            .filter-container {
                flex-direction: column;
            }
            
            .filter-container > div {
                margin-bottom: 0.5rem;
                width: 100%;
            }
            
            .dataTables_length, 
            .dataTables_filter {
                text-align: left !important;
                margin-bottom: 10px;
            }
            
            .dt-buttons {
                margin-bottom: 10px;
            }
            
            .filter-card .row {
                row-gap: 0.5rem;
            }
        }
    </style>
</head>

<body>
    <div class="d-flex flex-column flex-lg-row">
    <div class="d-none d-md-block">
        <!-- Sidebar -->
        <?php include '../../includes/sidebar.php' ?>
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
                        <p class="text-muted mb-0">Gerencie todas as transações financeiras da clínica</p>
                    </div>
                    <div class="mt-3 mt-md-0">
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#novaTransacaoModal">
                            <i class="bi bi-plus-lg me-1"></i> <span class="d-none d-sm-inline">Nova Transação</span>
                        </button>
                    </div>
                </div>
                
                <!-- Filtros -->
                <div class="card filter-card mb-4 fade-in">
                    <div class="card-body">
                        <form id="filtroForm" class="row g-3">
                            <div class="col-md-6 col-lg-3">
                                <label for="filtroSearch" class="form-label">Buscar</label>
                                <input type="text" class="form-control" id="filtroSearch" placeholder="Nome, CPF ou serviço">
                            </div>
                            <div class="col-md-6 col-lg-3">
                                <label for="filtroStatus" class="form-label">Status</label>
                                <select class="form-select" id="filtroStatus">
                                    <option value="">Todos</option>
                                    <option value="PAGO">Pago</option>
                                    <option value="PENDENTE">Pendente</option>
                                </select>
                            </div>
                            <div class="col-md-6 col-lg-3">
                                <label for="filtroServico" class="form-label">Serviço</label>
                                <select class="form-select" id="filtroServico">
                                    <option value="">Todos</option>
                                    <!-- Opções de serviços serão carregadas via JavaScript -->
                                </select>
                            </div>
                            <div class="col-md-6 col-lg-3">
                                <label for="filtroPeriodo" class="form-label">Período</label>
                                <div class="input-group">
                                    <input type="text" class="form-control datepicker" id="filtroDataInicio" placeholder="Data inicial">
                                    <span class="input-group-text">até</span>
                                    <input type="text" class="form-control datepicker" id="filtroDataFim" placeholder="Data final">
                                </div>
                            </div>
                            <div class="col-12 text-end">
                                <button type="button" class="btn btn-outline-secondary me-2" id="limparFiltros">
                                    <i class="bi bi-eraser me-1"></i> Limpar
                                </button>
                                <button type="button" class="btn btn-primary" id="aplicarFiltros">
                                    <i class="bi bi-filter me-1"></i> Filtrar
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
                
                <!-- Tabela de Transações (visível apenas em desktop) -->
                <div class="card shadow-sm d-none d-md-block slide-in-up">
                    <div class="card-body p-4">
                        <div class="table-responsive">
                            <table id="transacoesTable" class="table table-hover align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>ID</th>
                                        <th>Paciente</th>
                                        <th>Serviço</th>
                                        <th>Data</th>
                                        <th>Valor</th>
                                        <th>Status</th>
                                        <th>Ações</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <!-- Dados serão carregados via JavaScript -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                
                <!-- Cards de Transações (visível apenas em mobile) -->
                <div class="d-md-none" id="transacoesCards">
                    <!-- Cards serão carregados via JavaScript -->
                    <div class="text-center py-4">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Carregando...</span>
                        </div>
                        <p class="mt-2">Carregando transações...</p>
                    </div>
                </div>
            </div>
        </main>
    </div>
    
    <!-- Incluir Mobile Nav -->
    <?php include '../../includes/mobile-nav.php'; ?>
    
    <!-- Modal Nova Transação -->
    <div class="modal fade" id="novaTransacaoModal" tabindex="-1" aria-labelledby="novaTransacaoModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="novaTransacaoModalLabel">Nova Transação</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>
                <div class="modal-body">
                    <form id="novaTransacaoForm">
                        <div class="mb-3">
                            <label for="consulta_id" class="form-label">Consulta*</label>
                            <select class="form-select" id="consulta_id" name="consulta_id" required>
                                <option value="">Selecione uma consulta</option>
                                <!-- Opções de consultas serão carregadas via JavaScript -->
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="servico_id" class="form-label">Serviço*</label>
                            <select class="form-select" id="servico_id" name="servico_id" required>
                                <option value="">Selecione um serviço</option>
                                <!-- Opções de serviços serão carregadas via JavaScript -->
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="valor" class="form-label">Valor (R$)*</label>
                            <input type="text" class="form-control" id="valor" name="valor" required>
                        </div>
                        <div class="mb-3">
                            <label for="status" class="form-label">Status*</label>
                            <select class="form-select" id="status" name="status" required>
                                <option value="PENDENTE">Pendente</option>
                                <option value="PAGO">Pago</option>
                            </select>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-primary" id="salvarTransacao">Salvar</button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Modal Atualizar Status -->
    <div class="modal fade" id="atualizarStatusModal" tabindex="-1" aria-labelledby="atualizarStatusModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="atualizarStatusModalLabel">Atualizar Status</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>
                <div class="modal-body">
                    <form id="atualizarStatusForm">
                        <input type="hidden" id="transacao_id" name="id">
                        <div class="mb-3">
                            <label for="novo_status" class="form-label">Status*</label>
                            <select class="form-select" id="novo_status" name="status" required>
                                <option value="PENDENTE">Pendente</option>
                                <option value="PAGO">Pago</option>
                            </select>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-primary" id="confirmarAtualizacao">Confirmar</button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap JS e dependências -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    
    <!-- DataTables JS -->
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.5.0/js/responsive.bootstrap5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.bootstrap5.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/pdfmake.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/vfs_fonts.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.html5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.print.min.js"></script>
    
    <!-- jQuery Mask Plugin -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.mask/1.14.16/jquery.mask.min.js"></script>
    
    <!-- Flatpickr JS -->
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/pt.js"></script>
    
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <script>
        // Variáveis globais
        let dataTable;
        let servicos = [];
        let transacoesData = [];
        
        // Inicialização
        document.addEventListener('DOMContentLoaded', function() {
            // Inicializar máscaras
            $('#valor').mask('#.##0,00', {reverse: true});
            
            // Inicializar datepickers
            flatpickr(".datepicker", {
                locale: "pt",
                dateFormat: "d/m/Y",
                allowInput: true
            });
            
            // Inicializar DataTable
            dataTable = $('#transacoesTable').DataTable({
                processing: true,
                serverSide: false,
                responsive: true,
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/pt-BR.json',
                },
                dom: '<"row"<"col-sm-12 col-md-6"B><"col-sm-12 col-md-6"f>>rt<"row"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>',
                buttons: [
                    {
                        extend: 'excel',
                        text: '<i class="bi bi-file-earmark-excel me-1"></i> Excel',
                        className: 'btn btn-sm btn-success',
                        exportOptions: {
                            columns: [0, 1, 2, 3, 4, 5]
                        }
                    },
                    {
                        extend: 'pdf',
                        text: '<i class="bi bi-file-earmark-pdf me-1"></i> PDF',
                        className: 'btn btn-sm btn-danger',
                        exportOptions: {
                            columns: [0, 1, 2, 3, 4, 5]
                        }
                    },
                    {
                        extend: 'print',
                        text: '<i class="bi bi-printer me-1"></i> Imprimir',
                        className: 'btn btn-sm btn-info text-white',
                        exportOptions: {
                            columns: [0, 1, 2, 3, 4, 5]
                        }
                    }
                ],
                ajax: {
                    url: '../../api/financeiro/listar.php',
                    dataSrc: function(json) {
                        if (json.success) {
                            // Armazenar opções de serviços para filtro
                            if (json.data.filtros && json.data.filtros.servicos) {
                                servicos = json.data.filtros.servicos;
                                preencherOpcoesServicos();
                            }
                            
                            transacoesData = json.data.registros;
                            renderizarCardsTransacoes(transacoesData);
                            return json.data.registros;
                        } else {
                            console.error('Erro ao carregar dados:', json.message);
                            return [];
                        }
                    }
                },
                columns: [
                    { data: 'id' },
                    { 
                        data: 'paciente',
                        render: function(data) {
                            return `<div class="fw-semibold">${data.nome}</div>
                                    <small class="text-muted">${formataCPF(data.cpf)}</small>`;
                        }
                    },
                    { data: 'servico' },
                    { data: 'data_criacao' },
                    { 
                        data: 'valor',
                        render: function(data) {
                            return formataMoeda(data);
                        }
                    },
                    { 
                        data: 'status_pagamento',
                        render: function(data) {
                            const statusText = data === 'PAGO' ? 'Pago' : 'Pendente';
                            return `<span class="status-badge status-${data}">${statusText}</span>`;
                        }
                    },
                    { 
                        data: null,
                        orderable: false,
                        render: function(data) {
                            return `
                            <div class="btn-group">
                                <button type="button" class="btn btn-sm btn-outline-primary atualizar-status" data-id="${data.id}" data-status="${data.status_pagamento}">
                                    <i class="bi bi-arrow-repeat"></i>
                                </button>
                                <a href="../consultas/detalhes.php?id=${data.consulta_id}" class="btn btn-sm btn-outline-info">
                                    <i class="bi bi-eye"></i>
                                </a>
                            </div>`;
                        }
                    }
                ],
                order: [[0, 'desc']]
            });
            
            // Carregar serviços para o formulário
            carregarServicos();
            
            // Carregar consultas para o formulário
            carregarConsultas();
            
            // Configurar eventos
            document.getElementById('aplicarFiltros').addEventListener('click', aplicarFiltros);
            document.getElementById('limparFiltros').addEventListener('click', limparFiltros);
            document.getElementById('salvarTransacao').addEventListener('click', salvarTransacao);
            document.getElementById('confirmarAtualizacao').addEventListener('click', atualizarStatus);
            
            // Evento para atualizar status (delegação de eventos)
            $('#transacoesTable').on('click', '.atualizar-status', function() {
                const id = $(this).data('id');
                const status = $(this).data('status');
                
                $('#transacao_id').val(id);
                $('#novo_status').val(status === 'PAGO' ? 'PENDENTE' : 'PAGO');
                
                $('#atualizarStatusModal').modal('show');
            });
            
            // Eventos para cards em mobile
            $('#transacoesCards').on('click', '.atualizar-status', function() {
                const id = $(this).data('id');
                const status = $(this).data('status');
                
                $('#transacao_id').val(id);
                $('#novo_status').val(status === 'PAGO' ? 'PENDENTE' : 'PAGO');
                
                $('#atualizarStatusModal').modal('show');
            });
            
            // Atualizar valor ao selecionar serviço
            document.getElementById('servico_id').addEventListener('change', function() {
                const servicoId = this.value;
                if (servicoId) {
                    const servico = servicos.find(s => s.id == servicoId);
                    if (servico) {
                        const valorFormatado = servico.preco.toFixed(2).replace('.', ',');
                        document.getElementById('valor').value = valorFormatado;
                    }
                }
            });
            
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
                        dataTable.ajax.reload();
                    }, 1000);
                } else {
                    $('.ptr-indicator').css('transform', 'translateY(-100%)');
                    $('.ptr-indicator').removeClass('visible');
                }
                
                touchStartY = 0;
                touchEndY = 0;
            }, false);
        });
        
        // Função para renderizar cards de transações para visualização mobile
        function renderizarCardsTransacoes(transacoes) {
            const container = document.getElementById('transacoesCards');
            
            if (!transacoes || transacoes.length === 0) {
                container.innerHTML = `
                    <div class="alert alert-info text-center">
                        <i class="bi bi-info-circle me-2"></i>
                        Nenhuma transação encontrada.
                    </div>
                `;
                return;
            }
            
            let html = '';
            
            transacoes.forEach(transacao => {
                const statusText = transacao.status_pagamento === 'PAGO' ? 'Pago' : 'Pendente';
                
                html += `
                <div class="card transacao-card status-${transacao.status_pagamento} slide-in-up">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <div>
                                <h5 class="card-title">${transacao.paciente.nome}</h5>
                                <div class="text-muted small">${formataCPF(transacao.paciente.cpf)}</div>
                            </div>
                            <span class="status-badge status-${transacao.status_pagamento}">${statusText}</span>
                        </div>
                        
                        <div class="mb-2">
                            <strong>Serviço:</strong> ${transacao.servico}
                        </div>
                        <div class="mb-2">
                            <strong>Data:</strong> ${transacao.data_criacao}
                        </div>
                        <div class="mb-3">
                            <strong>Valor:</strong> <span class="fw-bold fs-5">${formataMoeda(transacao.valor)}</span>
                        </div>
                        
                        <div class="d-flex justify-content-end">
                            <button type="button" class="btn btn-action btn-primary me-2 atualizar-status" 
                                data-id="${transacao.id}" 
                                data-status="${transacao.status_pagamento}">
                                <i class="bi bi-arrow-repeat"></i>
                            </button>
                            <a href="../consultas/detalhes.php?id=${transacao.consulta_id}" class="btn btn-action btn-info text-white">
                                <i class="bi bi-eye-fill"></i>
                            </a>
                        </div>
                    </div>
                </div>
                `;
            });
            
            container.innerHTML = html;
        }
        
        // Função para preencher opções de serviços no filtro
        function preencherOpcoesServicos() {
            const select = document.getElementById('filtroServico');
            
            // Limpar opções existentes (exceto a primeira)
            while (select.options.length > 1) {
                select.remove(1);
            }
            
            // Adicionar novas opções
            servicos.forEach(servico => {
                const option = document.createElement('option');
                option.value = servico.id;
                option.textContent = servico.nome;
                select.appendChild(option);
            });
        }
        
        // Função para carregar serviços
        function carregarServicos() {
            fetch('../../api/financeiro/servicos.php?action=listar')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        servicos = data.data;
                        
                        const select = document.getElementById('servico_id');
                        
                        // Limpar opções existentes (exceto a primeira)
                        while (select.options.length > 1) {
                            select.remove(1);
                        }
                        
                        // Adicionar novas opções
                        servicos.forEach(servico => {
                            const option = document.createElement('option');
                            option.value = servico.id;
                            option.textContent = `${servico.nome} - ${formataMoeda(servico.preco)}`;
                            select.appendChild(option);
                        });
                    } else {
                        console.error('Erro ao carregar serviços:', data.message);
                    }
                })
                .catch(error => {
                    console.error('Erro na requisição:', error);
                });
        }
        
        // Função para carregar consultas
        function carregarConsultas() {
            fetch('../../api/consultas/listar.php?status=Agendada,Realizada')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const consultas = data.data;
                        const select = document.getElementById('consulta_id');
                        
                        // Limpar opções existentes (exceto a primeira)
                        while (select.options.length > 1) {
                            select.remove(1);
                        }
                        
                        // Adicionar novas opções
                        consultas.forEach(consulta => {
                            const option = document.createElement('option');
                            option.value = consulta.id;
                            option.textContent = `#${consulta.id} - ${consulta.paciente_nome} - ${consulta.data_formatada}`;
                            select.appendChild(option);
                        });
                    } else {
                        console.error('Erro ao carregar consultas:', data.message);
                    }
                })
                .catch(error => {
                    console.error('Erro na requisição:', error);
                });
        }
        
        // Função para aplicar filtros
        function aplicarFiltros() {
            const search = document.getElementById('filtroSearch').value;
            const status = document.getElementById('filtroStatus').value;
            const servico_id = document.getElementById('filtroServico').value;
            const data_inicio = document.getElementById('filtroDataInicio').value;
            const data_fim = document.getElementById('filtroDataFim').value;
            
            // Construir URL com parâmetros
            let url = '../../api/financeiro/listar.php?';
            
            if (search) {
                url += `&search=${encodeURIComponent(search)}`;
            }
            
            if (status) {
                url += `&status=${encodeURIComponent(status)}`;
            }
            
            if (servico_id) {
                url += `&servico_id=${encodeURIComponent(servico_id)}`;
            }
            
            if (data_inicio) {
                url += `&data_inicio=${encodeURIComponent(data_inicio)}`;
            }
            
            if (data_fim) {
                url += `&data_fim=${encodeURIComponent(data_fim)}`;
            }
            
            // Atualizar DataTable
            dataTable.ajax.url(url).load();
        }
        
        // Função para limpar filtros
        function limparFiltros() {
            document.getElementById('filtroSearch').value = '';
            document.getElementById('filtroStatus').value = '';
            document.getElementById('filtroServico').value = '';
            document.getElementById('filtroDataInicio').value = '';
            document.getElementById('filtroDataFim').value = '';
            
            // Atualizar DataTable
            dataTable.ajax.url('../../api/financeiro/listar.php').load();
        }
        
        // Função para salvar nova transação
        function salvarTransacao() {
            const form = document.getElementById('novaTransacaoForm');
            
            if (!form.checkValidity()) {
                form.reportValidity();
                return;
            }
            
            const consulta_id = document.getElementById('consulta_id').value;
            const servico_id = document.getElementById('servico_id').value;
            const valor = document.getElementById('valor').value.replace('.', '').replace(',', '.');
            const status = document.getElementById('status').value;
            
            const formData = new FormData();
            formData.append('consulta_id', consulta_id);
            formData.append('servico_id', servico_id);
            formData.append('valor', valor);
            formData.append('status', status);
            
            fetch('../../api/financeiro/registrar_pagamento.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Sucesso!',
                        text: data.message,
                        confirmButtonColor: '#4e73df'
                    }).then(() => {
                        // Limpar formulário
                        form.reset();
                        
                        // Fechar modal
                        $('#novaTransacaoModal').modal('hide');
                        
                        // Atualizar tabela
                        dataTable.ajax.reload();
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Erro!',
                        text: data.message,
                        confirmButtonColor: '#4e73df'
                    });
                }
            })
            .catch(error => {
                console.error('Erro na requisição:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Erro!',
                    text: 'Ocorreu um erro ao processar a solicitação.',
                    confirmButtonColor: '#4e73df'
                });
            });
        }
        
        // Função para atualizar status
        function atualizarStatus() {
            const id = document.getElementById('transacao_id').value;
            const status = document.getElementById('novo_status').value;
            
            const formData = new FormData();
            formData.append('id', id);
            formData.append('status', status);
            
            fetch('../../api/financeiro/atualizar_status.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Sucesso!',
                        text: data.message,
                        confirmButtonColor: '#4e73df'
                    }).then(() => {
                        // Fechar modal
                        $('#atualizarStatusModal').modal('hide');
                        
                        // Atualizar tabela
                        dataTable.ajax.reload();
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Erro!',
                        text: data.message,
                        confirmButtonColor: '#4e73df'
                    });
                }
            })
            .catch(error => {
                console.error('Erro na requisição:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Erro!',
                    text: 'Ocorreu um erro ao processar a solicitação.',
                    confirmButtonColor: '#4e73df'
                });
            });
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
    </script>
</body>
</html>