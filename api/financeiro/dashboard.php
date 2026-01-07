<?php
// Iniciar sessão para gerenciamento de login
session_start();

// Verificar autenticação
if (!isset($_SESSION['logado']) || $_SESSION['logado'] !== true) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Não autorizado']);
    exit;
}

// Conexão com o banco de dados
require_once '../../config/config.php';

// Obter parâmetros
$periodo = isset($_GET['periodo']) ? $_GET['periodo'] : 'mes';
$dataInicio = isset($_GET['data_inicio']) ? $_GET['data_inicio'] : null;
$dataFim = isset($_GET['data_fim']) ? $_GET['data_fim'] : null;

// Definir datas com base no período
$hoje = date('Y-m-d');
$dataInicioSQL = '';
$dataFimSQL = '';

switch ($periodo) {
    case 'dia':
        $dataInicioSQL = "$hoje 00:00:00";
        $dataFimSQL = "$hoje 23:59:59";
        break;
    case 'semana':
        $dataInicioSQL = date('Y-m-d', strtotime('-6 days')) . ' 00:00:00';
        $dataFimSQL = "$hoje 23:59:59";
        break;
    case 'mes':
        $dataInicioSQL = date('Y-m-01') . ' 00:00:00';
        $dataFimSQL = date('Y-m-t') . ' 23:59:59';
        break;
    case 'ano':
        $dataInicioSQL = date('Y-01-01') . ' 00:00:00';
        $dataFimSQL = date('Y-12-31') . ' 23:59:59';
        break;
    case 'personalizado':
        if ($dataInicio && $dataFim) {
            $dataInicioSQL = date('Y-m-d', strtotime(str_replace('/', '-', $dataInicio))) . ' 00:00:00';
            $dataFimSQL = date('Y-m-d', strtotime(str_replace('/', '-', $dataFim))) . ' 23:59:59';
        } else {
            echo json_encode(['success' => false, 'message' => 'Datas não fornecidas']);
            exit;
        }
        break;
}

// Calcular período anterior para comparação
$diasPeriodo = (strtotime($dataFimSQL) - strtotime($dataInicioSQL)) / (60 * 60 * 24);
$dataInicioAnteriorSQL = date('Y-m-d H:i:s', strtotime($dataInicioSQL . " -$diasPeriodo days"));
$dataFimAnteriorSQL = date('Y-m-d H:i:s', strtotime($dataInicioSQL . " -1 second"));

// Consulta ao banco de dados para o período atual
$sqlResumo = "SELECT 
                COUNT(*) AS total_transacoes,
                SUM(IF(status_pagamento = 'PAGO', 1, 0)) AS transacoes_pagas,
                SUM(IF(status_pagamento = 'PENDENTE', 1, 0)) AS transacoes_pendentes,
                SUM(IF(status_pagamento = 'PAGO', valor, 0)) AS total_recebido,
                SUM(IF(status_pagamento = 'PENDENTE', valor, 0)) AS total_pendente
              FROM financeiro 
              WHERE data_criacao BETWEEN '$dataInicioSQL' AND '$dataFimSQL'";

$resultResumo = $conn->query($sqlResumo);
$resumo = $resultResumo->fetch_assoc();

// Consulta ao banco de dados para o período anterior (para comparação)
$sqlResumoAnterior = "SELECT 
                        COUNT(*) AS total_transacoes,
                        SUM(IF(status_pagamento = 'PAGO', valor, 0)) + SUM(IF(status_pagamento = 'PENDENTE', valor, 0)) AS total_valor
                      FROM financeiro 
                      WHERE data_criacao BETWEEN '$dataInicioAnteriorSQL' AND '$dataFimAnteriorSQL'";

$resultResumoAnterior = $conn->query($sqlResumoAnterior);
$resumoAnterior = $resultResumoAnterior->fetch_assoc();

// Calcular variações percentuais
$variacaoReceita = 0;
$variacaoTransacoes = 0;

$totalAtual = $resumo['total_recebido'] + $resumo['total_pendente'];
$totalAnterior = $resumoAnterior['total_valor'];

if ($totalAnterior > 0) {
    $variacaoReceita = round((($totalAtual - $totalAnterior) / $totalAnterior) * 100, 1);
}

if ($resumoAnterior['total_transacoes'] > 0) {
    $variacaoTransacoes = round((($resumo['total_transacoes'] - $resumoAnterior['total_transacoes']) / $resumoAnterior['total_transacoes']) * 100, 1);
}

// Dados para gráfico diário
$sqlGraficoDiario = "SELECT 
                        DATE(data_criacao) as data,
                        SUM(IF(status_pagamento = 'PAGO', valor, 0)) as valor_pago,
                        SUM(IF(status_pagamento = 'PENDENTE', valor, 0)) as valor_pendente
                      FROM financeiro
                      WHERE data_criacao BETWEEN '$dataInicioSQL' AND '$dataFimSQL'
                      GROUP BY DATE(data_criacao)
                      ORDER BY data";

$resultGraficoDiario = $conn->query($sqlGraficoDiario);
$graficoDiario = [];

while ($row = $resultGraficoDiario->fetch_assoc()) {
    $data = new DateTime($row['data']);
    $graficoDiario[] = [
        'data' => $data->format('d/m'),
        'valor_pago' => (float)$row['valor_pago'],
        'valor_pendente' => (float)$row['valor_pendente']
    ];
}

// Distribuição por status
$sqlDistribuicaoStatus = "SELECT 
                            status_pagamento as status,
                            COUNT(*) as quantidade,
                            SUM(valor) as valor_total
                          FROM financeiro
                          WHERE data_criacao BETWEEN '$dataInicioSQL' AND '$dataFimSQL'
                          GROUP BY status_pagamento";

$resultDistribuicaoStatus = $conn->query($sqlDistribuicaoStatus);
$distribuicaoStatus = [];

while ($row = $resultDistribuicaoStatus->fetch_assoc()) {
    $distribuicaoStatus[] = [
        'status' => $row['status'],
        'quantidade' => (int)$row['quantidade'],
        'valor_total' => (float)$row['valor_total']
    ];
}

// Top serviços
$sqlTopServicos = "SELECT 
                      s.id,
                      s.nome_servico as nome,
                      COUNT(f.id) as quantidade,
                      SUM(f.valor) as valor_total
                    FROM financeiro f
                    JOIN servicos s ON f.servico_id = s.id
                    WHERE f.data_criacao BETWEEN '$dataInicioSQL' AND '$dataFimSQL'
                    GROUP BY s.id
                    ORDER BY valor_total DESC
                    LIMIT 5";

$resultTopServicos = $conn->query($sqlTopServicos);
$topServicos = [];

while ($row = $resultTopServicos->fetch_assoc()) {
    $topServicos[] = [
        'id' => (int)$row['id'],
        'nome' => $row['nome'],
        'quantidade' => (int)$row['quantidade'],
        'valor_total' => (float)$row['valor_total']
    ];
}

// Transações recentes
$sqlTransacoesRecentes = "SELECT 
                            f.id,
                            f.valor,
                            f.status_pagamento,
                            DATE_FORMAT(f.data_criacao, '%d/%m/%Y %H:%i') as data_criacao,
                            s.nome_servico as servico,
                            p.cpf as paciente_cpf,
                            p.nome as paciente_nome
                          FROM financeiro f
                          JOIN consultas c ON f.consulta_id = c.id
                          JOIN pacientes p ON c.paciente_cpf = p.cpf
                          JOIN servicos s ON f.servico_id = s.id
                          WHERE f.data_criacao BETWEEN '$dataInicioSQL' AND '$dataFimSQL'
                          ORDER BY f.data_criacao DESC
                          LIMIT 10";

$resultTransacoesRecentes = $conn->query($sqlTransacoesRecentes);
$transacoesRecentes = [];

while ($row = $resultTransacoesRecentes->fetch_assoc()) {
    $transacoesRecentes[] = [
        'id' => (int)$row['id'],
        'valor' => (float)$row['valor'],
        'status_pagamento' => $row['status_pagamento'],
        'data_criacao' => $row['data_criacao'],
        'servico' => $row['servico'],
        'paciente' => [
            'cpf' => $row['paciente_cpf'],
            'nome' => $row['paciente_nome']
        ]
    ];
}

// Preparar resposta
$response = [
    'success' => true,
    'data' => [
        'filtros' => [
            'periodo' => $periodo,
            'data_inicio' => $dataInicioSQL,
            'data_fim' => $dataFimSQL
        ],
        'resumo' => [
            'total_transacoes' => (int)$resumo['total_transacoes'],
            'transacoes_pagas' => (int)$resumo['transacoes_pagas'],
            'transacoes_pendentes' => (int)$resumo['transacoes_pendentes'],
            'total_recebido' => (float)$resumo['total_recebido'],
            'total_pendente' => (float)$resumo['total_pendente']
        ],
        'comparacao' => [
            'periodo_anterior' => [
                'data_inicio' => $dataInicioAnteriorSQL,
                'data_fim' => $dataFimAnteriorSQL,
                'total_transacoes' => (int)$resumoAnterior['total_transacoes'],
                'total_valor' => (float)$resumoAnterior['total_valor']
            ],
            'variacao' => [
                'receita' => $variacaoReceita,
                'transacoes' => $variacaoTransacoes
            ]
        ],
        'grafico_diario' => $graficoDiario,
        'distribuicao_status' => $distribuicaoStatus,
        'top_servicos' => $topServicos,
        'transacoes_recentes' => $transacoesRecentes
    ]
];

header('Content-Type: application/json');
echo json_encode($response);

$conn->close();