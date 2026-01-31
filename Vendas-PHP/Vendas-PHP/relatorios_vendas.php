<?php
session_start();
require "conexao.php";

// Apenas gerente pode acessar
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'gerente') {
    header("Location: index.php");
    exit;
}

// Captura filtros
$data_inicial = isset($_GET['data_inicial']) ? $_GET['data_inicial'] : '';
$data_final   = isset($_GET['data_final']) ? $_GET['data_final'] : '';
$vendedor     = isset($_GET['vendedor']) ? $_GET['vendedor'] : '';
$pagamento    = isset($_GET['pagamento']) ? $_GET['pagamento'] : '';

// Monta SQL com filtros
$sql = "
    SELECT s.id as sale_id, s.quantity, s.total, s.sale_date, s.metodo_pagamento,
           p.name as product_name, p.price as product_price, p.image, p.stock,
           c.name as cliente_nome, v.name as vendedor_nome
    FROM sales s
    LEFT JOIN products p ON s.product_id = p.id
    LEFT JOIN users c ON s.cliente_id = c.id
    LEFT JOIN users v ON s.vendedor_id = v.id
    WHERE 1=1
";

// aplica filtros dinamicamente
if (!empty($data_inicial)) {
    $sql .= " AND DATE(s.sale_date) >= '".$conn->real_escape_string($data_inicial)."'";
}
if (!empty($data_final)) {
    $sql .= " AND DATE(s.sale_date) <= '".$conn->real_escape_string($data_final)."'";
}
if (!empty($vendedor) && $vendedor != "todos") {
    $sql .= " AND v.id = ".intval($vendedor);
}
if (!empty($pagamento) && $pagamento != "todos") {
    $sql .= " AND s.metodo_pagamento = '".$conn->real_escape_string($pagamento)."'";
}

$sql .= " ORDER BY s.sale_date DESC";

$result = $conn->query($sql);

// Calcular totais
$totalVendas = 0;
$totalQuantidade = 0;
if ($result && $result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $totalVendas += $row['total'];
        $totalQuantidade += $row['quantity'];
    }
    $result->data_seek(0);
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Relatório de Vendas - Rosa Cores e Tintas</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        :root {
            --primary: #14592C;       /* Verde escuro principal */
            --primary-light: #1E7A41; /* Verde mais claro */
            --secondary: #732D14;     /* Marrom escuro */
            --accent: #A7D9B8;        /* Verde claro */
            --accent-dark: #0F4020;   /* Verde muito escuro */
            --danger: #BF1B1B;        /* Vermelho */
            --danger-dark: #F20707;   /* Vermelho mais vibrante */
            --light: #DFF2E7;         /* Verde muito claro */
            --dark: #0F4020;          /* Verde escuro para textos */
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f8f9fa;
        }
        
        /* Header Styles */
        .navbar {
            background: linear-gradient(135deg, var(--primary) 0%, var(--accent-dark) 100%);
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        
        .navbar-brand {
            font-weight: 700;
            color: white !important;
        }
        
        .nav-link {
            color: rgba(255, 255, 255, 0.85) !important;
            font-weight: 500;
            transition: all 0.3s;
        }
        
        .nav-link:hover {
            color: white !important;
            transform: translateY(-2px);
        }
        
        .navbar-toggler {
            border: none;
            color: white !important;
        }
        
        /* Main Content */
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
        
        .card-report {
            border: none;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
        }
        
        .card-header-report {
            background: linear-gradient(135deg, var(--primary) 0%, var(--accent-dark) 100%);
            color: white;
            padding: 20px;
            position: relative;
            overflow: hidden;
        }
        
        .card-header-report::before {
            content: '';
            position: absolute;
            top: -20px;
            right: -20px;
            width: 80px;
            height: 80px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
        }
        
        .card-header-report::after {
            content: '';
            position: absolute;
            bottom: -30px;
            left: -30px;
            width: 100px;
            height: 100px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
        }
        
        .card-body-report {
            padding: 30px;
            background: var(--light);
        }
        
        .stats-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
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
        
        .stat-card h3 {
            font-size: 14px;
            color: var(--dark);
            margin-bottom: 10px;
            font-weight: 600;
        }
        
        .stat-card .value {
            font-size: 24px;
            font-weight: 700;
            color: var(--primary);
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
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%);
            color: white;
            font-weight: 600;
            padding: 15px 12px;
            border: none;
            text-align: center;
        }
        
        .custom-table tbody tr {
            transition: all 0.3s;
            border-bottom: 1px solid #e9ecef;
        }
        
        .custom-table tbody tr:hover {
            background-color: rgba(167, 217, 184, 0.15);
            transform: translateY(-1px);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        }
        
        .custom-table tbody td {
            padding: 12px;
            vertical-align: middle;
            border: none;
            text-align: center;
        }
        
        .product-image {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 8px;
            border: 2px solid var(--accent);
            transition: all 0.3s;
        }
        
        .product-image:hover {
            transform: scale(1.05);
            border-color: var(--primary);
        }
        
        .no-image {
            width: 60px;
            height: 60px;
            background-color: var(--light);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--dark);
            font-size: 12px;
            text-align: center;
        }
        
        .text-total {
            font-weight: 700;
            color: var(--primary);
        }
        
        .empty-state {
            text-align: center;
            padding: 40px 20px;
            color: var(--dark);
        }
        
        .empty-state i {
            font-size: 50px;
            color: var(--accent);
            margin-bottom: 15px;
        }
        
        .filters {
            background: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.05);
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
        
        /* Footer Styles */
        footer {
            background: linear-gradient(135deg, var(--accent-dark) 0%, var(--primary) 100%);
            color: white;
            padding: 30px 0 10px;
        }
        
        .footer-links a {
            color: rgba(255, 255, 255, 0.85);
            text-decoration: none;
            transition: all 0.3s;
        }
        
        .footer-links a:hover {
            color: white;
            text-decoration: underline;
        }
        
        .social-links a {
            display: inline-block;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.1);
            color: white;
            text-align: center;
            line-height: 40px;
            margin-right: 10px;
            transition: all 0.3s;
        }
        
        .social-links a:hover {
            background: var(--accent);
            color: var(--dark);
            transform: translateY(-3px);
        }
        
        @media (max-width: 768px) {
            .container-main {
                padding: 15px;
            }
            
            .card-body-report {
                padding: 20px;
            }
            
            .page-title {
                flex-direction: column;
                text-align: center;
                gap: 15px;
            }
            
            .stats-container {
                grid-template-columns: 1fr;
            }
            
            .custom-table thead {
                display: none;
            }
            
            .custom-table tbody tr {
                display: block;
                margin-bottom: 15px;
                border: 1px solid #e9ecef;
                border-radius: 8px;
                padding: 10px;
            }
            
            .custom-table tbody td {
                display: block;
                text-align: right;
                padding: 8px;
                position: relative;
                padding-left: 50%;
            }
            
            .custom-table tbody td::before {
                content: attr(data-label);
                position: absolute;
                left: 12px;
                width: 45%;
                padding-right: 10px;
                text-align: left;
                font-weight: 600;
                color: var(--dark);
            }
        }
    </style>
</head>
<body>
   <?php include"header.php"?>

    <!-- Main Content -->
    <main class="container">
        <div class="container-main">
            <div class="page-title">
                <h2 class="mb-0"><i class="bi bi-graph-up me-2"></i>Relatório de Vendas</h2>
                <div class="d-flex">
                    <a href="realizar_venda.php" class="btn btn-filter text-white me-2">
                        <i class="bi bi-cash-coin me-1"></i>Nova Venda
                    </a>
                    <button class="btn btn-filter text-black" onclick="window.print()">
                        <i class="bi bi-printer me-1"></i>Imprimir
                    </button>
                </div>
            </div>
            
           <!-- Filtros -->
<div class="filters">
    <form method="GET" class="row">
        <div class="col-md-3 mb-2">
            <input type="date" name="data_inicial" class="form-control-filter"
                   value="<?php echo htmlspecialchars($data_inicial); ?>">
        </div>
        <div class="col-md-3 mb-2">
            <input type="date" name="data_final" class="form-control-filter"
                   value="<?php echo htmlspecialchars($data_final); ?>">
        </div>
        <div class="col-md-3 mb-2">
            <select name="vendedor" class="form-control-filter">
                <option value="todos">Todos os vendedores</option>
                <?php
                $vend_q = $conn->query("SELECT id, name FROM users WHERE user_type='funcionario'");
                while($vend = $vend_q->fetch_assoc()):
                ?>
                    <option value="<?php echo $vend['id']; ?>" 
                        <?php if($vendedor == $vend['id']) echo 'selected'; ?>>
                        <?php echo htmlspecialchars($vend['name']); ?>
                    </option>
                <?php endwhile; ?>
            </select>
        </div>
        <div class="col-md-3 mb-2">
            <select name="pagamento" class="form-control-filter">
                <option value="todos">Todos os métodos</option>
                <option value="pix" <?php if($pagamento=="pix") echo "selected"; ?>>Pix</option>
                <option value="cartão" <?php if($pagamento=="cartão") echo "selected"; ?>>Cartão</option>
                <option value="boleto" <?php if($pagamento=="boleto") echo "selected"; ?>>Boleto</option>
                <option value="google pay" <?php if($pagamento=="google pay") echo "selected"; ?>>Google Pay</option>
            </select>
        </div>
        <div class="col-md-3 mb-2">
            <button type="submit" class="btn btn-filter w-100">
                <i class="bi bi-funnel me-1"></i>Filtrar
            </button>
        </div>
    </form>
</div>

            
            <!-- Estatísticas -->
            <div class="stats-container">
                <div class="stat-card">
                    <h3>TOTAL DE VENDAS</h3>
                    <div class="value">R$ <?php echo number_format($totalVendas, 2, ',', '.'); ?></div>
                </div>
                <div class="stat-card">
                    <h3>QUANTIDADE VENDIDA</h3>
                    <div class="value"><?php echo $totalQuantidade; ?> unidades</div>
                </div>
                <div class="stat-card">
                    <h3>TOTAL DE REGISTROS</h3>
                    <div class="value"><?php echo $result->num_rows; ?> vendas</div>
                </div>
            </div>

            <div class="card-report">
                <div class="card-header-report text-center">
                    <h4 class="mb-0"><i class="bi bi-list-check me-2"></i>Detalhamento de Vendas</h4>
                </div>
                <div class="card-body-report">
                    
                    <?php if ($result->num_rows > 0): ?>
                        <div class="table-container">
                            <table class="custom-table">
                                <thead>
    <tr>
        <th>ID Venda</th>
        <th>Produto</th>
        <th>Imagem</th>
        <th>Preço Unitário</th>
        <th>Quantidade</th>
        <th>Total</th>
        <th>Cliente</th>
        <th>Vendedor</th>
        <th>Método de Pagamento</th> <!-- NOVO -->
        <th>Data</th>
        <th>Estoque Restante</th>
    </tr>
</thead>
<tbody>
    <?php while($row = $result->fetch_assoc()): ?>
        <tr>
            <td data-label="ID Venda"><?php echo $row['sale_id']; ?></td>
            <td data-label="Produto"><?php echo htmlspecialchars($row['product_name']); ?></td>
            <td data-label="Imagem">
                <?php if (!empty($row['image'])): ?>
                    <img src="assets/img/<?php echo htmlspecialchars($row['image']); ?>" class="product-image">
                <?php else: ?>
                    <div class="no-image"><i class="bi bi-image"></i></div>
                <?php endif; ?>
            </td>
            <td data-label="Preço Unitário">R$ <?php echo number_format($row['product_price'], 2, ',', '.'); ?></td>
            <td data-label="Quantidade"><?php echo $row['quantity']; ?></td>
            <td data-label="Total" class="text-total">R$ <?php echo number_format($row['total'], 2, ',', '.'); ?></td>
            <td data-label="Cliente"><?php echo $row['cliente_nome']; ?></td>
            <td data-label="Vendedor"><?php echo $row['vendedor_nome']; ?></td>
            <td data-label="Método de Pagamento">
                <?php echo !empty($row['metodo_pagamento']) ? htmlspecialchars($row['metodo_pagamento']) : '—'; ?>
            </td>
            <td data-label="Data"><?php echo date("d/m/Y H:i", strtotime($row['sale_date'])); ?></td>
            <td data-label="Estoque"><?php echo $row['stock']; ?></td>
        </tr>
    <?php endwhile; ?>
</tbody>

                            </table>
                        </div>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="bi bi-inbox"></i>
                            <h4>Nenhuma venda registrada</h4>
                            <p>Não há vendas registradas no sistema até o momento.</p>
                            <a href="realizar_venda.php" class="btn btn-filter text-white mt-2">
                                <i class="bi bi-cash-coin me-1"></i>Realizar Primeira Venda
                            </a>
                        </div>
                    <?php endif; ?>

                </div>
            </div>
        </div>
    </main>

  <?php include "footer.php"?>

    <script>
        // Funcionalidade de filtro simples
        document.addEventListener('DOMContentLoaded', function() {
            const filterButton = document.querySelector('.btn-filter');
            const tableRows = document.querySelectorAll('.custom-table tbody tr');
            
            filterButton.addEventListener('click', function() {
                alert('Funcionalidade de filtro será implementada em breve!');
            });
        });
    </script>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php
$conn->close();
?>