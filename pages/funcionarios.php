<?php
// Iniciar sessão para gerenciamento de login
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

// Título da página
$titulo = "Gerenciamento de Funcionários";

// Buscar funcionários
$search = isset($_GET['search']) ? $_GET['search'] : '';
$cargo = isset($_GET['cargo']) ? $_GET['cargo'] : '';

$sql = "SELECT id, cpf, nome, cargo, telefone, email, turno FROM funcionarios WHERE 1=1";

if (!empty($search)) {
    $search = $conn->real_escape_string($search);
    $sql .= " AND (nome LIKE '%$search%' OR cpf LIKE '%$search%' OR email LIKE '%$search%')";
}

if (!empty($cargo)) {
    $cargo = $conn->real_escape_string($cargo);
    $sql .= " AND cargo = '$cargo'";
}

$sql .= " ORDER BY nome ASC";
$result = $conn->query($sql);

// Buscar cargos disponíveis para filtro
$sql_cargos = "SELECT DISTINCT cargo FROM funcionarios ORDER BY cargo ASC";
$result_cargos = $conn->query($sql_cargos);
$cargos = [];
if ($result_cargos->num_rows > 0) {
    while ($row = $result_cargos->fetch_assoc()) {
        $cargos[] = $row['cargo'];
    }
}
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
    <link rel="stylesheet" href="../css/styles.css">
    <link rel="stylesheet" href="../css/responsive.css">

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


 #btnSearch{
                width:200px; 
               
            }

        @media (min-width: 992px) {
            body {
                padding-bottom: 0;
            }
        }

        .avatar-circle {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 14px;
        }

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
            
            
            #btnSearch {
                width:326.4px; 
                margin-left:10px; 
               
            }
            
            
            .card-body {
                padding: 1rem;
            }

            .btn-action {
                width: 52px;
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

            .filter-row {
                flex-direction: column;
            }

            .filter-row > div {
                margin-bottom: 0.5rem;
            }
            
            .dataTables_length, 
            .dataTables_filter {
                text-align: left !important;
                margin-bottom: 10px;
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

        /* Ajustes para o mobile-nav */
        .has-mobile-nav {
            padding-bottom: var(--mobile-nav-height);
        }

        /* Card view para funcionários em telas pequenas */
        .funcionario-card {
            transition: all 0.2s ease;
            border-left: 4px solid transparent;
        }

        .funcionario-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            border-left: 4px solid var(--primary-color);
        }

        /* Ajustes para DataTables responsivo */
        .dataTables_wrapper .row {
            margin-left: 0;
            margin-right: 0;
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
    </style>
</head>

<body>
    <div class="d-flex flex-column flex-lg-row">
    <div class="d-none d-md-block">
        <!-- Sidebar (visível apenas em desktop) -->
        <?php include '../includes/sidebar.php' ?>
        </div>

        <!-- Conteúdo Principal -->
        <main class="main-content">
            <!-- Topbar -->
            <?php include '../includes/topbar.php' ?>

            <!-- Conteúdo da Página -->
            <div class="container-fluid px-3 px-md-4 py-3 py-md-4 mt-5">
                <!-- Header com título e ações -->
                <div class="row mb-4 align-items-center fade-in mt-5">
                    <div class="col-md-8 mt-5">
                        <h2 class="mb-0"><?= $titulo ?></h2>
                        <p class="text-muted mb-0">Gerencie os profissionais e funcionários da clínica</p>
                    </div>
                    <div class="col-md-4 text-md-end mt-3 mt-md-0">
                        <a href="add_funcionario.php" class="btn btn-primary">
                            <i class="bi bi-person-plus"></i> <span class="d-none d-sm-inline">Novo Funcionário</span>
                        </a>
                    </div>
                </div>

                <!-- Filtros -->
                <div class="row mb-4 slide-in-up" style="animation-delay: 0.1s;">
                    <div class="col-12">
                        <div class="card border-0 shadow-sm">
                            <div class="card-body">
                                <form action="<?= htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="get" class="row g-3">
                                    <div class="col-md-6 col-sm-12">
                                        <label for="search" class="form-label">Buscar</label>
                                        <div class="input-group">
                                            <input type="text" class="form-control" id="search" name="search" placeholder="Nome, CPF ou email" value="<?= htmlspecialchars($search) ?>">
                                        
                                        </div>
                                    </div>
                                    <div class="col-md-4 col-sm-8">
                                        <label for="cargo" class="form-label">Cargo</label>
                                        <select class="form-select" id="cargo" name="cargo">
                                            <option value="">Todos os cargos</option>
                                            <?php foreach ($cargos as $c): ?>
                                                <option value="<?= htmlspecialchars($c) ?>" <?= $cargo === $c ? 'selected' : '' ?>>
                                                    <?= htmlspecialchars($c) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                      <div class="col-md-2 col-sm-4 d-flex align-items-end">
                                     <button class="btn btn-outline-secondary" type="submit" id="btnSearch" style="margin-right:10px; background-color:rgb(78, 115, 223);">
                                                <i class="bi bi-search text-white"></i>
                                            </button>
                                  
                                        <a href="<?= htmlspecialchars($_SERVER["PHP_SELF"]); ?>" class="btn btn-outline-secondary w-100" >
                                           <span >Limpar</span>
                                        </a>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Lista de Funcionários - Visualização em Tabela (Desktop) --> 
                <div class="row mb-4 slide-in-up d-none d-md-flex" style="animation-delay: 0.2s;">
                    <div class="col-12">
                        <div class="card border-0 shadow-sm">
                            <div class="card-body p-4">
                                <?php if ($result && $result->num_rows > 0): ?>
                                    <div class="table-responsive">
                                        <table class="table table-hover align-middle" id="funcionariosTable">
                                            <thead class="table-light">
                                                <tr>
                                                    <th>Nome</th>
                                                    <th>CPF</th>
                                                    <th>Cargo</th>
                                                    <th>Contato</th>
                                                    <th>Turno</th>
                                                    <th>Ações</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php 
                                                $result->data_seek(0);
                                                while ($row = $result->fetch_assoc()): 
                                                ?>
                                                    <tr>
                                                        <td>
                                                            <div class="d-flex align-items-center">
                                                                <div class="avatar-circle bg-primary text-white me-2">
                                                                    <?= getInitials($row['nome']) ?>
                                                                </div>
                                                                <div>
                                                                    <div class="fw-semibold"><?= htmlspecialchars($row['nome']) ?></div>
                                                                </div>
                                                            </div>
                                                        </td>
                                                        <td><?= isset($row['cpf']) ? formatarCPF($row['cpf']) : '-' ?></td>
                                                        <td><?= htmlspecialchars($row['cargo']) ?></td>
                                                        <td>
                                                            <div><small><i class="bi bi-telephone me-1"></i><?= formatarTelefone($row['telefone']) ?></small></div>
                                                            <div><small><i class="bi bi-envelope me-1"></i><?= htmlspecialchars($row['email']) ?></small></div>
                                                        </td>
                                                        <td><?= htmlspecialchars($row['turno'] ?? '-') ?></td>
                                                        <td>
                                                            <div class="btn-group">
                                                                <a href="add_funcionario.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-outline-primary">
                                                                    <i class="bi bi-pencil"></i>
                                                                </a>
                                                                <button type="button" class="btn btn-sm btn-outline-danger delete-funcionario" data-id="<?= $row['id'] ?>" data-nome="<?= htmlspecialchars($row['nome']) ?>">
                                                                    <i class="bi bi-trash"></i>
                                                                </button>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                <?php endwhile; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php else: ?>
                                    <div class="text-center py-5">
                                        <img src="../img/empty-data.svg" alt="Sem dados" style="width: 100px; opacity: 0.5;" class="mb-3">
                                        <h5 class="text-muted">Nenhum funcionário encontrado</h5>
                                        <p class="text-muted mb-3">Tente ajustar os filtros ou adicione um novo funcionário</p>
                                        <a href="add_funcionario.php" class="btn btn-primary">
                                            <i class="bi bi-person-plus"></i> Adicionar Funcionário
                                        </a>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Lista de Funcionários - Visualização em Cards (Mobile) -->
                <div class="row g-3 mb-4 slide-in-up d-md-none" style="animation-delay: 0.2s;">
                    <?php if ($result && $result->num_rows > 0): ?>
                        <?php 
                        $result->data_seek(0);
                        while ($row = $result->fetch_assoc()): 
                        ?>
                            <div class="col-12">
                                <div class="card funcionario-card shadow-sm">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-center mb-3">
                                            <div class="d-flex align-items-center">
                                                <div class="avatar-circle bg-primary text-white me-2">
                                                    <?= getInitials($row['nome']) ?>
                                                </div>
                                                <div>
                                                    <h5 class="card-title mb-0 fs-6"><?= htmlspecialchars($row['nome']) ?></h5>
                                                    <small class="text-muted"><?= htmlspecialchars($row['cargo']) ?></small>
                                                </div>
                                            </div>
                                            <span class="badge bg-light text-dark w-48"><?= htmlspecialchars($row['turno'] ?? '-') ?></span>
                                        </div>
                                        
                                        <div class="mb-2">
                                            <i class="bi bi-person-vcard text-muted me-2"></i>
                                            <?= isset($row['cpf']) ? formatarCPF($row['cpf']) : '-' ?>
                                        </div>
                                        <div class="mb-2">
                                            <i class="bi bi-telephone text-muted me-2"></i>
                                            <?= formatarTelefone($row['telefone']) ?>
                                        </div>
                                        <div class="mb-3">
                                            <i class="bi bi-envelope text-muted me-2"></i>
                                            <?= htmlspecialchars($row['email']) ?>
                                        </div>
                                        
                                        <div class="d-flex justify-content-end mt-3">
                                            <a href="add_funcionario.php?id=<?= $row['id'] ?>" class="btn btn-action btn-primary me-2" title="Editar">
                                                <i class="bi bi-pencil-fill"></i>
                                            </a>
                                            <button type="button" class="btn btn-action btn-danger delete-funcionario" data-id="<?= $row['id'] ?>" data-nome="<?= htmlspecialchars($row['nome']) ?>" title="Excluir">
                                                <i class="bi bi-trash-fill"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="col-12">
                            <div class="card shadow-sm">
                                <div class="card-body text-center py-5">
                                    <img src="../img/empty-data.svg" alt="Sem dados" style="width: 80px; opacity: 0.5;" class="mb-3">
                                    <h5 class="text-muted">Nenhum funcionário encontrado</h5>
                                    <p class="text-muted mb-3">Tente ajustar os filtros ou adicione um novo funcionário</p>
                                    <a href="add_funcionario.php" class="btn btn-primary">
                                        <i class="bi bi-person-plus"></i> Adicionar Funcionário
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>

    <!-- Incluir Mobile Nav -->
    <?php include '../includes/mobile-nav.php'; ?>

    <!-- Bootstrap JS e dependências -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>

    <!-- DataTables JS -->
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.5.0/js/responsive.bootstrap5.min.js"></script>

    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <!-- Script principal -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Inicializar DataTable
            $('#funcionariosTable').DataTable({
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/pt-BR.json',
                },
                pageLength: 10,
                lengthMenu: [
                    [10, 25, 50, -1],
                    [10, 25, 50, "Todos"]
                ],
                responsive: true,
                dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>t<"row"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>',
            });

            // Evento para excluir funcionário
            document.querySelectorAll('.delete-funcionario').forEach(button => {
                button.addEventListener('click', function() {
                    const id = this.getAttribute('data-id');
                    const nome = this.getAttribute('data-nome');

                    Swal.fire({
                        title: 'Tem certeza?',
                        html: `Você está prestes a excluir o funcionário <strong>${nome}</strong>.<br>Esta ação não poderá ser revertida!`,
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#e74a3b',
                        cancelButtonColor: '#6c757d',
                        confirmButtonText: 'Sim, excluir!',
                        cancelButtonText: 'Cancelar'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            // Enviar requisição para excluir
                            const formData = new FormData();
                            formData.append('action', 'delete');
                            formData.append('id', id);

                            fetch('../api/funcionarios/gerenciar.php', {
                                    method: 'POST',
                                    body: formData
                                })
                                .then(response => response.json())
                                .then(data => {
                                    if (data.success) {
                                        Swal.fire({
                                            icon: 'success',
                                            title: 'Excluído!',
                                            text: 'O funcionário foi excluído com sucesso.',
                                            confirmButtonColor: '#4e73df'
                                        }).then(() => {
                                            // Recarregar a página
                                            window.location.reload();
                                        });
                                    } else {
                                        Swal.fire({
                                            icon: 'error',
                                            title: 'Erro!',
                                            text: data.message || 'Ocorreu um erro ao excluir o funcionário.',
                                            confirmButtonColor: '#4e73df'
                                        });
                                    }
                                })
                                .catch(error => {
                                    console.error('Erro:', error);
                                    Swal.fire({
                                        icon: 'error',
                                        title: 'Erro!',
                                        text: 'Ocorreu um erro ao processar a solicitação.',
                                        confirmButtonColor: '#4e73df'
                                    });
                                });
                        }
                    });
                });
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
                        window.location.reload();
                    }, 1000);
                } else {
                    $('.ptr-indicator').css('transform', 'translateY(-100%)');
                    $('.ptr-indicator').removeClass('visible');
                }
                
                touchStartY = 0;
                touchEndY = 0;
            }, false);
        });
    </script>
</body>

</html>