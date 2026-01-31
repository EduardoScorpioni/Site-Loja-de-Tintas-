<?php
require "init.php";

// Verificar se o usuário está logado
if (!isset($_SESSION['user_id'])) {
    $_SESSION['error_message'] = "Você precisa fazer login para finalizar a compra.";
    header("Location: login.php");
    exit;
}

require "conexao.php";

$userId = $_SESSION['user_id'];
// Buscar endereços salvos do cliente
$enderecos = [];
$sqlEnd = $conn->prepare("SELECT id, endereco FROM enderecos WHERE cliente_id = ?");
$sqlEnd->bind_param("i", $userId);
$sqlEnd->execute();
$resEnd = $sqlEnd->get_result();
while ($row = $resEnd->fetch_assoc()) {
    $enderecos[] = $row;
}
$sqlEnd->close();

// Buscar itens do carrinho do banco
$sql = "SELECT c.product_id, c.quantity, p.price, p.stock, p.name, p.image 
        FROM carts c
        INNER JOIN products p ON c.product_id = p.id
        WHERE c.user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();

$carrinhoItens = [];
$totalGeral = 0;
$totalItens = 0;
$descontoCupom = 0;
$cupomAplicado = false;
$cupomCodigo = '';

// Verificar se há cupom aplicado
if (isset($_POST['aplicar_cupom'])) {
    $cupomCodigo = trim($_POST['cupom']);
    if ($cupomCodigo === 'CarecaLindo') {
        $cupomAplicado = true;
        $_SESSION['cupom_aplicado'] = true;
    } else {
        $_SESSION['error_message'] = "Cupom inválido!";
    }
} elseif (isset($_SESSION['cupom_aplicado']) && $_SESSION['cupom_aplicado']) {
    $cupomAplicado = true;
    $cupomCodigo = 'CarecaLindo';
}

if ($result && $result->num_rows > 0) {
    while ($item = $result->fetch_assoc()) {
        $totalItem = $item['price'] * $item['quantity'];
        $totalGeral += $totalItem;
        $totalItens += $item['quantity'];
        $carrinhoItens[] = $item;
        
        // Verificar se tem estoque suficiente
        if ($item['stock'] < $item['quantity']) {
            $_SESSION['error_message'] = "Estoque insuficiente para o produto: " . $item['name'];
            header("Location: view_cart.php");
            exit;
        }
    }
    
    // Aplicar desconto do cupom se válido
    if ($cupomAplicado) {
        $descontoCupom = $totalGeral * 0.5; // 50% de desconto
        $totalComDesconto = $totalGeral - $descontoCupom;
    } else {
        $totalComDesconto = $totalGeral;
    }
} else {
    $_SESSION['error_message'] = "Seu carrinho está vazio.";
    header("Location: view_cart.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['finalizar_compra'])) {
    $metodoPagamento = $_POST['metodo_pagamento'];
    $enderecoId = !empty($_POST['endereco_id']) ? intval($_POST['endereco_id']) : null;
    $novoEndereco = trim($_POST['novo_endereco']);

    // Se usuário digitou novo endereço, salva na tabela e pega o id
    if (!empty($novoEndereco)) {
        $stmtEnd = $conn->prepare("INSERT INTO enderecos (cliente_id, endereco) VALUES (?, ?)");
        $stmtEnd->bind_param("is", $userId, $novoEndereco);
        $stmtEnd->execute();
        $enderecoId = $stmtEnd->insert_id;
        $stmtEnd->close();
    }

    foreach ($carrinhoItens as $item) {
        $productId = $item['product_id'];
        $quantity = $item['quantity'];
        $price = $item['price'];
        $total = $price * $quantity;

        // Cupom proporcional
        if ($cupomAplicado) {
            $percentualItem = $total / $totalGeral;
            $total = $total - ($descontoCupom * $percentualItem);
        }

        // Inserir venda com endereço
        $insert = $conn->prepare("
            INSERT INTO sales (product_id, quantity, total, cliente_id, endereco_id, metodo_pagamento, cupom_codigo) 
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $insert->bind_param("iidiiis", $productId, $quantity, $total, $userId, $enderecoId, $metodoPagamento, $cupomCodigo);
        $insert->execute();
        $insert->close();

        // Atualizar estoque
        $update = $conn->prepare("UPDATE products SET stock = stock - ? WHERE id = ?");
        $update->bind_param("ii", $quantity, $productId);
        $update->execute();
        $update->close();
    }

    // Limpa carrinho
    $clear = $conn->prepare("DELETE FROM carts WHERE user_id = ?");
    $clear->bind_param("i", $userId);
    $clear->execute();
    $clear->close();

    unset($_SESSION['cupom_aplicado']);

    $_SESSION['success_message'] = "Compra finalizada com sucesso! Método: " . $metodoPagamento;
    header("Location:loja.php=Sucesso");
    exit;
}

include "header.php";
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Finalizar Compra - Rosa Cores e Tintas</title>
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
    
    .page-header {
        background: linear-gradient(135deg, var(--primary) 0%, var(--accent-dark) 100%);
        color: white;
        padding: 3rem 0;
        margin-bottom: 2rem;
        border-radius: 0 0 20px 20px;
        position: relative;
        overflow: hidden;
    }
    
    .page-header::before {
        content: '';
        position: absolute;
        top: -50px;
        right: -50px;
        width: 150px;
        height: 150px;
        background: rgba(255, 255, 255, 0.1);
        border-radius: 50%;
    }
    
    .checkout-container {
        background: white;
        border-radius: 15px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        overflow: hidden;
        margin-bottom: 3rem;
    }
    
    .checkout-section {
        padding: 2rem;
        border-bottom: 2px solid var(--light);
    }
    
    .checkout-section:last-child {
        border-bottom: none;
    }
    
    .section-title {
        color: var(--dark);
        font-weight: 700;
        margin-bottom: 1.5rem;
        padding-bottom: 0.5rem;
        border-bottom: 2px solid var(--accent);
        display: flex;
        align-items: center;
    }
    
    .section-title i {
        margin-right: 10px;
        color: var(--primary);
    }
    
    .product-image-checkout {
        width: 80px;
        height: 80px;
        object-fit: contain;
        border-radius: 10px;
        background: var(--light);
        padding: 8px;
    }
    
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
    
    .coupon-section {
        background: var(--light);
        border-radius: 12px;
        padding: 1.5rem;
        margin-bottom: 1.5rem;
    }
    
    .coupon-form {
        display: flex;
        gap: 1rem;
    }
    
    .coupon-input {
        flex: 1;
        border: 2px solid var(--accent);
        border-radius: 8px;
        padding: 0.75rem;
        font-size: 1rem;
    }
    
    .coupon-input:focus {
        border-color: var(--primary);
        outline: none;
        box-shadow: 0 0 0 0.2rem rgba(20, 89, 44, 0.25);
    }
    
    .btn-coupon {
        background: var(--primary);
        border: none;
        border-radius: 8px;
        padding: 0.75rem 1.5rem;
        color: white;
        font-weight: 600;
        transition: all 0.3s;
    }
    
    .btn-coupon:hover {
        background: var(--accent-dark);
        transform: translateY(-2px);
    }
    
    .coupon-success {
        color: var(--primary);
        font-weight: 600;
        margin-top: 0.5rem;
    }
    
    .coupon-error {
        color: var(--danger);
        margin-top: 0.5rem;
    }
    
    .summary-card {
        background: var(--light);
        border-radius: 15px;
        padding: 2rem;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
    }
    
    .summary-item {
        display: flex;
        justify-content: space-between;
        margin-bottom: 1rem;
        color: var(--dark);
    }
    
    .summary-total {
        display: flex;
        justify-content: space-between;
        font-size: 1.3rem;
        font-weight: 700;
        color: var(--primary);
        padding-top: 1rem;
        margin-top: 1rem;
        border-top: 2px solid var(--accent);
    }
    
    .discount-item {
        color: var(--danger);
        font-weight: 600;
    }
    
    .btn-checkout {
        background: var(--primary);
        border: none;
        padding: 15px;
        border-radius: 12px;
        font-weight: 700;
        font-size: 1.1rem;
        transition: all 0.3s;
        color: white;
        width: 100%;
        margin-top: 1.5rem;
    }
    
    .btn-checkout:hover {
        background: var(--accent-dark);
        transform: translateY(-3px);
        box-shadow: 0 5px 15px rgba(20, 89, 44, 0.3);
    }
    
    .btn-back {
        background: white;
        border: 2px solid var(--primary);
        color: var(--primary);
        padding: 12px;
        border-radius: 12px;
        font-weight: 600;
        transition: all 0.3s;
        width: 100%;
        margin-top: 1rem;
    }
    
    .btn-back:hover {
        background: var(--primary);
        color: white;
        transform: translateY(-2px);
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
    
    .qr-code-container {
        text-align: center;
        padding: 2rem;
    }
    
    .qr-code {
        max-width: 250px;
        margin-bottom: 1rem;
        border: 2px solid var(--accent);
        border-radius: 12px;
        padding: 1rem;
        background: white;
    }
    
    .card-form .form-group {
        margin-bottom: 1rem;
    }
    
    .card-form label {
        color: var(--dark);
        font-weight: 600;
        margin-bottom: 0.5rem;
    }
    
    .card-form input {
        border: 2px solid var(--accent);
        border-radius: 8px;
        padding: 0.75rem;
        width: 100%;
    }
    
    .card-form input:focus {
        border-color: var(--primary);
        outline: none;
        box-shadow: 0 0 0 0.2rem rgba(20, 89, 44, 0.25);
    }
    
    .card-form-row {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 1rem;
    }
    
    /* ESTILO PARA SEÇÃO DE ENDEREÇO - CHECKOUT */
    .address-section {
        background: var(--light);
        border-radius: 15px;
        padding: 2rem;
        margin-bottom: 2rem;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
        border-left: 4px solid var(--primary);
    }

    .address-header {
        display: flex;
        align-items: center;
        margin-bottom: 1.5rem;
        padding-bottom: 1rem;
        border-bottom: 2px solid rgba(20, 89, 44, 0.1);
    }

    .address-header i {
        font-size: 1.8rem;
        color: var(--primary);
        margin-right: 15px;
    }

    .address-title {
        color: var(--dark);
        font-weight: 700;
        margin: 0;
        font-size: 1.4rem;
    }

    .address-subtitle {
        color: #6c757d;
        font-size: 0.95rem;
        margin: 0.25rem 0 0 0;
    }

    /* Seleção de endereço salvo */
    .saved-addresses {
        margin-bottom: 2rem;
    }

    .address-select-label {
        display: block;
        color: var(--dark);
        font-weight: 600;
        margin-bottom: 0.8rem;
        font-size: 1.1rem;
    }

    .form-control-filter {
        width: 100%;
        padding: 12px 15px;
        border: 2px solid var(--accent);
        border-radius: 10px;
        font-size: 1rem;
        transition: all 0.3s;
        background: white;
    }

    .form-control-filter:focus {
        border-color: var(--primary);
        outline: none;
        box-shadow: 0 0 0 0.2rem rgba(20, 89, 44, 0.25);
    }

    /* Novo endereço */
    .new-address {
        background: white;
        border-radius: 12px;
        padding: 1.5rem;
        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05);
        border: 2px dashed var(--accent);
    }

    .new-address-label {
        display: block;
        color: var(--dark);
        font-weight: 600;
        margin-bottom: 0.8rem;
        font-size: 1.1rem;
    }

    .new-address textarea {
        min-height: 100px;
        resize: vertical;
    }

    /* Endereços salvos - estilo de cards */
    .address-cards {
        display: grid;
        gap: 1rem;
        margin-bottom: 1.5rem;
    }

    .address-card {
        background: white;
        border: 2px solid var(--accent);
        border-radius: 12px;
        padding: 1.5rem;
        cursor: pointer;
        transition: all 0.3s;
        position: relative;
    }

    .address-card:hover {
        border-color: var(--primary);
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
    }

    .address-card.selected {
        border-color: var(--primary);
        background: rgba(20, 89, 44, 0.05);
    }

    .address-card input[type="radio"] {
        position: absolute;
        opacity: 0;
    }

    .address-card-content {
        color: var(--dark);
        line-height: 1.5;
        font-size: 0.95rem;
    }

    .address-card .bi-check-circle-fill {
        position: absolute;
        top: 15px;
        right: 15px;
        color: var(--primary);
        font-size: 1.2rem;
        opacity: 0;
        transition: opacity 0.3s;
    }

    .address-card.selected .bi-check-circle-fill {
        opacity: 1;
    }

    /* Botão para adicionar novo endereço */
    .add-address-btn {
        background: var(--accent);
        border: 2px dashed var(--primary);
        color: var(--dark);
        padding: 1rem;
        border-radius: 10px;
        text-align: center;
        cursor: pointer;
        transition: all 0.3s;
        font-weight: 600;
        margin-top: 1rem;
    }

    .add-address-btn:hover {
        background: var(--primary);
        color: white;
        transform: translateY(-2px);
    }

    .add-address-btn i {
        margin-right: 8px;
    }

    /* Campos de endereço detalhado (opcional para expansão futura) */
    .address-fields {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 1rem;
        margin-top: 1.5rem;
    }

    .address-field-group {
        margin-bottom: 1rem;
    }

    .address-field-label {
        display: block;
        color: var(--dark);
        font-weight: 600;
        margin-bottom: 0.5rem;
        font-size: 0.95rem;
    }

    .address-field-input {
        width: 100%;
        padding: 10px 12px;
        border: 2px solid var(--accent);
        border-radius: 8px;
        font-size: 0.95rem;
        transition: all 0.3s;
    }

    .address-field-input:focus {
        border-color: var(--primary);
        outline: none;
        box-shadow: 0 0 0 0.2rem rgba(20, 89, 44, 0.25);
    }

    /* Responsividade */
    @media (max-width: 768px) {
        .address-section {
            padding: 1.5rem;
        }
        
        .address-fields {
            grid-template-columns: 1fr;
        }
        
        .address-card {
            padding: 1.2rem;
        }
        
        .payment-methods {
            grid-template-columns: 1fr;
        }
        
        .coupon-form {
            flex-direction: column;
        }
        
        .card-form-row {
            grid-template-columns: 1fr;
        }
        
        .checkout-section {
            padding: 1.5rem;
        }
    }

    @media (max-width: 576px) {
        .address-header {
            flex-direction: column;
            align-items: flex-start;
            gap: 10px;
        }
        
        .address-header i {
            margin-right: 0;
            margin-bottom: 10px;
        }
    }

    /* Animações */
    @keyframes pulse {
        0% { transform: scale(1); }
        50% { transform: scale(1.02); }
        100% { transform: scale(1); }
    }

    .address-card {
        animation: pulse 0.5s ease-out;
    }

    /* Indicador de endereço principal */
    .address-primary-badge {
        position: absolute;
        top: -10px;
        right: 15px;
        background: var(--primary);
        color: white;
        padding: 4px 10px;
        border-radius: 15px;
        font-size: 0.75rem;
        font-weight: 600;
    }

    /* Tooltip para informações de endereço */
    .address-tooltip {
        position: relative;
        display: inline-block;
        margin-left: 8px;
        color: var(--primary);
        cursor: help;
    }

    .address-tooltip .tooltip-text {
        visibility: hidden;
        width: 200px;
        background-color: var(--dark);
        color: white;
        text-align: center;
        border-radius: 6px;
        padding: 8px;
        position: absolute;
        z-index: 1;
        bottom: 125%;
        left: 50%;
        transform: translateX(-50%);
        opacity: 0;
        transition: opacity 0.3s;
        font-size: 0.85rem;
        font-weight: normal;
    }

    .address-tooltip:hover .tooltip-text {
        visibility: visible;
        opacity: 1;
    }

    /* Validação visual */
    .address-validation {
        color: var(--danger);
        font-size: 0.85rem;
        margin-top: 0.5rem;
        display: none;
    }

    .address-validation.visible {
        display: block;
    }

    .address-validation i {
        margin-right: 5px;
    }
    </style>
</head>
<body>

<div class="page-header">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-md-8">
                <h1 class="display-4 fw-bold">Finalizar Compra</h1>
                <p class="lead">Complete seu pedido com segurança</p>
            </div>
            <div class="col-md-4 text-md-end">
                <div class="d-inline-block bg-white bg-opacity-25 px-3 py-2 rounded-pill">
                    <i class="bi bi-shield-check me-2"></i>
                    <span>Compra Segura</span>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="container">
    <?php if (isset($_SESSION['error_message'])): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-triangle me-2"></i>
            <?php echo $_SESSION['error_message']; unset($_SESSION['error_message']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    
    <form method="POST" action="">
        <div class="row">
            <!-- Resumo do Pedido -->
            <div class="col-lg-5">
                <div class="checkout-container">
                    <div class="checkout-section">
                        <h5 class="section-title"><i class="bi bi-bag-check"></i> Resumo do Pedido</h5>
                        
                        <?php foreach ($carrinhoItens as $item): ?>
                        <div class="d-flex align-items-center mb-3">
                            <img src="assets/img/<?php echo htmlspecialchars($item['image']); ?>" 
                                 class="product-image-checkout me-3"
                                 alt="<?php echo htmlspecialchars($item['name']); ?>"
                                 onerror="this.src='https://via.placeholder.com/80x80/DFF2E7/14592C?text=Produto'">
                            <div class="flex-grow-1">
                                <h6 class="mb-1"><?php echo htmlspecialchars($item['name']); ?></h6>
                                <p class="mb-1"><?php echo $item['quantity']; ?> x R$ <?php echo number_format($item['price'], 2, ',', '.'); ?></p>
                            </div>
                            <div class="fw-bold">R$ <?php echo number_format($item['price'] * $item['quantity'], 2, ',', '.'); ?></div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <div class="checkout-section">
                        <h5 class="section-title"><i class="bi bi-ticket-perforated"></i> Cupom de Desconto</h5>
                        
                        <div class="coupon-section">
                            <form method="POST" class="coupon-form">
                                <input type="text" name="cupom" class="coupon-input" placeholder="Digite seu cupom" value="<?php echo $cupomCodigo; ?>">
                                <button type="submit" name="aplicar_cupom" class="btn-coupon">Aplicar</button>
                            </form>
                            
                            <?php if ($cupomAplicado): ?>
                                <div class="coupon-success">
                                    <i class="bi bi-check-circle"></i> Cupom "CarecaLindo" aplicado! 50% de desconto.
                                </div>
                            <?php elseif (isset($_POST['aplicar_cupom']) && !$cupomAplicado): ?>
                                <div class="coupon-error">
                                    <i class="bi bi-x-circle"></i> Cupom inválido. Tente "CarecaLindo" para 50% de desconto.
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="summary-card">
                            <div class="summary-item">
                                <span>Subtotal (<?php echo $totalItens; ?> itens)</span>
                                <span>R$ <?php echo number_format($totalGeral, 2, ',', '.'); ?></span>
                            </div>
                            
                            <?php if ($cupomAplicado): ?>
                            <div class="summary-item discount-item">
                                <span>Desconto Cupom (50%)</span>
                                <span>-R$ <?php echo number_format($descontoCupom, 2, ',', '.'); ?></span>
                            </div>
                            <?php endif; ?>
                            
                            <div class="summary-item">
                                <span>Frete</span>
                                <span class="text-success">Grátis</span>
                            </div>
                            
                            <div class="summary-total">
                                <span>Total</span>
                                <span>R$ <?php echo number_format($totalComDesconto, 2, ',', '.'); ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Seção de Endereço e Pagamento -->
            <div class="col-lg-7">
                <div class="checkout-container">
                    <!-- Seção de Endereço -->
                    <div class="address-section">
                        <div class="address-header">
                            <i class="bi bi-geo-alt-fill"></i>
                            <div>
                                <h3 class="address-title">Endereço de Entrega</h3>
                                <p class="address-subtitle">Para onde devemos enviar seu pedido?</p>
                            </div>
                        </div>

                        <?php if (!empty($enderecos)): ?>
                            <div class="saved-addresses">
                                <label class="address-select-label">
                                    <i class="bi bi-bookmark-check"></i> Seus endereços salvos:
                                </label>
                                
                                <div class="address-cards">
                                    <?php foreach ($enderecos as $index => $end): ?>
                                        <div class="address-card" onclick="selectAddress(<?php echo $end['id']; ?>)">
                                            <input type="radio" name="endereco_id" value="<?php echo $end['id']; ?>" 
                                                   id="endereco_<?php echo $end['id']; ?>">
                                            <i class="bi bi-check-circle-fill"></i>
                                            
                                            <?php if ($index === 0): ?>
                                                <span class="address-primary-badge">Principal</span>
                                            <?php endif; ?>
                                            
                                            <div class="address-card-content">
                                                <?php echo htmlspecialchars($end['endereco']); ?>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            
                            <div class="text-center">
                                <div class="add-address-btn" onclick="focusNewAddress()">
                                    <i class="bi bi-plus-circle"></i> Usar um endereço diferente
                                </div>
                            </div>
                        <?php endif; ?>

                        <div class="new-address" id="novo-endereco-section" <?php echo empty($enderecos) ? 'style="display:block;"' : 'style="display:none;"'; ?>>
                            <label class="new-address-label">
                                <i class="bi bi-pencil-square"></i> 
                                <?php echo empty($enderecos) ? 'Digite seu endereço completo:' : 'Novo endereço:'; ?>
                                <span class="address-tooltip">
                                    <i class="bi bi-question-circle"></i>
                                    <span class="tooltip-text">Inclua rua, número, complemento, bairro, cidade e CEP</span>
                                </span>
                            </label>
                            
                            <textarea name="novo_endereco" id="novo_endereco" class="form-control-filter"
                                      placeholder="Ex: Rua das Flores, 123 - Apt 101, Centro, São Paulo - SP, 01234-567"
                                      oninput="validateAddress(this)"></textarea>
                            
                            <div class="address-validation" id="address-validation">
                                <i class="bi bi-exclamation-triangle"></i>
                                <span>Por favor, digite um endereço completo para entrega.</span>
                            </div>
                            
                            <?php if (!empty($enderecos)): ?>
                                <div class="text-center mt-3">
                                    <button type="button" class="btn btn-sm btn-outline-primary" onclick="saveAddressPrompt()">
                                        <i class="bi bi-bookmark-plus"></i> Salvar este endereço para próximas compras
                                    </button>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Métodos de Pagamento -->
                    <div class="checkout-section">
                        <h5 class="section-title"><i class="bi bi-credit-card"></i> Método de Pagamento</h5>
                        
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
                            
                            <div class="payment-method" onclick="selectPayment('googlepay')">
                                <i class="bi bi-phone"></i>
                                <h6>Google Pay</h6>
                                <p>Pagamento digital</p>
                                <input type="radio" name="metodo_pagamento" value="Google Pay" style="display: none;">
                            </div>
                        </div>
                        
                        <!-- Detalhes do PIX -->
                        <div id="pix-details" class="payment-details">
                            <div class="qr-code-container">
                                <img src="assets/qr-code-pix.jpeg" alt="QR Code PIX" class="qr-code">
                                <p>Escaneie o QR Code avec votre app bancaire para pagar</p>
                                <p class="text-muted">Pagamento aprovado instantaneamente</p>
                            </div>
                        </div>
                        
                        <!-- Detalhes do Cartão -->
                        <div id="cartao-details" class="payment-details">
                            <div class="card-form">
                                <div class="form-group">
                                    <label>Número do Cartão</label>
                                    <input type="text" placeholder="0000 0000 0000 0000" maxlength="19">
                                </div>
                                
                                <div class="form-group">
                                    <label>Nome no Cartão</label>
                                    <input type="text" placeholder="Como está no cartão">
                                </div>
                                
                                <div class="card-form-row">
                                    <div class="form-group">
                                        <label>Validade</label>
                                        <input type="text" placeholder="MM/AA" maxlength="5">
                                    </div>
                                    
                                    <div class="form-group">
                                        <label>CVV</label>
                                        <input type="text" placeholder="000" maxlength="3">
                                    </div>
                                </div>
                                
                                <div class="form-group">
                                    <label>CPF do Titular</label>
                                    <input type="text" placeholder="000.000.000-00">
                                </div>
                            </div>
                        </div>
                        
                        <!-- Detalhes do Boleto -->
                        <div id="boleto-details" class="payment-details">
                            <div class="alert alert-info">
                                <i class="bi bi-info-circle"></i>
                                <p>O boleto bancário será gerado após a confirmação do pedido. O prazo para pagamento é de 2 dias úteis.</p>
                            </div>
                        </div>
                        
                    
                        <!-- Detalhes do Google Pay -->
                        <div id="googlepay-details" class="payment-details">
                            <div class="alert alert-info">
                                <i class="bi bi-info-circle"></i>
                                <p>Você será redirecionado para o Google Pay para finalizar o pagamento de forma segura.</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="checkout-section">
                        <button type="submit" name="finalizar_compra" class="btn btn-checkout" disabled>
                            <a href="checkout.php"><i class="bi bi-shield-check me-2"></i>Finalizar Compra</a>
                    
                    </button>
                        
                        <a href="view_cart.php" class="btn btn-back">
                            <i class="bi bi-arrow-left me-2"></i>Voltar ao Carrinho
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
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
    
    // Habilitar botão de finalizar compra
    document.querySelector('button[name="finalizar_compra"]').disabled = false;
}

function validateCheckoutForm() {
    var finalizarBtn = document.getElementById('finalizar-btn');
    var metodoSelecionado = document.querySelector('input[name="metodo_pagamento"]:checked');
    var enderecoSelecionado = document.querySelector('input[name="endereco_id"]:checked');
    var novoEndereco = document.getElementById('novo_endereco').value.trim();
    
    var temEndereco = enderecoSelecionado !== null || novoEndereco.length > 10;
    var temMetodoPagamento = metodoSelecionado !== null;
    
    if (temEndereco && temMetodoPagamento) {
        finalizarBtn.disabled = false;
    } else {
        finalizarBtn.disabled = true;
    }
}


// Formatação do número do cartão
document.addEventListener('DOMContentLoaded', function() {
    const cardInput = document.querySelector('input[placeholder="0000 0000 0000 0000"]');
    if (cardInput) {
        cardInput.addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            value = value.replace(/(\d{4})(?=\d)/g, '$1 ');
            e.target.value = value.substring(0, 19);
        });
    }
    
    const expiryInput = document.querySelector('input[placeholder="MM/AA"]');
    if (expiryInput) {
        expiryInput.addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            if (value.length > 2) {
                value = value.substring(0, 2) + '/' + value.substring(2, 4);
            }
            e.target.value = value.substring(0, 5);
        });
    }
});
</script>

<?php 
$stmt->close();
$conn->close();
include "footer.php"; 
?>