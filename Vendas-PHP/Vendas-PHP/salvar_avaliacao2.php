<?php
session_start();
require "conexao.php";

// Verifica se o usuário está logado
if (!isset($_SESSION['user_id'])) {
    $_SESSION['error_message'] = "Você precisa estar logado para avaliar um produto.";
    header("Location: loja.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$product_id = intval($_POST['product_id']);
$rating = intval($_POST['nota']);
$comentario = trim($_POST['comentario'] );

// Validações básicas
if ($product_id <= 0 || $rating < 1 || $rating > 5 || empty($comentario)) {
    $_SESSION['error_message'] = "Preencha todos os campos corretamente.";
    header("Location: loja.php");
    exit;
}

// Verificar se o usuário comprou esse produto
$checkCompra = $conn->prepare("
    SELECT COUNT(*) 
    FROM sales 
    WHERE cliente_id = ? AND product_id = ?
");
$checkCompra->bind_param("ii", $user_id, $product_id);
$checkCompra->execute();
$checkCompra->bind_result($comprou);
$checkCompra->fetch();
$checkCompra->close();

if ($comprou == 0) {
    $_SESSION['error_message'] = "Você só pode avaliar produtos que já comprou.";
    header("Location: loja.php");
    exit;
}

// Verificar se o usuário já avaliou esse produto (opcional, se quiser limitar 1 avaliação por pessoa)
$checkAvaliacao = $conn->prepare("
    SELECT id FROM avaliacoes WHERE user_id = ? AND product_id = ?
");
$checkAvaliacao->bind_param("ii", $user_id, $product_id);
$checkAvaliacao->execute();
$checkAvaliacao->store_result();

if ($checkAvaliacao->num_rows > 0) {
    $_SESSION['error_message'] = "Você já avaliou este produto.";
    $checkAvaliacao->close();
    header("Location: loja.php");
    exit;
}
$checkAvaliacao->close();

// Inserir avaliação
$stmt = $conn->prepare("
    INSERT INTO avaliacoes (product_id, user_id, rating, comentario) 
    VALUES (?, ?, ?, ?)
");
$stmt->bind_param("iiis", $product_id, $user_id, $rating, $comentario);

if ($stmt->execute()) {
    $_SESSION['success_message'] = "Obrigado pela sua avaliação!";
} else {
    $_SESSION['error_message'] = "Erro ao salvar avaliação: " . $stmt->error;
}

$stmt->close();
$conn->close();

// Redirecionar de volta para a loja
header("Location: loja.php");
exit;
?>
