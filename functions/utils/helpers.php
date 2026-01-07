<?php
/**
 * Arquivo com funções utilitárias para o sistema
 */

/**
 * Formata um valor monetário para exibição
 * 
 * @param float $valor Valor a ser formatado
 * @return string Valor formatado
 */
function formataMoeda($valor) {
    return 'R$ ' . number_format($valor, 2, ',', '.');
}

/**
 * Formata uma data para o formato brasileiro
 * 
 * @param string $data Data no formato Y-m-d
 * @param bool $comHora Se deve incluir a hora
 * @return string Data formatada
 */

 
function formataData($data, $comHora = false) {
    if (empty($data)) return '';
    
    $timestamp = strtotime($data);
    if ($comHora) {
        return date('d/m/Y H:i', $timestamp);
    } else {
        return date('d/m/Y', $timestamp);
    }
}


/**
 * Formata um CPF para exibição
 * 
 * @param string $cpf CPF sem formatação
 * @return string CPF formatado
 */
function formataCPF($cpf) {
    if (strlen($cpf) != 11) return $cpf;
    
    return substr($cpf, 0, 3) . '.' . 
           substr($cpf, 3, 3) . '.' . 
           substr($cpf, 6, 3) . '-' . 
           substr($cpf, 9, 2);
}

/**
 * Formata um telefone para exibição
 * 
 * @param string $telefone Telefone sem formatação
 * @return string Telefone formatado
 */
function formataTelefone($telefone) {
    $telefone = preg_replace('/[^0-9]/', '', $telefone);
    $len = strlen($telefone);
    
    if ($len == 11) {
        return '(' . substr($telefone, 0, 2) . ') ' . 
               substr($telefone, 2, 5) . '-' . 
               substr($telefone, 7, 4);
    } elseif ($len == 10) {
        return '(' . substr($telefone, 0, 2) . ') ' . 
               substr($telefone, 2, 4) . '-' . 
               substr($telefone, 6, 4);
    }
    
    return $telefone;
}

/**
 * Calcula a idade a partir da data de nascimento
 * 
 * @param string $dataNascimento Data de nascimento no formato Y-m-d
 * @return int Idade em anos
 */
function calculaIdade($dataNascimento) {
    if (empty($dataNascimento)) return 0;
    
    $nascimento = new DateTime($dataNascimento);
    $hoje = new DateTime();
    $idade = $hoje->diff($nascimento);
    
    return $idade->y;
}

/**
 * Limpa uma string para uso em URLs ou como ID
 * 
 * @param string $string String a ser limpa
 * @return string String limpa
 */
function limpaString($string) {
    $string = preg_replace('/[áàãâä]/ui', 'a', $string);
    $string = preg_replace('/[éèêë]/ui', 'e', $string);
    $string = preg_replace('/[íìîï]/ui', 'i', $string);
    $string = preg_replace('/[óòõôö]/ui', 'o', $string);
    $string = preg_replace('/[úùûü]/ui', 'u', $string);
    $string = preg_replace('/[ç]/ui', 'c', $string);
    $string = preg_replace('/[^a-z0-9]/i', '_', $string);
    $string = preg_replace('/_+/', '_', $string);
    
    return strtolower($string);
}

/**
 * Verifica se uma string é uma data válida
 * 
 * @param string $data Data a ser verificada
 * @param string $formato Formato da data
 * @return bool True se for uma data válida, false caso contrário
 */
function validaData($data, $formato = 'Y-m-d') {
    $d = DateTime::createFromFormat($formato, $data);
    return $d && $d->format($formato) === $data;
}

/**
 * Gera um log de atividade no sistema
 * 
 * @param string $acao Ação realizada
 * @param string $descricao Descrição da ação
 * @param string $usuario Usuário que realizou a ação
 * @return bool True se o log foi gerado com sucesso, false caso contrário
 */
function geraLog($acao, $descricao, $usuario = '') {
    global $conn;
    
    if (empty($usuario) && isset($_SESSION['usuario'])) {
        $usuario = $_SESSION['usuario'];
    }
    
    $sql = "INSERT INTO logs (acao, descricao, usuario, data_hora) 
            VALUES (?, ?, ?, NOW())";
    
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        return false;
    }
    
    $stmt->bind_param("sss", $acao, $descricao, $usuario);
    $resultado = $stmt->execute();
    $stmt->close();
    
    return $resultado;
}

/**
 * Verifica se o usuário tem permissão para acessar determinada funcionalidade
 * 
 * @param string $permissao Permissão necessária
 * @return bool True se o usuário tem permissão, false caso contrário
 */
function verificaPermissao($permissao) {
    if (!isset($_SESSION['permissoes'])) {
        return false;
    }
    
    return in_array($permissao, $_SESSION['permissoes']);
}

/**
 * Redireciona para outra página
 * 
 * @param string $url URL para redirecionamento
 */
function redireciona($url) {
    header("Location: $url");
    exit;
}

/**
 * Registra uma atividade no sistema
 * 
 * @param string $mensagem Mensagem descritiva da atividade
 * @param mysqli $conn Conexão com o banco de dados
 * @return bool Sucesso ou falha
 */
function registrarAtividade($mensagem, $conn) {
    // Verificar se a tabela existe
    $check_table = $conn->query("SHOW TABLES LIKE 'atividades'");
    if ($check_table->num_rows == 0) {
        // Tabela não existe, criar
        $conn->query("CREATE TABLE IF NOT EXISTS `atividades` (
            `id` INT(11) NOT NULL AUTO_INCREMENT,
            `descricao` TEXT NOT NULL,
            `data` DATETIME NOT NULL,
            `usuario_id` INT(11) DEFAULT NULL,
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    }
    
    $usuario_id = isset($_SESSION['usuario_id']) ? $_SESSION['usuario_id'] : NULL;
    
    $sql = "INSERT INTO atividades (descricao, data, usuario_id) VALUES (?, NOW(), ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("si", $mensagem, $usuario_id);
    return $stmt->execute();
}

/**
 * Formata um telefone para exibição
 * 
 * @param string $telefone Telefone sem formatação
 * @return string Telefone formatado
 */
function formatarTelefone($telefone) {
    if (!$telefone) return '-';
    
    // Remover caracteres não numéricos
    $telefone = preg_replace('/\D/', '', $telefone);

    // Verificar o comprimento do número
    if (strlen($telefone) === 11) {
        // Formato: (XX) 9XXXX-XXXX
        return '(' . substr($telefone, 0, 2) . ') ' . substr($telefone, 2, 5) . '-' . substr($telefone, 7);
    } elseif (strlen($telefone) === 10) {
        // Formato: (XX) XXXX-XXXX
        return '(' . substr($telefone, 0, 2) . ') ' . substr($telefone, 2, 4) . '-' . substr($telefone, 6);
    } elseif (strlen($telefone) === 8) {
        // Formato: XXXX-XXXX (caso seja sem DDD)
        return substr($telefone, 0, 4) . '-' . substr($telefone, 4);
    } else {
        // Retornar como está se o formato não for reconhecido
        return $telefone;
    }
}

/**
 * Formata um CPF para exibição
 * 
 * @param string $cpf CPF sem formatação
 * @return string CPF formatado
 */
function formatarCPF($cpf) {
    if (!$cpf) return '-';
    
    // Remove caracteres não numéricos
    $numeros = preg_replace('/[^0-9]/', '', $cpf);
    
    if (strlen($numeros) !== 11) return $cpf;
    
    return substr($numeros, 0, 3) . '.' . 
           substr($numeros, 3, 3) . '.' . 
           substr($numeros, 6, 3) . '-' . 
           substr($numeros, 9, 2);
}

/**
 * Formata uma data para exibição
 * 
 * @param string $data Data no formato Y-m-d
 * @param string $formato Formato desejado
 * @return string Data formatada
 */
function formatarData($data, $formato = 'd/m/Y') {
    if (!$data || $data == '0000-00-00') return '-';
    
    $timestamp = strtotime($data);
    return date($formato, $timestamp);
}

/**
 * Formata um valor monetário para exibição
 * 
 * @param float $valor Valor a ser formatado
 * @param string $simbolo Símbolo da moeda
 * @return string Valor formatado
 */
function formatarValor($valor, $simbolo = 'R$') {
    if (!is_numeric($valor)) return '-';
    
    return $simbolo . ' ' . number_format($valor, 2, ',', '.');
}

/**
 * Obtém as iniciais de um nome
 * 
 * @param string $name Nome completo
 * @return string Iniciais do nome
 */
function getInitials($name) {
    if (!$name) return '';
    
    $names = explode(' ', $name);
    if (count($names) === 1) return strtoupper(substr($names[0], 0, 1));
    
    return strtoupper(substr($names[0], 0, 1) . substr($names[count($names) - 1], 0, 1));
}

/**
 * Valida um CPF
 * 
 * @param string $cpf CPF a ser validado
 * @return bool True se o CPF for válido, false caso contrário
 */
function validarCPF($cpf) {
    // Remove caracteres não numéricos
    $cpf = preg_replace('/[^0-9]/', '', $cpf);
    
    // Verifica se o CPF tem 11 dígitos
    if (strlen($cpf) != 11) {
        return false;
    }
    
    // Verifica se todos os dígitos são iguais
    if (preg_match('/^(\d)\1+$/', $cpf)) {
        return false;
    }
    
    // Calcula o primeiro dígito verificador
    $soma = 0;
    for ($i = 0; $i < 9; $i++) {
        $soma += $cpf[$i] * (10 - $i);
    }
    $resto = $soma % 11;
    $dv1 = ($resto < 2) ? 0 : 11 - $resto;
    
    // Verifica o primeiro dígito verificador
    if ($cpf[9] != $dv1) {
        return false;
    }
    
    // Calcula o segundo dígito verificador
    $soma = 0;
    for ($i = 0; $i < 10; $i++) {
        $soma += $cpf[$i] * (11 - $i);
    }
    $resto = $soma % 11;
    $dv2 = ($resto < 2) ? 0 : 11 - $resto;
    
    // Verifica o segundo dígito verificador
    if ($cpf[10] != $dv2) {
        return false;
    }
    
    return true;
}

/**
 * Gera uma senha aleatória
 * 
 * @param int $tamanho Tamanho da senha
 * @return string Senha gerada
 */
function gerarSenha($tamanho = 8) {
    $caracteres = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()';
    $senha = '';
    
    for ($i = 0; $i < $tamanho; $i++) {
        $senha .= $caracteres[rand(0, strlen($caracteres) - 1)];
    }
    
    return $senha;
}

/**
 * Sanitiza dados de entrada para prevenir injeção SQL
 * 
 * @param string $data Dados a serem sanitizados
 * @param mysqli $conn Conexão com o banco de dados
 * @return string Dados sanitizados
 */
function sanitizarDados($data, $conn) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    if ($conn) {
        $data = $conn->real_escape_string($data);
    }
    return $data;
}

/**
 * Verifica se um email é válido
 * 
 * @param string $email Email a ser verificado
 * @return bool True se o email for válido, false caso contrário
 */
function validarEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Gera um token único
 * 
 * @param int $length Tamanho do token
 * @return string Token gerado
 */
function gerarToken($length = 32) {
    return bin2hex(random_bytes($length / 2));
}

/**
 * Verifica se uma requisição é AJAX
 * 
 * @return bool True se for uma requisição AJAX, false caso contrário
 */
function isAjax() {
    return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
           strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
}

/**
 * Retorna uma resposta JSON e encerra a execução
 * 
 * @param array $data Dados a serem retornados
 * @param int $status Código de status HTTP
 */
function jsonResponse($data, $status = 200) {
    http_response_code($status);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}
?>

