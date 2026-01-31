<?php
require "conexao.php";

if (isset($_GET['id'])) {
    $id = intval($_GET['id']);

    $stmt = $conn->prepare("SELECT id, name FROM users WHERE id = ? AND user_type = 'cliente'");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($cliente = $result->fetch_assoc()) {
        echo json_encode($cliente);
    } else {
        echo json_encode(null);
    }

    $stmt->close();
}
$conn->close();
