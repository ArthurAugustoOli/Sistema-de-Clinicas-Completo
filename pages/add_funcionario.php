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

// Verificar se é edição
$isEdit = false;
$funcionarioId = 0;

if (isset($_GET['id']) && !empty($_GET['id'])) {
    $funcionarioId = intval($_GET['id']);
    $isEdit = true;

    // Buscar dados do funcionário
    $sql = "SELECT * FROM funcionarios WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $funcionarioId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        // Funcionário não encontrado, redirecionar
        header("Location: funcionarios.php");
        exit;
    }

    $funcionario = $result->fetch_assoc();
}

// Título da página
$titulo = $isEdit ? "Editar Funcionário" : "Novo Funcionário";
?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $titulo ?> - Sistema de Gerenciamento de Clínica</title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <!-- Estilos personalizados -->
    <link rel="stylesheet" href="../css/styles.css">
    <link rel="stylesheet" href="../css/responsive.css">
    <style>
        .soberba{
            margin-top: 50px !important;
        }
    </style>
</head>

<body>

    <div class="d-flex">
        <!-- Sidebar -->
        <div class="d-none d-md-block">
            <!-- Sidebar (visível apenas em desktop) -->
            <?php include '../includes/sidebar.php' ?>
        </div>

        <!-- Conteúdo Principal -->
        <div class="col-lg-10 col-md-9 ms-auto px-0">
            <!-- Topbar -->
            <div class="mb-5">
                <?php include '../includes/topbar.php' ?>
            </div>
            
                <!-- Conteúdo da Página -->
                <div class="container-fluid px-4 mt-5 soberba">
                    <!-- Header com título e ações -->
                    <div class="row mb-4 align-items-center fade-in mt-5">
                        <div class="col-md-8">
                            <h2 class="mb-0 mt-5"><?= $titulo ?></h2>
                            <p class="text-muted mb-0"><?= $isEdit ? "Atualize os dados do funcionário" : "Cadastre um novo funcionário no sistema" ?></p>
                        </div>
                        <div class="col-md-4 text-md-end mt-3">
                            <a href="funcionarios.php" class="btn btn-outline-secondary ">
                                <i class="bi bi-arrow-left"></i> Voltar
                            </a>
                        </div>
                    </div>

                    <!-- Formulário -->
                    <div class="row mb-4 slide-in-up" style="animation-delay: 0.1s;">
                        <div class="col-12">
                            <div class="card border-0 shadow-sm">
                                <div class="card-body p-4">
                                    <form id="funcionarioForm" class="row g-3">
                                        <?php if ($isEdit): ?>
                                            <input type="hidden" id="id" name="id" value="<?= $funcionarioId ?>">
                                            <input type="hidden" id="action" name="action" value="update">
                                        <?php else: ?>
                                            <input type="hidden" id="action" name="action" value="create">
                                        <?php endif; ?>

                                        <div class="col-md-6">
                                            <label for="nome" class="form-label">Nome Completo <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control" id="nome" name="nome" required
                                                value="<?= $isEdit ? htmlspecialchars($funcionario['nome']) : '' ?>">
                                        </div>

                                        <div class="col-md-6">
                                            <label for="cpf" class="form-label">CPF <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control" id="cpf" name="cpf" required
                                                value="<?= $isEdit ? htmlspecialchars($funcionario['cpf']) : '' ?>"
                                                <?= $isEdit ? 'readonly' : '' ?>>
                                            <div class="form-text">Formato: 000.000.000-00</div>
                                        </div>

                                        <div class="col-md-6">
                                            <label for="cargo" class="form-label">Cargo <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control" id="cargo" name="cargo" required
                                                value="<?= $isEdit ? htmlspecialchars($funcionario['cargo']) : '' ?>"
                                                list="cargos-list">
                                            <datalist id="cargos-list">
                                                <option value="Médico(a)">
                                                <option value="Enfermeiro(a)">
                                                <option value="Recepcionista">
                                                <option value="Administrador(a)">
                                                <option value="Fisioterapeuta">
                                                <option value="Psicólogo(a)">
                                                <option value="Nutricionista">
                                            </datalist>
                                        </div>

                                        <div class="col-md-6">
                                            <label for="turno" class="form-label">Turno</label>
                                            <select class="form-select" id="turno" name="turno">
                                                <option value="">Selecione</option>
                                                <option value="Manhã" <?= $isEdit && isset($funcionario['turno']) && $funcionario['turno'] === 'Manhã' ? 'selected' : '' ?>>Manhã</option>
                                                <option value="Tarde" <?= $isEdit && isset($funcionario['turno']) && $funcionario['turno'] === 'Tarde' ? 'selected' : '' ?>>Tarde</option>
                                                <option value="Noite" <?= $isEdit && isset($funcionario['turno']) && $funcionario['turno'] === 'Noite' ? 'selected' : '' ?>>Noite</option>
                                                <option value="Integral" <?= $isEdit && isset($funcionario['turno']) && $funcionario['turno'] === 'Integral' ? 'selected' : '' ?>>Integral</option>
                                            </select>
                                        </div>

                                        <div class="col-md-6">
                                            <label for="telefone" class="form-label">Telefone <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control" id="telefone" name="telefone" required
                                                value="<?= $isEdit ? htmlspecialchars($funcionario['telefone']) : '' ?>">
                                            <div class="form-text">Formato: (00) 00000-0000</div>
                                        </div>

                                        <div class="col-md-6">
                                            <label for="whatsapp" class="form-label">WhatsApp</label>
                                            <input type="text" class="form-control" id="whatsapp" name="whatsapp"
                                                value="<?= $isEdit ? htmlspecialchars($funcionario['whatsapp']) : '' ?>">
                                            <div class="form-text">Formato: (00) 00000-0000</div>
                                        </div>

                                        <div class="col-md-6">
                                            <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
                                            <input type="email" class="form-control" id="email" name="email" required
                                                value="<?= $isEdit ? htmlspecialchars($funcionario['email']) : '' ?>">
                                        </div>

                                        <div class="col-md-6">
                                            <label for="data_nascimento" class="form-label">Data de Nascimento</label>
                                            <input type="date" class="form-control" id="data_nascimento" name="data_nascimento"
                                                value="<?= $isEdit && isset($funcionario['data_nasc']) ? htmlspecialchars($funcionario['data_nasc']) : '' ?>">
                                        </div>

                                        <div class="col-md-12">
                                            <label for="horario_trabalho" class="form-label">Horário de Trabalho</label>
                                            <input type="text" class="form-control" id="horario_trabalho" name="horario_trabalho"
                                                value="<?= $isEdit && isset($funcionario['horario_trabalho']) ? htmlspecialchars($funcionario['horario_trabalho']) : '' ?>"
                                                placeholder="Ex: Segunda a Sexta, 08:00 às 17:00">
                                        </div>

                                        <div class="col-12 mt-4">
                                            <hr>
                                            <div class="d-flex justify-content-between">
                                                <button type="button" class="btn btn-outline-secondary" onclick="window.location.href='funcionarios.php'">Cancelar</button>
                                                <button type="submit" class="btn btn-primary" id="salvarBtn">
                                                    <i class="bi bi-save"></i> <?= $isEdit ? 'Atualizar' : 'Salvar' ?>
                                                </button>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            
        </div>
    </div>
    <!-- Incluir Mobile Nav -->
    <?php include '../includes/mobile-nav.php'; ?>
    <!-- Bootstrap JS e dependências -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>

    <!-- jQuery Mask Plugin -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.mask/1.14.16/jquery.mask.min.js"></script>

    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <!-- Script principal -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Aplicar máscaras
            $('#cpf').mask('000.000.000-00');
            $('#telefone').mask('(00) 00000-0000');
            $('#whatsapp').mask('(00) 00000-0000');

            // Manipular envio do formulário
            const form = document.getElementById('funcionarioForm');

            form.addEventListener('submit', function(e) {
                e.preventDefault();

                // Validar formulário
                if (!form.checkValidity()) {
                    form.reportValidity();
                    return;
                }

                // Mostrar indicador de carregamento
                const salvarBtn = document.getElementById('salvarBtn');
                const btnText = salvarBtn.innerHTML;
                salvarBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Processando...';
                salvarBtn.disabled = true;

                // Preparar dados
                const formData = new FormData(form);

                // Enviar requisição
                fetch('../api/funcionarios/gerenciar.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        // Restaurar botão
                        salvarBtn.innerHTML = btnText;
                        salvarBtn.disabled = false;

                        if (data.success) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Sucesso!',
                                text: data.message,
                                confirmButtonColor: '#4e73df'
                            }).then(() => {
                                // Redirecionar para a lista de funcionários
                                window.location.href = 'funcionarios.php';
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Erro!',
                                text: data.message || 'Ocorreu um erro ao processar a solicitação.',
                                confirmButtonColor: '#4e73df'
                            });
                        }
                    })
                    .catch(error => {
                        // Restaurar botão
                        salvarBtn.innerHTML = btnText;
                        salvarBtn.disabled = false;

                        console.error('Erro:', error);
                        Swal.fire({
                            icon: 'error',
                            title: 'Erro!',
                            text: 'Ocorreu um erro ao processar a solicitação.',
                            confirmButtonColor: '#4e73df'
                        });
                    });
            });
        });
    </script>

    <style>
       .form-select {
  font-size: 20px;
  padding: 4px 8px;
 
}

.form-select option {
  font-size: 12px;
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
    </style>
</body>

</html>