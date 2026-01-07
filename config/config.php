<?php
// Configurações do banco de dados
$host = 'localhost';
$username = 'u566100020_Clinica';
$password = 'Romulo@130948A';
$database = 'u566100020_ClinicaTemplat';

// Criar conexão
$conn = new mysqli($host, $username, $password, $database);

// Verificar conexão
if ($conn->connect_error) {
    die("Falha na conexão: " . $conn->connect_error);
}

// Definir charset para UTF-8
$conn->set_charset("utf8mb4");

// Configurações do sistema
define('SITE_NAME', 'Sistema de Gerenciamento de Clínica');
define('BASE_URL', '/');
define('UPLOAD_DIR', 'uploads/');
define('CACHE_DIR', 'cache/');

// Configurações de timezone
date_default_timezone_set('America/Sao_Paulo');
$mysqli = $conn;
// Variável para uso em includes
$baseUrl = BASE_URL;
?>

