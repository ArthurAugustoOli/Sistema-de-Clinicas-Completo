<?php
namespace App\Models;

use Exception;

class Despesas
{
    private $conn;

    public function __construct()
    {
        require_once __DIR__ . '/../config/config.php';
        global $mysqli;
        $this->conn = $mysqli;
    }

    /**
     * Retorna todas as despesas
     */
    public function getAllDespesas()
    {
        $sql = "SELECT * FROM despesas";
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            throw new Exception("Erro no prepare (getAllDespesas): " . $this->conn->error);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        $despesas = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $despesas;
    }

    /**
     * Retorna uma despesa pelo ID
     */
    public function getDespesaById($id_despesa)
    {
        $sql = "SELECT * FROM despesas WHERE id_despesa = ?";
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            throw new Exception("Erro no prepare (getDespesaById): " . $this->conn->error);
        }
        $stmt->bind_param("i", $id_despesa);
        $stmt->execute();
        $despesa = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $despesa;
    }

    /**
     * Insere nova despesa
     */
    public function createDespesa($categoria, $descricao, $valor, $data_despesa, $status)
    {
        $sql = "INSERT INTO despesas (categoria, descricao, valor, data_despesa, status)
                VALUES (?, ?, ?, ?, ?)";
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            throw new Exception("Erro no prepare (createDespesa): " . $this->conn->error);
        }
        $stmt->bind_param("ssdss", $categoria, $descricao, $valor, $data_despesa, $status);
        $stmt->execute();
        $insertId = $this->conn->insert_id;
        $stmt->close();
        return $insertId;
    }

    /**
     * Atualiza uma despesa existente
     */
    public function updateDespesa($id_despesa, $categoria, $descricao, $valor, $data_despesa, $status)
    {
        $sql = "UPDATE despesas
                SET categoria = ?, descricao = ?, valor = ?, data_despesa = ?, status = ?
                WHERE id_despesa = ?";
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            throw new Exception("Erro no prepare (updateDespesa): " . $this->conn->error);
        }
        $stmt->bind_param("ssdssi", $categoria, $descricao, $valor, $data_despesa, $status, $id_despesa);
        $stmt->execute();
        $stmt->close();
        return true;
    }

    /**
     * Exclui uma despesa
     */
    public function deleteDespesa($id_despesa)
    {
        $sql = "DELETE FROM despesas WHERE id_despesa = ?";
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            throw new Exception("Erro no prepare (deleteDespesa): " . $this->conn->error);
        }
        $stmt->bind_param("i", $id_despesa);
        $stmt->execute();
        $stmt->close();
        return true;
    }

    /**
     * Conta todas as despesas (sem filtro)
     */
    public function getTotalDespesas()
    {
        $sql = "SELECT COUNT(*) AS total FROM despesas";
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            throw new Exception("Erro no prepare (getTotalDespesas): " . $this->conn->error);
        }
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return (int)$row['total'];
    }

    /**
     * Retorna despesas paginadas
     */
    public function getDespesasPaginadas($offset, $limite)
    {
        $sql = "SELECT * FROM despesas ORDER BY data_despesa DESC LIMIT ? OFFSET ?";
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            throw new Exception("Erro no prepare (getDespesasPaginadas): " . $this->conn->error);
        }
        $stmt->bind_param("ii", $limite, $offset);
        $stmt->execute();
        $despesas = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $despesas;
    }
}
