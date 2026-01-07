<?php
// Iniciar sessão
session_start();

// Verificar autenticação
if (!isset($_SESSION['logado']) || $_SESSION['logado'] !== true) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Acesso não autorizado.']);
    exit;
}

// Conexão com o banco de dados
require_once '../../config/config.php';

// Definir cabeçalho para JSON
header('Content-Type: application/json');

// Verificar se é uma requisição POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método não permitido.']);
    exit;
}

$action = $_POST['action'] ?? '';

$transactionStarted = false; // Variável para controlar se a transação foi iniciada

try {
    // Verificar se a coluna servico_id existe na tabela consultas
    $columnCheckQuery = "SHOW COLUMNS FROM consultas LIKE 'servico_id'";
    $columnResult = $conn->query($columnCheckQuery);
    
    if ($columnResult && $columnResult->num_rows === 0) {
        // A coluna não existe, vamos adicioná-la
        $alterTableQuery = "ALTER TABLE consultas ADD COLUMN servico_id INT NULL";
        if (!$conn->query($alterTableQuery)) {
            throw new Exception('Erro ao adicionar coluna servico_id: ' . $conn->error);
        }
        
        // Adicionar a chave estrangeira
        $addForeignKeyQuery = "ALTER TABLE consultas ADD CONSTRAINT fk_consulta_servico FOREIGN KEY (servico_id) REFERENCES servicos(id) ON DELETE SET NULL ON UPDATE CASCADE";
        if (!$conn->query($addForeignKeyQuery)) {
            throw new Exception('Erro ao adicionar chave estrangeira: ' . $conn->error);
        }
    }
    
    switch ($action) {
        case 'create':
            $paciente_cpf = trim($_POST['paciente_cpf'] ?? '');
            $profissional_id = intval($_POST['profissional_id'] ?? 0);
            $data_consulta = trim($_POST['data_consulta'] ?? '');
            $servico_id = intval($_POST['servico_id'] ?? 0);
            $observacoes = trim($_POST['observacoes'] ?? '');
            $status = trim($_POST['status'] ?? 'Agendada');
            $manual_selection = isset($_POST['manual_selection']) && $_POST['manual_selection'] == '1';
            
            // Verificar campos obrigatórios
            if (empty($paciente_cpf) || empty($profissional_id) || empty($data_consulta) || empty($servico_id)) {
                throw new Exception('Todos os campos obrigatórios devem ser preenchidos.');
            }
            
            // Obter horários selecionados
            $horarios = [];
            
            // Verificar se temos horários no formato de string separada por vírgulas
            if (!empty($_POST['horarios_consulta'])) {
                $horarios = explode(',', $_POST['horarios_consulta']);
            } 
            // Ou verificar se temos um único horário
            else if (!empty($_POST['horario_selecionado'])) {
                $horarios[] = $_POST['horario_selecionado'];
            }
            
            if (empty($horarios)) {
                throw new Exception('É necessário selecionar pelo menos um horário para a consulta.');
            }
            
            // Obter informações do serviço
            $sqlServico = "SELECT nome_servico, preco, duracao_minutos FROM servicos WHERE id = ?";
            $stmtServico = $conn->prepare($sqlServico);
            $stmtServico->bind_param("i", $servico_id);
            $stmtServico->execute();
            $resultServico = $stmtServico->get_result();
            
            if ($resultServico->num_rows === 0) {
                throw new Exception('Serviço não encontrado.');
            }
            
            $servico = $resultServico->fetch_assoc();
            $procedimento = $servico['nome_servico'];
            $valor_servico = $servico['preco'];
            $duracao_minutos = isset($servico['duracao_minutos']) ? $servico['duracao_minutos'] : 30;
            $stmtServico->close();
            
            // Iniciar transação
            $conn->begin_transaction();
            $transactionStarted = true;
            
            // Array para armazenar os IDs das consultas criadas
            $consulta_ids = [];
            
            // Data base (apenas a data, sem horário)
            $data_apenas = date('Y-m-d', strtotime($data_consulta));
            
            // Criar uma consulta para cada horário selecionado
            foreach ($horarios as $horario) {
                // Combinar data com horário
                $data_hora_consulta = $data_apenas . ' ' . $horario;
                
                // Verificar se o horário já está ocupado
                $sqlCheckHorario = "SELECT id FROM consultas 
                                   WHERE profissional_id = ? 
                                   AND data_consulta = ?
                                   AND status != 'Cancelada'";
                $stmtCheckHorario = $conn->prepare($sqlCheckHorario);
                $stmtCheckHorario->bind_param("is", $profissional_id, $data_hora_consulta);
                $stmtCheckHorario->execute();
                $resultCheckHorario = $stmtCheckHorario->get_result();
                
                if ($resultCheckHorario->num_rows > 0) {
                    throw new Exception('O horário ' . date('H:i', strtotime($horario)) . ' já está ocupado.');
                }
                $stmtCheckHorario->close();
                
                // Inserir consulta
                $sqlInsertConsulta = "INSERT INTO consultas (
                                        paciente_cpf, 
                                        profissional_id, 
                                        data_consulta, 
                                        procedimento, 
                                        observacoes, 
                                        status, 
                                        servico_id
                                    ) VALUES (?, ?, ?, ?, ?, ?, ?)";
                $stmtInsertConsulta = $conn->prepare($sqlInsertConsulta);
                $stmtInsertConsulta->bind_param(
                    "sissssi", 
                    $paciente_cpf, 
                    $profissional_id, 
                    $data_hora_consulta, 
                    $procedimento, 
                    $observacoes, 
                    $status, 
                    $servico_id
                );
                
                if (!$stmtInsertConsulta->execute()) {
                    throw new Exception('Erro ao criar consulta: ' . $stmtInsertConsulta->error);
                }
                
                $consulta_id = $conn->insert_id;
                $consulta_ids[] = $consulta_id;
                
                // Verificar se a tabela financeiro existe
                $tableCheckQuery = "SHOW TABLES LIKE 'financeiro'";
                $tableResult = $conn->query($tableCheckQuery);
                
                if ($tableResult && $tableResult->num_rows > 0) {
                    // Inserir registro financeiro
                    $sqlInsertFinanceiro = "INSERT INTO financeiro (
                                            consulta_id, 
                                            servico_id, 
                                            valor, 
                                            status_pagamento
                                        ) VALUES (?, ?, ?, 'PENDENTE')";
                    $stmtInsertFinanceiro = $conn->prepare($sqlInsertFinanceiro);
                    $stmtInsertFinanceiro->bind_param("iid", $consulta_id, $servico_id, $valor_servico);
                    
                    if (!$stmtInsertFinanceiro->execute()) {
                        throw new Exception('Erro ao registrar financeiro: ' . $stmtInsertFinanceiro->error);
                    }
                    
                    $stmtInsertFinanceiro->close();
                }
                
                $stmtInsertConsulta->close();
            }
            
            // Commit da transação
            $conn->commit();
            $transactionStarted = false;
            
            echo json_encode([
                'success' => true, 
                'message' => count($consulta_ids) > 1 
                    ? 'Consultas criadas com sucesso!' 
                    : 'Consulta criada com sucesso!', 
                'ids' => $consulta_ids,
                'detalhes' => [
                    'procedimento' => $procedimento,
                    'valor' => $valor_servico,
                    'horarios' => count($horarios)
                ]
            ]);
            break;
            
        case 'update':
            $id = intval($_POST['id'] ?? 0);
            $paciente_cpf = trim($_POST['paciente_cpf'] ?? '');
            $profissional_id = intval($_POST['profissional_id'] ?? 0);
            $data_consulta = trim($_POST['data_consulta'] ?? '');
            $servico_id = intval($_POST['servico_id'] ?? 0);
            $observacoes = trim($_POST['observacoes'] ?? '');
            $status = trim($_POST['status'] ?? '');
            $manual_selection = isset($_POST['manual_selection']) && $_POST['manual_selection'] == '1';
            
            // Verificar campos obrigatórios
            if (empty($paciente_cpf) || empty($profissional_id) || empty($data_consulta) || empty($servico_id) || empty($status)) {
                throw new Exception('Todos os campos obrigatórios devem ser preenchidos.');
            }
            
            // Obter horários selecionados
            $horarios = [];
            
            // Verificar se temos horários no formato de string separada por vírgulas
            if (!empty($_POST['horarios_consulta'])) {
                $horarios = explode(',', $_POST['horarios_consulta']);
            } 
            // Ou verificar se temos um único horário
            else if (!empty($_POST['horario_selecionado'])) {
                $horarios[] = $_POST['horario_selecionado'];
            }
            
            if (empty($horarios)) {
                throw new Exception('É necessário selecionar pelo menos um horário para a consulta.');
            }
            
            // Obter informações do serviço
            $sqlServico = "SELECT nome_servico, preco, duracao_minutos FROM servicos WHERE id = ?";
            $stmtServico = $conn->prepare($sqlServico);
            $stmtServico->bind_param("i", $servico_id);
            $stmtServico->execute();
            $resultServico = $stmtServico->get_result();
            
            if ($resultServico->num_rows === 0) {
                throw new Exception('Serviço não encontrado.');
            }
            
            $servico = $resultServico->fetch_assoc();
            $procedimento = $servico['nome_servico'];
            $valor_servico = $servico['preco'];
            $duracao_minutos = isset($servico['duracao_minutos']) ? $servico['duracao_minutos'] : 30;
            $stmtServico->close();
            
            // Verificar si tenemos IDs de grupo para actualización en masa
            $grupo_ids = [];
            if (!empty($_POST['grupo_ids'])) {
                $grupo_ids = json_decode($_POST['grupo_ids'], true);
                if (!is_array($grupo_ids)) {
                    $grupo_ids = [$id]; // Fallback para el ID actual
                }
            } else {
                $grupo_ids = [$id]; // Apenas el ID actual
            }
            
            // Iniciar transação
            $conn->begin_transaction();
            $transactionStarted = true;
            
            // Data base (apenas la data, sin horario)
            $data_apenas = date('Y-m-d', strtotime($data_consulta));
            
            // Primero, eliminar todas las consultas del grupo existente, excepto la primera
            if (count($grupo_ids) > 1) {
                $ids_to_delete = array_slice($grupo_ids, 1);
                foreach ($ids_to_delete as $delete_id) {
                    // Eliminar registros financieros asociados
                    $sqlDeleteFinanceiro = "DELETE FROM financeiro WHERE consulta_id = ?";
                    $stmtDeleteFinanceiro = $conn->prepare($sqlDeleteFinanceiro);
                    $stmtDeleteFinanceiro->bind_param("i", $delete_id);
                    $stmtDeleteFinanceiro->execute();
                    $stmtDeleteFinanceiro->close();
                    
                    // Eliminar la consulta
                    $sqlDeleteConsulta = "DELETE FROM consultas WHERE id = ?";
                    $stmtDeleteConsulta = $conn->prepare($sqlDeleteConsulta);
                    $stmtDeleteConsulta->bind_param("i", $delete_id);
                    $stmtDeleteConsulta->execute();
                    $stmtDeleteConsulta->close();
                }
            }
            
            // Actualizar la primera consulta con los nuevos datos
            $data_hora_consulta = $data_apenas . ' ' . $horarios[0];
            
            // Verificar conflictos de horario para el primer slot
            $sqlCheckHorario = "SELECT id FROM consultas 
                               WHERE profissional_id = ? 
                               AND id != ? 
                               AND data_consulta = ?
                               AND status != 'Cancelada'";
            $stmtCheckHorario = $conn->prepare($sqlCheckHorario);
            $stmtCheckHorario->bind_param("iis", $profissional_id, $grupo_ids[0], $data_hora_consulta);
            $stmtCheckHorario->execute();
            $resultCheckHorario = $stmtCheckHorario->get_result();
            
            if ($resultCheckHorario->num_rows > 0) {
                throw new Exception('Ya existe una consulta agendada para este profesional en el horario ' . date('H:i', strtotime($horarios[0])) . '.');
            }
            $stmtCheckHorario->close();
            
            // Actualizar la primera consulta
            $sqlUpdateConsulta = "UPDATE consultas 
                                 SET paciente_cpf = ?, 
                                     profissional_id = ?, 
                                     data_consulta = ?, 
                                     procedimento = ?, 
                                     observacoes = ?, 
                                     status = ?,
                                     servico_id = ?,
                                     duracao_minutos = ?
                                 WHERE id = ?";
            $stmtUpdateConsulta = $conn->prepare($sqlUpdateConsulta);
            $stmtUpdateConsulta->bind_param(
                "sissssiis", 
                $paciente_cpf, 
                $profissional_id, 
                $data_hora_consulta, 
                $procedimento, 
                $observacoes, 
                $status, 
                $servico_id,
                $duracao_minutos,
                $grupo_ids[0]
            );
            
            if (!$stmtUpdateConsulta->execute()) {
                throw new Exception('Error al actualizar consulta: ' . $stmtUpdateConsulta->error);
            }
            $stmtUpdateConsulta->close();
            
            // Actualizar o crear registro financiero para la primera consulta
            $sqlCheckFinanceiro = "SELECT id FROM financeiro WHERE consulta_id = ?";
            $stmtCheckFinanceiro = $conn->prepare($sqlCheckFinanceiro);
            $stmtCheckFinanceiro->bind_param("i", $grupo_ids[0]);
            $stmtCheckFinanceiro->execute();
            $resultCheckFinanceiro = $stmtCheckFinanceiro->get_result();
            
            if ($resultCheckFinanceiro->num_rows > 0) {
                // Actualizar registro financiero existente
                $row = $resultCheckFinanceiro->fetch_assoc();
                $financeiro_id = $row['id'];
                
                $sqlUpdateFinanceiro = "UPDATE financeiro 
                                       SET servico_id = ?, 
                                           valor = ? 
                                       WHERE id = ?";
                $stmtUpdateFinanceiro = $conn->prepare($sqlUpdateFinanceiro);
                $stmtUpdateFinanceiro->bind_param("idi", $servico_id, $valor_servico, $financeiro_id);
                
                if (!$stmtUpdateFinanceiro->execute()) {
                    throw new Exception('Error al actualizar financiero: ' . $stmtUpdateFinanceiro->error);
                }
                $stmtUpdateFinanceiro->close();
            } else {
                // Crear nuevo registro financiero
                $sqlInsertFinanceiro = "INSERT INTO financeiro (
                                    consulta_id, 
                                    servico_id, 
                                    valor, 
                                    status_pagamento
                                ) VALUES (?, ?, ?, 'PENDENTE')";
                $stmtInsertFinanceiro = $conn->prepare($sqlInsertFinanceiro);
                $stmtInsertFinanceiro->bind_param("iid", $grupo_ids[0], $servico_id, $valor_servico);
                
                if (!$stmtInsertFinanceiro->execute()) {
                    throw new Exception('Error al registrar financiero: ' . $stmtInsertFinanceiro->error);
                }
                $stmtInsertFinanceiro->close();
            }
            $stmtCheckFinanceiro->close();
            
            // Crear nuevas consultas para los horarios adicionales
            $consulta_ids = [$grupo_ids[0]]; // Comenzar con el ID de la primera consulta
            
            // Crear una consulta para cada horario adicional
            for ($i = 1; $i < count($horarios); $i++) {
                $data_hora_consulta = $data_apenas . ' ' . $horarios[$i];
                
                // Verificar si el horario ya está ocupado
                $sqlCheckHorario = "SELECT id FROM consultas 
                                   WHERE profissional_id = ? 
                                   AND data_consulta = ?
                                   AND status != 'Cancelada'";
                $stmtCheckHorario = $conn->prepare($sqlCheckHorario);
                $stmtCheckHorario->bind_param("is", $profissional_id, $data_hora_consulta);
                $stmtCheckHorario->execute();
                $resultCheckHorario = $stmtCheckHorario->get_result();
                
                if ($resultCheckHorario->num_rows > 0) {
                    throw new Exception('El horario ' . date('H:i', strtotime($horarios[$i])) . ' ya está ocupado.');
                }
                $stmtCheckHorario->close();
                
                // Insertar nueva consulta
                $sqlInsertConsulta = "INSERT INTO consultas (
                                    paciente_cpf, 
                                    profissional_id, 
                                    data_consulta, 
                                    procedimento, 
                                    observacoes, 
                                    status, 
                                    servico_id,
                                    duracao_minutos
                                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
                $stmtInsertConsulta = $conn->prepare($sqlInsertConsulta);
                $stmtInsertConsulta->bind_param(
                    "sissssis", 
                    $paciente_cpf, 
                    $profissional_id, 
                    $data_hora_consulta, 
                    $procedimento, 
                    $observacoes, 
                    $status, 
                    $servico_id,
                    $duracao_minutos
                );
                
                if (!$stmtInsertConsulta->execute()) {
                    throw new Exception('Error al crear consulta adicional: ' . $stmtInsertConsulta->error);
                }
                
                $consulta_id = $conn->insert_id;
                $consulta_ids[] = $consulta_id;
                
                // Insertar registro financiero para la nueva consulta
                $sqlInsertFinanceiro = "INSERT INTO financeiro (
                                    consulta_id, 
                                    servico_id, 
                                    valor, 
                                    status_pagamento
                                ) VALUES (?, ?, ?, 'PENDENTE')";
                $stmtInsertFinanceiro = $conn->prepare($sqlInsertFinanceiro);
                $stmtInsertFinanceiro->bind_param("iid", $consulta_id, $servico_id, $valor_servico);
                
                if (!$stmtInsertFinanceiro->execute()) {
                    throw new Exception('Error al registrar financiero para consulta adicional: ' . $stmtInsertFinanceiro->error);
                }
                
                $stmtInsertFinanceiro->close();
                $stmtInsertConsulta->close();
            }
            
            // Commit de la transacción
            $conn->commit();
            $transactionStarted = false;
            
            echo json_encode([
                'success' => true, 
                'message' => count($consulta_ids) > 1 
                    ? 'Grupo de consultas actualizado con éxito!' 
                    : 'Consulta actualizada con éxito!',
                'ids' => $consulta_ids,
                'detalhes' => [
                    'procedimento' => $procedimento,
                    'valor' => $valor_servico,
                    'consultas_actualizadas' => count($consulta_ids)
                ]
            ]);
            break;
            
        default:
            throw new Exception('Ação não reconhecida.');
    }
} catch (Exception $e) {
    // Rollback em caso de erro, se a transação foi iniciada
    if ($transactionStarted) {
        $conn->rollback();
    }
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

$conn->close();
?>

