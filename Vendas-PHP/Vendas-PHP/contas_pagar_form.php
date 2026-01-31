<?php
require "init.php";
if (!isset($_SESSION['user_id']) || ($_SESSION['user_type'] != 'funcionario' && $_SESSION['user_type'] != 'gerente')) {
    header("Location: login.php");
    exit;
}

include "header.php";
require "conexao.php";

$id = null;
$conta = array(
    'descricao' => '',
    'valor' => '',
    'categoria' => '',
    'data_vencimento' => '',
    'data_pagamento' => '',
    'status' => 'pendente',
    'metodo_pagamento' => '',
    'observacoes' => ''
);

$action = isset($_GET['action']) ? $_GET['action'] : '';

if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $id = intval($_GET['id']);
    $stmt = $conn->prepare("SELECT * FROM contas_pagar WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $conta = $result->fetch_assoc();
    }
    $stmt->close();
}

// Se for ação de pagar, muda status para pago e preenche data atual
if ($action == 'pay' && $id) {
    $conta['status'] = 'pago';
    $conta['data_pagamento'] = date('Y-m-d');
}

$categorias = [
    "Fornecedores",
    "Funcionários",
    "Impostos",
    "Aluguel",
    "Energia/Água",
    "Internet/Telefone",
    "Manutenção",
    "Marketing",
    "Transporte",
    "Outros"
];

$metodos_pagamento = [
    "Dinheiro",
    "PIX",
    "Cartão de Crédito",
    "Cartão de Débito",
    "Boleto",
    "Transferência",
    "Google Pay",
    "Carteira"
];
?>

<h2 class="mb-4"><?php echo $id ? "Editar Conta" : "Nova Conta a Pagar"; ?></h2>

<form action="contas_pagar_salvar.php" method="POST" class="bg-white p-4 rounded shadow-sm">
    <?php if ($id): ?>
        <input type="hidden" name="id" value="<?php echo $id; ?>">
    <?php endif; ?>

    <div class="row">
        <div class="col-md-8">
            <div class="form-group mb-3">
                <label class="form-label">Descrição *</label>
                <input type="text" name="descricao" class="form-control" 
                       value="<?php echo htmlspecialchars($conta['descricao']); ?>" required>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="form-group mb-3">
                <label class="form-label">Valor *</label>
                <input type="number" step="0.01" name="valor" class="form-control" 
                       value="<?php echo htmlspecialchars($conta['valor']); ?>" required>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6">
            <div class="form-group mb-3">
                <label class="form-label">Categoria</label>
                <select name="categoria" class="form-control">
                    <option value="">Selecione...</option>
                    <?php foreach ($categorias as $cat): ?>
                        <option value="<?php echo htmlspecialchars($cat); ?>" 
                            <?php echo ($conta['categoria'] == $cat ? "selected" : ""); ?>>
                            <?php echo htmlspecialchars($cat); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        
        <div class="col-md-6">
            <div class="form-group mb-3">
                <label class="form-label">Data de Vencimento *</label>
                <input type="date" name="data_vencimento" class="form-control" 
                       value="<?php echo htmlspecialchars($conta['data_vencimento']); ?>" required>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-4">
            <div class="form-group mb-3">
                <label class="form-label">Status</label>
                <select name="status" id="status" class="form-control">
                    <option value="pendente" <?php echo ($conta['status'] == 'pendente' ? "selected" : ""); ?>>Pendente</option>
                    <option value="pago" <?php echo ($conta['status'] == 'pago' ? "selected" : ""); ?>>Pago</option>
                    <option value="atrasado" <?php echo ($conta['status'] == 'atrasado' ? "selected" : ""); ?>>Atrasado</option>
                </select>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="form-group mb-3">
                <label class="form-label">Data de Pagamento</label>
                <input type="date" name="data_pagamento" id="data_pagamento" class="form-control" 
                       value="<?php echo htmlspecialchars($conta['data_pagamento']); ?>"
                       <?php echo ($conta['status'] != 'pago' ? 'disabled' : ''); ?>>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="form-group mb-3">
                <label class="form-label">Método de Pagamento</label>
                <select name="metodo_pagamento" id="metodo_pagamento" class="form-control"
                        <?php echo ($conta['status'] != 'pago' ? 'disabled' : ''); ?>>
                    <option value="">Selecione...</option>
                    <?php foreach ($metodos_pagamento as $metodo): ?>
                        <option value="<?php echo htmlspecialchars($metodo); ?>" 
                            <?php echo ($conta['metodo_pagamento'] == $metodo ? "selected" : ""); ?>>
                            <?php echo htmlspecialchars($metodo); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
    </div>

    <div class="form-group mb-3">
        <label class="form-label">Observações</label>
        <textarea name="observacoes" class="form-control" rows="3"><?php echo htmlspecialchars($conta['observacoes']); ?></textarea>
    </div>

    <div class="d-flex gap-2">
        <button type="submit" class="btn btn-primary">
            <i class="bi bi-check-circle me-1"></i>Salvar
        </button>
        <a href="contas_pagar.php" class="btn btn-secondary">
            <i class="bi bi-x-circle me-1"></i>Cancelar
        </a>
    </div>
</form>

<script>
document.addEventListener("DOMContentLoaded", function() {
    const statusSelect = document.getElementById("status");
    const dataPagamento = document.getElementById("data_pagamento");
    const metodoPagamento = document.getElementById("metodo_pagamento");
    
    function toggleCamposPagamento() {
        if (statusSelect.value === "pago") {
            dataPagamento.disabled = false;
            dataPagamento.required = true;
            metodoPagamento.disabled = false;
            metodoPagamento.required = true;
            
            // Se data_pagamento estiver vazia, preenche com data atual
            if (!dataPagamento.value) {
                const today = new Date().toISOString().split('T')[0];
                dataPagamento.value = today;
            }
        } else {
            dataPagamento.disabled = true;
            dataPagamento.required = false;
            metodoPagamento.disabled = true;
            metodoPagamento.required = false;
        }
    }
    
    statusSelect.addEventListener("change", toggleCamposPagamento);
    toggleCamposPagamento(); // Inicial
});
</script>

<?php include "footer.php"; ?>