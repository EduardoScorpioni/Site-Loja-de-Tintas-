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

// Buscar itens do carrinho do banco
$sql = "SELECT c.product_id, c.quantity, p.price, p.stock 
        FROM carts c
        INNER JOIN products p ON c.product_id = p.id
        WHERE c.user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();

if ($result && $result->num_rows > 0) {
    while ($item = $result->fetch_assoc()) {
        $productId = $item['product_id'];
        $quantity = $item['quantity'];
        $price = $item['price'];
        $stock = $item['stock'];
        $total = $price * $quantity;

        // Verificar se tem estoque suficiente
        if ($stock < $quantity) {
            $_SESSION['error_message'] = "Estoque insuficiente para o produto ID: $productId";
            header("Location: view_cart.php");
            exit;
        }

        // Inserir venda
        $insert = $conn->prepare("INSERT INTO sales (product_id, quantity, total) VALUES (?, ?, ?)");
        $insert->bind_param("iid", $productId, $quantity, $total);
        $insert->execute();
        $insert->close();

        // Atualizar estoque
        $update = $conn->prepare("UPDATE products SET stock = stock - ? WHERE id = ?");
        $update->bind_param("ii", $quantity, $productId);
        $update->execute();
        $update->close();
    }

    // Limpar o carrinho do usuário
    $clear = $conn->prepare("DELETE FROM carts WHERE user_id = ?");
    $clear->bind_param("i", $userId);
    $clear->execute();
    $clear->close();

    $mensagem = "Compra finalizada com sucesso!";
} else {
    $mensagem = "Seu carrinho está vazio.";
}

$conn->close();

include "header.php";
?>

<div class="container mt-5">
    <div class="alert alert-info text-center">
        <?php echo $mensagem; ?>
    </div>
    <a href="loja.php" class="btn btn-primary">Voltar à Loja</a>
</div>

<?php include "footer.php"; ?>
