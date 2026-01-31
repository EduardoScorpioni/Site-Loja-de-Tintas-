<?php
require "conexao.php";

if (!isset($_GET['id'])) {
    echo json_encode(['error' => 'ID não especificado']);
    exit();
}

$id = intval($_GET['id']);
$sql = "SELECT * FROM products WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result && $result->num_rows > 0) {
    $produto = $result->fetch_assoc();
    echo json_encode($produto);
} else {
    echo json_encode(['error' => 'Produto não encontrado']);
}

$conn->close();
?>