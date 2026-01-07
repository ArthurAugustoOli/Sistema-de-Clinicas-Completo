<?php
session_start();

require_once '../../config/config.php';
require_once '../../functions/utils/helpers.php';

// Carregar a biblioteca do DOMPDF (ajuste o caminho se necessário)
require_once '../../vendor/autoload.php'; // Ex: dompdf autoload

use Dompdf\Dompdf;
use Dompdf\Options;

header('Content-Type: application/pdf');

// Verificar se o CPF foi passado
if (!isset($_GET['cpf']) || empty($_GET['cpf'])) {
    echo "CPF não fornecido.";
    exit;
}

$cpf = preg_replace('/\D/', '', $_GET['cpf']);

// Buscar dados do paciente
$sqlPaciente = "SELECT * FROM pacientes WHERE cpf = ?";
$stmt = $conn->prepare($sqlPaciente);
$stmt->bind_param("s", $cpf);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo "Paciente não encontrado.";
    exit;
}

$paciente = $result->fetch_assoc();
$stmt->close();

// Buscar documentos
$sqlDocs = "SELECT * FROM paciente_documentos WHERE paciente_cpf = ? ORDER BY data_upload DESC";
$stmtDocs = $conn->prepare($sqlDocs);
$stmtDocs->bind_param("s", $cpf);
$stmtDocs->execute();
$resultDocs = $stmtDocs->get_result();

$documentos = [];
while ($row = $resultDocs->fetch_assoc()) {
    $documentos[] = $row;
}
$stmtDocs->close();
$conn->close();

// Montar HTML do PDF
$html = "
<html>
<head>
  <meta charset='UTF-8'>
  <style>
    body { font-family: sans-serif; font-size: 12px; }
    h1, h2, h3 { margin: 0; }
    .header { text-align: center; margin-bottom: 20px; }
    .info-section { margin-bottom: 15px; }
    .info-label { font-weight: bold; width: 150px; display: inline-block; }
    .docs { margin-top: 20px; }
    .doc-item { margin-bottom: 10px; }
  </style>
</head>
<body>
  <div class='header'>
    <h2>Relatório do Paciente</h2>
  </div>
  <div class='info-section'>
    <span class='info-label'>Nome:</span> {$paciente['nome']} <br/>
    <span class='info-label'>CPF:</span> " . formataCPF($paciente['cpf']) . "<br/>
    <span class='info-label'>Data Nasc:</span> " . formataData($paciente['data_nasc']) . "<br/>
    <span class='info-label'>Telefone:</span> {$paciente['telefone']} <br/>
    <span class='info-label'>Email:</span> {$paciente['email']} <br/>
    <span class='info-label'>Alergias:</span> {$paciente['alergias']} <br/>
    <span class='info-label'>Doenças:</span> {$paciente['doencas']} <br/>
    <span class='info-label'>Convênio:</span> " . ($paciente['tem_convenio'] ? $paciente['convenio'] : 'Não possui') . "<br/>
  </div>
";

// Documentos
$html .= "<div class='docs'><h3>Documentos</h3>";
if (count($documentos) === 0) {
    $html .= "<p>Nenhum documento cadastrado.</p>";
} else {
    foreach ($documentos as $doc) {
        $html .= "<div class='doc-item'>
            <strong>{$doc['nome_documento']}</strong><br/>
            Descrição: " . ($doc['descricao'] ?: 'Sem descrição') . "<br/>
            Arquivo: {$doc['caminho_arquivo']}<br/>
            Data Upload: {$doc['data_upload']}
        </div>";
    }
}
$html .= "</div></body></html>";

// Instanciar o DOMPDF
$options = new Options();
$options->set('isRemoteEnabled', true); // caso queira carregar imagens remotas
$dompdf = new Dompdf($options);

$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

// Opcional: forçar download
// $dompdf->stream("relatorio_paciente_{$cpf}.pdf", ["Attachment" => true]);

// Exibir no navegador
$dompdf->stream("relatorio_paciente_{$cpf}.pdf", ["Attachment" => false]);
