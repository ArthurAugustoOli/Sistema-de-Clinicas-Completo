<?php
/* Nome: topbar.php | Caminho: /includes/topbar.php */

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

// Obter informações do usuário logado
$nomeUsuario = isset($_SESSION['nome']) ? $_SESSION['nome'] : 'Usuário';
$cargoUsuario = isset($_SESSION['cargo']) ? $_SESSION['cargo'] : 'Administrador';
$fotoUsuario = isset($_SESSION['foto_perfil']) ? $_SESSION['foto_perfil'] : $baseUrl . 'uploads/perfil/default.png';

$paginaAtual = basename($_SERVER['PHP_SELF'], '.php');

// Mapeie para o título que você quer
$titulosPaginas = [
  'index'          => 'Dashboard',
  'pacientes'      => 'Pacientes',
  'consulta_dia'   => 'Consultas do Dia',
  'calendario'     => 'Calendário',
  'dashboard'      => 'Dashboard Financeiro',
  'transacoes'     => 'Transações',
  'servicos'       => 'Serviços',
  'funcionarios'   => 'Funcionários',
  'perfil'         => 'Meu Perfil',
  'todas_notificacoes' => 'Notificações'
];

// Se existir um título pré-definido ($titulo) na página que incluiu, tenha preferência
if (isset($titulo) && is_string($titulo)) {
  $tituloPagina = $titulo;
}
// Senão, pegue do mapa
elseif (isset($titulosPaginas[$paginaAtual])) {
  $tituloPagina = $titulosPaginas[$paginaAtual];
}
else {
  $tituloPagina = 'Dashboard'; // fallback
}


?>



<div class="topbar mb-5 ">
    <div class="topbar-left">
      
        <div class="page-title">
            <h4><?= $tituloPagina ?></h4>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="<?= $baseUrl ?>index.php">Home</a></li>
                    <?php if ($paginaAtual != 'index'): ?>
                    <li class="breadcrumb-item active" aria-current="page"><?= $tituloPagina ?></li>
                    <?php endif; ?>
                </ol>
            </nav>
        </div>
    </div>

    <div class="topbar-right">
       

        <div class="topbar-actions">
            <!-- Botão de Chat -->
            <div class="action-item">
                <button class="action-btn" data-bs-toggle="modal" data-bs-target="#chatModalWindow">
                    <i class="bi bi-chat-dots"></i>
                    <span class="badge chat-badge d-none">0</span>
                </button>
            </div>

            <!-- Notificações -->
            <div class="action-item">
                <?php include $baseUrl . 'includes/notificacoes.php'; ?>
            </div>

            <!-- Dropdown do Usuário -->
            <div class="action-item user-dropdown">
                <button class="user-toggle" id="userDropdownToggle">
                    <div class="user-avatar">
                        <?php if (file_exists($fotoUsuario) && $fotoUsuario != $baseUrl . 'uploads/perfil/default.png'): ?>
                        <img src="<?= $fotoUsuario ?>" alt="<?= $nomeUsuario ?>">
                        <?php else: ?>
                        <i class="bi bi-person-circle"></i>
                        <?php endif; ?>
                    </div>
                    <div class="user-info">
                        <span class="user-name"><?= $nomeUsuario ?></span>
                        <span class="user-role"><?= $cargoUsuario ?></span>
                    </div>
                    <i class="bi bi-chevron-down"></i>
                </button>

                <div class="dropdown-menu" id="userDropdownMenu">
                    <div class="dropdown-header">
                        <div class="user-avatar large">
                            <?php if (file_exists($fotoUsuario) && $fotoUsuario != $baseUrl . 'uploads/perfil/default.png'): ?>
                            <img src="<?= $fotoUsuario ?>" alt="<?= $nomeUsuario ?>">
                            <?php else: ?>
                            <i class="bi bi-person-circle"></i>
                            <?php endif; ?>
                        </div>
                        <div class="user-details">
                            <h6><?= $nomeUsuario ?></h6>
                            <span><?= $cargoUsuario ?></span>
                        </div>
                    </div>

                    <div class="dropdown-body">
                        <a href="<?= $baseUrl ?>pages/perfil.php" class="dropdown-item">
                            <i class="bi bi-person"></i>
                            <span>Meu Perfil</span>
                        </a>
                    
                    </div>

                    <div class="dropdown-footer">
                        <a href="<?= $baseUrl ?>logout.php" class="dropdown-item">
                            <i class="bi bi-box-arrow-right"></i>
                            <span>Sair</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Incluir o modal de chat -->
<?php include $baseUrl . 'includes/chat.php'; ?>

<style>
/* Topbar Styles */
:root {
    --topbar-height: 70px;
    --topbar-bg: #fff;
    --topbar-color: #333;
    --topbar-border: rgba(0, 0, 0, 0.05);
    --topbar-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
    --topbar-transition: all 0.3s ease;
    --primary-color: #4e73df;
    --primary-hover: #3a5ccc;
    --sidebar-width: 260px;
    --sidebar-collapsed-width: 70px;
}

.topbar {
    height: var(--topbar-height);
    background-color: var(--topbar-bg);
    box-shadow: var(--topbar-shadow);
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0 25px;
    position: fixed;
    top: 0;
    right: 0;
    left: var(--sidebar-width);
    z-index: 999;
    transition: var(--topbar-transition);
}

body:has(.sidebar.collapsed) .topbar {
    left: var(--sidebar-collapsed-width);
}

.topbar-left {
    display: flex;
    align-items: center;
}

.mobile-toggle {
    display: none;
    background: none;
    border: none;
    color: var(--primary-color);
    font-size: 24px;
    cursor: pointer;
    margin-right: 15px;
    width: 40px;
    height: 40px;
    border-radius: 50%;
    transition: var(--topbar-transition);
}

.mobile-toggle:hover {
    background-color: rgba(78, 115, 223, 0.1);
}

.page-title h4 {
    margin: 0;
    font-size: 20px;
    font-weight: 600;
    color: #333;
    font-family: Poppins, sans-serif;
}

.breadcrumb {
    margin: 0;
    padding: 0;
    background: none;
    font-size: 12px;
}

.breadcrumb-item a {
    color: var(--primary-color);
    text-decoration: none;
    transition: var(--topbar-transition);
}

.breadcrumb-item a:hover {
    color: var(--primary-hover);
    text-decoration: underline;
}

.breadcrumb-item.active {
    color: #6c757d;
}

.topbar-right {
    display: flex;
    align-items: center;
}

.topbar-search {
    margin-right: 20px;
}

.search-input {
    position: relative;
    width: 250px;
}

.search-input i {
    position: absolute;
    left: 15px;
    top: 50%;
    transform: translateY(-50%);
    color: #6c757d;
    font-size: 14px;
}

.search-input input {
    width: 100%;
    height: 40px;
    padding: 0 15px 0 40px;
    border: 1px solid #e0e0e0;
    border-radius: 20px;
    background-color: #f8f9fa;
    transition: var(--topbar-transition);
    font-size: 14px;
}

.search-input input:focus {
    outline: none;
    border-color: var(--primary-color);
    background-color: #fff;
    box-shadow: 0 0 0 3px rgba(78, 115, 223, 0.1);
}

.search-input input::placeholder {
    color: #adb5bd;
}

.topbar-actions {
    display: flex;
    align-items: center;
}

.action-item {
    position: relative;
    margin-left: 10px;
}

.action-btn {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    background: none;
    border: none;
    color: #6c757d;
    font-size: 20px;
    cursor: pointer;
    transition: var(--topbar-transition);
}

.action-btn:hover {
    background-color: rgba(78, 115, 223, 0.1);
    color: var(--primary-color);
}

.badge {
    position: absolute;
    top: 0;
    right: 0;
    width: 18px;
    height: 18px;
    border-radius: 50%;
    background-color: #e74a3b;
    color: #fff;
    font-size: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
    box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
}

.user-dropdown {
    position: relative;
}

.user-toggle {
    display: flex;
    align-items: center;
    background: none;
    border: none;
    padding: 0 10px;
    cursor: pointer;
    transition: var(--topbar-transition);
    border-radius: 30px;
}

.user-toggle:hover {
    background-color: rgba(78, 115, 223, 0.05);
}

.user-avatar {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    overflow: hidden;
    background-color: #e0e0e0;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-right: 10px;
    border: 2px solid rgba(78, 115, 223, 0.2);
    box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
}

.user-avatar img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.user-avatar i {
    font-size: 24px;
    color: #6c757d;
}

.user-avatar.large {
    width: 60px;
    height: 60px;
}

.user-avatar.large i {
    font-size: 36px;
}

.user-info {
    display: flex;
    flex-direction: column;
    text-align: left;
    margin-right: 10px;
}

.user-name {
    font-size: 14px;
    font-weight: 600;
    color: #333;
}

.user-role {
    font-size: 12px;
    color: #6c757d;
}

.dropdown-menu {
    position: absolute;
    top: 100%;
    right: 0;
    width: 280px;
    background-color: #fff;
    border-radius: 10px;
    box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
    margin-top: 10px;
    opacity: 0;
    visibility: hidden;
    transform: translateY(10px);
    transition: var(--topbar-transition);
    z-index: 1000;
    border: 1px solid rgba(0, 0, 0, 0.05);
    overflow: hidden;
}

.dropdown-menu.show {
    opacity: 1;
    visibility: visible;
    transform: translateY(0);
}

.dropdown-header {
    padding: 15px;
    display: flex;
    align-items: center;
    border-bottom: 1px solid #f0f0f0;
    background-color: #f8f9fa;
}

.user-details {
    margin-left: 15px;
}

.user-details h6 {
    margin: 0;
    font-size: 16px;
    font-weight: 600;
}

.user-details span {
    font-size: 12px;
    color: #6c757d;
}

.dropdown-body {
    padding: 10px 0;
}

.dropdown-item {
    display: flex;
    align-items: center;
    padding: 10px 15px;
    color: #333;
    text-decoration: none;
    transition: var(--topbar-transition);
}

.dropdown-item:hover {
    background-color: #f8f9fa;
    color: var(--primary-color);
}

.dropdown-item i {
    font-size: 18px;
    margin-right: 10px;
    width: 20px;
    text-align: center;
    color: #6c757d;
}

.dropdown-item:hover i {
    color: var(--primary-color);
}

.dropdown-footer {
    padding: 10px 0;
    border-top: 1px solid #f0f0f0;
    background-color: #f8f9fa;
}

/* Mobile Styles */
@media (max-width: 991.98px) {
    .topbar {
        left: 0;
    }

    .mobile-toggle {
        display: block;
    }

    .topbar-search {
        display: none;
    }

    .user-info {
        display: none;
    }
    
    .page-title h4 {
        font-size: 18px;
    }
    
    .breadcrumb {
        display: none;
    }
}

@media (max-width: 575.98px) {
    .topbar {
        padding: 0 15px;
    }
    
    .action-item {
        margin-left: 5px;
    }
    
    .action-btn {
        width: 36px;
        height: 36px;
        font-size: 18px;
    }
}
@media (max-width: 991.98px) {
    /* Força a topbar a ficar alinhada à esquerda, mesmo se a sidebar estiver colapsada */
    .topbar,
    body:has(.sidebar.collapsed) .topbar {
        left: 0;
    }
    /* Outras regras para mobile... */
    .mobile-toggle {
        display: block;
    }
    .topbar-search {
        display: none;
    }
    .user-info {
        display: none;
    }
    .page-title h4 {
        font-size: 18px;
        
    }
    .breadcrumb {
        display: none;
    }
}

</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const userDropdownToggle = document.getElementById('userDropdownToggle');
    const userDropdownMenu = document.getElementById('userDropdownMenu');

    // Toggle user dropdown
    userDropdownToggle.addEventListener('click', function(e) {
        e.stopPropagation();
        userDropdownMenu.classList.toggle('show');
    });

    // Close dropdown when clicking outside
    document.addEventListener('click', function(e) {
        if (!userDropdownMenu.contains(e.target) && !userDropdownToggle.contains(e.target)) {
            userDropdownMenu.classList.remove('show');
        }
    });

    // Verificar mensagens não lidas periodicamente
    function verificarMensagensNaoLidas() {
        fetch('<?= $baseUrl ?>api/chat/check_unread.php')
            .then(response => response.json())
            .then(data => {
                const chatBadge = document.querySelector('.chat-badge');
                if (data.count > 0) {
                    chatBadge.textContent = data.count;
                    chatBadge.classList.remove('d-none');
                } else {
                    chatBadge.classList.add('d-none');
                }
            })
            .catch(error => console.error('Erro ao verificar mensagens não lidas:', error));
    }

    // Verificar a cada 30 segundos
    setInterval(verificarMensagensNaoLidas, 30000);

    // Verificar ao carregar a página
    verificarMensagensNaoLidas();
    
    // Pesquisa global
    const globalSearch = document.getElementById('globalSearch');
    if (globalSearch) {
        globalSearch.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                const searchTerm = this.value.trim();
                if (searchTerm) {
                    window.location.href = '<?= $baseUrl ?>pages/pesquisa.php?q=' + encodeURIComponent(searchTerm);
                }
            }
        });
    }
});
</script>