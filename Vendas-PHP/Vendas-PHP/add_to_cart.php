<?php
require "init.php";
require "conexao.php";

// Verificar se o usuário está logado
if (!isset($_SESSION['user_id'])) {
    $_SESSION['error_message'] = "Você precisa fazer login para adicionar produtos ao carrinho.";
    header("Location: login.php");
    exit;
}

$userId = $_SESSION['user_id'];

// Aceitar ID via POST ou GET
$productId = null;
if (isset($_POST['id'])) {
    $productId = intval($_POST['id']);
} elseif (isset($_GET['id'])) {
    $productId = intval($_GET['id']);
}

if (!$productId) {
    $_SESSION['error_message'] = "ID do produto inválido.";
    header("Location: loja.php");
    exit;
}

// Quantidade solicitada (padrão = 1)
$quantity = 1;
if (isset($_POST['quantidade']) && is_numeric($_POST['quantidade'])) {
    $quantity = max(1, intval($_POST['quantidade']));
} elseif (isset($_POST['qtd']) && is_numeric($_POST['qtd'])) {
    $quantity = max(1, intval($_POST['qtd']));
}

// Buscar informações do produto
$stmt = $conn->prepare("SELECT id, name, price, stock FROM products WHERE id = ?");
$stmt->bind_param("i", $productId);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows > 0) {
    $stmt->bind_result($id, $name, $price, $stock);
    $stmt->fetch();

    // Verifica estoque
    if ($stock < $quantity) {
        $_SESSION['error_message'] = "Estoque insuficiente para este produto.";
        $stmt->close();
        header("Location: loja.php");
        exit;
    }

    $stmt->close();

    // Verificar se o produto já está no carrinho
    $check = $conn->prepare("SELECT quantity FROM carts WHERE user_id = ? AND product_id = ?");
    $check->bind_param("ii", $userId, $productId);
    $check->execute();
    $check->store_result();

    if ($check->num_rows > 0) {
        $check->bind_result($currentQuantity);
        $check->fetch();

        if ($currentQuantity + $quantity > $stock) {
            $_SESSION['error_message'] = "Quantidade solicitada excede o estoque disponível.";
            $check->close();
            header("Location: loja.php");
            exit;
        }

        $update = $conn->prepare("UPDATE carts SET quantity = quantity + ? WHERE user_id = ? AND product_id = ?");
        $update->bind_param("iii", $quantity, $userId, $productId);
        $update->execute();
        $update->close();
    } else {
        $insert = $conn->prepare("INSERT INTO carts (user_id, product_id, quantity) VALUES (?, ?, ?)");
        $insert->bind_param("iii", $userId, $productId, $quantity);
        $insert->execute();
        $insert->close();
    }
    $check->close();

    $_SESSION['success_message'] = "Produto adicionado ao carrinho com sucesso!";
    header("Location: view_cart.php");
    exit;
} else {
    $_SESSION['error_message'] = "Produto não encontrado.";
    $stmt->close();
    header("Location: loja.php");
    exit;
}

$conn->close();
?>
