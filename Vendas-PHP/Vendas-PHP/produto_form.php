<?php
require "init.php";
if (!isset($_SESSION['user_id']) || ($_SESSION['user_type'] != 'funcionario' && $_SESSION['user_type'] != 'gerente')) {
    header("Location: login.php");
    exit;
}

include "header.php";
require "conexao.php";

$id = null;
$product = array('name'=>'','category'=>'','tipo_tinta'=>'','price'=>'','stock'=>'','description'=>'','image'=>'');

if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $id = intval($_GET['id']);
    // seleciona colunas de forma explícita
    $stmt = $conn->prepare("SELECT id, name, price, description, image, category, stock, tipo_tinta 
                            FROM products WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->bind_result($pid, $pname, $pprice, $pdescription, $pimage, $pcategory, $pstock, $ptipo_tinta);
    if ($stmt->fetch()) {
        $product = array(
            'name'        => $pname,
            'category'    => $pcategory,
            'tipo_tinta'  => $ptipo_tinta,
            'price'       => $pprice,
            'stock'       => $pstock,
            'description' => $pdescription,
            'image'       => $pimage
        );
    }
    $stmt->close();
}

// lista fixa de tipos de tinta
$tipos_tinta = [
    "Acrílica",
    "Látex PVA",
    "Esmalte Sintético",
    "Epóxi",
    "Automotiva",
    "Impermeabilizante",
    "Texturizada",
    "Spray",
    "Óleo",
    "Metálica",
    "Incolor/Verniz"
];
?>

<h2 class="mb-4"><?php echo $id ? "Editar Produto" : "Cadastrar Produto"; ?></h2>

<form action="produto_salvar.php" method="POST" enctype="multipart/form-data" class="bg-white p-4 rounded shadow-sm">
    <?php if ($id): ?>
        <input type="hidden" name="id" value="<?php echo $id; ?>">
    <?php endif; ?>

    <div class="form-group mb-3">
        <label>Nome</label>
        <input type="text" name="name" class="form-control" value="<?php echo htmlspecialchars($product['name']); ?>" required>
    </div>

    <div class="form-group mb-3">
        <label>Categoria</label>
        <select name="category" id="category" class="form-control" required>
            <option value="">Selecione...</option>
            <option value="Interior" <?php echo ($product['category']=="Interior"?"selected":""); ?>>Interior</option>
            <option value="Exterior" <?php echo ($product['category']=="Exterior"?"selected":""); ?>>Exterior</option>
            <option value="Interior/Exterior" <?php echo ($product['category']=="Interior/Exterior"?"selected":""); ?>>Interior/Exterior</option>
            <option value="Acessorios/Ferramentas" <?php echo ($product['category']=="Acessorios/Ferramentas"?"selected":""); ?>>Acessórios/Ferramentas</option>
            <option value="Especiais" <?php echo ($product['category']=="Especiais"?"selected":""); ?>>Especiais</option>
        </select>
    </div>

    <div class="form-group mb-3" id="tipo-tinta-container" style="display:none;">
        <label>Tipo de Tinta</label>
        <select name="tipo_tinta" class="form-control" id="tipo_tinta">
            <option value="">Selecione...</option>
            <?php foreach ($tipos_tinta as $tipo): ?>
                <option value="<?php echo htmlspecialchars($tipo); ?>" <?php echo ($product['tipo_tinta']==$tipo?"selected":""); ?>>
                    <?php echo htmlspecialchars($tipo); ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="form-group mb-3">
        <label>Preço</label>
        <input type="number" step="0.01" name="price" class="form-control" value="<?php echo $product['price']; ?>" required>
    </div>

    <div class="form-group mb-3">
        <label>Estoque</label>
        <input type="number" name="stock" class="form-control" value="<?php echo $product['stock']; ?>" required>
    </div>

    <div class="form-group mb-3">
        <label>Descrição</label>
        <textarea name="description" class="form-control" required><?php echo htmlspecialchars($product['description']); ?></textarea>
    </div>

    <div class="form-group mb-3">
        <label>Imagem</label>
        <?php if ($product['image']): ?>
            <p>Imagem atual: <img src="assets/img/<?php echo htmlspecialchars($product['image']); ?>" width="100"></p>
        <?php endif; ?>
        <input type="file" name="image" class="form-control">
    </div>

    <button type="submit" class="btn btn-primary">Salvar</button>
    <a href="products.php" class="btn btn-secondary">Cancelar</a>
</form>

<script>
document.addEventListener("DOMContentLoaded", function() {
    const category = document.getElementById("category");
    const tipoTintaContainer = document.getElementById("tipo-tinta-container");

    function toggleTipoTinta() {
        if (category.value === "Interior" || category.value === "Exterior" || category.value === "Interior/Exterior") {
            tipoTintaContainer.style.display = "block";
        } else {
            tipoTintaContainer.style.display = "none";
            document.getElementById("tipo_tinta").value = "";
        }
    }

    category.addEventListener("change", toggleTipoTinta);
    toggleTipoTinta(); // inicial
});
</script>

<?php include "footer.php"; ?>
