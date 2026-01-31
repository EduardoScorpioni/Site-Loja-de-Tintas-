<?php
require "init.php";
if (!isset($_SESSION['user_id']) || ($_SESSION['user_type'] != 'funcionario' && $_SESSION['user_type'] != 'gerente')) {
    header("Location: login.php");
    exit;
}

require "conexao.php";

$id = isset($_POST['id']) ? intval($_POST['id']) : null;
$name = $_POST['name'];
$category = $_POST['category'];
$tipo_tinta = isset($_POST['tipo_tinta']) ? $_POST['tipo_tinta'] : null;
$price = $_POST['price'];
$stock = $_POST['stock'];
$description = $_POST['description'];
$image = null;

// Upload da imagem
if (!empty($_FILES["image"]["name"])) {
    $target_dir = __DIR__ . "/assets/img/";
    if (!is_dir($target_dir)) {
        mkdir($target_dir, 0777, true);
    }
    $image = basename($_FILES["image"]["name"]);
    $target_file = $target_dir . $image;
    if (!move_uploaded_file($_FILES["image"]["tmp_name"], $target_file)) {
        die("Erro ao enviar a imagem");
    }
} elseif ($id) {
    // manter imagem existente ao editar
    $stmt_img = $conn->prepare("SELECT image FROM products WHERE id = ?");
    $stmt_img->bind_param("i", $id);
    $stmt_img->execute();
    $stmt_img->bind_result($existingImage);
    if ($stmt_img->fetch()) {
        $image = $existingImage;
    }
    $stmt_img->close();
}

// Se categoria for acessórios, não salvar tipo_tinta
if ($category === "Acessorios/Ferramentas") {
    $tipo_tinta = null;
}

if ($id) {
    // UPDATE
    $stmt = $conn->prepare("UPDATE products 
        SET name=?, price=?, description=?, image=?, category=?, stock=?, tipo_tinta=? 
        WHERE id=?");
    $stmt->bind_param("sdsssisi", $name, $price, $description, $image, $category, $stock, $tipo_tinta, $id);
} else {
    // INSERT
    $stmt = $conn->prepare("INSERT INTO products (name, price, description, image, category, stock, tipo_tinta) 
        VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sdsssis", $name, $price, $description, $image, $category, $stock, $tipo_tinta);
}

if ($stmt->execute()) {
    header("Location: products.php?success=1");
    exit;
} else {
    echo "Erro: " . $stmt->error;
}

$stmt->close();
$conn->close();
?>
