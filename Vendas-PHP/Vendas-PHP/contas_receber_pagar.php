<?php
require "init.php";
if (!isset($_SESSION['user_id']) || ($_SESSION['user_type'] != 'funcionario' && $_SESSION['user_type'] != 'gerente')) {
    header("Location: login.php");
    exit;
}

include "header.php";
require "conexao.php";

$id = isset($_GET['id']) ? intval($_GET['id']) : null;

if (!$id) {
    header("Location: contas_receber.php");
    exit;
}

// Busca dados da venda
$stmt = $conn->prepare("SELECT s.*, p.name as product_name, u.name as cliente_nome 
                       FROM sales s 
                       LEFT JOIN products p ON s.product_id = p.id 
                       LEFT JOIN users u ON s.cliente_id = u.id 
                       WHERE s.id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$venda = $result->fetch_assoc();
$stmt->close();

if (!$venda) {
    header("Location: contas_receber.php");
    exit;
}

$metodos_pagamento = [
    "Dinheiro",
    "PIX", 
    "Cartão de Crédito",
    "Cartão de Débito",
    "Boleto",
    "Transferência",
    "Google Pay"
];
?>

<h2 class="mb-4"><i class="bi bi-cash-coin me-2"></i>Registrar Recebimento</h2>

<div class="bg-white p-4 rounded shadow-sm">
    <div class="row mb-4">
        <div class="col-md-6">
            <h5>Informações da Venda</h5>
            <table class="table table-bordered">
                <tr>
                    <th>Cliente:</th>
                    <td><?php echo htmlspecialchars($venda['cliente_nome']); ?></td>
                </tr>
                <tr>
                    <th>Produto:</th>
                    <td><?php echo htmlspecialchars($venda['product_name']); ?></td>
                </tr>
                <tr>
                    <th>Quantidade:</th>
                    <td><?php echo $venda['quantity']; ?></td>
                </tr>
                <tr>
                    <th>Valor Total:</th>
                    <td class="fw-bold">R$ <?php echo number_format($venda['total'], 2, ',', '.'); ?></td>
                </tr>
                <tr>
                    <th>Data Venda:</th>
                    <td><?php echo date('d/m/Y H:i', strtotime($venda['sale_date'])); ?></td>
                </tr>
            </table>
        </div>
    </div>

    <form action="contas_receber_processar.php" method="POST">
        <input type="hidden" name="id" value="<?php echo $id; ?>">
        
        <div class="row">
            <div class="col-md-6">
                <div class="form-group mb-3">
                    <label class="form-label">Método de Recebimento *</label>
                    <select name="metodo_pagamento" class="form-control" required>
                        <option value="">Selecione...</option>
                        <?php foreach ($metodos_pagamento as $metodo): ?>
                            <option value="<?php echo htmlspecialchars($metodo); ?>">
                                <?php echo htmlspecialchars($metodo); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group mb-3">
                    <label class="form-label">Data do Recebimento *</label>
                    <input type="date" name="data_pagamento" class="form-control" 
                           value="<?php echo date('Y-m-d'); ?>" required>
                </div>
            </div>
        </div>
        
        <div class="form-group mb-3">
            <label class="form-label">Observações</label>
            <textarea name="observacoes" class="form-control" rows="3" 
                      placeholder="Observações sobre o recebimento..."></textarea>
        </div>
        
        <div class="d-flex gap-2">
            <button type="submit" class="btn btn-success">
                <i class="bi bi-check-circle me-1"></i>Confirmar Recebimento
            </button>
            <a href="contas_receber.php" class="btn btn-secondary">
                <i class="bi bi-x-circle me-1"></i>Cancelar
            </a>
        </div>
    </form>
</div>

<?php include "footer.php"; ?>