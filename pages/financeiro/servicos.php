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
$titulo = "Serviços e Procedimentos";
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
        
        /* Ajustes para telas pequenas */
        @media (max-width: 767.98px) {
            .card-body {
                padding: 1rem;
            }
            
            .dataTables_length, 
            .dataTables_filter {
                text-align: left !important;
                margin-bottom: 10px;
            }
            
            .btn-action {
                width: 32px;
                height: 32px;
                margin-right: 3px;
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
            to { transform: rotate(360deg); }
        }
        
        /* Serviço card para visualização mobile */
        .servico-card {
            transition: all 0.2s ease;
            border-left: 4px solid var(--primary-color);
            margin-bottom: 1rem;
        }

        .servico-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
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
                        <p class="text-muted mb-0">Gerencie os serviços e procedimentos oferecidos pela clínica</p>
                    </div>
                    <div class="mt-3 mt-md-0">
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#novoServicoModal">
                            <i class="bi bi-plus-lg me-1"></i> <span class="d-none d-sm-inline">Novo Serviço</span>
                        </button>
                    </div>
                </div>

                <!-- Tabela de Serviços (visível apenas em desktop) -->
                <div class="card shadow-sm d-none d-md-block fade-in">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table id="servicosTable" class="table table-hover align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>ID</th>
                                        <th>Nome do Serviço</th>
                                        <th>Preço</th>
                                        <th>Duração (min)</th>
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
                
                <!-- Cards de Serviços (visível apenas em mobile) -->
                <div class="d-md-none" id="servicosCards">
                    <!-- Cards serão carregados via JavaScript -->
                    <div class="text-center py-4">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Carregando...</span>
                        </div>
                        <p class="mt-2">Carregando serviços...</p>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Incluir Mobile Nav -->
    <?php include '../../includes/mobile-nav.php'; ?>

    <!-- Modal Novo Serviço -->
    <div class="modal fade" id="novoServicoModal" tabindex="-1" aria-labelledby="novoServicoModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="novoServicoModalLabel">Novo Serviço</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>
                <div class="modal-body">
                    <form id="novoServicoForm">
                        <div class="mb-3">
                            <label for="nome_servico" class="form-label">Nome do Serviço*</label>
                            <input type="text" class="form-control" id="nome_servico" name="nome_servico" required>
                        </div>
                        <div class="mb-3">
                            <label for="preco" class="form-label">Preço (R$)*</label>
                            <input type="text" class="form-control" id="preco" name="preco" required>
                        </div>
                        <div class="mb-3">
                            <label for="duracao_minutos" class="form-label">Duração (minutos)*</label>
                            <input type="number" class="form-control" id="duracao_minutos" name="duracao_minutos" required min="1">
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-primary" id="salvarServico">Salvar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Editar Serviço -->
    <div class="modal fade" id="editarServicoModal" tabindex="-1" aria-labelledby="editarServicoModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editarServicoModalLabel">Editar Serviço</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>
                <div class="modal-body">
                    <form id="editarServicoForm">
                        <input type="hidden" id="edit_id" name="id">
                        <div class="mb-3">
                            <label for="edit_nome_servico" class="form-label">Nome do Serviço*</label>
                            <input type="text" class="form-control" id="edit_nome_servico" name="nome_servico" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_preco" class="form-label">Preço (R$)*</label>
                            <input type="text" class="form-control" id="edit_preco" name="preco" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_duracao" class="form-label">Duração (minutos)*</label>
                            <input type="number" class="form-control" id="edit_duracao" name="duracao_minutos" required min="1">
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-primary" id="atualizarServico">Atualizar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Confirmar Exclusão -->
    <div class="modal fade" id="confirmarExclusaoModal" tabindex="-1" aria-labelledby="confirmarExclusaoModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="confirmarExclusaoModalLabel">Confirmar Exclusão</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>
                <div class="modal-body">
                    <p>Tem certeza que deseja excluir este serviço?</p>
                    <p class="text-danger"><i class="bi bi-exclamation-triangle-fill me-2"></i> Esta ação não pode ser desfeita.</p>
                    <input type="hidden" id="excluir_id">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-danger" id="confirmarExclusao">Excluir</button>
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

    <!-- jQuery Mask Plugin -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.mask/1.14.16/jquery.mask.min.js"></script>

    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        // Variáveis globais
        let dataTable;
        let servicosData = [];

        // Inicialização
        document.addEventListener('DOMContentLoaded', function() {
            // Inicializar máscaras
            $('#preco').mask('#.##0,00', {
                reverse: true
            });
            $('#edit_preco').mask('#.##0,00', {
                reverse: true
            });

            // Inicializar DataTable
            dataTable = $('#servicosTable').DataTable({
                processing: true,
                serverSide: false,
                responsive: true,
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/pt-BR.json',
                },
                ajax: {
                    url: '../../api/financeiro/servicos.php?action=listar',
                    dataSrc: function(json) {
                        if (json.success) {
                            servicosData = json.data;
                            renderizarCardsServicos(servicosData);
                            return json.data;
                        } else {
                            console.error('Erro ao carregar dados:', json.message);
                            return [];
                        }
                    }
                },
                columns: [{
                        data: 'id'
                    },
                    {
                        data: 'nome'
                    }, // Alterado de 'nome_servico' para 'nome'
                    {
                        data: 'preco',
                        render: function(data) {
                            return formataMoeda(data);
                        }
                    },
                    {
                        data: 'duracao_minutos'
                    },
                    {
                        data: null,
                        orderable: false,
                        render: function(data) {
                            return `
                            <div class="btn-group">
                                <button type="button" class="btn btn-sm btn-outline-primary editar-servico" data-id="${data.id}" data-nome="${data.nome}" data-preco="${data.preco}" data-duracao="${data.duracao_minutos}">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-danger excluir-servico" data-id="${data.id}">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </div>`;
                        }
                    }
                ],

                order: [
                    [0, 'asc']
                ]
            });

            // Configurar eventos
            document.getElementById('salvarServico').addEventListener('click', salvarServico);
            document.getElementById('atualizarServico').addEventListener('click', atualizarServico);
            document.getElementById('confirmarExclusao').addEventListener('click', excluirServico);

            // Evento para editar serviço (delegação de eventos)
            $('#servicosTable').on('click', '.editar-servico', function() {
                const id = $(this).data('id');
                const nome = $(this).data('nome');
                const preco = $(this).data('preco');
                const duracao = $(this).data('duracao');

                $('#edit_id').val(id);
                $('#edit_nome_servico').val(nome);
                $('#edit_preco').val(preco.toFixed(2).replace('.', ','));
                $('#edit_duracao').val(duracao);

                $('#editarServicoModal').modal('show');
            });

            // Evento para excluir serviço (delegação de eventos)
            $('#servicosTable').on('click', '.excluir-servico', function() {
                const id = $(this).data('id');

                $('#excluir_id').val(id);
                $('#confirmarExclusaoModal').modal('show');
            });
            
            // Eventos para cards em mobile
            $('#servicosCards').on('click', '.editar-servico', function() {
                const id = $(this).data('id');
                const nome = $(this).data('nome');
                const preco = $(this).data('preco');
                const duracao = $(this).data('duracao');

                $('#edit_id').val(id);
                $('#edit_nome_servico').val(nome);
                $('#edit_preco').val(preco.toFixed(2).replace('.', ','));
                $('#edit_duracao').val(duracao);

                $('#editarServicoModal').modal('show');
            });

            $('#servicosCards').on('click', '.excluir-servico', function() {
                const id = $(this).data('id');

                $('#excluir_id').val(id);
                $('#confirmarExclusaoModal').modal('show');
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
        
        // Função para renderizar cards de serviços para visualização mobile
        function renderizarCardsServicos(servicos) {
            const container = document.getElementById('servicosCards');
            
            if (!servicos || servicos.length === 0) {
                container.innerHTML = `
                    <div class="alert alert-info text-center">
                        <i class="bi bi-info-circle me-2"></i>
                        Nenhum serviço cadastrado.
                    </div>
                `;
                return;
            }
            
            let html = '';
            
            servicos.forEach(servico => {
                html += `
                <div class="card servico-card slide-in-up">
                    <div class="card-body">
                        <h5 class="card-title">${servico.nome}</h5>
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div class="badge bg-primary rounded-pill" style="width:38px;" >${servico.duracao_minutos} min</div>
                            <div class="fw-bold fs-5">${formataMoeda(servico.preco)}</div>
                        </div>
                        <div class="d-flex justify-content-end">
                            <button type="button" class="btn btn-action btn-primary me-2 editar-servico" 
                                data-id="${servico.id}" 
                                data-nome="${servico.nome}" 
                                data-preco="${servico.preco}" 
                                data-duracao="${servico.duracao_minutos}">
                                <i class="bi bi-pencil-fill"></i>
                            </button>
                            <button type="button" class="btn btn-action btn-danger excluir-servico" data-id="${servico.id}">
                                <i class="bi bi-trash-fill"></i>
                            </button>
                        </div>
                    </div>
                </div>
                `;
            });
            
            container.innerHTML = html;
        }

        // Função para salvar novo serviço
        function salvarServico() {
            const form = document.getElementById('novoServicoForm');

            if (!form.checkValidity()) {
                form.reportValidity();
                return;
            }

            const nome_servico = document.getElementById('nome_servico').value;
            const preco = document.getElementById('preco').value.replace('.', '').replace(',', '.');
            const duracao = document.getElementById('duracao_minutos').value;

            const formData = new FormData();
            formData.append('nome_servico', nome_servico);
            formData.append('preco', preco);
            formData.append('duracao_minutos', duracao);

            fetch('../../api/financeiro/servicos.php?action=criar', {
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
                            form.reset();
                            $('#novoServicoModal').modal('hide');
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

        // Função para atualizar serviço
        function atualizarServico() {
            const form = document.getElementById('editarServicoForm');

            if (!form.checkValidity()) {
                form.reportValidity();
                return;
            }

            const id = document.getElementById('edit_id').value;
            const nome_servico = document.getElementById('edit_nome_servico').value;
            const preco = document.getElementById('edit_preco').value.replace('.', '').replace(',', '.');
            const duracao = document.getElementById('edit_duracao').value;

            const formData = new FormData();
            formData.append('id', id);
            formData.append('nome_servico', nome_servico);
            formData.append('preco', preco);
            formData.append('duracao_minutos', duracao);

            fetch('../../api/financeiro/servicos.php?action=atualizar', {
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
                            $('#editarServicoModal').modal('hide');
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

        // Função para excluir serviço
        function excluirServico() {
            const id = document.getElementById('excluir_id').value;

            const formData = new FormData();
            formData.append('id', id);

            fetch('../../api/financeiro/servicos.php?action=excluir', {
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
                            $('#confirmarExclusaoModal').modal('hide');
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

        // Função para formatar moeda
        function formataMoeda(valor) {
            return 'R$ ' + parseFloat(valor).toFixed(2).replace('.', ',').replace(/(\d)(?=(\d{3})+(?!\d))/g, '$1.');
        }
    </script>
</body>

</html>