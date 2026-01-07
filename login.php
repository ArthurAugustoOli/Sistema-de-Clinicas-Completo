<?php
/* Nome: login.php | Caminho: /login.php */
session_start();

// Conecte-se ao banco, se necessário
require_once 'config/config.php'; // ajuste o caminho conforme necessário

$username = $password = "";
$username_err = $password_err = "";
$login_err = "";

// Processar o formulário ao enviar
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST["username"]);
    $password = trim($_POST["password"]);

    if (empty($username)) {
        $username_err = "Digite o nome de usuário.";
    }

    if (empty($password)) {
        $password_err = "Digite a senha.";
    }

    if (empty($username_err) && empty($password_err)) {
        // Verificar se o usuário existe
        $sql = "SELECT id, username, password, nome, cargo, foto_perfil FROM usuarios WHERE username = ? AND status = 'ativo'";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $username);

        if ($stmt->execute()) {
            $result = $stmt->get_result();
            if ($result->num_rows === 1) {
                $user = $result->fetch_assoc();
                // Verifica a senha
                if (password_verify($password, $user["password"])) {
                    // Login válido, salvar sessão
                    $_SESSION["logado"] = true;
                    $_SESSION["id"] = $user["id"];
                    $_SESSION["username"] = $user["username"];
                    $_SESSION["nome"] = $user["nome"];
                    $_SESSION["cargo"] = $user["cargo"];
                    $_SESSION["foto_perfil"] = $user["foto_perfil"];

                    header("Location: index.php");
                    exit;
                } else {
                    $login_err = "Senha incorreta.";
                }
            } else {
                $login_err = "Usuário não encontrado ou inativo.";
            }
        } else {
            $login_err = "Erro ao consultar o banco de dados.";
        }

        $stmt->close();
    }
}
?>


<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Sistema de Gerenciamento de Clínica</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    
    <!-- Estilos personalizados -->
    <link rel="stylesheet" href="css/styles.css">
    
    <style>
        body {
            background-color: #f8f9fc;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .login-container {
            max-width: 450px;
            width: 100%;
            padding: 2rem;
        }
        
        .login-card {
            border: none;
            border-radius: 1rem;
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
            overflow: hidden;
        }
        
        .login-header {
            background: linear-gradient(to right, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 2rem;
            text-align: center;
        }
        
        .login-logo {
            width: 80px;
            height: 80px;
            background-color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem;
            color: var(--primary-color);
            font-size: 2rem;
        }
        
        .login-body {
            padding: 2rem;
        }
        
        .form-floating {
            margin-bottom: 1rem;
        }
        
        .login-btn {
            width: 100%;
            padding: 0.75rem;
            font-weight: 600;
            margin-top: 1rem;
        }
        
        .forgot-password {
            text-align: center;
            margin-top: 1rem;
        }
        
        .alert {
            margin-bottom: 1.5rem;
        }
    </style>
</head>
<body>

<div class="login-container">
    <div class="card login-card">
        <div class="login-header">
            <div class="login-logo">
                <i class="bi bi-hospital"></i>
            </div>
            <h4 class="mb-0">Sistema de Gerenciamento de Clínica</h4>
            <p class="mb-0">Faça login para acessar o sistema</p>
        </div>
        
        <div class="login-body">
            <?php if (!empty($login_err)): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?= $login_err ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
            
            <?php if (isset($_GET['reset']) && $_GET['reset'] == 'success'): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    Sua senha foi redefinida com sucesso. Você já pode fazer login com sua nova senha.
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
            
            <form action="<?= htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                <div class="form-floating">
                    <input type="text" class="form-control <?= (!empty($username_err)) ? 'is-invalid' : ''; ?>" id="username" name="username" placeholder="Nome de usuário" value="<?= $username; ?>">
                    <label for="username">Nome de usuário</label>
                    <div class="invalid-feedback"><?= $username_err; ?></div>
                </div>
                
                <div class="form-floating">
                    <input type="password" class="form-control <?= (!empty($password_err)) ? 'is-invalid' : ''; ?>" id="password" name="password" placeholder="Senha">
                    <label for="password">Senha</label>
                    <div class="invalid-feedback"><?= $password_err; ?></div>
                </div>
                
                <button type="submit" class="btn btn-primary login-btn">
                    <i class="bi bi-box-arrow-in-right me-2"></i> Entrar
                </button>
            </form>
            
            

        </div>
    </div>
    
    <div class="text-center mt-3 text-muted">
        <small>&copy; <?= date('Y') ?> Sistema de Gerenciamento de Clínica. Todos os direitos reservados.</small>
    </div>
</div>

<!-- Bootstrap JS e dependências -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>