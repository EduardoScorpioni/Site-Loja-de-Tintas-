<?php
require "init.php";
require "conexao.php";

// Verificar login
if (!isset($_SESSION['user_id'])) {
    $_SESSION['error_message'] = "Você precisa fazer login.";
    header("Location: login.php");
    exit;
}

if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $productId = intval($_GET['id']);
    $userId = $_SESSION['user_id'];

    $stmt = $conn->prepare("DELETE FROM carts WHERE user_id = ? AND product_id = ?");
    $stmt->bind_param("ii", $userId, $productId);
    $stmt->execute();
    $stmt->close();
}

$conn->close();
header("Location: view_cart.php");
exit;
