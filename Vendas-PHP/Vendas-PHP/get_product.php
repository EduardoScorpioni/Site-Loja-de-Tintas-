<?php
require "conexao.php";
session_start();

// Forçar retorno JSON sempre válido
header("Content-Type: application/json; charset=UTF-8");

// Importante: corrigir charset da conexão MySQL
$conn->set_charset("utf8");

// Validar ID
if (!isset($_GET['id'])) {
    echo json_encode(["erro" => "ID inválido"]);
    exit;
}

$id = intval($_GET['id']);

// Buscar produto
$sql = "SELECT id, name, description, price, stock, image, category 
        FROM products 
        WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result && $result->num_rows > 0) {

    $produto = $result->fetch_assoc();

    // Fix imagem – evitar quebrar JSON se não existir
    if (!empty($produto['image'])) {
        $caminho = "assets/img/" . $produto['image'];
        if (!file_exists($caminho)) {
            $produto['image'] = null;
        }
    }

    // Buscar média e quantidade de avaliações
    $sql_av = "SELECT AVG(rating) as media, COUNT(*) as total 
               FROM avaliacoes 
               WHERE product_id = ?";
    $stmt_av = $conn->prepare($sql_av);
    $stmt_av->bind_param("i", $id);
    $stmt_av->execute();
    $stmt_av->bind_result($media, $total);
    $stmt_av->fetch();
    $stmt_av->close();

    $produto["media_avaliacoes"] = $media ? round($media, 1) : 0;
    $produto["total_avaliacoes"] = $total ;

    // Retorno JSON seguro para acentos
    echo json_encode($produto, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

} else {

    echo json_encode(["erro" => "Produto não encontrado"]);
}

$stmt->close();
$conn->close();
?>
