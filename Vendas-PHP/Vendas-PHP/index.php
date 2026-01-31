<?php 
require "init.php";
include "header.php"; 
require "conexao.php";

// Consulta para buscar produtos em destaque
$sql = "SELECT * FROM products ORDER BY id ASC LIMIT 4"; 
$result = $conn->query($sql);
?>

<style>
:root {
    --primary: #14592C;
    --secondary: #BF1B1B;
    --accent: #A7D9B8;
    --dark: #0F4020;
    --light: #DFF2E7;
    --danger: #F20707;
    --brown: #732D14;
}

body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    background-color: var(--light);
    color: var(--dark);
}

.navbar { box-shadow: 0 2px 10px rgba(0,0,0,0.1); background-color: var(--primary); }
.hero-section { background: linear-gradient(to right, #14592C, #2ecc71); color: white; border-radius: 0 0 20px 20px; padding: 3rem 0; margin-bottom: 2rem; }
.category-card, .product-card { transition: transform 0.3s; border-radius: 12px; overflow: hidden; background-color: white; box-shadow: 0 4px 8px rgba(0,0,0,0.05); }
.category-card:hover, .product-card:hover { transform: translateY(-5px); box-shadow: 0 10px 20px rgba(0,0,0,0.1); }
.btn-primary { background-color: var(--primary); border-color: var(--primary); }
.btn-primary:hover { background-color: #0F4020; border-color: #0F4020; }
.promo-banner { background-color: var(--secondary); color: white; padding: 10px 0; text-align: center; font-weight: bold; }
.feature-icon { font-size: 2rem; color: var(--primary); margin-bottom: 1rem; }
.price { font-size: 1.25rem; color: var(--danger); font-weight: bold; }
.store-section { padding: 4rem 0; background-color: white; }
.map-container { position: relative; width: 100%; height: 400px; border-radius: 12px; overflow: hidden; box-shadow: 0 5px 15px rgba(0,0,0,0.1); margin-top: 20px; }
.map-iframe { width: 100%; height: 100%; border: 0; }
.store-info { background-color: var(--light); border-radius: 12px; padding: 20px; margin-top: 20px; }
.store-btn.active { background-color: var(--primary); color: white; }
.product-image-container { height: 220px; display: flex; align-items: center; justify-content: center; overflow: hidden; }
.product-image { max-width: 100%; max-height: 100%; object-fit: contain; }
</style>

<!-- Promo Banner -->
<div class="promo-banner">
    🎉 Frete Grátis em compras acima de R$ 200,00 | Use o cupom TINTAS10 para 10% de desconto 🎉
</div>

<!-- Hero Section -->
<section class="hero-section">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-md-6">
                <h1 class="display-4 fw-bold">As melhores tintas para sua casa</h1>
                <p class="lead">Transforme seus ambientes com nossas cores e produtos de alta qualidade.</p>
                <a href="loja.php" class="btn btn-light btn-lg mt-3">Comprar Agora</a>
            </div>
            <div class="col-md-6">
                <div id="mainCarousel" class="carousel slide" data-bs-ride="carousel">
                    <div class="carousel-inner rounded">
                        <div class="carousel-item active"><img src="assets/car/img1.jpg" class="d-block w-100" alt="Tintas de qualidade"></div>
                        <div class="carousel-item"><img src="assets/car/img2.jpg" class="d-block w-100" alt="Cores variadas"></div>
                        <div class="carousel-item"><img src="assets/car/img3.jpg" class="d-block w-100" alt="Projetos especiais"></div>
                        <div class="carousel-item"><img src="assets/car/img4.jpg" class="d-block w-100" alt="Promoções"></div>
                        <div class="carousel-item"><img src="assets/car/img5.jpg" class="d-block w-100" alt="Acessórios"></div>
                    </div>
                    <button class="carousel-control-prev" type="button" data-bs-target="#mainCarousel" data-bs-slide="prev">
                        <span class="carousel-control-prev-icon"></span>
                    </button>
                    <button class="carousel-control-next" type="button" data-bs-target="#mainCarousel" data-bs-slide="next">
                        <span class="carousel-control-next-icon"></span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Categorias -->
<section class="mb-5">
    <div class="container">
        <h2 class="text-center mb-4">Categorias em Destaque</h2>
        <div class="row g-4">
            <div class="col-md-3 col-6"><div class="category-card card text-center"><div class="card-body"><i class="bi bi-house-door feature-icon"></i><h5>Tintas para Interior</h5></div></div></div>
            <div class="col-md-3 col-6"><div class="category-card card text-center"><div class="card-body"><i class="bi bi-building feature-icon"></i><h5>Tintas para Exterior</h5></div></div></div>
            <div class="col-md-3 col-6"><div class="category-card card text-center"><div class="card-body"><i class="bi bi-brush feature-icon"></i><h5>Acessórios</h5></div></div></div>
            <div class="col-md-3 col-6"><div class="category-card card text-center"><div class="card-body"><i class="bi bi-palette feature-icon"></i><h5>Cores Especiais</h5></div></div></div>
        </div>
    </div>
</section>

<!-- Produtos em Destaque -->
<section class="mb-5">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Produtos em Destaque</h2>
            <a href="loja.php" class="btn btn-outline-primary">Ver Todos</a>
        </div>
        <div class="row g-4">
            <?php
            if ($result && $result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    ?>
                    <div class="col-md-3 col-6">
                        <div class="product-card card h-100">
                            <div class="product-image-container bg-light">
                                <?php if (!empty($row['image'])): ?>
                                    <img src="assets/img/<?php echo htmlspecialchars($row['image']); ?>" class="product-image" alt="<?php echo htmlspecialchars($row['name']); ?>">
                                <?php else: ?>
                                    <img src="https://via.placeholder.com/250x200?text=Sem+Imagem" class="product-image" alt="Imagem não disponível">
                                <?php endif; ?>
                            </div>
                            <div class="card-body">
                                <h5><?php echo htmlspecialchars($row['name']); ?></h5>
                                <p><?php echo htmlspecialchars($row['description']); ?></p>
                                <p class="price mb-1">R$ <?php echo number_format($row['price'], 2, ',', '.'); ?></p>
                                <p class="<?php echo ($row['stock'] > 0) ? 'text-success' : 'text-danger'; ?>">
                                    <?php echo ($row['stock'] > 0) ? "✓ Em estoque: {$row['stock']} unidades" : "✗ Produto esgotado"; ?>
                                </p>
                            </div>
                            <div class="card-footer bg-white">
                                <?php if (isset($_SESSION['user_id'])): ?>
                                    <?php if ($row['stock'] > 0): ?>
                                        <a href="add_to_cart.php?id=<?php echo $row['id']; ?>" class="btn btn-primary w-100"><i class="bi bi-cart-plus"></i> Adicionar ao Carrinho</a>
                                    <?php else: ?>
                                        <button class="btn btn-outline-secondary w-100" disabled><i class="bi bi-x-circle"></i> Produto Esgotado</button>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <a href="login.php" class="btn btn-outline-primary w-100"><i class="bi bi-box-arrow-in-right"></i> Faça login para comprar</a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <?php
                }
            }
            ?>
        </div>
    </div>
</section>

<!-- Seção de Lojas -->
<section class="store-section">
    <div class="container text-center">
        <h2 class="mb-5">Visite Nossas Lojas</h2>
        <div class="mb-4">
            <button class="btn btn-outline-primary store-btn active" data-store="1">Loja 1 - Jardim Bongiovani</button>
            <button class="btn btn-outline-primary store-btn" data-store="2">Loja 2 - Vila Marina</button>
        </div>
        <div class="row">
            <div class="col-md-6">
                <div class="map-container">
                    <!-- MAPAS REAIS E FUNCIONAIS -->
                    <iframe id="map-1" class="map-iframe" src="https://www.google.com/maps?q=Av.+Cel.+José+Soares+Marcondes,+4615,+Presidente+Prudente,+SP&output=embed"></iframe>
                    <iframe id="map-2" class="map-iframe" style="display:none;" src="https://www.google.com/maps?q=R.+Abílio+Nascimento,+347,+Presidente+Prudente,+SP&output=embed"></iframe>
                </div>
            </div>
            <div class="col-md-6">
                <div class="store-info" id="store-info-1">
                    <h4>Loja 1 - Jardim Bongiovani</h4>
                    <p><i class="bi bi-geo-alt-fill"></i> Av. Cel. José Soares Marcondes, 4615 A</p>
                    <p><i class="bi bi-clock-fill"></i> Segunda a Sábado: 8h às 18h</p>
                </div>
                <div class="store-info" id="store-info-2" style="display:none;">
                    <h4>Loja 2 - Vila Marina</h4>
                    <p><i class="bi bi-geo-alt-fill"></i> R. Abílio Nascimento, 347</p>
                    <p><i class="bi bi-clock-fill"></i> Segunda a Sábado: 8h às 18h</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Vantagens -->
<section class="mb-5 py-4 bg-light">
    <div class="container text-center">
        <div class="row">
            <div class="col-md-3"><i class="bi bi-truck feature-icon"></i><h5>Entrega Rápida</h5></div>
            <div class="col-md-3"><i class="bi bi-credit-card feature-icon"></i><h5>Pagamento Seguro</h5></div>
            <div class="col-md-3"><i class="bi bi-arrow-left-right feature-icon"></i><h5>Trocas Fáceis</h5></div>
            <div class="col-md-3"><i class="bi bi-headset feature-icon"></i><h5>Atendimento</h5></div>
        </div>
    </div>
</section>

<?php 
if (isset($conn)) $conn->close();
include "footer.php"; 
?>

<script>
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.store-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const id = this.dataset.store;
            document.querySelectorAll('.map-iframe, .store-info').forEach(el => el.style.display = 'none');
            document.getElementById(`map-${id}`).style.display = 'block';
            document.getElementById(`store-info-${id}`).style.display = 'block';
            document.querySelectorAll('.store-btn').forEach(b => b.classList.remove('active'));
            this.classList.add('active');
        });
    });
});
</script>
