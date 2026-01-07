<?php
// Public/despesas/index.php

require_once 'config/config.php';
require_once 'models/Despesas.php';

use App\models\Despesas;

// Instancia o model de Despesas
$despesaModel = new Despesas();

// Inicializa variável de mensagem
$msg = "";

// --- Processamento via POST ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    // 1) Cadastro de Despesa Geral
    if ($_POST['action'] === 'create_despesa') {
        $categoria     = $_POST['categoria'];
        $descricao     = $_POST['descricao'];
$raw   = str_replace(['.', ' '], ['', ''], $_POST['valor']);
$valor = str_replace(',', '.', $raw);
        $data_despesa  = $_POST['data_despesa'];
        $status        = $_POST['status'];
        try {
            $despesaModel->createDespesa($categoria, $descricao, $valor, $data_despesa, $status);
            $msg = "Despesa cadastrada com sucesso!";
        } catch (Exception $e) {
            $msg = "Erro ao cadastrar despesa: " . $e->getMessage();
        }
    }
    // 2) Atualização de Despesa Geral
    elseif ($_POST['action'] === 'update_despesa') {
        $id            = intval($_POST['id_despesa']);
        $categoria     = $_POST['categoria'];
        $descricao     = $_POST['descricao'];
$raw          = str_replace(['.', ' '], ['', ''], $_POST['valor']);
$valor        = str_replace(',', '.', $raw);
        $data_despesa  = $_POST['data_despesa'];
        $status        = $_POST['status'];
        try {
            $despesaModel->updateDespesa($id, $categoria, $descricao, $valor, $data_despesa, $status, []);
            $msg = "Despesa atualizada com sucesso!";
        } catch (Exception $e) {
            $msg = "Erro ao atualizar despesa: " . $e->getMessage();
        }
    }
    // 3) Exclusão de Despesa
    elseif ($_POST['action'] === 'delete_despesa') {
        $id = intval($_POST['id_despesa']);
        try {
            $despesaModel->deleteDespesa($id);
            $msg = "Despesa excluída com sucesso!";
        } catch (Exception $e) {
            $msg = "Erro ao excluir despesa: " . $e->getMessage();
        }
    }
}

// --- Paginação e Listagem ---
$limite_por_pagina  = 10;
$total_registros    = $despesaModel->getTotalDespesas();
$total_paginas      = ceil($total_registros / $limite_por_pagina);
$pagina_atual       = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($pagina_atual < 1) $pagina_atual = 1;
if ($pagina_atual > $total_paginas) $pagina_atual = $total_paginas;
$offset             = ($pagina_atual - 1) * $limite_por_pagina;
$despesas           = $despesaModel->getDespesasPaginadas($offset, $limite_por_pagina);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1.0">
  <title>Despesas Gerais</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <!-- Chart.js -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.css">

    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <!-- Estilos personalizados -->
    <link href="css/styles.css" rel="stylesheet">

    <!-- Estilos responsivos -->
    <link href="css/responsive.css" rel="stylesheet">
    
  <!-- Adicionar o CSS do mobile navigation -->
<style>
/* Mobile Bottom Navigation Styles */
.mobile-nav {
  position: fixed;
  bottom: 0;
  left: 0;
  width: 100%;
  height: 60px;
  background-color: #ffffff;
  box-shadow: 0 -2px 10px rgba(0, 0, 0, 0.1);
  display: flex;
  justify-content: space-around;
  align-items: center;
  z-index: 1001;
  padding: 0 10px;
}

.mobile-nav-item {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  color: #6c757d;
  text-decoration: none;
  font-size: 10px;
  padding: 8px 0;
  flex: 1;
  transition: all 0.2s;
  position: relative;
}

.mobile-nav-item i {
  font-size: 20px;
  margin-bottom: 4px;
  transition: all 0.2s;
}

.mobile-nav-item:hover, 
.mobile-nav-item:active,
.mobile-nav-item.active {
  color: var(--primary);
}

.mobile-nav-item:hover i, 
.mobile-nav-item:active i,
.mobile-nav-item.active i {
  transform: translateY(-2px);
}

.mobile-nav-item::after {
  content: '';
  position: absolute;
  bottom: 0;
  left: 50%;
  transform: translateX(-50%);
  width: 0;
  height: 3px;
  background-color: var(--primary);
  transition: width 0.2s;
  border-radius: 3px 3px 0 0;
}

.mobile-nav-item:hover::after,
.mobile-nav-item:active::after,
.mobile-nav-item.active::after {
  width: 40%;
}

.toggle-sidebar {
  position: relative;
}

.toggle-sidebar::before {
  content: '';
  position: absolute;
  top: 50%;
  left: 0;
  transform: translateY(-50%);
  height: 60%;
  width: 1px;
  background-color: rgba(0, 0, 0, 0.1);
}

/* Dark mode support */
body.dark-mode .mobile-nav {
  background-color: #1e1e1e;
  box-shadow: 0 -2px 10px rgba(0, 0, 0, 0.3);
}

body.dark-mode .mobile-nav-item {
  color: #adb5bd;
}

body.dark-mode .toggle-sidebar::before {
  background-color: rgba(255, 255, 255, 0.1);
}
</style>
<style>
    :root {
      /* Cores do tema claro */
      --primary: #5a5af3;
      --primary-dark: #4646e6;
      --primary-light: #7b7bf5;
      --secondary: #22c55e;
      --dark: #1e293b;
      --light: #f8fafc;
      --gray-light: #f1f5f9;
      --gray: #e2e8f0;
      --text-dark: #334155;
      --text-light: #94a3b8;
      --danger: #ef4444;
      --warning: #f59e0b;
      --success: #10b981;
      --border-radius: 0.5rem;
      
      /* Cores de fundo */
      --bg-main: #f8fafc;
      --bg-card: #ffffff;
      --bg-sidebar: #5a5af3;
      --bg-input: #f1f5f9;
      --bg-hover: #f1f5f9;
      --bg-active: rgba(90, 90, 243, 0.1);
      
      /* Cores de borda */
      --border-color: #e2e8f0;
      
      /* Cores de texto */
      --text-primary: #334155;
      --text-secondary: #94a3b8;
      --text-sidebar: rgba(255, 255, 255, 0.8);
      
      /* Sombras */
      --shadow-sm: 0 1px 3px rgba(0, 0, 0, 0.05);
      --shadow-md: 0 4px 6px rgba(0, 0, 0, 0.05);
      --shadow-lg: 0 10px 15px rgba(0, 0, 0, 0.05);
    }
    
    /* Tema escuro */
    [data-theme="dark"] {
      /* Cores do tema escuro */
      --primary: #6366f1;
      --primary-dark: #4f46e5;
      --primary-light: #818cf8;
      --secondary: #10b981;
      --dark: #0f172a;
      --light: #1e293b;
      --gray-light: #1e293b;
      --gray: #334155;
      --text-dark: #f1f5f9;
      --text-light: #cbd5e1;
      --danger: #f87171;
      --warning: #fbbf24;
      --success: #34d399;
      
      /* Cores de fundo */
      --bg-main: #0f172a;
      --bg-card: #1e293b;
      --bg-sidebar: #1e293b;
      --bg-input: #334155;
      --bg-hover: #334155;
      --bg-active: rgba(99, 102, 241, 0.2);
      
      /* Cores de borda */
      --border-color: #334155;
      
      /* Cores de texto */
      --text-primary: #f1f5f9;
      --text-secondary: #cbd5e1;
      --text-sidebar: rgba(255, 255, 255, 0.8);
      
      /* Sombras */
      --shadow-sm: 0 1px 3px rgba(0, 0, 0, 0.2);
      --shadow-md: 0 4px 6px rgba(0, 0, 0, 0.2);
      --shadow-lg: 0 10px 15px rgba(0, 0, 0, 0.2);
    }

    /* Estilos Gerais */
    body {
      font-family: 'Poppins', sans-serif;
      background-color: var(--bg-main);
      color: var(--text-primary);
      transition: all 0.3s ease;
      margin: 0;
      padding: 0;
      overflow-x: hidden;
      min-height: 100vh;
      display: flex;
      flex-direction: column;
    }
/* padrão: escondido em desktop */
.mobile-nav {
  display: none !important;
}

/* só exibe em tablet/mobile */
@media (max-width: 991.98px) {
  .mobile-nav {
    display: flex !important;
  }
}


    /* Sidebar */
    .sidebar {
      position: fixed;
      top: 0;
      left: 0;
      height: 100%;
      width: 260px;
      background-color: var(--bg-sidebar);
      color: var(--text-sidebar);
      z-index: 1000;
      transition: all 0.3s ease;
      overflow-y: auto;
      box-shadow: var(--shadow-md);
      padding-bottom: 70px; /* Espaço para o mobile nav */
    }

    .sidebar.active {
      left: -260px;
    }

    .sidebar-header {
      display: flex;
      align-items: center;
      padding: 20px;
      background-color: rgba(0, 0, 0, 0.1);
    }

    .sidebar-header h3 {
      margin: 0;
      font-size: 1.5rem;
      font-weight: 600;
      color: white;
    }

    .sidebar-category {
      padding: 15px 20px 5px;
      font-size: 0.75rem;
      font-weight: 600;
      text-transform: uppercase;
      letter-spacing: 1px;
      color: rgba(255, 255, 255, 0.6);
    }

    .sidebar .nav-link {
      padding: 12px 20px;
      color: var(--text-sidebar);
      display: flex;
      align-items: center;
      border-radius: 0;
      transition: all 0.2s ease;
    }

    .sidebar .nav-link i {
      margin-right: 10px;
      font-size: 1.1rem;
      width: 20px;
      text-align: center;
    }

    .sidebar .nav-link:hover {
      background-color: rgba(255, 255, 255, 0.1);
      color: white;
    }

    .sidebar .nav-link.active {
      background-color: rgba(255, 255, 255, 0.2);
      color: white;
      font-weight: 500;
    }

    /* Main Content */
    .main-content {
      margin-left: 260px;
      transition: all 0.3s ease;
      flex: 1;
      padding-bottom: 80px; /* Espaço para o mobile nav */
    }

    .main-content.active {
      margin-left: 0;
    }

    /* Top Navbar */
    .top-navbar {
      background-color: var(--bg-card);
      padding: 15px 20px;
      display: flex;
      align-items: center;
      justify-content: space-between;
      box-shadow: var(--shadow-sm);
      position: sticky;
      top: 0;
      z-index: 100;
    }

    .search-bar {
      flex: 1;
      max-width: 400px;
      margin: 0 20px;
    }

    .search-bar input {
      width: 100%;
      padding: 10px 15px;
      border: none;
      border-radius: var(--border-radius);
      background-color: var(--bg-input);
      color: var(--text-primary);
    }

    .dark-mode-toggle {
      background: none;
      border: none;
      color: var(--text-primary);
      font-size: 1.2rem;
      cursor: pointer;
      width: 40px;
      height: 40px;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      transition: all 0.2s ease;
    }

    .dark-mode-toggle:hover {
      background-color: var(--bg-hover);
    }

    /* Breadcrumb */
    .breadcrumb-nav {
      padding: 10px 20px;
      background-color: var(--bg-card);
      border-bottom: 1px solid var(--border-color);
    }

    .breadcrumb {
      margin: 0;
    }

    .breadcrumb-item a {
      color: var(--text-secondary);
      text-decoration: none;
    }

    .breadcrumb-item.active {
      color: var(--text-primary);
    }

    /* Page Content */
    .page-content {
      padding: 20px;
    }

    .page-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 20px;
    }

    .page-title {
      font-size: 1.5rem;
      font-weight: 600;
      margin: 0;
      display: flex;
      align-items: center;
    }

    .page-title i {
      margin-right: 10px;
      color: var(--primary);
    }

    /* Stats Cards */
    .stats-row {
      margin-bottom: 25px;
    }

    .stats-card {
      background-color: var(--bg-card);
      border-radius: var(--border-radius);
      padding: 20px;
      box-shadow: var(--shadow-sm);
      display: flex;
      align-items: center;
      margin-bottom: 15px;
      transition: all 0.3s ease;
      border-left: 4px solid transparent;
    }

    .stats-card:hover {
      transform: translateY(-5px);
      box-shadow: var(--shadow-md);
    }

    .stats-icon {
      width: 50px;
      height: 50px;
      border-radius: 12px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 1.5rem;
      margin-right: 15px;
      color: white;
    }

    .stats-icon.purple {
      background-color: var(--primary);
    }

    .stats-icon.green {
      background-color: var(--success);
    }

    .stats-icon.orange {
      background-color: var(--warning);
    }

    .stats-icon.red {
      background-color: var(--danger);
    }

    .stats-info {
      flex: 1;
    }

    .stats-value {
      font-size: 1.5rem;
      font-weight: 600;
      margin: 0;
      line-height: 1.2;
    }

    .stats-label {
      color: var(--text-secondary);
      margin: 0;
      font-size: 0.875rem;
    }

    /* Content Card */
    .content-card {
      background-color: var(--bg-card);
      border-radius: var(--border-radius);
      box-shadow: var(--shadow-sm);
      margin-bottom: 25px;
      overflow: hidden;
    }

    .content-card-header {
      padding: 15px 20px;
      border-bottom: 1px solid var(--border-color);
      display: flex;
      justify-content: space-between;
      align-items: center;
    }

    .content-card-title {
      margin: 0;
      font-weight: 600;
      display: flex;
      align-items: center;
    }

    .content-card-title i {
      margin-right: 10px;
      color: var(--primary);
    }

    /* Table */
    .table {
      margin-bottom: 0;
      color: var(--text-primary);
    }

    .table th {
      font-weight: 600;
      background-color: var(--bg-hover);
      border-bottom-width: 1px;
      padding: 12px 15px;
      white-space: nowrap;
    }

    .table td {
      padding: 12px 15px;
      vertical-align: middle;
      border-color: var(--border-color);
    }

    .table tr:hover {
      background-color: var(--bg-hover);
    }

    .badge-status {
      padding: 5px 10px;
      border-radius: 20px;
      font-size: 0.75rem;
      font-weight: 500;
      display: inline-block;
    }

    .badge-success {
      background-color: rgba(16, 185, 129, 0.1);
      color: var(--success);
    }

    .badge-warning {
      background-color: rgba(245, 158, 11, 0.1);
      color: var(--warning);
    }

    /* Action Buttons */
    .action-buttons {
      display: flex;
      gap: 8px;
    }

    .btn-action {
      width: 32px;
      height: 32px;
      border-radius: 8px;
      display: flex;
      align-items: center;
      justify-content: center;
      border: none;
      cursor: pointer;
      transition: all 0.2s ease;
      background-color: var(--bg-hover);
      color: var(--text-primary);
    }

    .btn-action-view:hover {
      background-color: rgba(99, 102, 241, 0.1);
      color: var(--primary);
    }

    .btn-action-edit:hover {
      background-color: rgba(245, 158, 11, 0.1);
      color: var(--warning);
    }

    .btn-action-delete:hover {
      background-color: rgba(239, 68, 68, 0.1);
      color: var(--danger);
    }

    /* Buttons */
    .btn {
      padding: 8px 16px;
      border-radius: var(--border-radius);
      font-weight: 500;
      transition: all 0.2s ease;
    }

    .btn-primary {
      background-color: var(--primary);
      border-color: var(--primary);
    }

    .btn-primary:hover {
      background-color: var(--primary-dark);
      border-color: var(--primary-dark);
    }

    .btn-outline-primary {
      color: var(--primary);
      border-color: var(--primary);
    }

    .btn-outline-primary:hover {
      background-color: var(--primary);
      color: white;
    }

    /* Pagination */
    .pagination {
      margin-bottom: 0;
    }

    .page-link {
      color: var(--primary);
      border-color: var(--border-color);
      background-color: var(--bg-card);
    }

    .page-item.active .page-link {
      background-color: var(--primary);
      border-color: var(--primary);
    }

    /* Modals */
    .modal-content {
      background-color: var(--bg-card);
      border: none;
      border-radius: var(--border-radius);
    }

    .modal-header {
      border-bottom-color: var(--border-color);
      padding: 15px 20px;
    }

    .modal-footer {
      border-top-color: var(--border-color);
      padding: 15px 20px;
    }

    .modal-title {
      font-weight: 600;
      display: flex;
      align-items: center;
    }

    .modal-title i {
      margin-right: 10px;
      color: var(--primary);
    }

    /* Form Controls */
    .form-label {
      font-weight: 500;
      color: var(--text-primary);
      margin-bottom: 8px;
    }

    .form-control, .form-select {
      padding: 10px 15px;
      border-radius: var(--border-radius);
      border: 1px solid var(--border-color);
      background-color: var(--bg-input);
      color: var(--text-primary);
    }

    .form-control:focus, .form-select:focus {
      border-color: var(--primary);
      box-shadow: 0 0 0 0.25rem rgba(90, 90, 243, 0.25);
    }

    .input-group-text {
      background-color: var(--bg-hover);
      border-color: var(--border-color);
      color: var(--text-secondary);
    }

    /* Detail Items */
    .detail-item {
      margin-bottom: 15px;
      display: flex;
      border-bottom: 1px solid var(--border-color);
      padding-bottom: 10px;
    }

    .detail-label {
      font-weight: 600;
      width: 120px;
      color: var(--text-secondary);
    }

    .detail-value {
      flex: 1;
      color: var(--text-primary);
    }

    /* Alerts */
    .alert {
      border-radius: var(--border-radius);
      border: none;
    }

    .alert-success {
      background-color: rgba(16, 185, 129, 0.1);
      color: var(--success);
    }

  /* Mobile Bottom Navigation */
.mobile-nav {
  display: none;
  position: fixed;
  bottom: 0;
  left: 0;
  width: 100%;
  background-color: var(--bg-card);
  box-shadow: 0 -2px 10px rgba(0, 0, 0, 0.1);
  z-index: 1000;
  padding: 10px 0;
  justify-content: space-around;
}

.mobile-nav-item {
  display: flex;
  align-items: center;
  justify-content: center;
  color: var(--text-secondary);
  text-decoration: none;
  padding: 8px 0;
  transition: all 0.2s ease;
}

.mobile-nav-item i {
  font-size: 1.5rem;
}

.mobile-nav-item.active {
  color: var(--primary);
}

.mobile-nav-item:hover {
  color: var(--primary);
}

@media (max-width: 991.98px) {
  .mobile-nav {
    display: flex;
  }
}


    /* Responsive Styles */
    @media (max-width: 991.98px) {
      .sidebar {
        left: -260px;
      }
      
      .sidebar.active {
        left: 0;
      }
      
      .main-content {
        margin-left: 0;
      }
      
      .search-bar {
        display: none;
      }
      
      .mobile-nav {
        display: flex;
      }
      
      .page-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 15px;
      }
      
      .page-header .btn {
        align-self: flex-start;
      }
    }

    @media (max-width: 767.98px) {
      .stats-card {
        margin-bottom: 15px;
      }
      
      .action-buttons {
        flex-wrap: wrap;
      }
      
      .table {
        min-width: 650px;
      }
      
      .modal-dialog {
        margin: 0.5rem;
      }
    }

    @media (max-width: 575.98px) {
      .top-navbar {
        padding: 10px 15px;
      }
      
      .page-content {
        padding: 15px;
      }
      
      .stats-value {
        font-size: 1.25rem;
      }

      
      .mobile-nav-item i {
        margin-bottom: 0;
        font-size: 1.5rem;
      }
      
      .mobile-nav {
        padding: 15px 0;
      }
    }
  </style>
</head>
<body class="p-4">
  <div class="container">
    <h1 class="mb-4">Despesas Gerais</h1>
    <?php if ($msg): ?>
      <div class="alert alert-info"><?php echo htmlspecialchars($msg); ?></div>
    <?php endif; ?>

    <!-- Botão Nova Despesa -->
    <button class="btn btn-primary mb-3" data-bs-toggle="modal" data-bs-target="#novaDespesaModal">
      + Nova Despesa
    </button>

    <!-- Tabela de Despesas -->
    <div class="table-responsive">
      <table class="table table-bordered">
        <thead class="table-light">
          <tr>
            <th>ID</th>
            <th>Categoria</th>
            <th>Descrição</th>
            <th>Valor</th>
            <th>Data</th>
            <th>Status</th>
            <th>Ações</th>
          </tr>
        </thead>
        <tbody>
          <?php if ($despesas): foreach ($despesas as $d): ?>
            <tr>
              <td><?php echo $d['id_despesa']; ?></td>
              <td><?php echo htmlspecialchars($d['categoria']); ?></td>
              <td><?php echo htmlspecialchars($d['descricao']); ?></td>
              <td>R$ <?php echo number_format($d['valor'],2,',','.'); ?></td>
              <td><?php echo date('d/m/Y',strtotime($d['data_despesa'])); ?></td>
              <td>
                <?php if ($d['status']==='paga'): ?>
                  <span class="badge bg-success">Paga</span>
                <?php else: ?>
                  <span class="badge bg-warning">Pendente</span>
                <?php endif; ?>
              </td>
              <td>
                <button 
                  class="btn btn-sm btn-outline-secondary" 
                  data-bs-toggle="modal" 
                  data-bs-target="#editDespesaModal"
                  data-id="<?php echo $d['id_despesa']; ?>"
                  data-categoria="<?php echo htmlspecialchars($d['categoria']); ?>"
                  data-descricao="<?php echo htmlspecialchars($d['descricao']); ?>"
                  data-valor="<?php echo $d['valor']; ?>"
                  data-data="<?php echo $d['data_despesa']; ?>"
                  data-status="<?php echo $d['status']; ?>">
                  Editar
                </button>
                <button 
                  class="btn btn-sm btn-outline-danger" 
                  data-bs-toggle="modal" 
                  data-bs-target="#deleteDespesaModal"
                  data-id="<?php echo $d['id_despesa']; ?>">
                  Excluir
                </button>
              </td>
            </tr>
          <?php endforeach; else: ?>
            <tr>
              <td colspan="7" class="text-center">Nenhuma despesa cadastrada.</td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>

    <!-- Paginação -->
    <nav>
      <ul class="pagination">
        <li class="page-item <?php if($pagina_atual<=1) echo 'disabled'; ?>">
          <a class="page-link" href="?page=<?php echo $pagina_atual-1; ?>">Anterior</a>
        </li>
        <?php for($i=1;$i<=$total_paginas;$i++): ?>
          <li class="page-item <?php if($i==$pagina_atual) echo 'active'; ?>">
            <a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
          </li>
        <?php endfor; ?>
        <li class="page-item <?php if($pagina_atual>=$total_paginas) echo 'disabled'; ?>">
          <a class="page-link" href="?page=<?php echo $pagina_atual+1; ?>">Próxima</a>
        </li>
      </ul>
    </nav>
  </div>

  <!-- Modal: Nova Despesa -->
  <div class="modal fade" id="novaDespesaModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
      <form method="post" class="modal-content">
        <input type="hidden" name="action" value="create_despesa">
        <div class="modal-header">
          <h5 class="modal-title">Nova Despesa</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label>Categoria</label>
            <input name="categoria" class="form-control" required>
          </div>
          <div class="mb-3">
            <label>Descrição</label>
            <textarea name="descricao" class="form-control" required></textarea>
          </div>
          <div class="mb-3">
            <label>Valor</label>
                <input type="text" name="valor" class="form-control mask-money" required>
          </div>
          <div class="mb-3">
            <label>Data</label>
            <input type="date" name="data_despesa" class="form-control" required>
          </div>
          <div class="mb-3">
            <label>Status</label>
            <select name="status" class="form-select">
              <option value="paga">Paga</option>
              <option value="pendente">Pendente</option>
            </select>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-primary">Salvar</button>
        </div>
      </form>
    </div>
  </div>

  <!-- Modal: Editar Despesa -->
  <div class="modal fade" id="editDespesaModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
      <form method="post" class="modal-content">
        <input type="hidden" name="action"      value="update_despesa">
        <input type="hidden" name="id_despesa" id="edit-id">
        <div class="modal-header">
          <h5 class="modal-title">Editar Despesa</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label>Categoria</label>
            <input name="categoria" id="edit-categoria" class="form-control" required>
          </div>
          <div class="mb-3">
            <label>Descrição</label>
            <textarea name="descricao" id="edit-descricao" class="form-control" required></textarea>
          </div>
          <div class="mb-3">
            <label>Valor</label>
                <input type="text" name="valor" id="edit-valor" class="form-control mask-money" required>
          </div>
          <div class="mb-3">
            <label>Data</label>
            <input type="date" name="data_despesa" id="edit-data" class="form-control" required>
          </div>
          <div class="mb-3">
            <label>Status</label>
            <select name="status" id="edit-status" class="form-select">
              <option value="paga">Paga</option>
              <option value="pendente">Pendente</option>
            </select>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-primary">Atualizar</button>
        </div>
      </form>
    </div>
  </div>

  <!-- Modal: Excluir Despesa -->
  <div class="modal fade" id="deleteDespesaModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
      <form method="post" class="modal-content">
        <input type="hidden" name="action"    value="delete_despesa">
        <input type="hidden" name="id_despesa" id="delete-id">
        <div class="modal-header">
          <h5 class="modal-title">Excluir Despesa</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <p>Tem certeza que deseja excluir esta despesa?</p>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-danger">Excluir</button>
        </div>
      </form>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    // Preenche os campos do modal de edição
    var editModal = document.getElementById('editDespesaModal');
    editModal.addEventListener('show.bs.modal', function(e) {
      var btn  = e.relatedTarget;
      document.getElementById('edit-id').value         = btn.getAttribute('data-id');
      document.getElementById('edit-categoria').value  = btn.getAttribute('data-categoria');
      document.getElementById('edit-descricao').value  = btn.getAttribute('data-descricao');
      document.getElementById('edit-valor').value      = btn.getAttribute('data-valor');
      document.getElementById('edit-data').value       = btn.getAttribute('data-data');
      document.getElementById('edit-status').value     = btn.getAttribute('data-status');
    });

    // Preenche o hidden do modal de exclusão
    var delModal = document.getElementById('deleteDespesaModal');
    delModal.addEventListener('show.bs.modal', function(e) {
      var btn = e.relatedTarget;
      document.getElementById('delete-id').value = btn.getAttribute('data-id');
    });
  </script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.inputmask/5.0.8/jquery.inputmask.min.js" integrity="sha512-jI5YDo1BbTK1ZBOqVaki3P6KyHR+Y6z+4PSJVpaz6RtWLpmjHtkobaN6D+PfYZ7R6pujISiFDUFxIr05o2NnDA==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
<script>
  document.addEventListener('DOMContentLoaded', function() {
  $('.mask-money').inputmask('decimal', {
    groupSeparator: '.',
    radixPoint: ',',
    digits: 2,
    digitsOptional: false,
    autoGroup: true,
    prefix: '',           // ou 'R$ '
    rightAlign: false,
    numericInput: true,   // << ESSENCIAL para digitar da direita p/ esquerda
    placeholder: '00,00',
    removeMaskOnSubmit: true
    });

    // Quando abrir o modal de edição, reaplica a máscara após preencher o valor
    var editModal = document.getElementById('editDespesaModal');
    editModal.addEventListener('show.bs.modal', function(e) {
      setTimeout(function(){
        $('#edit-valor').trigger('input');
      }, 10);
    });
  });
</script>
<script>
document.addEventListener('DOMContentLoaded', function() {
  // Para cada campo .mask-money
  document.querySelectorAll('.mask-money').forEach(el => {
    // valor inicial
    el.value = '00,00';
    el.dataset.raw = '';  // string de dígitos (em centavos)

    el.addEventListener('keydown', function(e) {
      // permitir navegação e tab
      if (e.key === 'Tab' || e.key.startsWith('Arrow')) return;
      // só dígitos e backspace
      if (/^\d$/.test(e.key)) {
        e.preventDefault();
        this.dataset.raw += e.key;
        atualizarMask(this);
      } else if (e.key === 'Backspace') {
        e.preventDefault();
        this.dataset.raw = this.dataset.raw.slice(0, -1);
        atualizarMask(this);
      } else {
        // bloqueia todo o resto
        e.preventDefault();
      }
    });
  });

  function atualizarMask(el) {
    // parseInt retorna NaN se raw vazio → 0
    const centsVal = parseInt(el.dataset.raw, 10) || 0;
    const reais   = Math.floor(centsVal / 100);
    const cents   = centsVal % 100;
    // pad com zeros à esquerda
    let reaisStr = reais.toString().padStart(2, '0');
    let centsStr = cents.toString().padStart(2, '0');
    el.value = `${reaisStr},${centsStr}`;
  }
});
</script>

</body>
</html>
