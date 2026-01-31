<?php
session_start();
require "conexao.php";

if (!isset($_GET['product_id'])) {
    echo json_encode(['error' => 'ID do produto não especificado']);
    exit();
}

$product_id = intval($_GET['product_id']);

// Buscar média e total de avaliações
$rating_sql = "SELECT AVG(rating) as media, COUNT(*) as total FROM avaliacoes WHERE product_id = ?";
$rating_stmt = $conn->prepare($rating_sql);
$rating_stmt->bind_param("i", $product_id);
$rating_stmt->execute();
$rating_result = $rating_stmt->get_result();
$rating_data = $rating_result->fetch_assoc();

// Buscar avaliações
$avaliacoes_sql = "SELECT u.name, a.rating, a.comentario as comment, a.created_at 
                   FROM avaliacoes a 
                   LEFT JOIN users u ON a.user_id = u.id
                   WHERE a.product_id = ? 
                   ORDER BY a.id DESC LIMIT 5";
$avaliacoes_stmt = $conn->prepare($avaliacoes_sql);
$avaliacoes_stmt->bind_param("i", $product_id);
$avaliacoes_stmt->execute();
$avaliacoes_result = $avaliacoes_stmt->get_result();

$avaliacoes = [];
while ($row = $avaliacoes_result->fetch_assoc()) {
    $avaliacoes[] = $row;
}

echo json_encode([
    'media' => $rating_data['media'] ? round($rating_data['media'], 1) : 0,
    'total' => $rating_data['total'],
    'avaliacoes' => $avaliacoes
]);

$conn->close();
?>
