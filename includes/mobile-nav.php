<?php
/* Nome: mobile-nav.php | Caminho: /includes/mobile-nav.php */

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
function isNavActive($pageName) {
    $currentPage = basename($_SERVER['PHP_SELF']);
    return ($currentPage == $pageName) ? 'active' : '';
}
?>

<div class="mobile-bottom-nav d-lg-none">
    <a href="<?= $baseUrl ?>index.php" class="mobile-bottom-nav-item <?= isNavActive('index.php') ?>">
        <i class="bi bi-speedometer2"></i>
        <span>Dashboard</span>
    </a>
    <a href="<?= $baseUrl ?>pages/pacientes.php" class="mobile-bottom-nav-item <?= isNavActive('pacientes.php') ?>">
        <i class="bi bi-people"></i>
        <span>Pacientes</span>
    </a>
    <a href="<?= $baseUrl ?>pages/consulta_dia.php" class="mobile-bottom-nav-item <?= isNavActive('consulta_dia.php') ?>">
        <i class="bi bi-calendar-check"></i>
        <span>Consultas</span>
    </a>
    <a href="<?= $baseUrl ?>pages/financeiro/dashboard.php" class="mobile-bottom-nav-item <?= isNavActive('dashboard.php') ?>">
        <i class="bi bi-cash-coin"></i>
        <span>Financeiro</span>
    </a>
    <a href="#" class="mobile-bottom-nav-item" id="moreMenuToggle">
        <i class="bi bi-three-dots"></i>
        <span>Mais</span>
    </a>
</div>

<!-- Menu "Mais" para dispositivos móveis -->
<div class="modal fade" id="moreMenuModal" tabindex="-1" aria-labelledby="moreMenuModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="moreMenuModalLabel">Menu</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-0">
                <div class="list-group list-group-flush">
                    <a href="<?= $baseUrl ?>pages/calendario.php" class="list-group-item list-group-item-action d-flex align-items-center">
                        <i class="bi bi-calendar3 me-3 text-primary"></i>
                        <span>Calendário</span>
                    </a>
                    <a href="<?= $baseUrl ?>pages/financeiro/transacoes.php" class="list-group-item list-group-item-action d-flex align-items-center">
                        <i class="bi bi-cash-coin me-3 text-success"></i>
                        <span>Transações</span>
                    </a>
                    <a href="<?= $baseUrl ?>pages/financeiro/servicos.php" class="list-group-item list-group-item-action d-flex align-items-center">
                        <i class="bi bi-list-check me-3 text-info"></i>
                        <span>Serviços</span>
                    </a>
                    <a href="<?= $baseUrl ?>pages/funcionarios.php" class="list-group-item list-group-item-action d-flex align-items-center">
                        <i class="bi bi-person-badge me-3 text-warning"></i>
                        <span>Funcionários</span>
                    </a>
                    <a href="<?= $baseUrl ?>pages/perfil.php" class="list-group-item list-group-item-action d-flex align-items-center">
                        <i class="bi bi-person me-3 text-danger"></i>
                        <span>Meu Perfil</span>
                    </a>
                    <a href="<?= $baseUrl ?>pages/todas_notificacoes.php" class="list-group-item list-group-item-action d-flex align-items-center">
                        <i class="bi bi-bell me-3 text-secondary"></i>
                        <span>Notificações</span>
                    </a>
                    <a href="<?= $baseUrl ?>logout.php" class="list-group-item list-group-item-action d-flex align-items-center">
                        <i class="bi bi-box-arrow-left me-3 text-dark"></i>
                        <span>Sair</span>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>


<script>
document.addEventListener('DOMContentLoaded', function() {
    // Adicionar classe ao body para ajustar o padding quando a navegação móvel estiver presente
    document.body.classList.add('has-mobile-nav');
    
    // Configurar o modal do menu "Mais"
    const moreMenuToggle = document.getElementById('moreMenuToggle');
    if (moreMenuToggle) {
        moreMenuToggle.addEventListener('click', function(e) {
            e.preventDefault();
            const moreMenuModal = new bootstrap.Modal(document.getElementById('moreMenuModal'));
            moreMenuModal.show();
        });
    }
    
    // Implementar pull-to-refresh para recarregar a página
    let touchStartY = 0;
    let touchEndY = 0;
    
    // Criar o indicador de pull-to-refresh
    const ptrIndicator = document.createElement('div');
    ptrIndicator.className = 'ptr-indicator';
    ptrIndicator.innerHTML = '<div class="ptr-spinner"></div>';
    document.body.appendChild(ptrIndicator);
    
    document.addEventListener('touchstart', function(e) {
        touchStartY = e.touches[0].clientY;
    }, false);
    
    document.addEventListener('touchmove', function(e) {
        touchEndY = e.touches[0].clientY;
        const distance = touchEndY - touchStartY;
        
        // Se o usuário estiver no topo da página e puxar para baixo
        if (window.scrollY === 0 && distance > 0 && distance < 100) {
            ptrIndicator.style.transform = `translateY(${distance - 50}px)`;
            ptrIndicator.classList.add('visible');
            e.preventDefault();
        }
    }, { passive: false });
    
    document.addEventListener('touchend', function(e) {
        const distance = touchEndY - touchStartY;
        
        // Se o usuário puxou o suficiente para recarregar
        if (window.scrollY === 0 && distance > 70) {
            ptrIndicator.style.transform = 'translateY(0)';
            
            // Recarregar a página após um pequeno atraso
            setTimeout(function() {
                window.location.reload();
            }, 1000);
        } else {
            ptrIndicator.style.transform = 'translateY(-100%)';
            ptrIndicator.classList.remove('visible');
        }
        
        touchStartY = 0;
        touchEndY = 0;
    }, false);
});
</script>

<style>
    .mobile-bottom-nav {
  z-index: 0 !important;
  
}

</style>

