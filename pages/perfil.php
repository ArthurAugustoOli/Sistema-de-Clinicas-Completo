<?php
/* Nome: perfil.php | Caminho: /pages/perfil.php */

// Iniciar sessão
session_start();

// Verificar se o usuário está logado
if (!isset($_SESSION['logado']) || $_SESSION['logado'] !== true) {
    header("Location: ../login.php");
    exit;
}

// Incluir configuração do banco de dados
include '../config/config.php';

// Inicializar variáveis
$nome = $_SESSION['nome'];
$username = $_SESSION['username'];
$email = "";
$cargo = $_SESSION['cargo'];
$foto_perfil = $_SESSION['foto_perfil'] ?? '../uploads/perfil/default.png';

$nome_err = $username_err = $email_err = $current_password_err = $new_password_err = $confirm_password_err = "";
$success_msg = $error_msg = "";

// Buscar informações atuais do usuário
$sql = "SELECT email FROM usuarios WHERE id = ?";
if ($stmt = $conn->prepare($sql)) {
    $stmt->bind_param("i", $_SESSION['id']);
    if ($stmt->execute()) {
        $stmt->store_result();
        if ($stmt->num_rows == 1) {
            $stmt->bind_result($email);
            $stmt->fetch();
        }
    }
    $stmt->close();
}

// Processar atualização de perfil
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // Verificar qual formulário foi enviado
    if (isset($_POST['update_profile'])) {

        // Validar nome
        if (empty(trim($_POST['nome']))) {
            $nome_err = "Por favor, informe seu nome.";
        } else {
            $nome = trim($_POST['nome']);
        }

        // Validar username
        if (empty(trim($_POST['username']))) {
            $username_err = "Por favor, informe um nome de usuário.";
        } else {
            // Verificar se o username já existe
            $sql = "SELECT id FROM usuarios WHERE username = ? AND id != ?";
            if ($stmt = $conn->prepare($sql)) {
                $stmt->bind_param("si", $param_username, $param_id);
                $param_username = trim($_POST['username']);
                $param_id = $_SESSION['id'];

                if ($stmt->execute()) {
                    $stmt->store_result();
                    if ($stmt->num_rows > 0) {
                        $username_err = "Este nome de usuário já está em uso.";
                    } else {
                        $username = trim($_POST['username']);
                    }
                } else {
                    $error_msg = "Oops! Algo deu errado. Por favor, tente novamente mais tarde.";
                }
                $stmt->close();
            }
        }

        // Validar email
        if (empty(trim($_POST['email']))) {
            $email_err = "Por favor, informe seu email.";
        } else {
            // Verificar se o email já existe
            $sql = "SELECT id FROM usuarios WHERE email = ? AND id != ?";
            if ($stmt = $conn->prepare($sql)) {
                $stmt->bind_param("si", $param_email, $param_id);
                $param_email = trim($_POST['email']);
                $param_id = $_SESSION['id'];

                if ($stmt->execute()) {
                    $stmt->store_result();
                    if ($stmt->num_rows > 0) {
                        $email_err = "Este email já está em uso.";
                    } else {
                        $email = trim($_POST['email']);
                    }
                } else {
                    $error_msg = "Oops! Algo deu errado. Por favor, tente novamente mais tarde.";
                }
                $stmt->close();
            }
        }

        // Verificar erros antes de atualizar
        if (empty($nome_err) && empty($username_err) && empty($email_err)) {
            // Atualizar perfil
            $sql = "UPDATE usuarios SET nome = ?, username = ?, email = ? WHERE id = ?";
            if ($stmt = $conn->prepare($sql)) {
                $stmt->bind_param("sssi", $nome, $username, $email, $_SESSION['id']);

                if ($stmt->execute()) {
                    // Atualizar dados da sessão
                    $_SESSION['nome'] = $nome;
                    $_SESSION['username'] = $username;

                    $success_msg = "Perfil atualizado com sucesso!";
                } else {
                    $error_msg = "Oops! Algo deu errado. Por favor, tente novamente mais tarde.";
                }
                $stmt->close();
            }
        }
    }
    // Processar atualização de senha
    elseif (isset($_POST['update_password'])) {

        // Validar senha atual
        if (empty(trim($_POST['current_password']))) {
            $current_password_err = "Por favor, informe sua senha atual.";
        } else {
            // Verificar se a senha atual está correta
            $sql = "SELECT password FROM usuarios WHERE id = ?";
            if ($stmt = $conn->prepare($sql)) {
                $stmt->bind_param("i", $_SESSION['id']);

                if ($stmt->execute()) {
                    $stmt->store_result();
                    if ($stmt->num_rows == 1) {
                        $stmt->bind_result($hashed_password);
                        $stmt->fetch();

                        // Para fins de teste, verificamos se a senha é igual (sem hash)
                        if ($_POST['current_password'] != $hashed_password) {
                            $current_password_err = "A senha atual está incorreta.";
                        }
                    }
                } else {
                    $error_msg = "Oops! Algo deu errado. Por favor, tente novamente mais tarde.";
                }
                $stmt->close();
            }
        }

        // Validar nova senha
        if (empty(trim($_POST['new_password']))) {
            $new_password_err = "Por favor, informe a nova senha.";
        } elseif (strlen(trim($_POST['new_password'])) < 6) {
            $new_password_err = "A senha deve ter pelo menos 6 caracteres.";
        } else {
            $new_password = trim($_POST['new_password']);
        }

        // Validar confirmação de senha
        if (empty(trim($_POST['confirm_password']))) {
            $confirm_password_err = "Por favor, confirme a senha.";
        } else {
            $confirm_password = trim($_POST['confirm_password']);
            if (empty($new_password_err) && ($new_password != $confirm_password)) {
                $confirm_password_err = "As senhas não coincidem.";
            }
        }

        // Verificar erros antes de atualizar
        if (empty($current_password_err) && empty($new_password_err) && empty($confirm_password_err)) {
            // Atualizar senha
            $sql = "UPDATE usuarios SET password = ? WHERE id = ?";
            if ($stmt = $conn->prepare($sql)) {
                $stmt->bind_param("si", $new_password, $_SESSION['id']);

                if ($stmt->execute()) {
                    $success_msg = "Senha atualizada com sucesso!";
                } else {
                    $error_msg = "Oops! Algo deu errado. Por favor, tente novamente mais tarde.";
                }
                $stmt->close();
            }
        }
    }
    // Processar upload de foto
    elseif (isset($_POST['update_photo'])) {
        if (isset($_FILES['foto_perfil']) && $_FILES['foto_perfil']['error'] == 0) {
            $allowed = ['jpg', 'jpeg', 'png', 'gif'];
            $filename = $_FILES['foto_perfil']['name'];
            $filetype = pathinfo($filename, PATHINFO_EXTENSION);

            // Verificar extensão do arquivo
            if (in_array(strtolower($filetype), $allowed)) {
                // Criar diretório de upload se não existir
                $upload_dir = '../uploads/perfil/';
                if (!file_exists($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }

                // Gerar nome único para o arquivo
                $new_filename = uniqid() . '.' . $filetype;
                $upload_path = $upload_dir . $new_filename;

                // Mover arquivo para o diretório de upload
                if (move_uploaded_file($_FILES['foto_perfil']['tmp_name'], $upload_path)) {
                    // Atualizar caminho da foto no banco de dados
                    $sql = "UPDATE usuarios SET foto_perfil = ? WHERE id = ?";
                    if ($stmt = $conn->prepare($sql)) {
                        $stmt->bind_param("si", $upload_path, $_SESSION['id']);

                        if ($stmt->execute()) {
                            // Atualizar foto na sessão
                            $_SESSION['foto_perfil'] = $upload_path;
                            $foto_perfil = $upload_path;

                            $success_msg = "Foto de perfil atualizada com sucesso!";
                        } else {
                            $error_msg = "Erro ao atualizar a foto no banco de dados.";
                        }
                        $stmt->close();
                    }
                } else {
                    $error_msg = "Erro ao fazer upload da foto.";
                }
            } else {
                $error_msg = "Formato de arquivo não permitido. Apenas JPG, JPEG, PNG e GIF são aceitos.";
            }
        } else {
            $error_msg = "Erro ao fazer upload da foto. Por favor, tente novamente.";
        }
    }
}

// Definir título da página
$titulo = "Meu Perfil";
?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Meu Perfil - Sistema de Gerenciamento de Clínica</title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <!-- Estilos personalizados -->
    <link rel="stylesheet" href="../css/styles.css">
    <link rel="stylesheet" href="../css/responsive.css">

    <style>
        .profile-header {
            background: linear-gradient(to right, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 2rem 0;
            margin-bottom: 2rem;
            border-radius: 0 0 1rem 1rem;
        }

.profile-photo-container {
    position: relative;
    width: 200px;
    height: 200px;
    margin: 0 auto 1rem;
}

.profile-photo,
.profile-photo.fallback {
    width: 100%;
    height: 100%;
    border-radius: 50%;
    border: 5px solid white;
    background-color: #f1f1f1;
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
    object-fit: cover;
    display: block;
    aspect-ratio: 1 / 1;
}

.profile-photo.fallback {
    display: flex;
    align-items: center;
    justify-content: center;
}

.profile-photo.fallback i {
    font-size: 3rem;
    color: #999;
}



        .profile-photo-edit {
            position: absolute;
            bottom: 0;
              border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            box-shadow: 0 0.25rem 0.5rem rgba(0, 0, 0, 0.15);
          right: 0;
            background-color: var(--primary-color);
            color: white;
            width: 40px;
            height: 40px;
        }

        .profile-name {
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 0.25rem;
        }

        .profile-role {
            font-size: 1rem;
            opacity: 0.8;
        }

        .profile-card {
            border-radius: 1rem;
            border: none;
            box-shadow: 0 0.25rem 0.5rem rgba(0, 0, 0, 0.05);
            margin-bottom: 1.5rem;
        }

        .profile-card-header {
            background-color: #f8f9fa;
            border-bottom: 1px solid #e9ecef;
            padding: 1rem 1.5rem;
            font-weight: 600;
        }

        .profile-card-body {
            padding: 1.5rem;
        }

        .form-label {
            font-weight: 500;
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
            <div class="container-fluid mt-5">
                <!-- Header do Perfil -->
                <div class="profile-header text-center mt-5
">
                   <div class="profile-photo-container mt-5 " >
<img
    src="<?= !empty($foto_perfil) ? htmlspecialchars($foto_perfil) : 'data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///ywAAAAAAQABAAACAUwAOw==' ?>"
    class="profile-photo"
    id="profilePhotoPreview"
/>


    <div class="profile-photo-edit" data-bs-toggle="modal" data-bs-target="#photoModal">
        <i class="bi bi-camera"></i>
    </div>
</div>

                    <div class="profile-name"><?= htmlspecialchars($nome) ?></div>
                    <div class="profile-role"><?= htmlspecialchars($cargo) ?></div>
                </div>

                <div class="container">
                    <!-- Mensagens de Sucesso/Erro -->
                    <?php if (!empty($success_msg)): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <?= $success_msg ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($error_msg)): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <?= $error_msg ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>

                    <div class="row">
                        <!-- Informações do Perfil -->
                        <div class="col-lg-6">
                            <div class="card profile-card">
                                <div class="profile-card-header">
                                    <i class="bi bi-person me-2"></i> Informações Pessoais
                                </div>
                                <div class="profile-card-body">
                                    <form action="<?= htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                                        <div class="mb-3">
                                            <label for="nome" class="form-label">Nome Completo</label>
                                            <input type="text" class="form-control <?= (!empty($nome_err)) ? 'is-invalid' : ''; ?>" id="nome" name="nome" value="<?= htmlspecialchars($nome) ?>">
                                            <div class="invalid-feedback"><?= $nome_err; ?></div>
                                        </div>

                                        <div class="mb-3">
                                            <label for="username" class="form-label">Nome de Usuário</label>
                                            <input type="text" class="form-control <?= (!empty($username_err)) ? 'is-invalid' : ''; ?>" id="username" name="username" value="<?= htmlspecialchars($username) ?>">
                                            <div class="invalid-feedback"><?= $username_err; ?></div>
                                        </div>

                                        <div class="mb-3">
                                            <label for="email" class="form-label">Email</label>
                                            <input type="email" class="form-control <?= (!empty($email_err)) ? 'is-invalid' : ''; ?>" id="email" name="email" value="<?= htmlspecialchars($email) ?>">
                                            <div class="invalid-feedback"><?= $email_err; ?></div>
                                        </div>

                                        <div class="mb-3">
                                            <label for="cargo" class="form-label">Cargo</label>
                                            <input type="text" class="form-control" id="cargo" value="<?= htmlspecialchars($cargo) ?>" disabled>
                                        </div>

                                        <button type="submit" name="update_profile" class="btn btn-primary">
                                            <i class="bi bi-check-circle me-2"></i> Salvar Alterações
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>

                        <!-- Alterar Senha -->
                        <div class="col-lg-6">
                            <div class="card profile-card">
                                <div class="profile-card-header">
                                    <i class="bi bi-shield-lock me-2"></i> Alterar Senha
                                </div>
                                <div class="profile-card-body">
                                    <form action="<?= htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                                        <div class="mb-3">
                                            <label for="current_password" class="form-label">Senha Atual</label>
                                            <input type="password" class="form-control <?= (!empty($current_password_err)) ? 'is-invalid' : ''; ?>" id="current_password" name="current_password">
                                            <div class="invalid-feedback"><?= $current_password_err; ?></div>
                                        </div>

                                        <div class="mb-3">
                                            <label for="new_password" class="form-label">Nova Senha</label>
                                            <input type="password" class="form-control <?= (!empty($new_password_err)) ? 'is-invalid' : ''; ?>" id="new_password" name="new_password">
                                            <div class="invalid-feedback"><?= $new_password_err; ?></div>
                                        </div>

                                        <div class="mb-3">
                                            <label for="confirm_password" class="form-label">Confirmar Nova Senha</label>
                                            <input type="password" class="form-control <?= (!empty($confirm_password_err)) ? 'is-invalid' : ''; ?>" id="confirm_password" name="confirm_password">
                                            <div class="invalid-feedback"><?= $confirm_password_err; ?></div>
                                        </div>

                                        <button type="submit" name="update_password" class="btn btn-primary">
                                            <i class="bi bi-key me-2"></i> Alterar Senha
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para Upload de Foto -->
    <div class="modal fade" id="photoModal" tabindex="-1" aria-labelledby="photoModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="photoModalLabel">Alterar Foto de Perfil</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form action="<?= htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" enctype="multipart/form-data" id="photoForm">
                        <div class="mb-3">
                            <label for="foto_perfil" class="form-label">Selecione uma nova foto</label>
                            <input class="form-control" type="file" id="foto_perfil" name="foto_perfil" accept="image/*" onchange="previewImage(this)">
                            <div class="form-text">Formatos aceitos: JPG, JPEG, PNG, GIF. Tamanho máximo: 2MB.</div>
                        </div>

                        <div class="text-center mt-3 mb-3">
                            <img id="imagePreview" src="#" alt="Preview" style="max-width: 100%; max-height: 200px; display: none;">
                        </div>

                        <div class="d-grid">
                            <button type="submit" name="update_photo" class="btn btn-primary">
                                <i class="bi bi-cloud-upload me-2"></i> Salvar Nova Foto
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
     <!-- Incluir Mobile Nav -->
     <?php include '../includes/mobile-nav.php'; ?>

    <!-- Bootstrap JS e dependências -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        // Função para preview da imagem
        function previewImage(input) {
            var preview = document.getElementById('imagePreview');

            if (input.files && input.files[0]) {
                var reader = new FileReader();

                reader.onload = function(e) {
                    preview.src = e.target.result;
                    preview.style.display = 'block';
                }

                reader.readAsDataURL(input.files[0]);
            }
        }
    </script>

</body>

</html>