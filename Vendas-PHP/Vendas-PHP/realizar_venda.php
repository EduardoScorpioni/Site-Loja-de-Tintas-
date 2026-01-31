<?php
session_start();
require "conexao.php";

// Apenas vendedor e gerente podem acessar
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_type'], ['funcionario','gerente'])) {
    header("Location: index.php");
    exit;
}

$success = '';
$error = '';

// Buscar produtos
$produtos = $conn->query("SELECT id, name FROM products ORDER BY name ASC");

// Registrar venda
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $produto_id = $_POST['produto_id'];
    $vendedor_id = $_POST['vendedor_id'];
    $nome_modificado = trim($_POST['nome_modificado']);
    $preco_modificado = floatval($_POST['preco_modificado']);
    $quantidade = intval($_POST['quantidade']);
    $metodo_pagamento = trim($_POST['metodo_pagamento']);

    // Se for método Carteira, adicionar os dias
    if (isset($_POST['dias_carteira']) && !empty($_POST['dias_carteira'])) {
        $dias_carteira = intval($_POST['dias_carteira']);
        $metodo_pagamento = "Carteira - " . $dias_carteira . " dias";
    }

    // Se for novo cliente
    if (!empty($_POST['novo_cliente_nome'])) {
        $novo_nome = trim($_POST['novo_cliente_nome']);
        $novo_email = trim($_POST['novo_cliente_email']);
        $senha = password_hash("123456", PASSWORD_BCRYPT); // senha padrão

        $stmt_cli = $conn->prepare("INSERT INTO users (name, email, password, user_type) VALUES (?, ?, ?, 'cliente')");
        $stmt_cli->bind_param("sss", $novo_nome, $novo_email, $senha);
        if ($stmt_cli->execute()) {
            $cliente_id = $stmt_cli->insert_id;
        } else {
            $error = "Erro ao cadastrar novo cliente.";
        }
        $stmt_cli->close();
    } else {
        $cliente_id = $_POST['cliente_id'];
    }

    if (!empty($produto_id) && !empty($cliente_id) && !empty($vendedor_id) && $quantidade > 0 && !empty($metodo_pagamento)) {
        $stmt = $conn->prepare("SELECT name, price, stock FROM products WHERE id = ?");
        $stmt->bind_param("i", $produto_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $produto = $result->fetch_assoc();

        if ($produto && $produto['stock'] >= $quantidade) {
            $nome_final = !empty($nome_modificado) ? $nome_modificado : $produto['name'];
            $preco_final = $preco_modificado > 0 ? $preco_modificado : $produto['price'];
            $total = $preco_final * $quantidade;

            $insert = $conn->prepare("INSERT INTO sales (product_id, quantity, total, cliente_id, vendedor_id, metodo_pagamento, sale_date) VALUES (?, ?, ?, ?, ?, ?, NOW())");
            $insert->bind_param("iidiis", $produto_id, $quantidade, $total, $cliente_id, $vendedor_id, $metodo_pagamento);

            if ($insert->execute()) {
                $novo_estoque = $produto['stock'] - $quantidade;
                $update = $conn->prepare("UPDATE products SET stock = ? WHERE id = ?");
                $update->bind_param("ii", $novo_estoque, $produto_id);
                $update->execute();

                $success = "Venda registrada! Produto: $nome_final | Quantidade: $quantidade | Total: R$ " . number_format($total, 2, ',', '.') . " | Método: $metodo_pagamento | Estoque restante: $novo_estoque";
            } else {
                $error = "Erro ao registrar a venda.";
            }
        } else {
            $error = "Estoque insuficiente ou produto inválido.";
        }
    } else {
        $error = "Preencha todos os campos corretamente.";
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Realizar Venda - Rosa Cores e Tintas</title>
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
        
        .card-sale {
            border: none;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
        }
        
        .card-header-sale {
            background: linear-gradient(135deg, var(--primary) 0%, var(--accent-dark) 100%);
            color: white;
            padding: 20px;
            position: relative;
            overflow: hidden;
        }
        
        .card-header-sale::before {
            content: '';
            position: absolute;
            top: -20px;
            right: -20px;
            width: 80px;
            height: 80px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
        }
        
        .card-header-sale::after {
            content: '';
            position: absolute;
            bottom: -30px;
            left: -30px;
            width: 100px;
            height: 100px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
        }
        
        .card-body-sale {
            padding: 30px;
            background: var(--light);
        }
        
        .form-control-custom {
            border-radius: 8px;
            padding: 12px 15px;
            border: 2px solid #c8e6d4;
            transition: all 0.3s;
            background: white;
        }
        
        .form-control-custom:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 0.2rem rgba(20, 89, 44, 0.25);
        }
        
        .form-select-custom {
            border-radius: 8px;
            padding: 12px 15px;
            border: 2px solid #c8e6d4;
            transition: all 0.3s;
            background: white;
        }
        
        .form-select-custom:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 0.2rem rgba(20, 89, 44, 0.25);
        }
        
        .input-group-custom .input-group-text {
            background: var(--accent);
            border: 2px solid #c8e6d4;
            border-right: none;
            color: var(--dark);
        }
        
        .btn-sale {
            background: var(--primary);
            border: none;
            padding: 12px 20px;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.3s;
        }
        
        .btn-sale:hover {
            background: var(--accent-dark);
            transform: translateY(-2px);
        }
        
        .btn-toggle {
            background: var(--secondary);
            border: none;
            border-radius: 8px;
            padding: 10px 15px;
            font-weight: 500;
            transition: all 0.3s;
        }
        
        .btn-toggle:hover {
            background: #8a3d1d;
            transform: translateY(-2px);
        }
        
        .alert-success-custom {
            background: rgba(20, 89, 44, 0.1);
            border: 1px solid var(--primary);
            color: var(--primary);
            border-radius: 8px;
            padding: 15px;
        }
        
        .alert-danger-custom {
            background: rgba(191, 27, 27, 0.1);
            border: 1px solid var(--danger);
            color: var(--danger-dark);
            border-radius: 8px;
            padding: 15px;
        }
        
        .section-divider {
            border-top: 2px dashed var(--accent);
            margin: 25px 0;
            position: relative;
        }
        
        .section-divider::before {
            content: attr(data-title);
            position: absolute;
            top: -12px;
            left: 20px;
            background: var(--light);
            padding: 0 10px;
            color: var(--dark);
            font-weight: 600;
            font-size: 16px;
        }
        
        .info-box {
            background: white;
            border-radius: 8px;
            padding: 15px;
            border-left: 4px solid var(--primary);
            margin-bottom: 15px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        }
        
        .info-box i {
            color: var(--primary);
            margin-right: 10px;
        }
        
        /* Métodos de Pagamento */
        .payment-methods {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 1.5rem;
        }
        
        .payment-method {
            border: 2px solid var(--accent);
            border-radius: 12px;
            padding: 1.5rem;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
            background: white;
        }
        
        .payment-method:hover {
            border-color: var(--primary);
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }
        
        .payment-method.selected {
            border-color: var(--primary);
            background: rgba(20, 89, 44, 0.05);
        }
        
        .payment-method i {
            font-size: 2rem;
            color: var(--primary);
            margin-bottom: 1rem;
        }
        
        .payment-method h6 {
            color: var(--dark);
            font-weight: 600;
            margin-bottom: 0.5rem;
        }
        
        .payment-method p {
            color: #6c757d;
            font-size: 0.9rem;
            margin: 0;
        }
        
        .payment-details {
            background: var(--light);
            border-radius: 12px;
            padding: 1.5rem;
            margin-top: 1.5rem;
            display: none;
        }
        
        .payment-details.active {
            display: block;
        }
        
        .carteira-options {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(100px, 1fr));
            gap: 0.5rem;
            margin-top: 1rem;
        }
        
        .carteira-option {
            border: 2px solid var(--accent);
            border-radius: 8px;
            padding: 0.75rem;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
            background: white;
        }
        
        .carteira-option:hover {
            border-color: var(--primary);
        }
        
        .carteira-option.selected {
            border-color: var(--primary);
            background: rgba(20, 89, 44, 0.1);
            font-weight: 600;
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
            
            .card-body-sale {
                padding: 20px;
            }
            
            .page-title {
                flex-direction: column;
                text-align: center;
                gap: 15px;
            }
            
            .payment-methods {
                grid-template-columns: 1fr;
            }
            
            .carteira-options {
                grid-template-columns: repeat(3, 1fr);
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
                <h2 class="mb-0"><i class="bi bi-cash-coin me-2"></i>Realizar Venda</h2>
                <div class="d-flex">
                    <a href="vendas_lista.php" class="btn btn-toggle text-white me-2">
                        <i class="bi bi-list-ul me-1"></i>Ver Vendas
                    </a>
                    <a href="lista_produtos.php" class="btn btn-sale text-white">
                        <i class="bi bi-bucket me-1"></i>Ver Produtos
                    </a>
                </div>
            </div>
            
            <div class="info-box">
                <i class="bi bi-info-circle"></i>
                <span>Preencha os dados abaixo para registrar uma nova venda. Você pode alterar nome e preço do produto se necessário.</span>
            </div>

            <div class="card-sale">
                <div class="card-header-sale text-center">
                    <h4 class="mb-0"><i class="bi bi-cart-check me-2"></i>Registrar Nova Venda</h4>
                </div>
                <div class="card-body-sale">

                    <?php if (!empty($success)): ?>
                        <div class="alert alert-success-custom alert-dismissible fade show" role="alert">
                            <i class="bi bi-check-circle-fill me-2"></i>
                            <?php echo $success; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($error)): ?>
                        <div class="alert alert-danger-custom alert-dismissible fade show" role="alert">
                            <i class="bi bi-exclamation-triangle-fill me-2"></i>
                            <?php echo $error; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="">
                        <!-- Produto -->
                        <div class="section-divider" data-title="Dados do Produto"></div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">Selecionar Produto</label>
                                <div class="input-group input-group-custom">
                                    <span class="input-group-text"><i class="bi bi-bucket"></i></span>
                                    <select id="produto" name="produto_id" class="form-select-custom" required>
                                        <option value="">Selecione um produto</option>
                                        <?php while($row = $produtos->fetch_assoc()): ?>
                                            <option value="<?php echo $row['id']; ?>">
                                                <?php echo $row['name']; ?>
                                            </option>
                                        <?php endwhile; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">Nome do Produto (opcional)</label>
                                <div class="input-group input-group-custom">
                                    <span class="input-group-text"><i class="bi bi-pencil"></i></span>
                                    <input type="text" id="nome_modificado" name="nome_modificado" class="form-control-custom" placeholder="Pode alterar o nome do produto">
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">Preço Unitário (R$)</label>
                                <div class="input-group input-group-custom">
                                    <span class="input-group-text">R$</span>
                                    <input type="number" step="0.01" id="preco_modificado" name="preco_modificado" class="form-control-custom" required>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">Quantidade</label>
                                <div class="input-group input-group-custom">
                                    <span class="input-group-text"><i class="bi bi-box"></i></span>
                                    <input type="number" name="quantidade" class="form-control-custom" min="1" required placeholder="Quantidade vendida">
                                </div>
                            </div>
                        </div>

                        <!-- Vendedor + Cliente -->
                        <div class="section-divider" data-title="Dados da Venda"></div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">Código do Vendedor (ID)</label>
                                <div class="input-group input-group-custom">
                                    <span class="input-group-text"><i class="bi bi-person-badge"></i></span>
                                    <input type="number" id="vendedor_id" name="vendedor_id" class="form-control-custom" required placeholder="ID do vendedor">
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">Nome do Vendedor</label>
                                <div class="input-group input-group-custom">
                                    <span class="input-group-text"><i class="bi bi-person"></i></span>
                                    <input type="text" id="vendedor_nome" class="form-control-custom" readonly placeholder="Nome do vendedor">
                                </div>
                            </div>
                        </div>

                        <!-- Cliente existente -->
                        <div class="row" id="cliente_existente">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">Código do Cliente (ID)</label>
                                <div class="input-group input-group-custom">
                                    <span class="input-group-text"><i class="bi bi-person-badge"></i></span>
                                    <input type="number" id="cliente_id" name="cliente_id" class="form-control-custom" placeholder="ID do cliente">
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">Nome do Cliente</label>
                                <div class="input-group input-group-custom">
                                    <span class="input-group-text"><i class="bi bi-person"></i></span>
                                    <input type="text" id="cliente_nome" class="form-control-custom" readonly placeholder="Nome do cliente">
                                </div>
                            </div>
                        </div>

                        <!-- Novo cliente (oculto inicialmente) -->
                        <div class="row" id="novo_cliente" style="display:none;">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">Nome do Novo Cliente</label>
                                <div class="input-group input-group-custom">
                                    <span class="input-group-text"><i class="bi bi-person-plus"></i></span>
                                    <input type="text" name="novo_cliente_nome" class="form-control-custom" placeholder="Nome completo">
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">Email do Novo Cliente</label>
                                <div class="input-group input-group-custom">
                                    <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                                    <input type="email" name="novo_cliente_email" class="form-control-custom" placeholder="Email do cliente">
                                </div>
                            </div>
                        </div>

                        <!-- Método de Pagamento -->
                        <div class="section-divider" data-title="Método de Pagamento"></div>
                        
                        <div class="payment-methods">
                            <div class="payment-method" onclick="selectPayment('pix')">
                                <i class="bi bi-qr-code"></i>
                                <h6>PIX</h6>
                                <p>Pagamento instantâneo</p>
                                <input type="radio" name="metodo_pagamento" value="PIX" style="display: none;">
                            </div>
                            
                            <div class="payment-method" onclick="selectPayment('cartao')">
                                <i class="bi bi-credit-card"></i>
                                <h6>Cartão</h6>
                                <p>Crédito/Débito</p>
                                <input type="radio" name="metodo_pagamento" value="Cartão" style="display: none;">
                            </div>
                            
                            <div class="payment-method" onclick="selectPayment('boleto')">
                                <i class="bi bi-upc-scan"></i>
                                <h6>Boleto</h6>
                                <p>Pagamento em até 2 dias</p>
                                <input type="radio" name="metodo_pagamento" value="Boleto" style="display: none;">
                            </div>
                            
                            <div class="payment-method" onclick="selectPayment('carteira')">
                                <i class="bi bi-wallet2"></i>
                                <h6>Carteira</h6>
                                <p>Pagamento parcelado</p>
                                <input type="radio" name="metodo_pagamento" value="Carteira" style="display: none;">
                            </div>
                        </div>

                        <!-- Detalhes do PIX -->
                        <div id="pix-details" class="payment-details">
                            <div class="alert alert-info">
                                <i class="bi bi-info-circle"></i>
                                <p>Pagamento via PIX - aprovação instantânea</p>
                            </div>
                        </div>
                        
                        <!-- Detalhes do Cartão -->
                        <div id="cartao-details" class="payment-details">
                            <div class="alert alert-info">
                                <i class="bi bi-info-circle"></i>
                                <p>Pagamento via Cartão de Crédito/Débito</p>
                            </div>
                        </div>
                        
                        <!-- Detalhes do Boleto -->
                        <div id="boleto-details" class="payment-details">
                            <div class="alert alert-info">
                                <i class="bi bi-info-circle"></i>
                                <p>Boleto bancário - prazo de 2 dias para pagamento</p>
                            </div>
                        </div>

                        <!-- Detalhes da Carteira -->
                        <div id="carteira-details" class="payment-details">
                            <div class="alert alert-info">
                                <i class="bi bi-info-circle"></i>
                                <p>Pagamento via Carteira - selecione o período de pagamento</p>
                            </div>
                            
                            <div class="carteira-options">
                                <div class="carteira-option" onclick="selectCarteiraDays(5)">
                                    <h6>5 dias</h6>
                                    <small>Vencimento rápido</small>
                                    <input type="radio" name="dias_carteira" value="5" style="display: none;">
                                </div>
                                <div class="carteira-option" onclick="selectCarteiraDays(10)">
                                    <h6>10 dias</h6>
                                    <small>1 semana e meia</small>
                                    <input type="radio" name="dias_carteira" value="10" style="display: none;">
                                </div>
                                <div class="carteira-option" onclick="selectCarteiraDays(15)">
                                    <h6>15 dias</h6>
                                    <small>2 semanas</small>
                                    <input type="radio" name="dias_carteira" value="15" style="display: none;">
                                </div>
                                <div class="carteira-option" onclick="selectCarteiraDays(20)">
                                    <h6>20 dias</h6>
                                    <small>Quase 3 semanas</small>
                                    <input type="radio" name="dias_carteira" value="20" style="display: none;">
                                </div>
                                <div class="carteira-option" onclick="selectCarteiraDays(25)">
                                    <h6>25 dias</h6>
                                    <small>Próximo do mês</small>
                                    <input type="radio" name="dias_carteira" value="25" style="display: none;">
                                </div>
                                <div class="carteira-option" onclick="selectCarteiraDays(30)">
                                    <h6>30 dias</h6>
                                    <small>1 mês completo</small>
                                    <input type="radio" name="dias_carteira" value="30" style="display: none;">
                                </div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-between mt-4">
                            <button type="button" class="btn btn-toggle text-black" onclick="toggleNovoCliente()">
                                <i class="bi bi-person-plus me-1"></i>Adicionar Novo Cliente
                            </button>
                            <button type="submit" class="btn btn-sale text-black" id="submit-btn">
                                <i class="bi bi-check-circle me-1"></i>Registrar Venda
                            </button>
                        </div>
                    </form>

                </div>
            </div>
        </div>
    </main>

 <?php include "footer.php" ?>

    <script>
    function toggleNovoCliente() {
        const novo = document.getElementById('novo_cliente');
        const existente = document.getElementById('cliente_existente');
        const btn = event.currentTarget;
        
        if (novo.style.display === "none") {
            novo.style.display = "flex";
            existente.style.display = "none";
            btn.innerHTML = '<i class="bi bi-person me-1"></i>Usar Cliente Existente';
        } else {
            novo.style.display = "none";
            existente.style.display = "flex";
            btn.innerHTML = '<i class="bi bi-person-plus me-1"></i>Adicionar Novo Cliente';
        }
    }

    function selectPayment(method) {
        // Remover seleção de todos os métodos
        document.querySelectorAll('.payment-method').forEach(el => {
            el.classList.remove('selected');
            el.querySelector('input[type="radio"]').checked = false;
        });
        
        // Esconder todos os detalhes
        document.querySelectorAll('.payment-details').forEach(el => {
            el.classList.remove('active');
        });
        
        // Selecionar método clicado
        const selectedMethod = document.querySelector(`.payment-method:nth-child(${
            method === 'pix' ? 1 : 
            method === 'cartao' ? 2 : 
            method === 'boleto' ? 3 : 4
        })`);
        
        selectedMethod.classList.add('selected');
        selectedMethod.querySelector('input[type="radio"]').checked = true;
        
        // Mostrar detalhes do método selecionado
        document.getElementById(`${method}-details`).classList.add('active');
        
        // Se for carteira, limpar seleção de dias anterior
        if (method === 'carteira') {
            document.querySelectorAll('.carteira-option').forEach(el => {
                el.classList.remove('selected');
                el.querySelector('input[type="radio"]').checked = false;
            });
        }
        
        // Habilitar botão de finalizar compra
        validateForm();
    }

    function selectCarteiraDays(days) {
        // Remover seleção de todos os dias
        document.querySelectorAll('.carteira-option').forEach(el => {
            el.classList.remove('selected');
            el.querySelector('input[type="radio"]').checked = false;
        });
        
        // Selecionar dia clicado
        const selectedOption = document.querySelector(`.carteira-option:nth-child(${
            days === 5 ? 1 :
            days === 10 ? 2 :
            days === 15 ? 3 :
            days === 20 ? 4 :
            days === 25 ? 5 : 6
        })`);
        
        selectedOption.classList.add('selected');
        selectedOption.querySelector('input[type="radio"]').checked = true;
        
        // Habilitar botão de finalizar compra
        validateForm();
    }

    function validateForm() {
        const produto = document.getElementById('produto').value;
        const vendedorId = document.getElementById('vendedor_id').value;
        const quantidade = document.querySelector('input[name="quantidade"]').value;
        const metodoPagamento = document.querySelector('input[name="metodo_pagamento"]:checked');
        const submitBtn = document.getElementById('submit-btn');
        
        // Verificar se é método carteira e se tem dias selecionados
        let carteiraValid = true;
        if (metodoPagamento && metodoPagamento.value === 'Carteira') {
            const diasCarteira = document.querySelector('input[name="dias_carteira"]:checked');
            carteiraValid = diasCarteira !== null;
        }
        
        if (produto && vendedorId && quantidade > 0 && metodoPagamento && carteiraValid) {
            submitBtn.disabled = false;
        } else {
            submitBtn.disabled = true;
        }
    }

    // Buscar produto
    document.getElementById('produto').addEventListener('change', function() {
        let id = this.value;
        if (id) {
            fetch('get_product.php?id=' + id)
                .then(response => response.json())
                .then(data => {
                    if (data) {
                        document.getElementById('nome_modificado').value = data.name;
                        document.getElementById('preco_modificado').value = data.price;
                    }
                });
        } else {
            document.getElementById('nome_modificado').value = '';
            document.getElementById('preco_modificado').value = '';
        }
        validateForm();
    });

    // Buscar cliente
    document.getElementById('cliente_id').addEventListener('blur', function() {
        let id = this.value;
        if (id) {
            fetch('get_cliente.php?id=' + id)
                .then(response => response.json())
                .then(data => {
                    if (data) {
                        document.getElementById('cliente_nome').value = data.name;
                    } else {
                        document.getElementById('cliente_nome').value = 'Cliente não encontrado';
                    }
                });
        } else {
            document.getElementById('cliente_nome').value = '';
        }
    });

    // Buscar vendedor
    document.getElementById('vendedor_id').addEventListener('blur', function() {
        let id = this.value;
        if (id) {
            fetch('get_vendedor.php?id=' + id)
                .then(response => response.json())
                .then(data => {
                    if (data) {
                        document.getElementById('vendedor_nome').value = data.name;
                    } else {
                        document.getElementById('vendedor_nome').value = 'Vendedor não encontrado';
                    }
                });
        } else {
            document.getElementById('vendedor_nome').value = '';
        }
        validateForm();
    });

    // Validar formulário quando campos mudarem
    document.querySelectorAll('input, select').forEach(element => {
        element.addEventListener('change', validateForm);
        element.addEventListener('input', validateForm);
    });

    // Validar inicialmente
    validateForm();
    </script>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>