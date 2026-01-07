<?php
/* Nome: sidebar.php | Caminho: /includes/sidebar.php */

// Determinar o nível de diretório atual
$currentFile = $_SERVER['PHP_SELF'];
$parts = explode('/', $currentFile);
$directoryDepth = count($parts) - 2; // -2 para ajustar ao padrão de diretórios

// Definir o caminho base de acordo com a profundidade do diretório
$baseUrl = '';
for ($i = 0; $i < $directoryDepth; $i++) {
    $baseUrl .= '../';
}

// Verificar se estamos na raiz
$isRoot = (basename($currentFile) == 'index.php' && $directoryDepth <= 2);
if ($isRoot) {
    $baseUrl = '';
}

// Função para verificar se o link atual está ativo
function isActive($pageName) {
    $currentPage = basename($_SERVER['PHP_SELF']);
    return ($currentPage == $pageName) ? 'active' : '';
}

// Função para verificar se um diretório está ativo
function isDirActive($dirName) {
    $currentDir = dirname($_SERVER['PHP_SELF']);
    return (strpos($currentDir, $dirName) !== false) ? 'active' : '';
}

// Obter informações do usuário logado
$nomeUsuario = isset($_SESSION['nome']) ? $_SESSION['nome'] : 'Usuário';
$cargoUsuario = isset($_SESSION['cargo']) ? $_SESSION['cargo'] : 'Administrador';
$fotoUsuario = isset($_SESSION['foto_perfil']) ? $_SESSION['foto_perfil'] : $baseUrl . 'uploads/perfil/default.png';
?>

<div class="sidebar ">
    <div class="sidebar-header d-flex flex-column flex-md-row">
        <div class="sidebar-logo">
            <i class="bi bi-hospital"></i>
            <span class="sidebar-title">Clínica</span>
        </div>
       
    </div>
    <div class="d-flex align-items-center justify-content-around">
    <button class="sidebar-toggle" id="sidebarToggle">
            <i class="bi bi-chevron-left"></i>
        </button>
        </div>
    
    <div class="sidebar-user">
        <div class="user-avatar">
            <?php if (file_exists($fotoUsuario) && $fotoUsuario != $baseUrl . 'uploads/perfil/default.png'): ?>
                <img src="<?= $fotoUsuario ?>" alt="<?= $nomeUsuario ?>">
            <?php else: ?>
                <i class="bi bi-person-circle"></i>
            <?php endif; ?>
        </div>
        <div class="user-info">
            <h6 class="user-name text-white"><?= $nomeUsuario ?></h6>
            <span class="user-role text-white"><?= $cargoUsuario ?></span>
        </div>
    </div>
    
    <div class="sidebar-menu">
        <ul>
            <li class="menu-item <?= isActive('index.php') ?>">
                <a href="<?= $baseUrl ?>index.php">
                    <i class="bi bi-speedometer2"></i>
                    <span>Dashboard</span>
                </a>
            </li>
            
            <li class="menu-header">Pacientes</li>
            
            <li class="menu-item <?= isActive('pacientes.php') ?>">
                <a href="<?= $baseUrl ?>pages/pacientes.php">
                    <i class="bi bi-people"></i>
                    <span>Listar Pacientes</span>
                </a>
            </li>
            
            <li class="menu-header">Consultas</li>
            
            <li class="menu-item <?= isActive('consulta_dia.php') ?>">
                <a href="<?= $baseUrl ?>pages/consulta_dia.php">
                    <i class="bi bi-calendar-check"></i>
                    <span>Consultas do Dia</span>
                </a>
            </li>
            
            <li class="menu-item <?= isActive('calendario.php') ?>">
                <a href="<?= $baseUrl ?>pages/calendario.php">
                    <i class="bi bi-calendar3"></i>
                    <span>Calendário</span>
                </a>
            </li>
            
            <li class="menu-header">Financeiro</li>
            
          <?php
$currentPage = basename($_SERVER['PHP_SELF']);
$currentDir = dirname($_SERVER['PHP_SELF']);
$isFinanceiroDashboard = ($currentPage === 'dashboard.php' && str_contains($currentDir, 'financeiro'));
?>

<li class="menu-item <?= $isFinanceiroDashboard ? 'active' : '' ?>">
    <a href="<?= $baseUrl ?>pages/financeiro/dashboard.php">
        <i class="bi bi-graph-up"></i>
        <span>Dashboard</span>
    </a>
</li>

            
            <li class="menu-item <?= isActive('transacoes.php') ?>">
                <a href="<?= $baseUrl ?>pages/financeiro/transacoes.php">
                    <i class="bi bi-cash-coin"></i>
                    <span>Transações</span>
                </a>
            </li>
            
            <li class="menu-item <?= isActive('servicos.php') ?>">
                <a href="<?= $baseUrl ?>pages/financeiro/servicos.php">
                    <i class="bi bi-list-check"></i>
                    <span>Serviços</span>
                </a>
            </li>
            
            <li class="menu-header">Sistema</li>
            
            <li class="menu-item <?= isActive('funcionarios.php') ?>">
                <a href="<?= $baseUrl ?>pages/funcionarios.php">
                    <i class="bi bi-person-badge"></i>
                    <span>Funcionários</span>
                </a>
            </li>
            
            <li class="menu-item <?= isActive('perfil.php') ?>">
                <a href="<?= $baseUrl ?>pages/perfil.php">
                    <i class="bi bi-person"></i>
                    <span>Meu Perfil</span>
                </a>
            </li>
            
            <li class="menu-item <?= isActive('todas_notificacoes.php') ?>">
                <a href="<?= $baseUrl ?>pages/todas_notificacoes.php">
                    <i class="bi bi-bell"></i>
                    <span>Notificações</span>
                </a>
            </li>
        </ul>
    </div>
    
    <div class="sidebar-footer">
        <a href="<?= $baseUrl ?>logout.php">
            <i class="bi bi-box-arrow-left"></i>
            <span>Sair</span>
        </a>
    </div>
</div>

<!-- Botão de toggle externo para quando a sidebar estiver fechada -->
<div class="sidebar-toggle-external" id="sidebarToggleExternal">
    <i class="bi bi-chevron-right"></i>
</div>

<style>
/* Sidebar Styles */
:root {
    --sidebar-width: 260px;
    --sidebar-collapsed-width: 70px;
    --sidebar-bg: linear-gradient(180deg, #4e73df 0%, #224abe 100%);
    --sidebar-color: #fff;
    --sidebar-hover-bg: rgba(255, 255, 255, 0.1);
    --sidebar-active-bg: rgba(255, 255, 255, 0.15);
    --sidebar-border: rgba(255, 255, 255, 0.1);
    --sidebar-icon-size: 18px;
    --sidebar-transition: all 0.3s ease;
    --sidebar-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
    --sidebar-header-height: 70px;
    --sidebar-footer-height: 50px;
}

.sidebar {
    width: var(--sidebar-width);
    height: 100vh;
    position: fixed;
    left: 0;
    top: 0;
    background: var(--sidebar-bg);
    color: var(--sidebar-color);
   font-family: Poppins, sans-serif;
    transition: var(--sidebar-transition);
    box-shadow: var(--sidebar-shadow);
    overflow-y: auto;
    scrollbar-width: thin;
    scrollbar-color: rgba(255, 255, 255, 0.2) transparent;
}

.sidebar::-webkit-scrollbar {
    width: 5px;
}

.sidebar::-webkit-scrollbar-track {
    background: transparent;
}

.sidebar::-webkit-scrollbar-thumb {
    background-color: rgba(255, 255, 255, 0.2);
    border-radius: 20px;
}

.sidebar.collapsed {
    width: var(--sidebar-collapsed-width);
}

.sidebar-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 20px;
    height: var(--sidebar-header-height);
    border-bottom: 1px solid var(--sidebar-border);
}

.sidebar-logo {
    display: flex;
    align-items: center;
}

.sidebar-logo i {
    font-size: 24px;
    margin-right: 10px;
    filter: drop-shadow(0 2px 4px rgba(0, 0, 0, 0.2));
}

.sidebar-title {
    font-size: 20px;
    font-weight: 700;
    white-space: nowrap;
    letter-spacing: 0.5px;
    text-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
}

.sidebar-toggle {
    background: rgba(255, 255, 255, 0.1);
    border: none;
    color: #fff;
    width: 32px;
    height: 32px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: var(--sidebar-transition);
    box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
}

.sidebar-toggle:hover {
    background: rgba(255, 255, 255, 0.2);
    transform: scale(1.05);
}

.sidebar-toggle i {
    font-size: 16px;
    transition: var(--sidebar-transition);
}

.sidebar-user {
    display: flex;
    align-items: center;
    padding: 20px;
    border-bottom: 1px solid var(--sidebar-border);
    background: rgba(0, 0, 0, 0.05);
}

.user-avatar {
    width: 42px;
    height: 42px;
    border-radius: 50%;
    overflow: hidden;
    margin-right: 12px;
    border: 2px solid rgba(255, 255, 255, 0.3);
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
    background-color: rgba(255, 255, 255, 0.1);
}

.user-avatar img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.user-avatar i {
    font-size: 24px;
}

.user-info {
    overflow: hidden;
}

.user-name {
    margin: 0;
    font-size: 14px;
    font-weight: 600;
    white-space: nowrap;
    text-overflow: ellipsis;
    overflow: hidden;
    max-width: 160px;
}

.user-role {
    font-size: 12px;
    opacity: 0.7;
    white-space: nowrap;
    display: block;
    text-overflow: ellipsis;
    overflow: hidden;
    max-width: 160px;
}

.sidebar-menu {
    padding: 10px 0;
    height: calc(100vh - var(--sidebar-header-height) - var(--sidebar-footer-height) - 80px);
    overflow-y: auto;
}

.sidebar-menu ul {
    list-style: none;
    padding: 0;
    margin: 0;
}

.menu-header {
    font-size: 11px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    padding: 15px 20px 5px;
    opacity: 0.7;
    font-weight: 600;
}

.menu-item {
    position: relative;
}

.menu-item a {
    display: flex;
    align-items: center;
    padding: 12px 20px;
    color: rgba(255, 255, 255, 0.8);
    text-decoration: none;
    transition: var(--sidebar-transition);
    white-space: nowrap;
    border-radius: 0 30px 30px 0;
    margin-right: 10px;
}

.menu-item a i {
    font-size: var(--sidebar-icon-size);
    min-width: 24px;
    margin-right: 10px;
    text-align: center;
    transition: var(--sidebar-transition);
}

.menu-item a:hover {
    color: #fff;
    background: var(--sidebar-hover-bg);
    transform: translateX(5px);
}

.menu-item.active a {
    color: #fff;
    background: var(--sidebar-active-bg);
    font-weight: 600;
    box-shadow: 0 5px 10px rgba(0, 0, 0, 0.1);
}

.menu-item.active a i {
    transform: scale(1.1);
}

.sidebar-footer {
    padding: 15px 20px;
    border-top: 1px solid var(--sidebar-border);
    height: var(--sidebar-footer-height);
    position: sticky;
    bottom: 0;
    background: var(--sidebar-bg);
}

.sidebar-footer a {
    display: flex;
    align-items: center;
    color: rgba(255, 255, 255, 0.8);
    text-decoration: none;
    transition: var(--sidebar-transition);
    border-radius: 30px;
    padding: 8px 15px;
}

.sidebar-footer a i {
    font-size: var(--sidebar-icon-size);
    margin-right: 10px;
}

.sidebar-footer a:hover {
    color: #fff;
    background: var(--sidebar-hover-bg);
}

/* Collapsed State */
.sidebar.collapsed .sidebar-title,
.sidebar.collapsed .user-info,
.sidebar.collapsed .menu-header,
.sidebar.collapsed .menu-item a span,
.sidebar.collapsed .sidebar-footer span {
    display: none;
}

.sidebar.collapsed .sidebar-toggle i {
    transform: rotate(180deg);
}

.sidebar.collapsed .menu-item a {
    padding: 15px 0;
    justify-content: center;
    border-radius: 0;
    margin-right: 0;
}

.sidebar.collapsed .menu-item a i {
    margin-right: 0;
    font-size: 20px;
}

.sidebar.collapsed .sidebar-footer a {
    justify-content: center;
    padding: 8px 0;
}

.sidebar.collapsed .sidebar-footer a i {
    margin-right: 0;
}

.sidebar.collapsed .user-avatar {
    margin-right: 0;
    margin: 0 auto;
}

/* Botão de toggle externo - MELHORADO */
.sidebar-toggle-external {
    position: fixed;
    left: 0;
    top: 20px;
    width: 36px;
    height: 36px;
    background: #4e73df;
    color: white;
    border-radius: 0 8px 8px 0;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    z-index: 999;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
    transition: var(--sidebar-transition);
    opacity: 0;
    visibility: hidden;
    transform: translateX(-100%);
    border: 2px solid rgba(255, 255, 255, 0.3);
}

.sidebar-toggle-external:hover {
    background: #3a5ccc;
    width: 42px;
}

.sidebar-toggle-external i {
    font-size: 18px;
}

/* Mostrar botão externo apenas quando a sidebar estiver colapsada */
body:has(.sidebar.collapsed) .sidebar-toggle-external {
    opacity: 1;
    visibility: visible;
    transform: translateX(0);
}

/* Mobile Styles */
@media (max-width: 991.98px) {
    .sidebar {
        transform: translateX(-100%);
        box-shadow: none;
    }
    
    .sidebar.mobile-open {
        transform: translateX(0);
        box-shadow: var(--sidebar-shadow);
    }
    
    /* Mostrar botão externo no mobile quando a sidebar estiver fechada */
    .sidebar-toggle-external {
        opacity: 1 !important;
        visibility: visible !important;
        transform: translateX(0) !important;
        top: 80px;
    }
    
    body:has(.sidebar.mobile-open) .sidebar-toggle-external {
        opacity: 0 !important;
        visibility: hidden !important;
    }
}

/* Content Wrapper */
.content-wrapper {
    margin-left: var(--sidebar-width);
    width: calc(100% - var(--sidebar-width));
    transition: var(--sidebar-transition);
}

body:has(.sidebar.collapsed) .content-wrapper {
    margin-left: var(--sidebar-collapsed-width);
    width: calc(100% - var(--sidebar-collapsed-width));
}

@media (max-width: 991.98px) {
    .content-wrapper {
        margin-left: 0;
        width: 100%;
    }
    
    body:has(.sidebar.mobile-open) .content-wrapper {
        filter: blur(3px);
        pointer-events: none;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const sidebar = document.querySelector('.sidebar');
    const sidebarToggle = document.getElementById('sidebarToggle');
    const sidebarToggleExternal = document.getElementById('sidebarToggleExternal');
    const mobileToggle = document.getElementById('mobileToggle');
    
    // Verificar se há um estado salvo no localStorage
    const savedState = localStorage.getItem('sidebarState');
    if (savedState === 'collapsed') {
        sidebar.classList.add('collapsed');
        if (sidebarToggle.querySelector('i')) {
            sidebarToggle.querySelector('i').classList.replace('bi-chevron-left', 'bi-chevron-right');
        }
    }
    
    // Toggle sidebar interno
    sidebarToggle.addEventListener('click', function() {
        toggleSidebar();
    });
    
    // Toggle sidebar externo (quando colapsado)
    sidebarToggleExternal.addEventListener('click', function() {
        toggleSidebar();
    });
    
    // Função para alternar o estado da sidebar
    function toggleSidebar() {
        sidebar.classList.toggle('collapsed');
        
        // Salvar estado no localStorage
        if (sidebar.classList.contains('collapsed')) {
            localStorage.setItem('sidebarState', 'collapsed');
        } else {
            localStorage.setItem('sidebarState', 'expanded');
        }
        
        // Alternar ícone do botão interno
        const icon = sidebarToggle.querySelector('i');
        if (icon) {
            if (icon.classList.contains('bi-chevron-left')) {
                icon.classList.replace('bi-chevron-left', 'bi-chevron-right');
            } else {
                icon.classList.replace('bi-chevron-right', 'bi-chevron-left');
            }
        }
    }
    
    // Mobile toggle
    if (mobileToggle) {
        mobileToggle.addEventListener('click', function() {
            sidebar.classList.toggle('mobile-open');
        });
    }
    
    // Fechar sidebar no mobile ao clicar fora
    document.addEventListener('click', function(e) {
        if (window.innerWidth <= 991.98 && 
            sidebar.classList.contains('mobile-open') && 
            !sidebar.contains(e.target) && 
            !e.target.closest('#mobileToggle')) {
            sidebar.classList.remove('mobile-open');
        }
    });
    
    // Ajustar ao redimensionar a janela
    window.addEventListener('resize', function() {
        if (window.innerWidth > 991.98) {
            sidebar.classList.remove('mobile-open');
        }
    });
});
</script>