<?php
require "init.php";
if (!isset($_SESSION['user_id']) || ($_SESSION['user_type'] != 'funcionario' && $_SESSION['user_type'] != 'gerente')) {
    header("Location: login.php");
    exit;
}

require "conexao.php";

$id = isset($_POST['id']) ? intval($_POST['id']) : null;
$metodo_pagamento = $_POST['metodo_pagamento'];
$data_pagamento = $_POST['data_pagamento'];
$observacoes = $_POST['observacoes'];

if ($id) {
    // Atualiza status para pago
    $stmt = $conn->prepare("UPDATE sales SET status_pagamento = 'pago', data_pagamento = ?, metodo_pagamento = ? WHERE id = ?");
    $stmt->bind_param("ssi", $data_pagamento, $metodo_pagamento, $id);
    
    if ($stmt->execute()) {
        header("Location: contas_receber.php?success=1");
        exit;
    } else {
        echo "Erro: " . $stmt->error;
    }
    
    $stmt->close();
} else {
    header("Location: contas_receber.php");
}

$conn->close();
?>