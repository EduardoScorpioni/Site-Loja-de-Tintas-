<?php
require "init.php";
require "conexao.php";

// Verificar login
if (!isset($_SESSION['user_id'])) {
    $_SESSION['error_message'] = "Você precisa fazer login.";
    header("Location: login.php");
    exit;
}

$userId = $_SESSION['user_id'];

if (isset($_GET['id']) && isset($_GET['action'])) {
    $productId = intval($_GET['id']);
    $action = $_GET['action'];

    // Buscar produto e estoque
    $stmt = $conn->prepare("SELECT stock FROM products WHERE id = ?");
    $stmt->bind_param("i", $productId);
    $stmt->execute();
    $stmt->bind_result($stock);
    $stmt->fetch();
    $stmt->close();

    if ($action === "increase") {
        // Verificar estoque antes de aumentar
        $stmt = $conn->prepare("UPDATE carts c 
                                INNER JOIN products p ON c.product_id = p.id 
                                SET c.quantity = c.quantity + 1 
                                WHERE c.user_id = ? AND c.product_id = ? AND c.quantity < p.stock");
        $stmt->bind_param("ii", $userId, $productId);
        $stmt->execute();
        $stmt->close();
    } elseif ($action === "decrease") {
        // Diminuir quantidade (mínimo 1)
        $stmt = $conn->prepare("UPDATE carts SET quantity = quantity - 1 
                                WHERE user_id = ? AND product_id = ? AND quantity > 1");
        $stmt->bind_param("ii", $userId, $productId);
        $stmt->execute();
        $stmt->close();
    }
}

$conn->close();
header("Location: view_cart.php");
exit;
