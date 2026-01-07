<?php
// filepath: c:\xampp\htdocs\ClinicaTemplate\api\consultas\atualizar_status.php

// Incluir a configuração do banco de dados
require_once '../../config/config.php';

// Verificar se a requisição é POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Obter os parâmetros enviados
    $consultaId = $_POST['id'] ?? null;
    $status = $_POST['status'] ?? null;

    // Validar os parâmetros
    if (!$consultaId || !$status) {
        echo json_encode(['success' => false, 'message' => 'Parâmetros inválidos.']);
        exit;
    }

    // Atualizar o status da consulta no banco de dados
    $sql = "UPDATE consultas SET status = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);

    if ($stmt) {
        $stmt->bind_param('si', $status, $consultaId);

        if ($stmt->execute()) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Erro ao atualizar o status da consulta.']);
        }

        $stmt->close();
    } else {
        echo json_encode(['success' => false, 'message' => 'Erro ao preparar a consulta SQL.']);
    }
} else {
    // Retornar erro se o método não for POST
    echo json_encode(['success' => false, 'message' => 'Método inválido.']);
}