<?php
require "init.php";

if (!isset($_SESSION['user_id']) || ($_SESSION['user_type'] != 'funcionario' && $_SESSION['user_type'] != 'gerente')) {
    header("Location: login.php");
    exit;
}

require "conexao.php";

$sql = "SELECT * FROM products ORDER BY id ASC";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lista de Produtos - Rosa Cores e Tintas</title>
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
            padding-top: 140px;
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
        
        /* BOTÃO ADICIONAR PRODUTO - ESTILOS CORRIGIDOS */
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
            box-shadow: 0 6px 18px rgba(20, 89, 44, 0.4);
            color: white !important;
        }
        
        .btn-add:active {
            transform: translateY(0);
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
        }
        
        .product-image {
            width: 80px;
            height: 80px;
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
            width: 80px;
            height: 80px;
            background-color: var(--light);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--dark);
            font-size: 12px;
            text-align: center;
        }
        
        .stock-low {
            color: var(--danger);
            font-weight: 600;
        }
        
        .stock-ok {
            color: var(--primary);
            font-weight: 500;
        }
        
        .btn-action {
            border-radius: 6px;
            padding: 6px 12px;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-block;
        }
        
        .btn-edit {
            background: var(--primary-light);
            color: white;
            border: none;
        }
        
        .btn-edit:hover {
            background: var(--primary);
            transform: translateY(-2px);
            color: white;
        }
        
        .btn-delete {
            background: var(--danger);
            color: white;
            border: none;
        }
        
        .btn-delete:hover {
            background: var(--danger-dark);
            transform: translateY(-2px);
            color: white;
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
        
        /* Search and Filter */
        .search-container {
            background: var(--light);
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 20px;
        }
        
        .form-control-search {
            border-radius: 8px;
            padding: 10px 15px;
            border: 2px solid #c8e6d4;
            transition: all 0.3s;
        }
        
        .form-control-search:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 0.2rem rgba(20, 89, 44, 0.25);
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
            
            .btn-add {
                width: 100%;
                justify-content: center;
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
            
            .btn-action {
                margin-bottom: 5px;
                display: block;
                width: 100%;
                text-align: center;
            }
        }
    </style>
</head>
<body>
    <?php include "header.php" ?>
    
    <!-- Main Content -->
    <main class="container">
        <div class="container-main">
            <div class="page-title">
                <h2 class="mb-0"><i class="bi bi-bucket me-2"></i>Lista de Produtos</h2>
                <!-- BOTÃO ADICIONAR PRODUTO - VISÍVEL E FUNCIONAL -->
                <a href="produto_form.php" class="btn btn-add">
                    <i class="bi bi-plus-circle me-1"></i>Adicionar Produto
                </a>
            </div>
            
            <!-- Search and Filter Section -->
            <div class="search-container mb-4">
                <div class="row">
                    <div class="col-md-8">
                        <div class="input-group">
                            <span class="input-group-text" style="background: var(--accent); color: var(--dark);">
                                <i class="bi bi-search"></i>
                            </span>
                            <input type="text" class="form-control form-control-search" placeholder="Buscar produtos...">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <select class="form-select form-control-search">
                            <option selected>Todas as categorias</option>
                            <option>Interior</option>
                            <option>Exterior</option>
                            <option>Interior/Exterior</option>
                            <option>Acessórios/Ferramentas</option>
                            <option>Especiais</option>
                        </select>
                    </div>
                </div>
            </div>
            
            <div class="table-container">
                <table class="custom-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Nome</th>
                            <th>Categoria</th>
                            <th>Preço</th>
                            <th>Estoque</th>
                            <th>Descrição</th>
                            <th>Imagem</th>
                            <th class="text-center">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        if ($result && $result->num_rows > 0) {
                            $contador = 1;
                            while ($row = $result->fetch_assoc()) {
                                $stockClass = ($row['stock'] < 10) ? 'stock-low' : 'stock-ok';
                                echo "<tr>";
                                echo "<td data-label='#'>{$contador}</td>";
                                echo "<td data-label='Nome'>" . htmlspecialchars($row['name'], ENT_QUOTES, 'UTF-8') . "</td>";
                                echo "<td data-label='Categoria'>" . htmlspecialchars($row['category'], ENT_QUOTES, 'UTF-8') . "</td>";
                                echo "<td data-label='Preço'>R$ " . number_format($row['price'], 2, ',', '.') . "</td>";
                                echo "<td data-label='Estoque' class='{$stockClass}'>" . intval($row['stock']) . "</td>";
                                echo "<td data-label='Descrição'>" . htmlspecialchars(substr($row['description'], 0, 50) . (strlen($row['description']) > 50 ? '...' : ''), ENT_QUOTES, 'UTF-8') . "</td>";
                                echo "<td data-label='Imagem'>";
                                echo $row['image'] ? "<img src='assets/img/" . htmlspecialchars($row['image'], ENT_QUOTES, 'UTF-8') . "' class='product-image'>" : "<div class='no-image'><i class='bi bi-image'></i><span>Sem imagem</span></div>";
                                echo "</td>";
                                echo "<td data-label='Ações' class='text-center'>
                                        <a href='produto_form.php?id={$row['id']}' class='btn btn-action btn-edit me-1'><i class='bi bi-pencil me-1'></i>Editar</a>
                                        <a href='produto_excluir.php?id={$row['id']}' class='btn btn-action btn-delete' onclick='return confirm(\"Tem certeza que deseja excluir este produto?\")'><i class='bi bi-trash me-1'></i>Excluir</a>
                                      </td>";
                                echo "</tr>";
                                $contador++;
                            }
                        } else {
                            echo "<tr><td colspan='8'>
                                    <div class='empty-state'>
                                        <i class='bi bi-inbox'></i>
                                        <h4>Nenhum produto encontrado</h4>
                                        <p>Comece adicionando seu primeiro produto ao catálogo.</p>
                                        <a href='produto_form.php' class='btn btn-add'><i class='bi bi-plus-circle me-1'></i>Adicionar Produto</a>
                                    </div>
                                  </td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
    
    <?php include "footer.php" ?>

    <script>
        // Funcionalidade de busca simples
        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.querySelector('.form-control-search');
            const tableRows = document.querySelectorAll('.custom-table tbody tr');
            
            if (searchInput) {
                searchInput.addEventListener('keyup', function() {
                    const searchText = this.value.toLowerCase();
                    
                    tableRows.forEach(row => {
                        const text = row.textContent.toLowerCase();
                        if (text.includes(searchText)) {
                            row.style.display = '';
                        } else {
                            row.style.display = 'none';
                        }
                    });
                });
            }
        });
    </script>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php
$conn->close();
?>