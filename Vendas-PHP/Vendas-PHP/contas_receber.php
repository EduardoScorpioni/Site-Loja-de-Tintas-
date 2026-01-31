<?php
require "init.php";

if (!isset($_SESSION['user_id']) || ($_SESSION['user_type'] != 'funcionario' && $_SESSION['user_type'] != 'gerente')) {
    header("Location: login.php");
    exit;
}

require "conexao.php";

// Filtros
$cliente_id = isset($_GET['cliente_id']) ? $_GET['cliente_id'] : '';
$status = isset($_GET['status']) ? $_GET['status'] : 'pendente';
$data_inicial = isset($_GET['data_inicial']) ? $_GET['data_inicial'] : '';
$data_final = isset($_GET['data_final']) ? $_GET['data_final'] : '';

// Busca vendas com pagamento "Carteira" e status pendente/atrasado
$sql = "SELECT s.*, p.name as product_name, u.name as cliente_nome, 
               v.name as vendedor_nome, p.image
        FROM sales s
        LEFT JOIN products p ON s.product_id = p.id
        LEFT JOIN users u ON s.cliente_id = u.id
        LEFT JOIN users v ON s.vendedor_id = v.id
        WHERE s.metodo_pagamento LIKE '%Carteira%'";

// Aplica filtros
if (!empty($cliente_id) && $cliente_id != 'todos') {
    $sql .= " AND s.cliente_id = " . intval($cliente_id);
}

if (!empty($status) && $status != 'todos') {
    $sql .= " AND s.status_pagamento = '" . $conn->real_escape_string($status) . "'";
}

if (!empty($data_inicial)) {
    $sql .= " AND DATE(s.sale_date) >= '" . $conn->real_escape_string($data_inicial) . "'";
}

if (!empty($data_final)) {
    $sql .= " AND DATE(s.sale_date) <= '" . $conn->real_escape_string($data_final) . "'";
}

$sql .= " ORDER BY s.sale_date DESC";

$result = $conn->query($sql);

// Calcula totais
$totalPendente = 0;
$totalRecebido = 0;
$totalGeral = 0;
$totaisPorCliente = [];

if ($result && $result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $totalGeral += $row['total'];
        
        if ($row['status_pagamento'] == 'pendente' || $row['status_pagamento'] == 'atrasado') {
            $totalPendente += $row['total'];
        } else {
            $totalRecebido += $row['total'];
        }
        
        // Soma por cliente
        $cliente_id = $row['cliente_id'];
        if (!isset($totaisPorCliente[$cliente_id])) {
            $totaisPorCliente[$cliente_id] = [
                'nome' => $row['cliente_nome'],
                'total' => 0
            ];
        }
        $totaisPorCliente[$cliente_id]['total'] += $row['total'];
    }
    $result->data_seek(0);
}

// Busca clientes para o filtro
$clientes_result = $conn->query("SELECT id, name FROM users WHERE user_type = 'cliente' ORDER BY name");
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contas a Receber - Rosa Cores e Tintas</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        :root {
            --primary: #14592C;
            --primary-light: #1E7A41;
            --accent: #A7D9B8;
            --accent-dark: #0F4020;
            --danger: #BF1B1B;
            --warning: #FFC107;
            --light: #DFF2E7;
            --dark: #0F4020;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f8f9fa;
            padding-top: 140px;
        }
        
        .container-main {
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.08);
            padding: 25px;
            margin: 30px auto;
        }
        
        .page-title {
            color: var(--dark);
            font-weight: 700;
            border-bottom: 2px solid var(--accent);
            padding-bottom: 15px;
            margin-bottom: 25px;
        }
        
        .stats-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 25px;
        }
        
        .stat-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            text-align: center;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.05);
            border-left: 4px solid var(--primary);
        }
        
        .stat-card.pendente { border-left-color: var(--warning); }
        .stat-card.recebido { border-left-color: var(--primary); }
        .stat-card.total { border-left-color: var(--accent-dark); }
        
        .stat-card h3 {
            font-size: 14px;
            color: var(--dark);
            margin-bottom: 10px;
            font-weight: 600;
        }
        
        .stat-card .value {
            font-size: 20px;
            font-weight: 700;
        }
        
        .filters {
            background: var(--light);
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
        }
        
        .form-control-filter {
            border-radius: 8px;
            padding: 10px 15px;
            border: 2px solid #c8e6d4;
            transition: all 0.3s;
        }
        
        .table-container {
            overflow-x: auto;
            border-radius: 10px;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.05);
        }
        
        .custom-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            border-radius: 10px;
            overflow: hidden;
        }
        
        .custom-table thead th {
            background: linear-gradient(135deg, var(--primary) 0%, var(--accent-dark) 100%);
            color: white;
            font-weight: 600;
            padding: 15px 12px;
            border: none;
        }
        
        .custom-table tbody tr {
            transition: all 0.3s;
        }
        
        .custom-table tbody tr:hover {
            background-color: rgba(167, 217, 184, 0.15);
        }
        
        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .status-pendente { background: #FFF3CD; color: #856404; }
        .status-pago { background: #D1ECF1; color: #0C5460; }
        .status-atrasado { background: #F8D7DA; color: #721C24; }
        
        .btn-action {
            border-radius: 6px;
            padding: 6px 12px;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-block;
            border: none;
        }
        
        .btn-receive {
            background: var(--primary);
            color: white;
        }
        
        .btn-receive:hover {
            background: var(--primary-light);
            color: white;
        }
        
        .client-summary {
            background: var(--light);
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 20px;
        }
        
        .client-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px;
            border-bottom: 1px solid #c8e6d4;
        }
        
        .client-item:last-child {
            border-bottom: none;
        }
    </style>
</head>
<body>
    <?php include "header.php" ?>
    
    <main class="container">
        <div class="container-main">
            <div class="page-title">
                <h2 class="mb-0"><i class="bi bi-cash-coin me-2"></i>Contas a Receber</h2>
            </div>
            
            <!-- Estatísticas -->
            <div class="stats-container">
                <div class="stat-card pendente">
                    <h3>A RECEBER</h3>
                    <div class="value">R$ <?php echo number_format($totalPendente, 2, ',', '.'); ?></div>
                </div>
                <div class="stat-card recebido">
                    <h3>RECEBIDO</h3>
                    <div class="value">R$ <?php echo number_format($totalRecebido, 2, ',', '.'); ?></div>
                </div>
                <div class="stat-card total">
                    <h3>TOTAL GERAL</h3>
                    <div class="value">R$ <?php echo number_format($totalGeral, 2, ',', '.'); ?></div>
                </div>
            </div>
            
            <!-- Resumo por Cliente -->
            <?php if (!empty($totaisPorCliente)): ?>
                <div class="client-summary">
                    <h5 class="mb-3"><i class="bi bi-people me-2"></i>Resumo por Cliente</h5>
                    <?php foreach($totaisPorCliente as $cliente_id => $cliente): ?>
                        <div class="client-item">
                            <span><?php echo htmlspecialchars($cliente['nome']); ?></span>
                            <strong>R$ <?php echo number_format($cliente['total'], 2, ',', '.'); ?></strong>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            
            <!-- Filtros -->
            <div class="filters">
                <form method="GET" class="row g-3">
                    <div class="col-md-3">
                        <select name="cliente_id" class="form-control-filter">
                            <option value="todos">Todos os clientes</option>
                            <?php while($cliente = $clientes_result->fetch_assoc()): ?>
                                <option value="<?php echo $cliente['id']; ?>" 
                                    <?php echo $cliente_id == $cliente['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($cliente['name']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <select name="status" class="form-control-filter">
                            <option value="todos">Todos os status</option>
                            <option value="pendente" <?php echo $status == 'pendente' ? 'selected' : ''; ?>>Pendente</option>
                            <option value="pago" <?php echo $status == 'pago' ? 'selected' : ''; ?>>Recebido</option>
                            <option value="atrasado" <?php echo $status == 'atrasado' ? 'selected' : ''; ?>>Atrasado</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <input type="date" name="data_inicial" class="form-control-filter" 
                               value="<?php echo htmlspecialchars($data_inicial); ?>">
                    </div>
                    <div class="col-md-2">
                        <input type="date" name="data_final" class="form-control-filter" 
                               value="<?php echo htmlspecialchars($data_final); ?>">
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bi bi-funnel me-1"></i>Filtrar
                        </button>
                    </div>
                </form>
            </div>
            
            <!-- Tabela de Contas a Receber -->
            <div class="table-container">
                <table class="custom-table">
                    <thead>
                        <tr>
                            <th>Cliente</th>
                            <th>Produto</th>
                            <th>Quantidade</th>
                            <th>Valor Total</th>
                            <th>Data Venda</th>
                            <th>Status</th>
                            <th>Vendedor</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($result && $result->num_rows > 0): ?>
                            <?php while($row = $result->fetch_assoc()): ?>
                                <?php
                                $status_class = 'status-' . $row['status_pagamento'];
                                $vencimento = new DateTime($row['sale_date']);
                                $vencimento->modify('+15 days'); // Carteira - 15 dias
                                $hoje = new DateTime();
                                $atrasado = $vencimento < $hoje && $row['status_pagamento'] == 'pendente';
                                ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($row['cliente_nome']); ?></td>
                                    <td><?php echo htmlspecialchars($row['product_name']); ?></td>
                                    <td><?php echo $row['quantity']; ?></td>
                                    <td>R$ <?php echo number_format($row['total'], 2, ',', '.'); ?></td>
                                    <td><?php echo date('d/m/Y', strtotime($row['sale_date'])); ?></td>
                                    <td>
                                        <span class="status-badge <?php echo $status_class; ?>">
                                            <?php 
                                            if ($atrasado) {
                                                echo 'ATRASADO';
                                            } else {
                                                echo strtoupper($row['status_pagamento']);
                                            }
                                            ?>
                                        </span>
                                        <?php if ($atrasado): ?>
                                            <br><small>Venceu: <?php echo $vencimento->format('d/m/Y'); ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($row['vendedor_nome'] ?: '-'); ?></td>
                                    <td>
                                        <?php if ($row['status_pagamento'] == 'pendente' || $atrasado): ?>
                                            <a href="contas_receber_pagar.php?id=<?php echo $row['id']; ?>" 
                                               class="btn btn-action btn-receive">
                                                <i class="bi bi-cash-coin me-1"></i>Receber
                                            </a>
                                        <?php else: ?>
                                            <span class="text-muted">Recebido</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8">
                                    <div class="text-center py-4">
                                        <i class="bi bi-cash-coin" style="font-size: 3rem; color: #ccc;"></i>
                                        <h4 class="mt-3">Nenhuma conta a receber</h4>
                                        <p class="text-muted">Não há vendas com pagamento em carteira pendentes.</p>
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
    
    <?php include "footer.php" ?>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php
$conn->close();
?>