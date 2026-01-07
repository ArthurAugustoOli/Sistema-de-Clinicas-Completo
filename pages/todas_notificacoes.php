<?php
/* Nome: todas_notificacoes.php | Caminho: /pages/todas_notificacoes.php */

// Iniciar sessão
session_start();

// Verificar se o usuário está logado
if (!isset($_SESSION['logado']) || $_SESSION['logado'] !== true) {
    header("Location: ../login.php");
    exit;
}

// Incluir configuração do banco de dados
require_once '../config/config.php';

// Função para obter todas as notificações do usuário
function getTodasNotificacoes()
{
    global $conn;
    $notificacoes = [];
    $usuario_id = $_SESSION['id'];

    // Buscar notificações para todos os usuários ou específicas para este usuário
    $sql = "SELECT n.*, u.nome as criador_nome, u.foto_perfil as criador_foto
            FROM notificacoes n 
            JOIN usuarios u ON n.criador_id = u.id 
            WHERE (n.para_todos = 1 OR EXISTS (
                SELECT 1 FROM notificacoes_usuarios nu 
                WHERE nu.notificacao_id = n.id AND nu.usuario_id = ?
            ))
            ORDER BY n.data_criacao DESC";

    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("i", $usuario_id);

        if ($stmt->execute()) {
            $result = $stmt->get_result();

            while ($row = $result->fetch_assoc()) {
                // Verificar se a notificação foi lida
                $sql_lida = "SELECT lida FROM notificacoes_usuarios 
                            WHERE notificacao_id = ? AND usuario_id = ?";
                $stmt_lida = $conn->prepare($sql_lida);

                if ($stmt_lida) {
                    $stmt_lida->bind_param("ii", $row['id'], $usuario_id);
                    $stmt_lida->execute();
                    $result_lida = $stmt_lida->get_result();

                    if ($result_lida->num_rows > 0) {
                        $lida_row = $result_lida->fetch_assoc();
                        $row['lida'] = $lida_row['lida'];
                    } else {
                        // Se não existe registro na tabela de relação, criar um
                        $sql_insert = "INSERT INTO notificacoes_usuarios (notificacao_id, usuario_id, lida) 
                                      VALUES (?, ?, 0)";
                        $stmt_insert = $conn->prepare($sql_insert);

                        if ($stmt_insert) {
                            $stmt_insert->bind_param("ii", $row['id'], $usuario_id);
                            $stmt_insert->execute();
                            $stmt_insert->close();
                        }

                        $row['lida'] = 0;
                    }

                    $stmt_lida->close();
                } else {
                    $row['lida'] = 0;
                }

                $notificacoes[] = $row;
            }
        }
        $stmt->close();
    }

    return $notificacoes;
}

// Obter todas as notificações
$notificacoes = getTodasNotificacoes();

// Definir título da página
$titulo = "Todas as Notificações";
?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Todas as Notificações - Sistema de Gerenciamento de Clínica</title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

<style>
@media (max-width: 767.98px) {
    .modal-content {
       
        height: 830px !important;
        max-height: 100% !important;
         max-width: 100% !important;
    }
    .modal-dialog { 
        height: 844px;
        width: 374.4px;
    }
#novaNotificacaoModal {
    padding: 0 !important;
}

#novaNotificacaoModal .modal-dialog {
    margin: 0 auto;
}

#novaNotificacaoModal .modal-content {
    padding: 0 !important;
}

}
</style>


    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <!-- Estilos personalizados -->
    <link rel="stylesheet" href="../css/styles.css">
    <link rel="stylesheet" href="../css/responsive.css">

    <style>
        .notification-card {
            border-radius: 0.5rem;
            border: none;
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
            margin-bottom: 1rem;
            transition: transform 0.2s;
        }
              .form-select {
  font-size: 12px;
  padding: 4px 8px;
 
}

.form-select option {
  font-size: 12px;
}

        .notification-card:hover {
            transform: translateY(-2px);
        }

        .notification-card.unread {
            border-left: 4px solid var(--primary-color);
        }

        .notification-icon {
            width: 48px;
            height: 48px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            font-size: 1.5rem;
        }

        .notification-time {
            font-size: 0.8rem;
            color: #6c757d;
        }

        .notification-creator {
            font-size: 0.8rem;
            color: #6c757d;
        }

        .notification-title {
            font-weight: 600;
            margin-bottom: 0.25rem;
        }

        .notification-message {
            margin-bottom: 0.5rem;
        }
        

.modal-backdrop {
    z-index: 900 !important;
    background-color: rgba(0, 0, 0, 0.5); /* fundo escuro com transparência */
}

.modal {
    z-index: 1050 !important;
    padding: 0px
}

 @media (max-width: 768px) {
        .modal-wide {
            width: 100% !important;
            margin: 0;
        }
       
    .modal-content {
        height:0;
    }

 }
    </style>
</head>

<body>

    <div class="d-flex">
        <!-- Sidebar -->
        <div class="d-none d-md-block">
            <?php include '../includes/sidebar.php'; ?>
        </div>
        <div class="content-wrapper">
            <!-- Topbar -->
            <?php include '../includes/topbar.php' ?>

            <!-- Conteúdo da Página -->
            <div class="container-fluid px-4 py-4 mt-5">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h2 class="mb-0">Todas as Notificações</h2>
                        <p class="text-muted mb-0">Visualize todas as suas notificações</p>
                    </div>

                    <?php if ($_SESSION['cargo'] == 'Administrador'): ?>
                        <!-- Botão alterado: remova os atributos data-bs-* e adicione um id -->
                        <button class="btn btn-primary" id="btnNovaNotificacao">
                            <i class="bi bi-plus-lg me-2"></i> Nova Notificação
                        </button>
                    <?php endif; ?>
                </div>

                <div class="row">
                    <div class="col-lg-8">
                        <?php if (count($notificacoes) > 0): ?>
                            <?php foreach ($notificacoes as $notificacao): ?>
                                <?php
                                $icon = 'info-circle';
                                $color = 'primary';

                                switch ($notificacao['tipo']) {
                                    case 'warning':
                                        $icon = 'exclamation-triangle';
                                        $color = 'warning';
                                        break;
                                    case 'success':
                                        $icon = 'check-circle';
                                        $color = 'success';
                                        break;
                                    case 'danger':
                                        $icon = 'exclamation-circle';
                                        $color = 'danger';
                                        break;
                                }
                                ?>
                                <div class="card notification-card <?= $notificacao['lida'] ? '' : 'unread' ?>" data-id="<?= $notificacao['id'] ?>">
                                    <div class="card-body">
                                        <div class="d-flex">
                                            <div class="me-3">
                                                <div class="notification-icon bg-<?= $color ?>-subtle text-<?= $color ?>">
                                                    <i class="bi bi-<?= $icon ?>"></i>
                                                </div>
                                            </div>
                                            <div class="flex-grow-1">
                                                <div class="d-flex justify-content-between align-items-start">
                                                    <h5 class="notification-title"><?= htmlspecialchars($notificacao['titulo']) ?></h5>
                                                    <?php if (!$notificacao['lida']): ?>
                                                        <span class="badge bg-primary">Nova</span>
                                                    <?php endif; ?>
                                                </div>
                                                <p class="notification-message"><?= htmlspecialchars($notificacao['mensagem']) ?></p>
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <div class="notification-time">
                                                        <i class="bi bi-clock me-1"></i>
                                                        <?= date('d/m/Y H:i', strtotime($notificacao['data_criacao'])) ?>
                                                        <?php if ($notificacao['expira_em']): ?>
                                                            <span class="ms-2">
                                                                <i class="bi bi-hourglass-split me-1"></i>
                                                                Expira em: <?= date('d/m/Y H:i', strtotime($notificacao['expira_em'])) ?>
                                                            </span>
                                                        <?php endif; ?>
                                                    </div>
                                                    <div class="notification-creator">
                                                        <img src="<?= htmlspecialchars($notificacao['criador_foto'] ?? '../uploads/perfil/default.png') ?>"
                                                             alt="<?= htmlspecialchars($notificacao['criador_nome']) ?>"
                                                             class="rounded-circle me-1"
                                                             width="20" height="20"
                                                             style="object-fit: cover;">
                                                        <?= htmlspecialchars($notificacao['criador_nome']) ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="alert alert-info">
                                <i class="bi bi-info-circle me-2"></i>
                                Você não possui notificações.
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="col-lg-4">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Filtros</h5>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <label class="form-label">Status</label>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="statusFilter" id="statusAll" value="all" checked>
                                        <label class="form-check-label" for="statusAll">
                                            Todas
                                        </label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="statusFilter" id="statusUnread" value="unread">
                                        <label class="form-check-label" for="statusUnread">
                                            Não lidas
                                        </label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="statusFilter" id="statusRead" value="read">
                                        <label class="form-check-label" for="statusRead">
                                            Lidas
                                        </label>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Tipo</label>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="typeFilter" id="typeInfo" value="info" checked>
                                        <label class="form-check-label" for="typeInfo">Informação</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="typeFilter" id="typeSuccess" value="success" checked>
                                        <label class="form-check-label" for="typeSuccess">Sucesso</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="typeFilter" id="typeWarning" value="warning" checked>
                                        <label class="form-check-label" for="typeWarning">Aviso</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="typeFilter" id="typeDanger" value="danger" checked>
                                        <label class="form-check-label" for="typeDanger">Alerta</label>
                                    </div>
                                </div>

                                <button class="btn btn-primary w-100" id="applyFilters">
                                    <i class="bi bi-funnel me-2"></i> Aplicar Filtros
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div> 
    </div>
    <!-- Modal para Nova Notificação (apenas para administradores) -->
<?php if ($_SESSION['cargo'] == 'Administrador'): ?> 
<div class="modal fade" id="novaNotificacaoModal" tabindex="-1" aria-labelledby="novaNotificacaoModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div  class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="novaNotificacaoModalLabel">Nova Notificação</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>

            <div class="modal-body">
                <form id="notificacaoForm">
                    <div class="row mb-3">
                        <div class="col-12">
                            <label for="titulo" class="form-label">Título</label>
                            <input type="text" class="form-control" id="titulo" name="titulo" required>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-12">
                            <label for="mensagem" class="form-label">Mensagem</label>
                            <textarea class="form-control" id="mensagem" name="mensagem" rows="3" required></textarea>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="tipo" class="form-label">Tipo</label>
                            <select class="form-select" id="tipo" name="tipo">
                                <option value="info">Informação</option>
                                <option value="success">Sucesso</option>
                                <option value="warning">Aviso</option>
                                <option value="danger">Alerta</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="expira_em" class="form-label">Expira em (opcional)</label>
                            <input type="datetime-local" class="form-control" id="expira_em" name="expira_em">
                        </div>
                    </div>

                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" id="para_todos" name="para_todos" checked>
                        <label class="form-check-label" for="para_todos">
                            Enviar para todos os usuários
                        </label>
                    </div>
                </form>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" id="enviarNotificacao">Enviar Notificação</button>
            </div>
        </div>
    </div>
</div>


        <!-- Incluir Mobile Nav -->
        <?php include '../includes/mobile-nav.php'; ?>
    <?php endif; ?>

    <!-- Bootstrap JS e dependências -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Marcar notificação como lida ao clicar
            document.querySelectorAll('.notification-card').forEach(card => {
                card.addEventListener('click', function() {
                    const notificacaoId = this.getAttribute('data-id');

                    if (!this.classList.contains('unread')) {
                        return;
                    }

                    fetch('../api/notificacoes/marcar_lida.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/x-www-form-urlencoded',
                            },
                            body: `notificacao_id=${notificacaoId}`
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                this.classList.remove('unread');
                                const badge = this.querySelector('.badge');
                                if (badge) {
                                    badge.remove();
                                }
                            }
                        })
                        .catch(error => console.error('Erro ao marcar notificação como lida:', error));
                });
            });

            // Aplicar filtros
            document.getElementById('applyFilters').addEventListener('click', function() {
                const statusFilter = document.querySelector('input[name="statusFilter"]:checked').value;
                const typeFilters = Array.from(document.querySelectorAll('input[name="typeFilter"]:checked')).map(input => input.value);

                document.querySelectorAll('.notification-card').forEach(card => {
                    let showByStatus = false;
                    let showByType = false;

                    // Filtrar por status
                    if (statusFilter === 'all') {
                        showByStatus = true;
                    } else if (statusFilter === 'unread' && card.classList.contains('unread')) {
                        showByStatus = true;
                    } else if (statusFilter === 'read' && !card.classList.contains('unread')) {
                        showByStatus = true;
                    }

                    // Filtrar por tipo
                    const icon = card.querySelector('.bi');
                    if (icon) {
                        if (icon.classList.contains('bi-info-circle') && typeFilters.includes('info')) {
                            showByType = true;
                        } else if (icon.classList.contains('bi-check-circle') && typeFilters.includes('success')) {
                            showByType = true;
                        } else if (icon.classList.contains('bi-exclamation-triangle') && typeFilters.includes('warning')) {
                            showByType = true;
                        } else if (icon.classList.contains('bi-exclamation-circle') && typeFilters.includes('danger')) {
                            showByType = true;
                        }
                    }

                    // Mostrar ou esconder o card
                    if (showByStatus && showByType) {
                        card.style.display = 'block';
                    } else {
                        card.style.display = 'none';
                    }
                });
            });

            // Enviar nova notificação
            const enviarNotificacaoBtn = document.getElementById('enviarNotificacao');
            if (enviarNotificacaoBtn) {
                enviarNotificacaoBtn.addEventListener('click', function() {
                    const form = document.getElementById('notificacaoForm');
                    const formData = new FormData(form);

                    // Converter para formato URL encoded
                    const urlEncoded = new URLSearchParams();
                    for (const [key, value] of formData) {
                        if (key === 'para_todos') {
                            urlEncoded.append(key, '1');
                        } else {
                            urlEncoded.append(key, value);
                        }
                    }

                    // Se para_todos não estiver marcado, adicionar com valor 0
                    if (!formData.has('para_todos')) {
                        urlEncoded.append('para_todos', '0');
                    }

                    fetch('../api/notificacoes/criar.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/x-www-form-urlencoded',
                            },
                            body: urlEncoded.toString()
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                // Fechar modal e mostrar alerta de sucesso
                                const modalInstance = bootstrap.Modal.getInstance(document.getElementById('novaNotificacaoModal'));
                                modalInstance.hide();

                                alert('Notificação enviada com sucesso!');

                                // Limpar formulário
                                form.reset();

                                // Recarregar a página para atualizar as notificações
                                window.location.reload();
                            } else {
                                alert('Erro ao enviar notificação: ' + data.message);
                            }
                        })
                        .catch(error => {
                            console.error('Erro ao enviar notificação:', error);
                            alert('Erro ao enviar notificação. Verifique o console para mais detalhes.');
                        });
                });
            }

            // Abrir o modal de "Nova Notificação" sem backdrop ao clicar no botão
const btnNovaNotificacao = document.getElementById('btnNovaNotificacao');
if (btnNovaNotificacao) {
    btnNovaNotificacao.addEventListener('click', function () {
        const modalEl = document.getElementById('novaNotificacaoModal');
        const modal = new bootstrap.Modal(modalEl);
        modal.show();
    });
}


        });
    </script>

</body>

</html>
