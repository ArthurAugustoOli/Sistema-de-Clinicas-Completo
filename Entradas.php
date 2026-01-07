<?php
// Public/entradas/index.php

require_once 'config/config.php';
require_once 'models/Entradas.php';

use App\models\Entradas;

$entradaModel = new Entradas();
$msg = "";

// --- Processamento via POST ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    // criar entrada
    if ($_POST['action'] === 'create_entrada') {
        $valor         = str_replace(',', '.', $_POST['valor']);
        $data_hora     = $_POST['data_hora'];
        $cliente       = $_POST['cliente'];
        $forma         = $_POST['forma_pagamento'];
        $parcelas      = in_array($forma, ['credito','boleto']) ? intval($_POST['parcelas']) : null;
        $valor_parcela = $parcelas ? round($valor / $parcelas, 2) : null;
        $montante      = $parcelas ? $valor : null;
        // ao criar, inicia com zero parcelas pagas
        $parcelas_pagas = 0;
        try {
            $entradaModel->createEntrada(
                $valor,
                $data_hora,
                $cliente,
                $forma,
                $parcelas,
                $valor_parcela,
                $montante,
                $parcelas_pagas
            );
            $msg = "Entrada cadastrada com sucesso!";
        } catch (Exception $e) {
            $msg = "Erro ao cadastrar entrada: " . $e->getMessage();
        }
    }
    // atualizar entrada
    elseif ($_POST['action'] === 'update_entrada') {
        $id              = intval($_POST['id_entrada']);
        $valor           = str_replace(',', '.', $_POST['valor']);
        $data_hora       = $_POST['data_hora'];
        $cliente         = $_POST['cliente'];
        $forma           = $_POST['forma_pagamento'];
        $parcelas        = in_array($forma, ['credito','boleto']) ? intval($_POST['parcelas']) : null;
        $valor_parcela   = $parcelas ? round($valor / $parcelas, 2) : null;
        $montante        = $parcelas ? $valor : null;
        // mantém o número atual de parcelas pagas
        // vamos buscar antes de atualizar
        $entAntiga = $entradaModel->getEntradasPaginadas(0, PHP_INT_MAX);
        $parcelas_pagas = 0;
        foreach ($entAntiga as $ea) {
            if ($ea['id_entrada'] === $id) {
                $parcelas_pagas = (int)$ea['parcelas_pagas'];
                break;
            }
        }
        try {
            $entradaModel->updateEntrada(
                $id,
                $valor,
                $data_hora,
                $cliente,
                $forma,
                $parcelas,
                $valor_parcela,
                $montante,
                $parcelas_pagas
            );
            $msg = "Entrada atualizada com sucesso!";
        } catch (Exception $e) {
            $msg = "Erro ao atualizar entrada: " . $e->getMessage();
        }
    }
    // deletar entrada
    elseif ($_POST['action'] === 'delete_entrada') {
        $id = intval($_POST['id_entrada']);
        try {
            $entradaModel->deleteEntrada($id);
            $msg = "Entrada excluída com sucesso!";
        } catch (Exception $e) {
            $msg = "Erro ao excluir entrada: " . $e->getMessage();
        }
    }
    // marcar mês atual como pago
    elseif ($_POST['action'] === 'mark_paid') {
        $id_entrada = intval($_POST['id_entrada']);
        $ano  = date('Y');
        $mes  = date('n');
        try {
            $entradaModel->markPayment($id_entrada, $ano, $mes);
            $msg = "Mês {$mes}/{$ano} marcado como pago!";
        } catch (Exception $e) {
            $msg = "Erro ao marcar pagamento: " . $e->getMessage();
        }
    }
}

// --- Listagem e paginação ---
$limite_por_pagina = 10;
$total             = $entradaModel->getTotalEntradas();
$total_paginas     = ceil($total / $limite_por_pagina);
$pag               = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$pag               = min($pag, $total_paginas);
$offset            = ($pag - 1) * $limite_por_pagina;
$entradas          = $entradaModel->getEntradasPaginadas($offset, $limite_por_pagina);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <title>Entradas</title>
  <meta name="viewport" content="width=device-width,initial-scale=1.0">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="p-4">

<div class="container">
  <h1 class="mb-4">Gerenciar Entradas</h1>
  <?php if ($msg): ?>
    <div class="alert alert-info"><?=htmlspecialchars($msg)?></div>
  <?php endif; ?>

  <!-- Botão Nova Entrada -->
  <button class="btn btn-primary mb-3" data-bs-toggle="modal" data-bs-target="#novaEntradaModal">
    + Nova Entrada
  </button>

  <!-- Tabela -->
  <div class="table-responsive">
    <table class="table table-bordered">
      <thead class="table-light">
        <tr>
          <th>ID</th>
          <th>Valor (R$)</th>
          <th>Data + Hora</th>
          <th>Cliente</th>
          <th>Forma</th>
          <th>Parcelas</th>
          <th>Valor Parcela</th>
          <th>Montante</th>
          <th>Meses Pagos</th>
          <th>Ações</th>
        </tr>
      </thead>
      <tbody>
        <?php if ($entradas): foreach ($entradas as $e): ?>
          <tr>
            <td><?=$e['id_entrada']?></td>
            <td><?=number_format($e['valor'],2,',','.')?></td>
            <td><?=$e['data_hora']?></td>
            <td><?=htmlspecialchars($e['cliente'])?></td>
            <td><?=ucfirst($e['forma_pagamento'])?></td>
            <td><?=$e['parcelas'] ?? '-'?></td>
            <td>
              <?= isset($e['valor_parcela'])
                   ? number_format($e['valor_parcela'],2,',','.')
                   : '-' ?>
            </td>
            <td>
              <?= isset($e['montante'])
                   ? number_format($e['montante'],2,',','.')
                   : '-' ?>
            </td>
            <td>
              <?php
                $pagList = $entradaModel->getPagamentos($e['id_entrada']);
                if ($pagList) {
                  $meses = array_map(fn($p)=> "{$p['mes']}/{$p['ano']}", $pagList);
                  echo implode(', ', $meses);
                } else {
                  echo '-';
                }
              ?>
            </td>
            <td class="d-flex gap-1">
              <!-- Editar -->
              <button class="btn btn-sm btn-outline-secondary"
                      data-bs-toggle="modal" data-bs-target="#editEntradaModal"
                      data-id="<?=$e['id_entrada']?>"
                      data-valor="<?=number_format($e['valor'],2,',','.')?>"
                      data-datahora="<?=$e['data_hora']?>"
                      data-cliente="<?=htmlspecialchars($e['cliente'])?>"
                      data-forma="<?=$e['forma_pagamento']?>"
                      data-parcelas="<?=$e['parcelas']?>">
                Editar
              </button>
              <!-- Excluir -->
              <button class="btn btn-sm btn-outline-danger"
                      data-bs-toggle="modal" data-bs-target="#deleteEntradaModal"
                      data-id="<?=$e['id_entrada']?>">
                Excluir
              </button>
              <!-- Marcar mês atual -->
              <form method="post" style="display:inline">
                <input type="hidden" name="action" value="mark_paid">
                <input type="hidden" name="id_entrada" value="<?=$e['id_entrada']?>">
                <button type="submit" class="btn btn-sm btn-outline-success">
                  Marcar mês atual pago
                </button>
              </form>
            </td>
          </tr>
        <?php endforeach; else: ?>
          <tr><td colspan="10" class="text-center">Nenhuma entrada registrada.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

  <!-- Paginação -->
  <nav>
    <ul class="pagination">
      <li class="page-item <?= $pag<=1?'disabled':'' ?>">
        <a class="page-link" href="?page=<?=$pag-1?>">Anterior</a>
      </li>
      <?php for($i=1;$i<=$total_paginas;$i++): ?>
        <li class="page-item <?= $i===$pag?'active':'' ?>">
          <a class="page-link" href="?page=<?=$i?>"><?=$i?></a>
        </li>
      <?php endfor; ?>
      <li class="page-item <?= $pag>=$total_paginas?'disabled':'' ?>">
        <a class="page-link" href="?page=<?=$pag+1?>">Próxima</a>
      </li>
    </ul>
  </nav>
</div>

<!-- Modal: Nova Entrada -->
<div class="modal fade" id="novaEntradaModal" tabindex="-1">
  <div class="modal-dialog">
    <form method="post" class="modal-content">
      <input type="hidden" name="action" value="create_entrada">
      <div class="modal-header">
        <h5 class="modal-title">Nova Entrada</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="mb-3">
          <label>Valor (R$)</label>
          <input type="text" name="valor" class="form-control" required>
        </div>
        <div class="mb-3">
          <label>Data + Hora</label>
          <input type="datetime-local" name="data_hora" class="form-control" required>
        </div>
        <div class="mb-3">
          <label>Cliente</label>
          <input type="text" name="cliente" class="form-control" required>
        </div>
        <div class="mb-3">
          <label>Forma de Pagamento</label>
          <select name="forma_pagamento" class="form-select" id="formaNova" required>
            <option value="boleto">Boleto</option>
            <option value="pix">Pix</option>
            <option value="dinheiro">Dinheiro</option>
            <option value="credito">Crédito</option>
            <option value="debito">Débito</option>
          </select>
        </div>
        <div class="mb-3 d-none" id="groupParcelasNova">
          <label>Parcelas</label>
          <input type="number" name="parcelas" class="form-control" min="1">
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button type="submit" class="btn btn-primary">Salvar</button>
      </div>
    </form>
  </div>
</div>

<!-- Modal: Editar Entrada -->
<div class="modal fade" id="editEntradaModal" tabindex="-1">
  <div class="modal-dialog">
    <form method="post" class="modal-content">
      <input type="hidden" name="action" value="update_entrada">
      <input type="hidden" name="id_entrada" id="edit-id">
      <div class="modal-header">
        <h5 class="modal-title">Editar Entrada</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="mb-3">
          <label>Valor (R$)</label>
          <input type="text" name="valor" id="edit-valor" class="form-control" required>
        </div>
        <div class="mb-3">
          <label>Data + Hora</label>
          <input type="datetime-local" name="data_hora" id="edit-datahora" class="form-control" required>
        </div>
        <div class="mb-3">
          <label>Cliente</label>
          <input type="text" name="cliente" id="edit-cliente" class="form-control" required>
        </div>
        <div class="mb-3">
          <label>Forma de Pagamento</label>
          <select name="forma_pagamento" class="form-select" id="formaEdit" required>
            <option value="boleto">Boleto</option>
            <option value="pix">Pix</option>
            <option value="dinheiro">Dinheiro</option>
            <option value="credito">Crédito</option>
            <option value="debito">Débito</option>
          </select>
        </div>
        <div class="mb-3 d-none" id="groupParcelasEdit">
          <label>Parcelas</label>
          <input type="number" name="parcelas" id="edit-parcelas" class="form-control" min="1">
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button type="submit" class="btn btn-primary">Atualizar</button>
      </div>
    </form>
  </div>
</div>

<!-- Modal: Excluir Entrada -->
<div class="modal fade" id="deleteEntradaModal" tabindex="-1">
  <div class="modal-dialog">
    <form method="post" class="modal-content">
      <input type="hidden" name="action" value="delete_entrada">
      <input type="hidden" name="id_entrada" id="delete-id">
      <div class="modal-header">
        <h5 class="modal-title">Excluir Entrada</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <p>Tem certeza que deseja excluir esta entrada?</p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button type="submit" class="btn btn-danger">Excluir</button>
      </div>
    </form>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
  // show/hide parcelas
  function toggleParcelas(selectEl, groupId) {
    document.getElementById(groupId).classList[
      (selectEl.value === 'credito' || selectEl.value === 'boleto') ? 'remove' : 'add'
    ]('d-none');
  }

  // novo
  document.getElementById('formaNova').addEventListener('change', function(){
    toggleParcelas(this, 'groupParcelasNova');
  });

  // editar: preencher e ajustar parcelas
  var editModal = document.getElementById('editEntradaModal');
  editModal.addEventListener('show.bs.modal', function(e){
    var btn = e.relatedTarget;
    document.getElementById('edit-id').value        = btn.dataset.id;
    document.getElementById('edit-valor').value     = btn.dataset.valor;
    document.getElementById('edit-datahora').value  = btn.dataset.datahora.replace(' ', 'T');
    document.getElementById('edit-cliente').value   = btn.dataset.cliente;
    document.getElementById('formaEdit').value      = btn.dataset.forma;
    document.getElementById('edit-parcelas').value  = btn.dataset.parcelas;
    toggleParcelas(document.getElementById('formaEdit'), 'groupParcelasEdit');
  });
  editModal.querySelector('#formaEdit').addEventListener('change', function(){
    toggleParcelas(this, 'groupParcelasEdit');
  });

  // delete
  var delModal = document.getElementById('deleteEntradaModal');
  delModal.addEventListener('show.bs.modal', function(e){
    document.getElementById('delete-id').value = e.relatedTarget.dataset.id;
  });
</script>
</body>
</html>
