<?php
namespace App\models;

use Exception;

class Entradas
{
    private $conn;

    public function __construct()
    {
        require_once __DIR__ . '/../config/config.php';
        global $mysqli;
        $this->conn = $mysqli;
    }

    /**
     * Insere nova entrada
     */
    public function createEntrada(
        float $valor,
        string $data_hora,
        string $cliente,
        string $forma_pagamento,
        ?int   $parcelas,
        ?float $valor_parcela,
        ?float $montante,
        int   $parcelas_pagas
    ) {
        $sql = "INSERT INTO entradas \
            (valor, data_hora, cliente, forma_pagamento, parcelas, valor_parcela, montante, parcelas_pagas)\
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            throw new Exception("Erro no prepare (createEntrada): " . $this->conn->error);
        }
        $stmt->bind_param(
            "dsssiddi",
            $valor,
            $data_hora,
            $cliente,
            $forma_pagamento,
            $parcelas,
            $valor_parcela,
            $montante,
            $parcelas_pagas
        );
        $stmt->execute();
        $insertId = $this->conn->insert_id;
        $stmt->close();
        return $insertId;
    }

    /**
     * Atualiza uma entrada existente
     */
    public function updateEntrada(
        int   $id_entrada,
        float $valor,
        string $data_hora,
        string $cliente,
        string $forma_pagamento,
        ?int   $parcelas,
        ?float $valor_parcela,
        ?float $montante,
        int   $parcelas_pagas
    ) {
        $sql = "UPDATE entradas SET \
            valor = ?, data_hora = ?, cliente = ?, forma_pagamento = ?, \
            parcelas = ?, valor_parcela = ?, montante = ?, parcelas_pagas = ? \
            WHERE id_entrada = ?";
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            throw new Exception("Erro no prepare (updateEntrada): " . $this->conn->error);
        }
        $stmt->bind_param(
            "dsssiddii",
            $valor,
            $data_hora,
            $cliente,
            $forma_pagamento,
            $parcelas,
            $valor_parcela,
            $montante,
            $parcelas_pagas,
            $id_entrada
        );
        $stmt->execute();
        $stmt->close();
        return true;
    }

    /**
     * Exclui uma entrada
     */
    public function deleteEntrada(int $id_entrada)
    {
        $sql = "DELETE FROM entradas WHERE id_entrada = ?";
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            throw new Exception("Erro no prepare (deleteEntrada): " . $this->conn->error);
        }
        $stmt->bind_param("i", $id_entrada);
        $stmt->execute();
        $stmt->close();
        return true;
    }

    /**
     * Retorna todas as entradas
     */
    public function getAllEntradas(): array
    {
        $sql = "SELECT * FROM entradas ORDER BY data_hora DESC";
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            throw new Exception("Erro no prepare (getAllEntradas): " . $this->conn->error);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        $entradas = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $entradas;
    }

    /**
     * Conta todas as entradas (para paginação)
     */
    public function getTotalEntradas(): int
    {
        $sql = "SELECT COUNT(*) AS total FROM entradas";
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            throw new Exception("Erro no prepare (getTotalEntradas): " . $this->conn->error);
        }
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return (int)$row['total'];
    }

    /**
     * Retorna entradas paginadas
     */
    public function getEntradasPaginadas(int $offset, int $limite): array
    {
        $sql = "SELECT * FROM entradas ORDER BY data_hora DESC LIMIT ? OFFSET ?";
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            throw new Exception("Erro no prepare (getEntradasPaginadas): " . $this->conn->error);
        }
        $stmt->bind_param("ii", $limite, $offset);
        $stmt->execute();
        $entradas = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $entradas;
    }

    /**
     * Marca pagamento de um mês para uma entrada
     */
    public function markPayment(int $id_entrada, int $ano, int $mes)
    {
        $sql = "INSERT IGNORE INTO pagamentos (entrada_id, ano, mes, pago_em) VALUES (?, ?, ?, NOW())";
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            throw new Exception("Erro no prepare (markPayment): " . $this->conn->error);
        }
        $stmt->bind_param("iii", $id_entrada, $ano, $mes);
        $stmt->execute();
        $stmt->close();
        return true;
    }

    /**
     * Retorna lista de meses pagos para uma entrada
     */
    public function getPagamentos(int $id_entrada): array
    {
        $sql = "SELECT ano, mes FROM pagamentos WHERE entrada_id = ? ORDER BY ano DESC, mes DESC";
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            throw new Exception("Erro no prepare (getPagamentos): " . $this->conn->error);
        }
        $stmt->bind_param("i", $id_entrada);
        $stmt->execute();
        $res = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $res;
    }
}
