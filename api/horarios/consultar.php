<?php
header('Content-Type: application/json');
require_once '../../config/config.php';

// Validar os parâmetros recebidos
if (!isset($_GET['data']) || !isset($_GET['profissional_id'])) {
    echo json_encode(["error" => "Parâmetros inválidos."]);
    exit;
}

$data = $_GET['data'];
$profissional_id = $_GET['profissional_id'];

// Validar o formato da data
try {
    $dateObj = new DateTime($data);
} catch (Exception $e) {
    echo json_encode(["error" => "Data inválida."]);
    exit;
}

// Definir os horários de funcionamento (08:00 às 18:00, intervalo de 30 minutos)
$startTime = new DateTime($data . ' 08:00:00');
$endTime   = new DateTime($data . ' 18:00:00');
$interval  = new DateInterval('PT30M');

// Gerar todos os intervalos dentro do horário de funcionamento
$allSlots = [];
for ($time = clone $startTime; $time < $endTime; $time->add($interval)) {
    $allSlots[] = $time->format('H:i');
}

// Consultar os horários já agendados para o profissional neste dia
// Bloqueia os horários com status "Agendada" ou "Confirmada"
$sql = "SELECT data_consulta FROM consultas 
        WHERE profissional_id = ? 
          AND DATE(data_consulta) = ? 
          AND status IN ('Agendada', 'Confirmada')";
$stmt = $conn->prepare($sql);
if (!$stmt) {
    echo json_encode(["error" => "Erro no preparo da consulta."]);
    exit;
}
$stmt->bind_param("is", $profissional_id, $data);
$stmt->execute();
$result = $stmt->get_result();
$bookedSlots = [];
while ($row = $result->fetch_assoc()) {
    $dt = new DateTime($row['data_consulta']);
    $bookedSlots[] = $dt->format('H:i');
}
$stmt->close();

// Montar a resposta: todos os horários com um indicador de disponibilidade
$slots = [];
foreach ($allSlots as $slot) {
    $slots[] = [
        "time" => $slot,
        "available" => !in_array($slot, $bookedSlots)
    ];
}

echo json_encode($slots);
