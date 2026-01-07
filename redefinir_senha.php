<?php
// Iniciar sessão
session_start();

// Verificar se o usuário já está logado
if (isset($_SESSION['logado']) && $_SESSION['logado'] === true) {
    header("Location: dashboard.php");
    exit;
}

// Incluir configuração do banco de dados
require_once 'config/config.php';

// Inicializar variáveis
$new_password = $confirm_password = "";
$new_password_err = $confirm_password_err = $token_err = "";
$token = "";
$token_valid = false;
$user_id = 0;

// Verificar se o token está presente na URL
if (isset($_GET["token"]) && !empty(trim($_GET["token"]))) {
    $token = trim($_GET["token"]);
    
    // Verificar se o token é válido
    $sql = "SELECT id, reset_expira FROM usuarios WHERE reset_token = ? AND reset_expira > NOW()";
    
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("s", $token);
        
        if ($stmt->execute()) {
            $stmt->store_result();
            
            if ($stmt->num_rows == 1) {
                $stmt->bind_result($user_id, $expira);
                $stmt->fetch();
                $token_valid = true;
            } else {
                $token_err = "O token de redefinição é inválido ou expirou.";
            }
        } else {
            $token_err = "Oops! Algo deu errado. Por favor, tente novamente mais tarde.";
        }
        
        $stmt->close();
    }
} else {
    $token_err = "Token de redefinição não fornecido.";
}

// Processar dados do formulário quando for enviado
if ($_SERVER["REQUEST_METHOD"] == "POST" && $token_valid) {
    
    // Validar nova senha
    if (empty(trim($_POST["new_password"]))) {
        $new_password_err = "Por favor, informe a nova senha.";
    } elseif (strlen(trim($_POST["new_password"])) < 6) {
        $new_password_err = "A senha deve ter pelo menos 6 caracteres.";
    } else {
        $new_password = trim($_POST["new_password"]);
    }
    
    // Validar confirmação de senha
    if (empty(trim($_POST["confirm_password"]))) {
        $confirm_password_err = "Por favor, confirme a senha.";
    } else {
        $confirm_password = trim($_POST["confirm_password"]);
        if (empty($new_password_err) && ($new_password != $confirm_password)) {
            $confirm_password_err = "As senhas não coincidem.";
        }
    }
    
    // Verificar erros de entrada antes de atualizar o banco de dados
    if (empty($new_password_err) && empty($confirm_password_err)) {
        // Preparar declaração de atualização
        $sql = "UPDATE usuarios SET password = ?, reset_token = NULL, reset_expira = NULL WHERE id = ?";
        
        if ($stmt = $conn->prepare($sql)) {
            // Vincular variáveis à declaração preparada como parâmetros
            $stmt->bind_param("si", $param_password, $param_id);
            
            // Definir parâmetros
            $param_password = password_hash($new_password, PASSWORD_DEFAULT);
            $param_id = $user_id;
            
            // Tentar executar a declaração preparada
            if ($stmt->execute()) {
                // Senha atualizada com sucesso. Redirecionar para a página de login
                session_destroy();
                header("location: login.php?reset=success");
                exit();
            } else {
                $token_err = "Oops! Algo deu errado. Por favor, tente novamente mais tarde.";
            }
            
            // Fechar declaração
            $stmt->close();
        }
    }
    
    // Fechar conexão
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Redefinir Senha - Sistema de Gerenciamento de Clínica</title>
    
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
        
        .reset-container {
            max-width: 450px;
            width: 100%;
            padding: 2rem;
        }
        
        .reset-card {
            border: none;
            border-radius: 1rem;
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
            overflow: hidden;
        }
        
        .reset-header {
            background: linear-gradient(to right, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 2rem;
            text-align: center;
        }
        
        .reset-logo {
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
        
        .reset-body {
            padding: 2rem;
        }
        
        .form-floating {
            margin-bottom: 1rem;
        }
        
        .reset-btn {
            width: 100%;
            padding: 0.75rem;
            font-weight: 600;
            margin-top: 1rem;
        }
        
        .back-to-login {
            text-align: center;
            margin-top: 1rem;
        }
        
        .alert {
            margin-bottom: 1.5rem;
        }
    </style>
</head>
<body>

<div class="reset-container">
    <div class="card reset-card">
        <div class="reset-header">
            <div class="reset-logo">
                <i class="bi bi-shield-lock"></i>
            </div>
            <h4 class="mb-0">Redefinir Senha</h4>
            <p class="mb-0">Crie uma nova senha para sua conta</p>
        </div>
        
        <div class="reset-body">
            <?php if (!empty($token_err)): ?>
                <div class="alert alert-danger" role="alert">
                    <?= $token_err ?>
                </div>
                <div class="text-center">
                    <a href="recuperar_senha.php" class="btn btn-primary">Solicitar Novo Link</a>
                </div>
            <?php elseif ($token_valid): ?>
                <p class="text-muted mb-4">Crie uma nova senha forte para sua conta. Recomendamos usar uma combinação de letras, números e símbolos.</p>
                
                <form action="<?= htmlspecialchars($_SERVER["PHP_SELF"] . "?token=" . $token); ?>" method="post">
                    <div class="form-floating">
                        <input type="password" class="form-control <?= (!empty($new_password_err)) ? 'is-invalid' : ''; ?>" id="new_password" name="new_password" placeholder="Nova senha">
                        <label for="new_password">Nova senha</label>
                        <div class="invalid-feedback"><?= $new_password_err; ?></div>
                    </div>
                    
                    <div class="form-floating">
                        <input type="password" class="form-control <?= (!empty($confirm_password_err)) ? 'is-invalid' : ''; ?>" id="confirm_password" name="confirm_password" placeholder="Confirmar senha">
                        <label for="confirm_password">Confirmar senha</label>
                        <div class="invalid-feedback"><?= $confirm_password_err; ?></div>
                    </div>
                    
                    <button type="submit" class="btn btn-primary reset-btn">
                        <i class="bi bi-check-circle me-2"></i> Redefinir Senha
                    </button>
                </form>
            <?php endif; ?>
            
            <div class="back-to-login">
                <a href="login.php" class="text-decoration-none">
                    <i class="bi bi-arrow-left"></i> Voltar para o Login
                </a>
            </div>
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
