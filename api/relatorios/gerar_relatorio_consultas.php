<?php
include '../../config/config.php';
require_once '../../vendor/autoload.php'; // Requer a biblioteca FPDF ou similar

// Verificar se a data foi fornecida
if (!isset($_GET['data']) || empty($_GET['data'])) {
    die('Data não fornecida');
}

$data = $_GET['data'];
$date = new DateTime($data);

// Determinar o período do relatório
if (isset($_GET['view']) && $_GET['view'] === 'month') {
    $start = (clone $date)->modify('first day of this month');
    $end = (clone $start)->modify('last day of this month');
    $titulo = 'Relatório de Consultas - ' . $start->format('F Y');
} else {
    $start = new DateTime($data);
    $end = new DateTime($data);
    $titulo = 'Relatório de Consultas - ' . $start->format('d/m/Y');
}

// Buscar consultas do período
$query = "SELECT c.*, p.nome AS paciente_nome, f.nome AS profissional_nome
          FROM consultas c
          JOIN pacientes p ON c.paciente_cpf = p.cpf
          JOIN funcionarios f ON c.profissional_id = f.id
          WHERE DATE(c.data_consulta) BETWEEN ? AND ?
          ORDER BY c.data_consulta ASC";
$stmt = $conn->prepare($query);
$startFormatted = $start->format('Y-m-d');
$endFormatted = $end->format('Y-m-d');
$stmt->bind_param("ss", $startFormatted, $endFormatted);
$stmt->execute();
$result = $stmt->get_result();

// Iniciar buffer de saída para PDF
ob_start();
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title><?php echo $titulo; ?></title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
        }
        h1 {
            text-align: center;
            color: #333;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
        }
        .footer {
            margin-top: 30px;
            text-align: center;
            font-size: 12px;
            color: #666;
        }
    </style>
</head>
<body>
    <h1><?php echo $titulo; ?></h1>
    
    <?php if ($result->num_rows === 0): ?>
        <p>Nenhuma consulta encontrada para o período selecionado.</p>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>Data/Hora</th>
                    <th>Paciente</th>
                    <th>Profissional</th>
                    <th>Procedimento</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($consulta = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo date('d/m/Y H:i', strtotime($consulta['data_consulta'])); ?></td>
                        <td><?php echo htmlspecialchars($consulta['paciente_nome']); ?></td>
                        <td><?php echo htmlspecialchars($consulta['profissional_nome']); ?></td>
                        <td><?php echo htmlspecialchars($consulta['procedimento'] ?: 'Consulta'); ?></td>
                        <td><?php echo htmlspecialchars($consulta['status']); ?></td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    <?php endif; ?>
    
    <div class="footer">
        <p>Relatório gerado em <?php echo date('d/m/Y H:i:s'); ?></p>
    </div>
</body>
</html>
<?php
$html = ob_get_clean();

// Aqui você usaria uma biblioteca como FPDF ou DOMPDF para converter o HTML em PDF
// Como exemplo, estamos apenas exibindo o HTML
header('Content-Type: text/html; charset=utf-8');
echo $html;

// Exemplo com DOMPDF (requer instalação via Composer)
/*
use Dompdf\Dompdf;
$dompdf = new Dompdf();
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();
$dompdf->stream($titulo . ".pdf", array("Attachment" => false));
*/

