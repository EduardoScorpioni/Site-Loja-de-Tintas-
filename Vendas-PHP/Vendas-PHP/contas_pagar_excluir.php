<?php
require "init.php";
if (!isset($_SESSION['user_id']) || ($_SESSION['user_type'] != 'funcionario' && $_SESSION['user_type'] != 'gerente')) {
    header("Location: login.php");
    exit;
}

require "conexao.php";

$id = isset($_GET['id']) ? intval($_GET['id']) : null;
if ($id) {
    $stmt = $conn->prepare("DELETE FROM contas_pagar WHERE id=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
}
header("Location: contas_pagar.php");
?>