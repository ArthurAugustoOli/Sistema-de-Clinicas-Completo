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
$email = "";
$email_err = $success_msg = "";

// Processar dados do formulário quando for enviado
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // Validar email
    if (empty(trim($_POST["email"]))) {
        $email_err = "Por favor, informe o email.";
    } else {
        $email = trim($_POST["email"]);
        
        // Verificar se o email existe
        $sql = "SELECT id, username, nome FROM usuarios WHERE email = ? AND status = 'ativo'";
        
        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param("s", $email);
            
            if ($stmt->execute()) {
                $stmt->store_result();
                
                if ($stmt->num_rows == 1) {
                    $stmt->bind_result($id, $username, $nome);
                    $stmt->fetch();
                    
                    // Gerar token de redefinição
                    $token = bin2hex(random_bytes(32));
                    $expira = date('Y-m-d H:i:s', strtotime('+1 hour'));
                    
                    // Atualizar o token no banco de dados
                    $update_sql = "UPDATE usuarios SET reset_token = ?, reset_expira = ? WHERE id = ?";
                    
                    if ($update_stmt = $conn->prepare($update_sql)) {
                        $update_stmt->bind_param("ssi", $token, $expira, $id);
                        
                        if ($update_stmt->execute()) {
                            // Enviar email com link de redefinição
                            // Aqui você implementaria o envio de email
                            // Por enquanto, apenas exibimos uma mensagem de sucesso
                            
                            $reset_link = "http://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . "/redefinir_senha.php?token=" . $token;
                            
                            $success_msg = "Um link para redefinição de senha foi enviado para o seu email. O link é válido por 1 hora.<br><br>Link para teste: <a href='$reset_link'>$reset_link</a>";
                        } else {
                            $email_err = "Oops! Algo deu errado. Por favor, tente novamente mais tarde.";
                        }
                        
                        $update_stmt->close();
                    }
                } else {
                    $email_err = "Não foi encontrada uma conta com esse endereço de email.";
                }
            } else {
                $email_err = "Oops! Algo deu errado. Por favor, tente novamente mais tarde.";
            }
            
            $stmt->close();
        }
    }
    
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recuperar Senha - Sistema de Gerenciamento de Clínica</title>
    
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
        
        .recover-container {
            max-width: 450px;
            width: 100%;
            padding: 2rem;
        }
        
        .recover-card {
            border: none;
            border-radius: 1rem;
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
            overflow: hidden;
        }
        
        .recover-header {
            background: linear-gradient(to right, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 2rem;
            text-align: center;
        }
        
        .recover-logo {
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
        
        .recover-body {
            padding: 2rem;
        }
        
        .form-floating {
            margin-bottom: 1rem;
        }
        
        .recover-btn {
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

<div class="recover-container">
    <div class="card recover-card">
        <div class="recover-header">
            <div class="recover-logo">
                <i class="bi bi-key"></i>
            </div>
            <h4 class="mb-0">Recuperar Senha</h4>
            <p class="mb-0">Informe seu email para receber instruções</p>
        </div>
        
        <div class="recover-body">
            <?php if (!empty($success_msg)): ?>
                <div class="alert alert-success" role="alert">
                    <?= $success_msg ?>
                </div>
            <?php else: ?>
                <?php if (!empty($email_err)): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?= $email_err ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>
                
                <p class="text-muted mb-4">Informe o endereço de email associado à sua conta. Enviaremos um link para redefinir sua senha.</p>
                
                <form action="<?= htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                    <div class="form-floating">
                        <input type="email" class="form-control <?= (!empty($email_err)) ? 'is-invalid' : ''; ?>" id="email" name="email" placeholder="Email" value="<?= $email; ?>">
                        <label for="email">Email</label>
                        <div class="invalid-feedback"><?= $email_err; ?></div>
                    </div>
                    
                    <button type="submit" class="btn btn-primary recover-btn">
                        <i class="bi bi-envelope me-2"></i> Enviar Link de Recuperação
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
