<?php


// Iniciar sessão para gerenciamento de login
session_start();

if (!isset($_SESSION['logado']) || $_SESSION['logado'] !== true) {
    header("Location: login.php");
    exit;
}


// Conexão com o banco de dados
require_once 'config/config.php';

// Funções utilitárias
require_once 'functions/utils/helpers.php';

// Verificar se há dados de estatísticas em cache
$cacheFile = 'cache/dashboard_stats.json';
$dashboardStats = [];

if (file_exists($cacheFile) && (time() - filemtime($cacheFile) < 60)) {
    $dashboardStats = json_decode(file_get_contents($cacheFile), true);
} else {
    // Inicializar estatísticas padrão
    $dashboardStats = [
        'totalConsultas' => 0,
        'consultasMes' => 0,
        'totalPacientes' => 0,
        'pacientesMes' => 0,
        'totalFuncionarios' => 0,
        'consultasHoje' => 0,
        'recebimentosMes' => 0,
        'valorPendente' => 0,
        'percentualPago' => 0,
        'percentualPendente' => 0,
        'consultasRealizadas' => 0,
        'ticketMedio' => 0
    ];

    // Total de consultas
    $sqlConsultas = "SELECT COUNT(*) as total FROM consultas";
    try {
        $resultConsultas = $conn->query($sqlConsultas);
        if ($resultConsultas && $row = $resultConsultas->fetch_assoc()) {
            $dashboardStats['totalConsultas'] = $row['total'];
        }
    } catch (Exception $e) {
        $dashboardStats['totalConsultas'] = 0;
    }

    // Consultas do mês atual
    $sqlConsultasMes = "SELECT COUNT(*) as total FROM consultas WHERE MONTH(data_consulta) = MONTH(CURRENT_DATE()) AND YEAR(data_consulta) = YEAR(CURRENT_DATE())";
    try {
        $resultConsultasMes = $conn->query($sqlConsultasMes);
        if ($resultConsultasMes && $row = $resultConsultasMes->fetch_assoc()) {
            $dashboardStats['consultasMes'] = $row['total'];
        }
    } catch (Exception $e) {
        $dashboardStats['consultasMes'] = 0;
    }

    // Total de pacientes
    $sqlPacientes = "SELECT COUNT(*) as total FROM pacientes";
    try {
        $resultPacientes = $conn->query($sqlPacientes);
        if ($resultPacientes && $row = $resultPacientes->fetch_assoc()) {
            $dashboardStats['totalPacientes'] = $row['total'];
        }
    } catch (Exception $e) {
        $dashboardStats['totalPacientes'] = 0;
    }

    // Pacientes cadastrados no mês atual
    $sqlPacientesMes = "SELECT COUNT(*) as total FROM pacientes WHERE MONTH(data_cadastro) = MONTH(CURRENT_DATE()) AND YEAR(data_cadastro) = YEAR(CURRENT_DATE())";
    try {
        $resultPacientesMes = $conn->query($sqlPacientesMes);
        if ($resultPacientesMes && $row = $resultPacientesMes->fetch_assoc()) {
            $dashboardStats['pacientesMes'] = $row['total'];
        }
    } catch (Exception $e) {
        // Tentativa alternativa com data_nasc
        $sqlPacientesMes = "SELECT COUNT(*) as total FROM pacientes WHERE MONTH(data_nasc) = MONTH(CURRENT_DATE()) AND YEAR(data_nasc) = YEAR(CURRENT_DATE())";
        try {
            $resultPacientesMes = $conn->query($sqlPacientesMes);
            if ($resultPacientesMes && $row = $resultPacientesMes->fetch_assoc()) {
                $dashboardStats['pacientesMes'] = $row['total'];
            }
        } catch (Exception $e) {
            $dashboardStats['pacientesMes'] = 0;
        }
    }

    // Total de funcionários
    $sqlFuncionarios = "SELECT COUNT(*) as total FROM funcionarios";
    try {
        $resultFuncionarios = $conn->query($sqlFuncionarios);
        if ($resultFuncionarios && $row = $resultFuncionarios->fetch_assoc()) {
            $dashboardStats['totalFuncionarios'] = $row['total'];
        }
    } catch (Exception $e) {
        $dashboardStats['totalFuncionarios'] = 0;
    }

    // Consultas de hoje
    $sqlConsultasHoje = "SELECT COUNT(*) as total FROM consultas WHERE DATE(data_consulta) = CURRENT_DATE()";
    try {
        $resultConsultasHoje = $conn->query($sqlConsultasHoje);
        if ($resultConsultasHoje && $row = $resultConsultasHoje->fetch_assoc()) {
            $dashboardStats['consultasHoje'] = $row['total'];
        }
    } catch (Exception $e) {
        $dashboardStats['consultasHoje'] = 0;
    }

    // Dados financeiros
    $sqlFinanceiro = "SELECT 
                       SUM(CASE WHEN status_pagamento = 'PAGO' AND MONTH(data_pagamento) = MONTH(CURRENT_DATE()) THEN valor ELSE 0 END) as recebimentos_mes,
                       SUM(CASE WHEN status_pagamento = 'PENDENTE' THEN valor ELSE 0 END) as valor_pendente,
                       COUNT(CASE WHEN status_pagamento = 'PAGO' THEN 1 END) as consultas_pagas,
                       COUNT(*) as total_registros
                     FROM financeiro";
    try {
        $resultFinanceiro = $conn->query($sqlFinanceiro);
        if ($resultFinanceiro && $row = $resultFinanceiro->fetch_assoc()) {
            $dashboardStats['recebimentosMes'] = $row['recebimentos_mes'] ?: 0;
            $dashboardStats['valorPendente'] = $row['valor_pendente'] ?: 0;
            $dashboardStats['consultasRealizadas'] = $row['total_registros'] ?: 0;

            // Calcular percentuais
            $total = $row['consultas_pagas'] + ($row['total_registros'] - $row['consultas_pagas']);
            if ($total > 0) {
                $dashboardStats['percentualPago'] = round(($row['consultas_pagas'] / $total) * 100);
                $dashboardStats['percentualPendente'] = 100 - $dashboardStats['percentualPago'];
            }

            // Calcular ticket médio
            if ($row['consultas_pagas'] > 0) {
                $dashboardStats['ticketMedio'] = $row['recebimentos_mes'] / $row['consultas_pagas'];
            }
        }
    } catch (Exception $e) {
        $dashboardStats['recebimentosMes'] = 0;
        $dashboardStats['valorPendente'] = 0;
        $dashboardStats['consultasRealizadas'] = 0;
    }

    // Verifica se o diretório cache existe, se não, cria
    if (!is_dir('cache')) {
        mkdir('cache', 0777, true);
    }

    // Salvar em cache
    file_put_contents($cacheFile, json_encode($dashboardStats));
}

// Buscar próximas consultas
$proximasConsultas = [];
$sqlProximasConsultas = "SELECT c.id, c.data_consulta, c.procedimento, p.nome as paciente_nome, 
                       IF(f.nome IS NULL, 'Profissional não atribuído', f.nome) as profissional_nome 
                       FROM consultas c
                       JOIN pacientes p ON c.paciente_cpf = p.cpf
                       LEFT JOIN funcionarios f ON c.profissional_id = f.id
                       WHERE c.data_consulta >= NOW()
                       ORDER BY c.data_consulta ASC
                       LIMIT 5";
try {
    $resultProximasConsultas = $conn->query($sqlProximasConsultas);
    if ($resultProximasConsultas) {
        while ($row = $resultProximasConsultas->fetch_assoc()) {
            $proximasConsultas[] = $row;
        }
    }
} catch (Exception $e) {
    $sqlProximasConsultas = "SELECT c.id, c.data_consulta, c.procedimento, p.nome as paciente_nome, 
                           'Profissional não atribuído' as profissional_nome 
                           FROM consultas c
                           JOIN pacientes p ON c.paciente_cpf = p.cpf
                           WHERE c.data_consulta >= NOW()
                           ORDER BY c.data_consulta ASC
                           LIMIT 5";
    try {
        $resultProximasConsultas = $conn->query($sqlProximasConsultas);
        if ($resultProximasConsultas) {
            while ($row = $resultProximasConsultas->fetch_assoc()) {
                $proximasConsultas[] = $row;
            }
        }
    } catch (Exception $e) {
        // Não fazer nada
    }
}

// Buscar pacientes recentes
$pacientesRecentes = [];
$sqlPacientesRecentes = "SELECT cpf, nome, data_nasc, telefone, foto_perfil 
                        FROM pacientes 
                        ORDER BY id DESC 
                        LIMIT 5";
try {
    $resultPacientesRecentes = $conn->query($sqlPacientesRecentes);
    if ($resultPacientesRecentes) {
        while ($row = $resultPacientesRecentes->fetch_assoc()) {
            $pacientesRecentes[] = $row;
        }
    }
} catch (Exception $e) {
    $sqlPacientesRecentes = "SELECT cpf, nome, data_nasc, telefone, foto_perfil 
                           FROM pacientes 
                           ORDER BY data_nasc DESC 
                           LIMIT 5";
    try {
        $resultPacientesRecentes = $conn->query($sqlPacientesRecentes);
        if ($resultPacientesRecentes) {
            while ($row = $resultPacientesRecentes->fetch_assoc()) {
                $pacientesRecentes[] = $row;
            }
        }
    } catch (Exception $e) {
        // Não fazer nada
    }
}

// Dados para gráficos
$consultasPorMes = [];
$sqlConsultasPorMes = "SELECT MONTH(data_consulta) as mes, COUNT(*) as total 
                      FROM consultas 
                      WHERE YEAR(data_consulta) = YEAR(CURRENT_DATE())
                      GROUP BY MONTH(data_consulta)
                      ORDER BY mes";
try {
    $resultConsultasPorMes = $conn->query($sqlConsultasPorMes);
    if ($resultConsultasPorMes) {
        $mesesCompletos = array_fill(1, 12, 0);
        while ($row = $resultConsultasPorMes->fetch_assoc()) {
            $mesesCompletos[$row['mes']] = (int)$row['total'];
        }
        $consultasPorMes = array_values($mesesCompletos);
    }
} catch (Exception $e) {
    $consultasPorMes = array_fill(0, 12, 0);
}

// Top procedimentos
$topProcedimentos = [];
$sqlTopProcedimentos = "SELECT procedimento, COUNT(*) as total 
                       FROM consultas 
                       WHERE procedimento IS NOT NULL AND procedimento != ''
                       GROUP BY procedimento 
                       ORDER BY total DESC 
                       LIMIT 5";
try {
    $resultTopProcedimentos = $conn->query($sqlTopProcedimentos);
    if ($resultTopProcedimentos) {
        while ($row = $resultTopProcedimentos->fetch_assoc()) {
            $topProcedimentos[$row['procedimento']] = (int)$row['total'];
        }
    }
} catch (Exception $e) {
    // Tabela consultas pode não existir
}

// ATIVIDADES RECENTES - BUSCAR DADOS REAIS
$atividadesRecentes = [];
$sqlAtividades = "SELECT * FROM (
    SELECT 
        'Consulta concluída' AS tipo, 
        CONCAT('Paciente: ', p.nome, ' - Procedimento: ', IFNULL(c.procedimento, 'Consulta')) AS descricao, 
        c.data_consulta AS data_hora, 
        p.nome AS paciente_nome, 
        f.nome AS profissional_nome 
    FROM consultas c
    JOIN pacientes p ON c.paciente_cpf = p.cpf
    LEFT JOIN funcionarios f ON c.profissional_id = f.id
    WHERE c.status = 'Concluída'
    
    UNION ALL
    
    SELECT 
        'Pagamento recebido' AS tipo, 
        CONCAT('Valor: R$', FORMAT(fin.valor, 2), ' - Serviço: ', s.nome_servico) AS descricao, 
        fin.data_pagamento AS data_hora, 
        p.nome AS paciente_nome, 
        NULL AS profissional_nome 
    FROM financeiro fin
    JOIN consultas c ON fin.consulta_id = c.id
    JOIN pacientes p ON c.paciente_cpf = p.cpf
    JOIN servicos s ON fin.servico_id = s.id
    WHERE fin.status_pagamento = 'PAGO'
) AS atividades
ORDER BY data_hora DESC
LIMIT 5";

if ($resultAtividades = $conn->query($sqlAtividades)) {
    while ($row = $resultAtividades->fetch_assoc()) {
        $atividadesRecentes[] = $row;
    }
}

// Funções para formatação
function formatarMoeda($valor)
{
    return 'R$ ' . number_format($valor, 2, ',', '.');
}

function formatarNumero($numero)
{
    return number_format($numero, 0, ',', '.');
}
?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Dashboard - Sistema de Gerenciamento de Clínica</title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <!-- Chart.js -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.css">

    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <!-- Estilos personalizados -->
    <link href="css/styles.css" rel="stylesheet">

    <!-- Estilos responsivos -->
    <link href="css/responsive.css" rel="stylesheet">
    
    

    <!-- Meta tags para dispositivos móveis -->
    <meta name="theme-color" content="#4e73df">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">

    <!-- Ícones para dispositivos móveis -->
    <link rel="apple-touch-icon" href="img/app-icon.png">
    <link rel="icon" type="image/png" href="img/favicon.png">
    
    <style>
        :root {
            --primary-color: #4e73df;
            --primary-dark: #3a5ccc;
            --secondary-color: #6c757d;
            --success-color: #1cc88a;
            --info-color: #36b9cc;
            --warning-color: #f6c23e;
            --danger-color: #e74a3b;
            --light-color: #f8f9fc;
            --dark-color: #5a5c69;
            --card-border-radius: 0.75rem;
            --transition-speed: 0.3s;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f8f9fc;
            color: #333;
            overflow-x: hidden;
        }
        a {text-decoration: none;}
        
        /* Melhorias gerais */
        .card {
            border-radius: var(--card-border-radius);
            box-shadow: 0 0.25rem 1rem rgba(0, 0, 0, 0.08);
            transition: transform var(--transition-speed), box-shadow var(--transition-speed);
            overflow: hidden;
            border: none;
            height: 100%;
        }
        
        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 0.5rem 1.5rem rgba(0, 0, 0, 0.12);
        }
        
        .card-header {
            background-color: #fff;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
            padding: 1.25rem 1.5rem;
        }
        
        .card-body {
            padding: 1.5rem;
        }
        
        .card-footer {
            background-color: #fff;
            border-top: 1px solid rgba(0, 0, 0, 0.05);
            padding: 1rem 1.5rem;
        }
        
        /* Estatísticas Cards */
        .stats-card {
            position: relative;
            overflow: hidden;
        }
        
        .stats-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 5px;
            background: linear-gradient(90deg, var(--primary-color), var(--primary-dark));
        }
        
        .stats-card.success::before {
            background: linear-gradient(90deg, var(--success-color), #0da868);
        }
        
        .stats-card.info::before {
            background: linear-gradient(90deg, var(--info-color), #2a9aad);
        }
        
        .stats-card.warning::before {
            background: linear-gradient(90deg, var(--warning-color), #e5b01f);
        }
        
        .stats-card .icon-circle {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: rgba(78, 115, 223, 0.1);
            margin-bottom: 1rem;
        }
        
        .stats-card .card-title {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            background: linear-gradient(90deg, var(--primary-color), var(--primary-dark));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        
        .stats-card.success .card-title {
            background: linear-gradient(90deg, var(--success-color), #0da868);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        
        .stats-card.info .card-title {
            background: linear-gradient(90deg, var(--info-color), #2a9aad);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        
        .stats-card.warning .card-title {
            background: linear-gradient(90deg, var(--warning-color), #e5b01f);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        
        /* Ações Rápidas */
        .quick-action {
            border-radius: 1rem;
            padding: 1.25rem 1rem;
            transition: all 0.3s;
            position: relative;
            overflow: hidden;
            z-index: 1;
            border: none;
        }
        
        .quick-action::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, rgba(255,255,255,0.2) 0%, rgba(255,255,255,0) 100%);
            z-index: -1;
        }
        
        .quick-action i {
            font-size: 2.5rem;
            margin-bottom: 0.75rem;
            transition: transform 0.3s;
        }
        
        .quick-action:hover {
            transform: translateY(-7px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.15);
        }
        
        .quick-action:hover i {
            transform: scale(1.1);
        }
        
        .btn-primary {
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            border: none;
        }
        
        .btn-success {
            background: linear-gradient(135deg, var(--success-color), #0da868);
            border: none;
        }
        
        .btn-info {
            background: linear-gradient(135deg, var(--info-color), #2a9aad);
            border: none;
        }
        
        .btn-warning {
            background: linear-gradient(135deg, var(--warning-color), #e5b01f);
            border: none;
        }
        
        /* Calendário */
        .calendar-container {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
            padding-bottom: 10px;
        }
        
        .calendar-container table {
            min-width: 100%;
            table-layout: fixed;
        }
        
        .calendar-container th, 
        .calendar-container td {
            text-align: center;
            vertical-align: middle;
            padding: 0.5rem;
            width: 14.28%;
        }
        
        .calendar-day {
            height: 40px;
            width: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto;
            border-radius: 50%;
            cursor: pointer;
            transition: all 0.2s;
            position: relative;
            font-weight: 500;
        }
        
        .calendar-day:hover {
            background-color: rgba(78, 115, 223, 0.1);
            color: var(--primary-color);
        }
        
        .calendar-day.today {
            background: linear-gradient(135deg, var(--success-color), #0da868);
            color: white;
            box-shadow: 0 4px 10px rgba(28, 200, 138, 0.3);
        }
        
        .calendar-day.has-events::after {
            content: "";
            position: absolute;
            bottom: 2px;
            left: 50%;
            transform: translateX(-50%);
            width: 6px;
            height: 6px;
            border-radius: 50%;
            background-color: var(--primary-color);
        }
        
        .calendar-day.today.has-events::after {
            background-color: white;
        }
        
        /* Próximas Consultas */
        .list-group-item {
            border-left: none;
            border-right: none;
            padding: 1rem 1.5rem;
            transition: background-color 0.2s;
        }
        
        .list-group-item:hover {
            background-color: rgba(78, 115, 223, 0.05);
        }
        
        .list-group-item:first-child {
            border-top: none;
        }
        
        .list-group-item h6 {
            font-weight: 600;
            color: #333;
        }
        
        /* Gráficos */
        .chart-container {
            position: relative;
            height: 250px;
        }
        
        /* Resumo Financeiro */
        .progress {
            height: 12px;
            border-radius: 6px;
            overflow: hidden;
            background-color: #f0f0f0;
        }
        
        .progress-bar {
            transition: width 1s ease;
        }
        
        /* Pacientes Recentes */
        .user-avatar {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            overflow: hidden;
            background-color: #f0f0f0;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        
        .user-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        /* Animações */
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
        @keyframes slideInUp {
            from { 
                transform: translateY(30px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }
        
        .fade-in {
            animation: fadeIn 0.8s ease-in-out;
        }
        
        .slide-in-up {
            animation: slideInUp 0.8s ease-in-out;
        }
        
        /* Responsividade do Calendário */
        @media (max-width: 767.98px) {
            .calendar-container {
                margin: 0 -15px;
                padding: 0 15px;
                width: calc(100% + 30px);
            }
            
            .calendar-container table {
                width: 100%;
            }
            
            .calendar-container th, 
            .calendar-container td {
                padding: 0.25rem;
            }
            
            .calendar-day {
                height: 35px;
                width: 35px;
                font-size: 0.85rem;
            }
            
            .btn-group .btn {
                padding: 0.25rem 0.5rem;
                font-size: 0.875rem;
            }
        }
        
        @media (max-width: 575.98px) {
            .calendar-day {
                height: 30px;
                width: 30px;
                font-size: 0.75rem;
            }
            
            .calendar-container th {
                font-size: 0.75rem;
            }
        }
        
        /* Melhorias para dispositivos móveis */
        @media (max-width: 767.98px) {
            .content-wrapper {
                padding-top: 70px;
            }
            
            .card-body {
                padding: 1.25rem;
            }
            
            .stats-card .card-title {
                font-size: 1.75rem;
            }
            
            .stats-card .icon-circle {
                width: 50px;
                height: 50px;
            }
            
            .quick-action i {
                font-size: 2rem;
            }
            
             .quick-action {

    width: 100% !important;
   
    aspect-ratio: 1 / 1;
  }
        }
        
        /* Melhorias para o header */
        .dashboard-header {
            position: relative;
            padding: 2rem 0 1rem;
            margin-bottom: 2rem;
            background: linear-gradient(135deg, #f8f9fc 0%, #f1f3f9 100%);
            border-radius: 0 0 2rem 2rem;
        }
        
        .dashboard-header::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            height: 1px;
            background: linear-gradient(90deg, rgba(78,115,223,0) 0%, rgba(78,115,223,0.5) 50%, rgba(78,115,223,0) 100%);
        }
        
        .dashboard-title {
            font-weight: 700;
            color: #333;
            margin-bottom: 0.5rem;
        }
        
        .dashboard-subtitle {
            color: #6c757d;
            font-weight: 400;
        }
        
        .date-badge {
            background: white;
            border-radius: 1rem;
            padding: 0.5rem 1rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            font-weight: 500;
            color: #555;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        /* Melhorias para o sidebar em dispositivos móveis */
        @media (max-width: 991.98px) {
            .content-wrapper {
                margin-left: 0 !important;
                width: 100% !important;
            }
        }
        
        /* Melhorias para o mobile nav */
    .mobile-bottom-nav {

  display: flex;
  flex-direction: row; 
  background: white;
  box-shadow: 0 -2px 10px rgba(0,0,0,0.1);
  border-radius: 1.5rem 1.5rem 0 0;
  padding: 0.5rem 0;
  position: fixed;
  bottom: 0;
  left: 0;
  right: 0;
  z-index: 1030;
  justify-content: center;
  align-items: center;
}

.mobile-bottom-nav-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 0.5rem 0;
            color: #6c757d;
            transition: all 0.3s;
        }

.mobile-bottom-nav-item i {
  font-size: 1.25rem;
            margin-bottom: 0.25rem;
}

.mobile-bottom-nav-item span {
  font-size: 0.75rem;
  font-weight: 500;
}

.mobile-bottom-nav-item.active {
  color: var(--primary-color);
}
        
        /* Melhorias para o chat e notificações */
        .chat-container {
            display: flex;
            flex-direction: row;
            height: 600px;
            border-radius: 0.75rem;
            overflow: hidden;
        }
        
        @media (max-width: 767.98px) {
            .chat-container {
                flex-direction: column;
                height: 500px;
            }
            
            .chat-users {
                width: 100% !important;
                height: 200px;
                border-right: none !important;
                border-bottom: 1px solid var(--chat-gray);
            }
            
            .chat-area {
                flex: 1;
            }
            
            .notifications-dropdown {
                width: 300px !important;
                right: -100px !important;
            }
        }
        
        /* Ajustes para telas muito pequenas */
        @media (max-width: 359.98px) {
            .quick-action {
                padding: 0.75rem 0.5rem;
            }
            
            .quick-action i {
                font-size: 1.5rem;
                margin-bottom: 0.5rem;
            }
            
            .quick-action span {
                font-size: 0.75rem;
            }
            
            .stats-card .card-title {
                font-size: 1.5rem;
            }
            
            .stats-card .icon-circle {
                width: 40px;
                height: 40px;
            }
            
            .calendar-day {
                height: 25px;
                width: 25px;
                font-size: 0.7rem;
            }
        }
        
        /* Ajustes para o espaçamento do conteúdo quando o mobile nav está presente */
        body.has-mobile-nav {
            padding-bottom: 70px;
        }
    </style>
</head>

<body>
    <div class="d-flex">
        <div class="d-none d-lg-block">
            <!-- Incluir Sidebar -->
            <?php include 'includes/sidebar.php'; ?>
        </div>

        <!-- Conteúdo Principal -->
        <div class="content-wrapper">
            <!-- Incluir Topbar -->
            <?php include 'includes/topbar.php'; ?>

            <!-- Conteúdo do Dashboard -->
            <div class="container-fluid px-4 py-4 mt-3">
                <!-- Header -->
                <div class="dashboard-header mb-4 fade-in">
                    <div class="row align-items-center">
                        <div class="col-md-8 mt-2">
                            <h2 class="dashboard-title">Painel de Controle</h2>
                            <p class="dashboard-subtitle mb-0">Bem-vindo ao sistema de gerenciamento da clínica</p>
                        </div>
                        <div class="col-md-4 text-md-end mt-3 mt-md-0">
                            <div class="date-badge">
                                <i class="bi bi-calendar3"></i> <span id="currentDate"><?= date('d/m/Y') ?></span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Ações Rápidas -->
                <div class="row mb-4 slide-in-up" style="animation-delay: 0.1s;">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title mb-3">Ações Rápidas</h5>
                                <div class="row g-3">
                                    <div class="col-6 col-md-3" style="z-index:0;">
                                        <a href="pages/pacientes.php" class="btn btn-primary w-100 d-flex flex-column align-items-center py-3 quick-action">
                                            <i class="bi bi-person-plus-fill"></i>
                                            <span>Novo Paciente</span>
                                        </a>
                                    </div>
                                    <div class="col-6 col-md-3" style="z-index:0;">
                                        <a href="pages/calendario.php" class="btn btn-success w-100 d-flex flex-column align-items-center py-3 quick-action">
                                            <i class="bi bi-calendar-plus"></i>
                                            <span>Agendar Consulta</span>
                                        </a>
                                    </div>
                                    <div class="col-6 col-md-3" style="z-index:0;">
                                        <a href="pages/consulta_dia.php" class="btn btn-info w-100 d-flex flex-column align-items-center py-3 quick-action text-white">
                                            <i class="bi bi-calendar-check"></i>
                                            <span>Consultas do Dia</span>
                                        </a>
                                    </div>
                                    <div class="col-6 col-md-3" style="z-index:0;">
                                        <a href="pages/financeiro/dashboard.php" class="btn btn-warning w-100 d-flex flex-column align-items-center py-3 quick-action ">
                                           <i class="bi bi-cash-coin text-white"></i>
                                            <span class="text-white">Financeiro</span>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Cards com Estatísticas -->
                <div class="row mb-4">
                    <div class="col-12 col-sm-6 col-md-3 mb-3 slide-in-up" style="animation-delay: 0.2s;">
                   <div class="card stats-card primary h-100">
                            <div class="card-body d-flex flex-column">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <h6 class="card-subtitle text-muted">Total de Consultas</h6>
                                    <div class="icon-circle bg-primary-light">
                                        <i class="bi bi-calendar2-week text-primary fs-4"></i>
                                    </div>
                                </div>
                                <h2 class="card-title mb-0 mt-auto" id="totalConsultas"><?= formatarNumero($dashboardStats['totalConsultas']) ?></h2>
                                <p class=" mb-0" style="color: rgb(33,37,41, 0.75);"><i class="bi bi-graph-up"></i> <span id="consultasTendencia"><?= formatarNumero($dashboardStats['consultasMes']) ?></span> este mês</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-sm-6 col-md-3 mb-3 slide-in-up" style="animation-delay: 0.3s;">
                        <div class="card stats-card success h-100">
                            <div class="card-body d-flex flex-column">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <h6 class="card-subtitle text-muted">Total de Pacientes</h6>
                                    <div class="icon-circle bg-success-light">
                                        <i class="bi bi-people text-success fs-4"></i>
                                    </div>
                                </div>
                                <h2 class="card-title mb-0 mt-auto" id="totalPacientes"><?= formatarNumero($dashboardStats['totalPacientes']) ?></h2>
                                <p class="mb-0" style="color:rgba(33, 37, 41, 0.75);"><i class="bi bi-person-plus"></i> <span id="pacientesTendencia"><?= formatarNumero($dashboardStats['pacientesMes']) ?></span> novos este mês</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-sm-6 col-md-3 mb-3 slide-in-up" style="animation-delay: 0.4s;">
                        <div class="card stats-card info h-100">
                            <div class="card-body d-flex flex-column">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <h6 class="card-subtitle text-muted">Total de Funcionários</h6>
                                    <div class="icon-circle bg-info-light">
                                        <i class="bi bi-person-badge text-info fs-4"></i>
                                    </div>
                                </div>
                                <h2 class="card-title mb-0 mt-auto" id="totalFuncionarios"><?= formatarNumero($dashboardStats['totalFuncionarios']) ?></h2>
                                <p class="text-muted mb-0">Equipe completa</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-sm-6 col-md-3 mb-3 slide-in-up" style="animation-delay: 0.5s;">
                        <div class="card stats-card warning h-100" >
                            <div class="card-body d-flex flex-column">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <h6 class="card-subtitle text-muted">Consultas Hoje</h6>
                                    <div class="icon-circle bg-warning-light">
                                        <i class="bi bi-calendar-day text-warning fs-4"></i>
                                    </div>
                                </div>
                                <h2 class="card-title mb-0 mt-auto" id="consultasHoje"><?= formatarNumero($dashboardStats['consultasHoje']) ?></h2>
                                
                                <a href="pages/consulta_dia.php" style="color:rgba(33, 37, 41, 0.75);">Ver agenda do dia <i class="bi bi-arrow-right"></i></a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Calendário e Próximas Consultas -->
                <div class="row mb-4">
                    <div class="col-lg-8 mb-3 slide-in-up" style="animation-delay: 0.6s;">
                        <div class="card h-100">
                            <div class="card-header d-flex justify-content-between align-items-center flex-wrap">
                                <h5 class="card-title mb-2 mb-sm-0">Calendário de Consultas</h5>
                                <div class="btn-group mt-2" style="display:flex; flex-direction:row; gap:1rem;">
                                    
                                    
                                    <button type="button" class="btn btn-sm btn-outline-secondary" id="prevMonth">
                                        <i class="bi bi-chevron-left"></i>
                                    </button>
                                    <button type="button" class="btn btn-sm btn-outline-secondary" id="currentMonth">Mês Atual</button>
                                    <button type="button" class="btn btn-sm btn-outline-secondary" id="nextMonth">
                                        <i class="bi bi-chevron-right"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="card-body">
                                <div id="miniCalendar" class="mb-3">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <h6 id="calendarMonthYear" class="mb-0 fw-bold"></h6>
                                    </div>
                                    <div class="calendar-container">
                                        <table class="table table-sm table-borderless text-center">
                                            <thead>
                                                <tr>
                                                    <th>Dom</th>
                                                    <th>Seg</th>
                                                    <th>Ter</th>
                                                    <th>Qua</th>
                                                    <th>Qui</th>
                                                    <th>Sex</th>
                                                    <th>Sáb</th>
                                                </tr>
                                            </thead>
                                            <tbody id="calendarBody">
                                                <!-- Preenchido via JavaScript -->
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                                <div class="d-flex justify-content-center mt-2">
                                    <div class="d-flex align-items-center me-3">
                                        <span class="badge bg-primary me-1">&nbsp;</span>
                                        <small>Consultas</small>
                                    </div>
                                    <div class="d-flex align-items-center me-3">
                                        <span class="badge bg-success me-1">&nbsp;</span>
                                        <small>Hoje</small>
                                    </div>
                                </div>
                            </div>
                            <div class="card-footer">
                                <a href="pages/calendario.php" class="btn btn-sm btn-outline-primary w-100">Ver calendário completo</a>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-4 mb-3 slide-in-up" style="animation-delay: 0.7s;">
                        <div class="card h-100">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Próximas Consultas</h5>
                            </div>
                            <div class="card-body p-0">
                                <div class="list-group list-group-flush" id="proximasConsultasList">
                                    <?php if (empty($proximasConsultas)): ?>
                                        <div class="list-group-item text-center py-4">
                                            <p class="mb-0 text-muted">Nenhuma consulta agendada</p>
                                        </div>
                                    <?php else: ?>
                                        <?php foreach ($proximasConsultas as $consulta): ?>
                                            <div class="list-group-item">
                                                <div class="d-flex w-100 justify-content-between">
                                                    <h6 class="mb-1"><?= htmlspecialchars($consulta['paciente_nome']) ?></h6>
                                                    <small class="text-muted">
                                                        <?= date('d/m/Y H:i', strtotime($consulta['data_consulta'])) ?>
                                                    </small>
                                                </div>
                                                <p class="mb-1"><?= htmlspecialchars($consulta['procedimento'] ?: 'Consulta') ?></p>
                                                <small class="text-muted">Dr(a). <?= htmlspecialchars($consulta['profissional_nome']) ?></small>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="card-footer" >
                                <a href="pages/consulta_dia.php" class="btn btn-sm btn-outline-primary w-100">Ver todas as consultas</a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Gráficos e Resumo Financeiro -->
                <div class="row mb-4">
                    <div class="col-md-8 mb-3">
                        <div class="row h-100">
                            <div class="col-md-6 mb-3 mb-md-0 slide-in-up" style="animation-delay: 0.8s;">
                                <div class="card h-100">
                                    <div class="card-header">
                                        <h5 class="card-title mb-0">Consultas por Mês</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="chart-container">
                                            <canvas id="consultasPorMes"></canvas>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6 slide-in-up" style="animation-delay: 0.9s;">
                                <div class="card h-100">
                                    <div class="card-header">
                                        <h5 class="card-title mb-0">Top 5 Procedimentos</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="chart-container">
                                            <canvas id="topProcedimentos"></canvas>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 mb-3 slide-in-up" style="animation-delay: 1s;">
                        <div class="card h-100">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Resumo Financeiro</h5>
                            </div>
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <span>Recebimentos do mês</span>
                                    <span class="text-success fw-bold" id="recebimentosMes"><?= formatarMoeda($dashboardStats['recebimentosMes']) ?></span>
                                </div>
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <span>Pendentes</span>
                                    <span class="text-warning fw-bold" id="valorPendente"><?= formatarMoeda($dashboardStats['valorPendente']) ?></span>
                                </div>
                                <div class="progress mb-4">
                                    <div class="progress-bar bg-success" id="progressoPagos" role="progressbar" style="width: <?= $dashboardStats['percentualPago'] ?>%;" aria-valuenow="<?= $dashboardStats['percentualPago'] ?>" aria-valuemin="0" aria-valuemax="100"></div>
                                    <div class="progress-bar bg-warning" id="progressoPendentes" role="progressbar" style="width: <?= $dashboardStats['percentualPendente'] ?>%;" aria-valuenow="<?= $dashboardStats['percentualPendente'] ?>" aria-valuemin="0" aria-valuemax="100"></div>
                                </div>
                                <div class="d-flex align-items-center mb-2 flex-wrap">
                                    <div class="d-flex align-items-center me-3 mb-2">
                                        <span class="badge bg-success me-1">&nbsp;</span>
                                        <small>Pagos (<?= $dashboardStats['percentualPago'] ?>%)</small>
                                    </div>
                                    <div class="d-flex align-items-center mb-2">
                                        <span class="badge bg-warning me-1">&nbsp;</span>
                                        <small>Pendentes (<?= $dashboardStats['percentualPendente'] ?>%)</small>
                                    </div>
                                </div>
                                <hr>
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span>Consultas realizadas</span>
                                    <span class="fw-bold" id="consultasRealizadas"><?= formatarNumero($dashboardStats['consultasRealizadas']) ?></span>
                                </div>
                                <div class="d-flex justify-content-between align-items-center">
                                    <span>Ticket médio</span>
                                    <span class="fw-bold" id="ticketMedio"><?= formatarMoeda($dashboardStats['ticketMedio']) ?></span>
                                </div>
                            </div>
                            <div class="card-footer">
                                <a href="pages/financeiro/dashboard.php" class="btn btn-sm btn-outline-primary w-100">Ver relatório completo</a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Pacientes Recentes e Atividades Recentes -->
                <div class="row">
                    <div class="col-md-6 mb-3 slide-in-up" style="animation-delay: 1.1s;">
                        <div class="card h-100">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="card-title mb-0">Pacientes Recentes</h5>
                                <a href="pages/pacientes.php" class="btn btn-sm btn-outline-primary">Ver todos</a>
                            </div>
                            <div class="card-body p-0">
                                <div class="list-group list-group-flush" id="pacientesRecentesList">
                                    <?php if (empty($pacientesRecentes)): ?>
                                        <div class="list-group-item text-center py-4">
                                            <p class="mb-0 text-muted">Nenhum paciente cadastrado</p>
                                        </div>
                                    <?php else: ?>
                                        <?php foreach ($pacientesRecentes as $paciente): ?>
                                            <div class="list-group-item">
                                                <div class="d-flex align-items-center">
                                                    <div class="flex-shrink-0">
                                                        <?php if (!empty($paciente['foto_perfil']) && file_exists($paciente['foto_perfil'])): ?>
                                                            <img src="<?= htmlspecialchars($paciente['foto_perfil']) ?>" class="rounded-circle" width="45" height="45" alt="Foto de perfil">
                                                        <?php else: ?>
                                                            <div class="user-avatar bg-secondary text-white d-flex align-items-center justify-content-center">
                                                                <i class="bi bi-person"></i>
                                                            </div>
                                                        <?php endif; ?>
                                                    </div>
                                                    <div class="flex-grow-1 ms-3">
                                                        <div class="d-flex w-100 justify-content-between">
                                                            <h6 class="mb-1"><?= htmlspecialchars($paciente['nome']) ?></h6>
                                                            <small class="text-muted">
                                                                <?= date('d/m/Y', strtotime($paciente['data_nasc'])) ?>
                                                            </small>
                                                        </div>
                                                        <small class="text-muted">
                                                            <i class="bi bi-telephone me-1"></i> <?= htmlspecialchars($paciente['telefone']) ?>
                                                        </small>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 mb-3 slide-in-up" style="animation-delay: 1.2s;">
                        <div class="card h-100">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Atividades Recentes</h5>
                            </div>
                            <div class="card-body p-0">
                                <div class="list-group list-group-flush" id="atividadesRecentesList">
                                    <?php if (empty($atividadesRecentes)): ?>
                                        <div class="list-group-item text-center py-4">
                                            <p class="mb-0 text-muted">Nenhuma atividade recente</p>
                                        </div>
                                    <?php else: ?>
                                        <?php foreach ($atividadesRecentes as $atividade): ?>
                                            <div class="list-group-item">
                                                <div class="d-flex w-100 justify-content-between">
                                                    <h6 class="mb-1"><?= htmlspecialchars($atividade['tipo']) ?></h6>
                                                    <small class="text-muted">
                                                        <?= isset($atividade['data_hora']) ? date('d/m/Y H:i', strtotime($atividade['data_hora'])) : '' ?>
                                                    </small>
                                                </div>
                                                <p class="mb-1"><?= htmlspecialchars($atividade['descricao']) ?></p>
                                                <?php if (!empty($atividade['profissional_nome'])): ?>
                                                    <small class="text-muted">Profissional: <?= htmlspecialchars($atividade['profissional_nome']) ?></small>
                                                <?php endif; ?>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de Nova Notificação (para Administradores) -->
    <?php if (isset($_SESSION['cargo']) && $_SESSION['cargo'] == 'Administrador'): ?>
        <div class="modal fade" id="novaNotificacaoModal" tabindex="-1" aria-labelledby="novaNotificacaoModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="novaNotificacaoModalLabel">Nova Notificação</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <form id="notificacaoForm">
                            <div class="mb-3">
                                <label for="titulo" class="form-label">Título</label>
                                <input type="text" class="form-control" id="titulo" name="titulo" required>
                            </div>
                            <div class="mb-3">
                                <label for="mensagem" class="form-label">Mensagem</label>
                                <textarea class="form-control" id="mensagem" name="mensagem" rows="3" required></textarea>
                            </div>
                            <div class="mb-3">
                                <label for="tipo" class="form-label">Tipo</label>
                                <select class="form-select" id="tipo" name="tipo">
                                    <option value="info">Informação</option>
                                    <option value="success">Sucesso</option>
                                    <option value="warning">Aviso</option>
                                    <option value="danger">Alerta</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="expira_em" class="form-label">Expira em (opcional)</label>
                                <input type="datetime-local" class="form-control" id="expira_em" name="expira_em">
                            </div>
                            <div class="mb-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="para_todos" name="para_todos" checked>
                                    <label class="form-check-label" for="para_todos">Enviar para todos os usuários</label>
                                </div>
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
    <?php endif; ?>

    <!-- Incluir navegação móvel -->
    <?php include 'includes/mobile-nav.php'; ?>

    <!-- Bootstrap JS e dependências -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
    


    <!-- Script Principal -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Exibir data atual
            const today = new Date();
            const options = {
                weekday: 'long',
                year: 'numeric',
                month: 'long',
                day: 'numeric'
            };
            document.getElementById('currentDate').textContent = today.toLocaleDateString('pt-BR', options);

            // Inicializar o mini calendário
            initMiniCalendar();

            // Inicializar os gráficos
            initCharts();

            function initMiniCalendar() {
                const date = new Date();
                let currentMonth = date.getMonth();
                let currentYear = date.getFullYear();

                document.getElementById('prevMonth').addEventListener('click', function() {
                    currentMonth--;
                    if (currentMonth < 0) {
                        currentMonth = 11;
                        currentYear--;
                    }
                    showCalendar(currentMonth, currentYear);
                });

                document.getElementById('nextMonth').addEventListener('click', function() {
                   
                
                    currentMonth++;
                    if (currentMonth > 11) {
                        currentMonth = 0;
                        currentYear++;
                    }
                    showCalendar(currentMonth, currentYear);
                });

                document.getElementById('currentMonth').addEventListener('click', function() {
                    const now = new Date();
                    currentMonth = now.getMonth();
                    currentYear = now.getFullYear();
                    showCalendar(currentMonth, currentYear);
                });

                showCalendar(currentMonth, currentYear);

                function showCalendar(month, year) {
                    const firstDay = new Date(year, month, 1).getDay();
                    const daysInMonth = 32 - new Date(year, month, 32).getDate();
                    const monthNames = ["Janeiro", "Fevereiro", "Março", "Abril", "Maio", "Junho", "Julho", "Agosto", "Setembro", "Outubro", "Novembro", "Dezembro"];
                    document.getElementById('calendarMonthYear').textContent = `${monthNames[month]} ${year}`;

                    let tbl = document.getElementById('calendarBody');
                    tbl.innerHTML = "";
                    let date = 1;
                    for (let i = 0; i < 6; i++) {
                        const row = document.createElement('tr');
                        for (let j = 0; j < 7; j++) {
                            const cell = document.createElement('td');
                            if (i === 0 && j < firstDay) {
                                cell.innerHTML = "";
                                row.appendChild(cell);
                            } else if (date > daysInMonth) {
                                break;
                            } else {
                                cell.innerHTML = `<div class="calendar-day" data-date="${year}-${String(month + 1).padStart(2, '0')}-${String(date).padStart(2, '0')}">${date}</div>`;
                                if (date === today.getDate() && year === today.getFullYear() && month === today.getMonth()) {
                                    cell.querySelector('.calendar-day').classList.add('today');
                                }
                                cell.addEventListener('click', function() {
                                    const dateStr = cell.querySelector('.calendar-day').dataset.date;
                                    window.location.href = `pages/consulta_dia.php?data=${dateStr}`;
                                });
                                row.appendChild(cell);
                                date++;
                            }
                        }
                        if (date > daysInMonth) {
                            break;
                        }
                        tbl.appendChild(row);
                    }

                    fetchMonthEvents(month, year);
                }

                function fetchMonthEvents(month, year) {
                    const dataInicio = `${year}-${String(month + 1).padStart(2, '0')}-01`;
                    const dataFim = `${year}-${String(month + 1).padStart(2, '0')}-31`;
                    fetch(`api/consultas/listar.php?action=getAll&data_inicio=${dataInicio}&data_fim=${dataFim}`)
                        .then(response => response.json())
                        .then(data => {
                            const eventDates = {};
                            data.forEach(consulta => {
                                const date = consulta.data_consulta.substring(0, 10);
                                eventDates[date] = true;
                            });
                            document.querySelectorAll('.calendar-day').forEach(day => {
                                const dateStr = day.dataset.date;
                                if (eventDates[dateStr]) {
                                    day.classList.add('has-events');
                                }
                            });
                        })
                        .catch(error => {
                            console.error('Erro ao buscar eventos:', error);
                        });
                }
            }

            function initCharts() {
                const ctxConsultas = document.getElementById('consultasPorMes').getContext('2d');
                const consultasPorMesChart = new Chart(ctxConsultas, {
                    type: 'line',
                    data: {
                        labels: ['Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun', 'Jul', 'Ago', 'Set', 'Out', 'Nov', 'Dez'],
                        datasets: [{
                            label: 'Consultas',
                            data: <?= json_encode($consultasPorMes) ?>,
                            backgroundColor: 'rgba(78, 115, 223, 0.1)',
                            borderColor: 'rgba(78, 115, 223, 1)',
                            borderWidth: 2,
                            tension: 0.3,
                            fill: true,
                            pointBackgroundColor: '#fff',
                            pointBorderColor: 'rgba(78, 115, 223, 1)',
                            pointBorderWidth: 2,
                            pointRadius: 4,
                            pointHoverRadius: 6
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                display: false
                            },
                            tooltip: {
                                backgroundColor: 'rgba(0, 0, 0, 0.7)',
                                padding: 10,
                                titleFont: {
                                    size: 14,
                                    weight: 'bold'
                                },
                                bodyFont: {
                                    size: 13
                                },
                                displayColors: false
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    precision: 0,
                                    font: {
                                        size: 11
                                    }
                                },
                                grid: {
                                    color: 'rgba(0, 0, 0, 0.05)'
                                }
                            },
                            x: {
                                ticks: {
                                    font: {
                                        size: 11
                                    }
                                },
                                grid: {
                                    display: false
                                }
                            }
                        },
                        interaction: {
                            mode: 'index',
                            intersect: false
                        }
                    }
                });

                const ctxProcedimentos = document.getElementById('topProcedimentos').getContext('2d');
                const procedimentosData = <?= !empty($topProcedimentos) ? json_encode($topProcedimentos) : json_encode(["Consulta Padrão" => 0, "Avaliação" => 0, "Check-up" => 0]) ?>;
                const procedimentosLabels = Object.keys(procedimentosData);
                const procedimentosValues = Object.values(procedimentosData);
                const topProcedimentosChart = new Chart(ctxProcedimentos, {
                    type: 'doughnut',
                    data: {
                        labels: procedimentosLabels,
                        datasets: [{
                            data: procedimentosValues,
                            backgroundColor: [
                                'rgba(78, 115, 223, 0.8)',
                                'rgba(28, 200, 138, 0.8)',
                                'rgba(54, 185, 204, 0.8)',
                                'rgba(246, 194, 62, 0.8)',
                                'rgba(231, 74, 59, 0.8)'
                            ],
                            borderWidth: 2,
                            borderColor: '#ffffff',
                            hoverOffset: 5
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        cutout: '65%',
                        plugins: {
                            legend: {
                                position: 'bottom',
                                labels: {
                                    boxWidth: 12,
                                    padding: 15,
                                    font: {
                                        size: 11
                                    }
                                }
                            },
                            tooltip: {
                                backgroundColor: 'rgba(0, 0, 0, 0.7)',
                                padding: 10,
                                titleFont: {
                                    size: 14,
                                    weight: 'bold'
                                },
                                bodyFont: {
                                    size: 13
                                }
                            }
                        },
                        animation: {
                            animateScale: true,
                            animateRotate: true
                        }
                    }
                });
                
                // Animação para a barra de progresso
                const progressoPagos = document.getElementById('progressoPagos');
                const progressoPendentes = document.getElementById('progressoPendentes');
                
                setTimeout(() => {
                    progressoPagos.style.width = '<?= $dashboardStats['percentualPago'] ?>%';
                    progressoPendentes.style.width = '<?= $dashboardStats['percentualPendente'] ?>%';
                }, 500);
            }

            // Manipulação de modais e detalhes de consulta
            document.querySelectorAll('[data-bs-toggle="modal"]').forEach(element => {
                element.addEventListener('click', function(event) {
                    event.preventDefault();
                    const id = this.getAttribute('data-id');
                    if (id) {
                        fetchConsultaDetalhes(id);
                    }
                    const modal = new bootstrap.Modal(document.getElementById('consultaDetalhesModal'));
                    modal.show();
                });
            });

            function fetchConsultaDetalhes(id) {
                fetch(`api/consultas/detalhes.php?action=getById&id=${id}`)
                    .then(response => response.json())
                    .then(data => {
                        if (!data.success && !data.id) {
                            document.getElementById('consultaDetalhesBody').innerHTML = '<div class="alert alert-danger">Erro ao carregar detalhes da consulta.</div>';
                            return;
                        }
                        const dataConsulta = new Date(data.data_consulta);
                        const dataFormatada = dataConsulta.toLocaleDateString('pt-BR');
                        const horaFormatada = dataConsulta.toLocaleTimeString('pt-BR', {
                            hour: '2-digit',
                            minute: '2-digit'
                        });
                        let statusClass = '';
                        switch (data.status) {
                            case 'Confirmada':
                                statusClass = 'bg-success';
                                break;
                            case 'Cancelada':
                                statusClass = 'bg-danger';
                                break;
                            case 'Concluída':
                                statusClass = 'bg-info';
                                break;
                            default:
                                statusClass = 'bg-primary';
                        }
                        document.getElementById('consultaDetalhesBody').innerHTML = `
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <h6>Paciente</h6>
                                    <p>${data.paciente_nome}</p>
                                </div>
                                <div class="col-md-6">
                                    <h6>Profissional</h6>
                                    <p>${data.profissional_nome || 'Não atribuído'}</p>
                                </div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <h6>Data e Hora</h6>
                                    <p>${dataFormatada} às ${horaFormatada}</p>
                                </div>
                                <div class="col-md-6">
                                    <h6>Status</h6>
                                    <p><span class="badge ${statusClass}">${data.status || 'Agendada'}</span></p>
                                </div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-md-12">
                                    <h6>Procedimento</h6>
                                    <p>${data.procedimento || 'Não especificado'}</p>
                                </div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-md-12">
                                    <h6>Observações</h6>
                                    <p>${data.observacoes || 'Nenhuma observação'}</p>
                                </div>
                            </div>
                        `;
                        document.getElementById('editarConsultaBtn').href = `pages/consulta_dia.php?edit=${data.id}`;
                    })
                    .catch(error => {
                        console.error('Erro ao buscar detalhes da consulta:', error);
                        document.getElementById('consultaDetalhesBody').innerHTML = '<div class="alert alert-danger">Erro ao carregar detalhes da consulta.</div>';
                    });
            }
            
            // Configuração do envio de notificações
            const enviarNotificacaoBtn = document.getElementById('enviarNotificacao');
            if (enviarNotificacaoBtn) {
                enviarNotificacaoBtn.addEventListener('click', function() {
                    const form = document.getElementById('notificacaoForm');
                    const formData = new FormData(form);
                    const urlEncoded = new URLSearchParams();
                    for (const [key, value] of formData) {
                        urlEncoded.append(key, (key === 'para_todos') ? '1' : value);
                    }
                    if (!formData.has('para_todos')) {
                        urlEncoded.append('para_todos', '0');
                    }
                    fetch('api/notificacoes/criar.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/x-www-form-urlencoded'
                            },
                            body: urlEncoded.toString()
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                const modal = bootstrap.Modal.getInstance(document.getElementById('novaNotificacaoModal'));
                                modal.hide();
                                
                                // Mostrar notificação de sucesso
                                const alertDiv = document.createElement('div');
                                alertDiv.className = 'alert alert-success alert-dismissible fade show position-fixed top-0 end-0 m-3';
                                alertDiv.setAttribute('role', 'alert');
                                alertDiv.innerHTML = `
                                    <i class="bi bi-check-circle-fill me-2"></i> Notificação enviada com sucesso!
                                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                `;
                                document.body.appendChild(alertDiv);
                                
                                form.reset();
                                
                                // Remover alerta após 3 segundos
                                setTimeout(() => {
                                    alertDiv.remove();
                                }, 3000);
                                
                                // Recarregar a página após 3.5 segundos
                                setTimeout(() => {
                                    window.location.reload();
                                }, 3500);
                            } else {
                                // Mostrar notificação de erro
                                const alertDiv = document.createElement('div');
                                alertDiv.className = 'alert alert-danger alert-dismissible fade show position-fixed top-0 end-0 m-3';
                                alertDiv.setAttribute('role', 'alert');
                                alertDiv.innerHTML = `
                                    <i class="bi bi-exclamation-triangle-fill me-2"></i> Erro ao enviar notificação: ${data.message}
                                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                `;
                                document.body.appendChild(alertDiv);
                                
                                // Remover alerta após 5 segundos
                                setTimeout(() => {
                                    alertDiv.remove();
                                }, 5000);
                            }
                        })
                        .catch(error => {
                            console.error('Erro ao enviar notificação:', error);
                            
                            // Mostrar notificação de erro
                            const alertDiv = document.createElement('div');
                            alertDiv.className = 'alert alert-danger alert-dismissible fade show position-fixed top-0 end-0 m-3';
                            alertDiv.setAttribute('role', 'alert');
                            alertDiv.innerHTML = `
                                <i class="bi bi-exclamation-triangle-fill me-2"></i> Erro ao enviar notificação. Verifique o console para mais detalhes.
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            `;
                            document.body.appendChild(alertDiv);
                            
                            // Remover alerta após 5 segundos
                            setTimeout(() => {
                                alertDiv.remove();
                            }, 5000);
                        });
                });
            }
        });
    </script>
    

</body>

</html>

