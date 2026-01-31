<?php
require "init.php";

if (!isset($_SESSION['user_id']) || ($_SESSION['user_type'] != 'funcionario' && $_SESSION['user_type'] != 'gerente')) {
    header("Location: login.php");
    exit;
}

require "conexao.php";

// Filtros
$status = isset($_GET['status']) ? $_GET['status'] : '';
$categoria = isset($_GET['categoria']) ? $_GET['categoria'] : '';
$data_inicial = isset($_GET['data_inicial']) ? $_GET['data_inicial'] : '';
$data_final = isset($_GET['data_final']) ? $_GET['data_final'] : '';

// Monta SQL com filtros
$sql = "SELECT * FROM contas_pagar WHERE 1=1";

if (!empty($status) && $status != 'todos') {
    $sql .= " AND status = '" . $conn->real_escape_string($status) . "'";
}

if (!empty($categoria) && $categoria != 'todos') {
    $sql .= " AND categoria = '" . $conn->real_escape_string($categoria) . "'";
}

if (!empty($data_inicial)) {
    $sql .= " AND data_vencimento >= '" . $conn->real_escape_string($data_inicial) . "'";
}

if (!empty($data_final)) {
    $sql .= " AND data_vencimento <= '" . $conn->real_escape_string($data_final) . "'";
}

$sql .= " ORDER BY data_vencimento ASC, status ASC";

$result = $conn->query($sql);

// Calcula totais
$totalPendente = 0;
$totalPago = 0;
$totalGeral = 0;

if ($result && $result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $totalGeral += $row['valor'];
        if ($row['status'] == 'pendente' || $row['status'] == 'atrasado') {
            $totalPendente += $row['valor'];
        } else {
            $totalPago += $row['valor'];
        }
    }
    $result->data_seek(0);
}

// Busca categorias únicas para o filtro
$categorias_result = $conn->query("SELECT DISTINCT categoria FROM contas_pagar WHERE categoria IS NOT NULL AND categoria != ''");
$categorias = [];
while($cat = $categorias_result->fetch_assoc()) {
    $categorias[] = $cat['categoria'];
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contas a Pagar - Rosa Cores e Tintas</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        :root {
            --primary: #14592C;
            --primary-light: #1E7A41;
            --secondary: #732D14;
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
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .btn-add {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%);
            border: none;
            border-radius: 8px;
            padding: 12px 24px;
            font-weight: 600;
            color: white !important;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s;
            box-shadow: 0 4px 12px rgba(20, 89, 44, 0.3);
        }
        
        .btn-add:hover {
            background: linear-gradient(135deg, var(--primary-light) 0%, var(--accent-dark) 100%);
            transform: translateY(-2px);
            color: white !important;
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
        .stat-card.pago { border-left-color: var(--primary); }
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
        
        .form-control-filter:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 0.2rem rgba(20, 89, 44, 0.25);
        }
        
        .btn-filter {
            background: var(--primary);
            border: none;
            border-radius: 8px;
            padding: 10px 20px;
            font-weight: 600;
            transition: all 0.3s;
            color: white;
        }
        
        .btn-filter:hover {
            background: var(--accent-dark);
            transform: translateY(-2px);
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
        
        .custom-table tbody td {
            padding: 12px;
            vertical-align: middle;
            border: none;
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
        
        .btn-edit {
            background: var(--primary-light);
            color: white;
        }
        
        .btn-edit:hover {
            background: var(--primary);
            color: white;
        }
        
        .btn-delete {
            background: var(--danger);
            color: white;
        }
        
        .btn-delete:hover {
            background: var(--danger-dark);
            color: white;
        }
        
        .btn-pay {
            background: var(--warning);
            color: var(--dark);
        }
        
        .btn-pay:hover {
            background: #e0a800;
            color: var(--dark);
        }
        
        .empty-state {
            text-align: center;
            padding: 40px 20px;
            color: var(--dark);
        }
        
        @media (max-width: 768px) {
            .container-main {
                padding: 15px;
            }
            
            .page-title {
                flex-direction: column;
                gap: 15px;
                text-align: center;
            }
            
            .stats-container {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <?php include "header.php" ?>
    
    <main class="container">
        <div class="container-main">
            <div class="page-title">
                <h2 class="mb-0"><i class="bi bi-credit-card me-2"></i>Contas a Pagar</h2>
                <a href="contas_pagar_form.php" class="btn btn-add">
                    <i class="bi bi-plus-circle me-1"></i>Nova Conta
                </a>
            </div>
            
            <!-- Estatísticas -->
            <div class="stats-container">
                <div class="stat-card pendente">
                    <h3>PENDENTE</h3>
                    <div class="value">R$ <?php echo number_format($totalPendente, 2, ',', '.'); ?></div>
                </div>
                <div class="stat-card pago">
                    <h3>PAGO</h3>
                    <div class="value">R$ <?php echo number_format($totalPago, 2, ',', '.'); ?></div>
                </div>
                <div class="stat-card total">
                    <h3>TOTAL GERAL</h3>
                    <div class="value">R$ <?php echo number_format($totalGeral, 2, ',', '.'); ?></div>
                </div>
            </div>
            
            <!-- Filtros -->
            <div class="filters">
                <form method="GET" class="row g-3">
                    <div class="col-md-3">
                        <select name="status" class="form-control-filter">
                            <option value="todos">Todos os status</option>
                            <option value="pendente" <?php echo $status == 'pendente' ? 'selected' : ''; ?>>Pendente</option>
                            <option value="pago" <?php echo $status == 'pago' ? 'selected' : ''; ?>>Pago</option>
                            <option value="atrasado" <?php echo $status == 'atrasado' ? 'selected' : ''; ?>>Atrasado</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <select name="categoria" class="form-control-filter">
                            <option value="todos">Todas as categorias</option>
                            <?php foreach($categorias as $cat): ?>
                                <option value="<?php echo htmlspecialchars($cat); ?>" <?php echo $categoria == $cat ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($cat); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <input type="date" name="data_inicial" class="form-control-filter" 
                               value="<?php echo htmlspecialchars($data_inicial); ?>" placeholder="Data inicial">
                    </div>
                    <div class="col-md-2">
                        <input type="date" name="data_final" class="form-control-filter" 
                               value="<?php echo htmlspecialchars($data_final); ?>" placeholder="Data final">
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-filter w-100">
                            <i class="bi bi-funnel me-1"></i>Filtrar
                        </button>
                    </div>
                </form>
            </div>
            
            <!-- Tabela de Contas -->
            <div class="table-container">
                <table class="custom-table">
                    <thead>
                        <tr>
                            <th>Descrição</th>
                            <th>Valor</th>
                            <th>Categoria</th>
                            <th>Vencimento</th>
                            <th>Status</th>
                            <th>Método Pagamento</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($result && $result->num_rows > 0): ?>
                            <?php while($row = $result->fetch_assoc()): ?>
                                <?php
                                $status_class = 'status-' . $row['status'];
                                $vencimento = new DateTime($row['data_vencimento']);
                                $hoje = new DateTime();
                                $atrasado = $vencimento < $hoje && $row['status'] == 'pendente';
                                ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($row['descricao']); ?></td>
                                    <td>R$ <?php echo number_format($row['valor'], 2, ',', '.'); ?></td>
                                    <td><?php echo htmlspecialchars($row['categoria'] ?: '-'); ?></td>
                                    <td><?php echo date('d/m/Y', strtotime($row['data_vencimento'])); ?></td>
                                    <td>
                                        <span class="status-badge <?php echo $status_class; ?>">
                                            <?php 
                                            if ($atrasado) {
                                                echo 'ATRASADO';
                                            } else {
                                                echo strtoupper($row['status']);
                                            }
                                            ?>
                                        </span>
                                    </td>
                                    <td><?php echo htmlspecialchars($row['metodo_pagamento'] ?: '-'); ?></td>
                                    <td>
                                        <div class="btn-group">
                                            <?php if ($row['status'] == 'pendente' || $atrasado): ?>
                                                <a href="contas_pagar_form.php?id=<?php echo $row['id']; ?>&action=pay" 
                                                   class="btn btn-action btn-pay me-1">
                                                    <i class="bi bi-cash-coin me-1"></i>Pagar
                                                </a>
                                            <?php endif; ?>
                                            <a href="contas_pagar_form.php?id=<?php echo $row['id']; ?>" 
                                               class="btn btn-action btn-edit me-1">
                                                <i class="bi bi-pencil me-1"></i>Editar
                                            </a>
                                            <a href="contas_pagar_excluir.php?id=<?php echo $row['id']; ?>" 
                                               class="btn btn-action btn-delete"
                                               onclick="return confirm('Tem certeza que deseja excluir esta conta?')">
                                                <i class="bi bi-trash me-1"></i>Excluir
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7">
                                    <div class="empty-state">
                                        <i class="bi bi-credit-card-2-front"></i>
                                        <h4>Nenhuma conta encontrada</h4>
                                        <p>Comece adicionando sua primeira conta a pagar.</p>
                                        <a href="contas_pagar_form.php" class="btn btn-add">
                                            <i class="bi bi-plus-circle me-1"></i>Adicionar Conta
                                        </a>
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