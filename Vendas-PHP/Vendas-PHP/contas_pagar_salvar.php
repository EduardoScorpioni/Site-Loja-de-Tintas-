<?php
require "init.php";
if (!isset($_SESSION['user_id']) || ($_SESSION['user_type'] != 'funcionario' && $_SESSION['user_type'] != 'gerente')) {
    header("Location: login.php");
    exit;
}

require "conexao.php";

$id = isset($_POST['id']) ? intval($_POST['id']) : null;
$descricao = $_POST['descricao'];
$valor = $_POST['valor'];
$categoria = $_POST['categoria'];
$data_vencimento = $_POST['data_vencimento'];
$status = $_POST['status'];
$data_pagamento = $_POST['data_pagamento'];
$metodo_pagamento = $_POST['metodo_pagamento'];
$observacoes = $_POST['observacoes'];

// Verifica se está atrasado
$vencimento = new DateTime($data_vencimento);
$hoje = new DateTime();
if ($vencimento < $hoje && $status == 'pendente') {
    $status = 'atrasado';
}

// Se não for pago, limpa dados de pagamento
if ($status != 'pago') {
    $data_pagamento = null;
    $metodo_pagamento = null;
}

if ($id) {
    // UPDATE
    $stmt = $conn->prepare("UPDATE contas_pagar SET descricao=?, valor=?, categoria=?, data_vencimento=?, status=?, data_pagamento=?, metodo_pagamento=?, observacoes=? WHERE id=?");
    $stmt->bind_param("sdssssssi", $descricao, $valor, $categoria, $data_vencimento, $status, $data_pagamento, $metodo_pagamento, $observacoes, $id);
} else {
    // INSERT
    $stmt = $conn->prepare("INSERT INTO contas_pagar (descricao, valor, categoria, data_vencimento, status, data_pagamento, metodo_pagamento, observacoes) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sdssssss", $descricao, $valor, $categoria, $data_vencimento, $status, $data_pagamento, $metodo_pagamento, $observacoes);
}

if ($stmt->execute()) {
    header("Location: contas_pagar.php?success=1");
    exit;
} else {
    echo "Erro: " . $stmt->error;
}

$stmt->close();
$conn->close();
?>