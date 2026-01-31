<?php
require "init.php";
include "header.php";
require "conexao.php";

// Configuração de paginação
$produtos_por_pagina = 12;
$pagina_atual = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$offset = ($pagina_atual - 1) * $produtos_por_pagina;

// Inicializar filtros
$filtro_categorias = isset($_GET['categorias']) ? $_GET['categorias'] : [];
$filtro_preco = isset($_GET['preco']) ? $_GET['preco'] : [];
$filtro_estoque = isset($_GET['estoque']) ? $_GET['estoque'] : '';

// Construir a consulta base
$sql = "SELECT SQL_CALC_FOUND_ROWS * FROM products WHERE 1=1";

// Filtro de categorias
if (!empty($filtro_categorias)) {
    $escaped_cats = [];
    foreach ($filtro_categorias as $cat) {
        $escaped_cats[] = "'" . $conn->real_escape_string($cat) . "'";
    }
    if (!empty($escaped_cats)) {
        $sql .= " AND category IN (" . implode(',', $escaped_cats) . ")";
    }
}

// Filtro de preço
if (!empty($filtro_preco)) {
    $condicoes_preco = [];
    foreach ($filtro_preco as $faixa) {
        switch ($faixa) {
            case 'ate-50':
                $condicoes_preco[] = "price <= 50";
                break;
            case '50-100':
                $condicoes_preco[] = "(price > 50 AND price <= 100)";
                break;
            case '100-200':
                $condicoes_preco[] = "(price > 100 AND price <= 200)";
                break;
            case 'acima-200':
                $condicoes_preco[] = "price > 200";
                break;
        }
    }
    if (!empty($condicoes_preco)) {
        $sql .= " AND (" . implode(" OR ", $condicoes_preco) . ")";
    }
}

// Filtro de estoque
if ($filtro_estoque === 'disponivel') {
    $sql .= " AND stock > 0";
}

// Ordenação
$ordenacao = isset($_GET['ordenar']) ? $_GET['ordenar'] : 'recentes';
switch ($ordenacao) {
    case 'menor-preco':
        $sql .= " ORDER BY price ASC";
        break;
    case 'maior-preco':
        $sql .= " ORDER BY price DESC";
        break;
    case 'populares':
        $sql .= " ORDER BY id DESC";
        break;
    default:
        $sql .= " ORDER BY id ASC";
        break;
}

// Paginação
$sql .= " LIMIT $offset, $produtos_por_pagina";

// Executar query
$result = $conn->query($sql);
if ($result === false) {
    error_log("Query error (loja.php): " . $conn->error . " -- SQL: " . $sql);
    $total_produtos = 0;
    $total_paginas = 0;
} else {
    $total_produtos = $conn->query("SELECT FOUND_ROWS()")->fetch_row()[0];
    $total_paginas = ceil($total_produtos / $produtos_por_pagina);
}
?>

<style>
:root {
    --primary: #14592C;
    --primary-light: #1E7A41;
    --secondary: #732D14;
    --accent: #A7D9B8;
    --accent-dark: #0F4020;
    --danger: #BF1B1B;
    --danger-dark: #F20707;
    --light: #DFF2E7;
    --dark: #0F4020;
}
.page-header {background: linear-gradient(135deg, var(--primary) 0%, var(--accent-dark) 100%);color: white;padding: 3rem 0;margin-bottom: 2rem;border-radius: 0 0 20px 20px;}
.product-card {transition: all 0.3s ease;border: none;border-radius: 12px;overflow: hidden;box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);height: 100%;cursor: pointer;}
.product-card:hover {transform: translateY(-8px);box-shadow: 0 12px 25px rgba(0, 0, 0, 0.15);}
.product-image-container {height: 220px;overflow: hidden;display: flex;align-items: center;justify-content: center;background: linear-gradient(135deg, var(--light) 0%, #ffffff 100%);padding: 20px;position: relative;}
.product-image {max-height: 100%;max-width: 100%;object-fit: contain;transition: transform 0.3s ease;}
.product-card:hover .product-image {transform: scale(1.05);}
.product-badge {position: absolute;top: 15px;left: 15px;background: var(--primary);color: white;padding: 5px 12px;border-radius: 20px;font-size: 0.8rem;font-weight: 600;z-index: 2;}
.card-body {display: flex;flex-direction: column;padding: 1.5rem;}
.card-title {font-size: 1.1rem;font-weight: 700;color: var(--dark);margin-bottom: 0.5rem;line-height: 1.4;}
.card-text {flex-grow: 1;font-size: 0.9rem;color: #6c757d;margin-bottom: 1rem;line-height: 1.5;}
.price {font-size: 1.3rem;color: var(--primary);font-weight: bold;margin-bottom: 0.5rem;}
.old-price {font-size: 0.9rem;color: #6c757d;text-decoration: line-through;margin-right: 0.5rem;}
.discount {font-size: 0.8rem;color: var(--danger);font-weight: 600;background: rgba(191, 27, 27, 0.1);padding: 2px 8px;border-radius: 4px;}
.stock-info {font-size: 0.85rem;margin-bottom: 1rem;}
.stock-success {color: var(--primary);font-weight: 600;}
.stock-danger {color: var(--danger);font-weight: 600;}
.btn-add-cart {background: var(--primary);border: none;padding: 10px;border-radius: 8px;font-weight: 600;transition: all 0.3s;color: white;}
.btn-add-cart:hover {background: var(--accent-dark);transform: translateY(-2px);}
.btn-login-purchase {background: var(--accent);border: 2px solid var(--primary);color: var(--dark);padding: 10px;border-radius: 8px;font-weight: 600;transition: all 0.3s;}
.btn-login-purchase:hover {background: var(--primary);color: white;transform: translateY(-2px);}
.btn-disabled {background: #f8f9fa;border: 1px solid #dee2e6;color: #6c757d;padding: 10px;border-radius: 8px;font-weight: 600;}
.filters-sidebar {background: var(--light);border-radius: 12px;padding: 1.5rem;margin-bottom: 2rem;}
.filter-title {color: var(--dark);font-weight: 700;margin-bottom: 1rem;padding-bottom: 0.5rem;border-bottom: 2px solid var(--accent);}
.filter-group {margin-bottom: 1.5rem;}
.filter-group h6 {color: var(--primary);font-weight: 600;margin-bottom: 0.8rem;}
.form-check-label {color: var(--dark);font-size: 0.9rem;}
.form-check-input:checked {background-color: var(--primary);border-color: var(--primary);}
.pagination {margin-top: 3rem;}
.page-link {color: var(--primary);border: 1px solid var(--accent);padding: 0.5rem 1rem;}
.page-link:hover {color: white;background-color: var(--primary);border-color: var(--primary);}
.page-item.active .page-link {background-color: var(--primary);border-color: var(--primary);color: white;}
.results-info {color: var(--dark);font-weight: 600;margin-bottom: 1.5rem;}
.sort-select {border: 2px solid var(--accent);border-radius: 8px;padding: 0.5rem;color: var(--dark);}
.sort-select:focus {border-color: var(--primary);outline: none;box-shadow: 0 0 0 0.2rem rgba(20, 89, 44, 0.25);}
.empty-state {text-align: center;padding: 4rem 2rem;color: var(--dark);}
.empty-state i {font-size: 4rem;color: var(--accent);margin-bottom: 1rem;}
.rating {color: #ffc107;margin-bottom: 0.5rem;}
.rating-count {font-size: 0.8rem;color: #6c757d;}

/* MODAL DETALHES DO PRODUTO - ESTILO MODERNO */
.modal-product-detail {
    border-radius: 20px;
    overflow: hidden;
    border: none;
    box-shadow: 0 25px 50px rgba(0, 0, 0, 0.25);
}

.modal-product-detail .modal-header {
    background: linear-gradient(135deg, var(--primary) 0%, var(--accent-dark) 100%);
    color: white;
    border-bottom: none;
    padding: 25px 30px;
    position: relative;
    overflow: hidden;
}

.modal-product-detail .modal-header::before {
    content: '';
    position: absolute;
    top: -50px;
    right: -50px;
    width: 150px;
    height: 150px;
    background: rgba(255, 255, 255, 0.1);
    border-radius: 50%;
}

.modal-product-detail .modal-title {
    font-weight: 800;
    font-size: 1.8rem;
    margin: 0;
    letter-spacing: -0.5px;
}

.modal-product-detail .btn-close {
    filter: invert(1);
    opacity: 0.8;
    transition: opacity 0.3s;
    position: relative;
    z-index: 10;
}

.modal-product-detail .btn-close:hover {
    opacity: 1;
}

.modal-product-detail .modal-body {
    padding: 0;
}

.product-detail-content {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 0;
}

.product-image-section {
    background: linear-gradient(135deg, var(--light) 0%, #ffffff 100%);
    padding: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    position: relative;
}

.product-main-image {
    max-width: 100%;
    max-height: 400px;
    object-fit: contain;
    border-radius: 15px;
    box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.product-main-image:hover {
    transform: scale(1.03);
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
}

.product-badge {
    position: absolute;
    top: 20px;
    left: 20px;
    background: var(--primary);
    color: white;
    padding: 8px 16px;
    border-radius: 25px;
    font-size: 0.9rem;
    font-weight: 700;
    z-index: 10;
    box-shadow: 0 5px 15px rgba(20, 89, 44, 0.3);
}

.product-info-section {
    padding: 40px;
    background: white;
}

.product-category {
    background: var(--accent);
    color: var(--dark);
    padding: 8px 16px;
    border-radius: 20px;
    font-size: 0.85rem;
    font-weight: 700;
    display: inline-block;
    margin-bottom: 20px;
    box-shadow: 0 4px 8px rgba(167, 217, 184, 0.3);
}

.product-description {
    color: var(--dark);
    line-height: 1.7;
    font-size: 1.1rem;
    margin-bottom: 25px;
}

.product-price-section {
    background: var(--light);
    padding: 25px;
    border-radius: 15px;
    margin-bottom: 25px;
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
}

.product-price {
    color: var(--primary);
    font-weight: 800;
    font-size: 2.2rem;
    margin-bottom: 10px;
    display: flex;
    align-items: baseline;
    gap: 10px;
}

.product-price-small {
    font-size: 1.1rem;
    color: #6c757d;
    font-weight: 500;
}

.stock-info {
    display: flex;
    align-items: center;
    margin-bottom: 20px;
    font-weight: 600;
    padding: 10px 15px;
    border-radius: 10px;
    background: rgba(20, 89, 44, 0.05);
}

.stock-success {
    color: var(--primary);
}

.stock-danger {
    color: var(--danger);
    background: rgba(191, 27, 27, 0.05);
}

.stock-info i {
    margin-right: 10px;
    font-size: 1.2rem;
}

.rating-overview {
    display: flex;
    align-items: center;
    margin-bottom: 20px;
    padding: 15px;
    background: var(--light);
    border-radius: 12px;
}

.rating-stars {
    color: #FFC107;
    font-size: 1.3rem;
    margin-right: 15px;
}

.rating-text {
    color: var(--dark);
    font-weight: 600;
}

.rating-count {
    color: #6c757d;
    font-size: 0.9rem;
}

.add-to-cart-section {
    margin-bottom: 30px;
}

.quantity-controls {
    display: flex;
    align-items: center;
    gap: 15px;
    margin-bottom: 20px;
}

.quantity-btn {
    width: 45px;
    height: 45px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    border: 2px solid var(--accent);
    background: white;
    color: var(--primary);
    font-weight: bold;
    font-size: 1.2rem;
    transition: all 0.3s;
    cursor: pointer;
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
}

.quantity-btn:hover {
    background: var(--primary);
    color: white;
    border-color: var(--primary);
    transform: scale(1.1);
    box-shadow: 0 6px 12px rgba(0, 0, 0, 0.15);
}

.quantity-number {
    font-size: 1.3rem;
    font-weight: 700;
    color: var(--dark);
    min-width: 50px;
    text-align: center;
}

.btn-add-cart-large {
    background: var(--primary);
    border: none;
    border-radius: 12px;
    padding: 15px 25px;
    font-weight: 700;
    font-size: 1.1rem;
    transition: all 0.3s;
    color: white;
    width: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    box-shadow: 0 5px 15px rgba(20, 89, 44, 0.3);
}

.btn-add-cart-large:hover {
    background: var(--accent-dark);
    transform: translateY(-3px);
    box-shadow: 0 8px 20px rgba(20, 89, 44, 0.4);
}

.btn-add-cart-large:disabled {
    background: #6c757d;
    cursor: not-allowed;
    transform: none;
    box-shadow: none;
}

.product-details-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 15px;
    margin-bottom: 25px;
}

.detail-item {
    display: flex;
    align-items: center;
    background: var(--light);
    padding: 15px;
    border-radius: 10px;
    color: var(--dark);
    transition: transform 0.3s, box-shadow 0.3s;
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.05);
}

.detail-item:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 16px rgba(0, 0, 0, 0.1);
}

.detail-item i {
    color: var(--primary);
    margin-right: 12px;
    font-size: 1.3rem;
}

.reviews-section {
    border-top: 2px solid var(--accent);
    padding-top: 30px;
    margin-top: 30px;
}

.reviews-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 25px;
}

.reviews-title {
    color: var(--dark);
    font-weight: 700;
    font-size: 1.4rem;
}

.review-item {
    background: white;
    border-radius: 12px;
    padding: 20px;
    margin-bottom: 20px;
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
    border-left: 4px solid var(--accent);
    transition: transform 0.3s;
}

.review-item:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
}

.review-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 15px;
}

.review-author {
    font-weight: 700;
    color: var(--dark);
}

.review-date {
    color: #6c757d;
    font-size: 0.9rem;
}

.review-rating {
    color: #FFC107;
    margin-bottom: 10px;
    font-size: 1.1rem;
}

.review-comment {
    color: var(--dark);
    line-height: 1.6;
}

.no-reviews {
    text-align: center;
    padding: 40px 20px;
    color: var(--dark);
}

.no-reviews i {
    font-size: 3.5rem;
    color: var(--accent);
    margin-bottom: 15px;
}

.review-form {
    background: var(--light);
    padding: 25px;
    border-radius: 15px;
    margin-top: 30px;
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
}

.review-form-title {
    color: var(--dark);
    font-weight: 700;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    gap: 10px;
}

.form-select-rating {
    border-radius: 10px;
    padding: 12px 15px;
    border: 2px solid var(--accent);
    background: white;
    font-size: 1rem;
    margin-bottom: 15px;
    transition: border-color 0.3s, box-shadow 0.3s;
}

.form-select-rating:focus {
    border-color: var(--primary);
    outline: none;
    box-shadow: 0 0 0 0.2rem rgba(20, 89, 44, 0.25);
}

.review-textarea {
    border-radius: 10px;
    padding: 15px;
    border: 2px solid var(--accent);
    width: 100%;
    min-height: 120px;
    resize: vertical;
    font-family: inherit;
    margin-bottom: 15px;
    transition: border-color 0.3s, box-shadow 0.3s;
}

.review-textarea:focus {
    border-color: var(--primary);
    outline: none;
    box-shadow: 0 0 0 0.2rem rgba(20, 89, 44, 0.25);
}

.login-prompt {
    text-align: center;
    padding: 30px;
    background: var(--light);
    border-radius: 15px;
    margin-top: 30px;
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
}

.login-prompt i {
    font-size: 3rem;
    color: var(--primary);
    margin-bottom: 15px;
}

.login-prompt a {
    color: var(--primary);
    font-weight: 700;
    text-decoration: none;
    transition: color 0.3s;
}

.login-prompt a:hover {
    color: var(--accent-dark);
    text-decoration: underline;
}

.product-features {
    margin: 25px 0;
}

.feature-list {
    list-style: none;
    padding: 0;
}

.feature-list li {
    padding: 8px 0;
    display: flex;
    align-items: center;
    color: var(--dark);
    transition: transform 0.3s;
}

.feature-list li:hover {
    transform: translateX(5px);
}

.feature-list li i {
    color: var(--primary);
    margin-right: 10px;
}

/* Animações */
@keyframes fadeIn {
    from { opacity: 0; transform: translateY(20px); }
    to { opacity: 1; transform: translateY(0); }
}

.modal-product-detail {
    animation: fadeIn 0.5s ease-out;
}

/* Responsividade */
@media (max-width: 992px) {
    .product-detail-content {
        grid-template-columns: 1fr;
    }
    
    .product-image-section {
        padding: 30px;
    }
    
    .product-info-section {
        padding: 30px;
    }
    
    .product-details-grid {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 768px) {
    .modal-product-detail .modal-header {
        padding: 20px;
    }
    
    .modal-product-detail .modal-title {
        font-size: 1.5rem;
    }
    
    .product-image-section,
    .product-info-section {
        padding: 20px;
    }
    
    .product-price {
        font-size: 1.8rem;
    }
    
    .quantity-controls {
        justify-content: center;
    }
    
    .btn-add-cart-large {
        padding: 12px 20px;
        font-size: 1rem;
    }
}

@media (max-width: 576px) {
    .modal-product-detail .modal-title {
        font-size: 1.3rem;
    }
    
    .product-main-image {
        max-height: 300px;
    }
    
    .reviews-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 10px;
    }
}

</style>

<div class="page-header">
  <div class="container">
    <div class="row align-items-center">
      <div class="col-md-8">
        <h1 class="display-4 fw-bold">Nossa Loja</h1>
        <p class="lead">Descubra as melhores tintas e produtos para sua casa</p>
      </div>
      <div class="col-md-4 text-md-end">
        <div class="d-inline-block bg-white bg-opacity-25 px-3 py-2 rounded-pill">
          <i class="bi bi-box-seam me-2"></i>
          <span><?php echo $total_produtos; ?> produtos disponíveis</span>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="container">
  <div class="row">
    <!-- Sidebar de filtros -->
    <div class="col-lg-3">
      <form method="GET" action="" id="filters-form">
        <input type="hidden" name="pagina" value="1">
        <div class="filters-sidebar">
          <h5 class="filter-title">Filtros</h5>
          <!-- Categorias -->
          <div class="filter-group">
            <h6>Categorias</h6>
            <?php
            $categorias_query = $conn->query("SELECT DISTINCT category FROM products WHERE category IS NOT NULL AND category != ''");
            while ($cat = $categorias_query->fetch_assoc()) {
              $categoria = $cat['category'];
              $categoria_id = preg_replace('/[^a-z0-9]/', '-', strtolower($categoria));
              $checked = in_array($categoria, $filtro_categorias) ? 'checked' : '';
              echo '<div class="form-check">
                      <input class="form-check-input" type="checkbox" name="categorias[]" value="'.htmlspecialchars($categoria).'" id="cat-'.$categoria_id.'" '.$checked.'>
                      <label class="form-check-label" for="cat-'.$categoria_id.'">'.htmlspecialchars($categoria).'</label>
                    </div>';
            }
            ?>
          </div>
          <!-- Preço -->
          <div class="filter-group">
            <h6>Preço</h6>
            <div class="form-check"><input class="form-check-input" type="checkbox" name="preco[]" value="ate-50" id="price-1" <?php echo in_array('ate-50',$filtro_preco)?'checked':''; ?>><label class="form-check-label" for="price-1">Até R$ 50</label></div>
            <div class="form-check"><input class="form-check-input" type="checkbox" name="preco[]" value="50-100" id="price-2" <?php echo in_array('50-100',$filtro_preco)?'checked':''; ?>><label class="form-check-label" for="price-2">R$ 50 - R$ 100</label></div>
            <div class="form-check"><input class="form-check-input" type="checkbox" name="preco[]" value="100-200" id="price-3" <?php echo in_array('100-200',$filtro_preco)?'checked':''; ?>><label class="form-check-label" for="price-3">R$ 100 - R$ 200</label></div>
            <div class="form-check"><input class="form-check-input" type="checkbox" name="preco[]" value="acima-200" id="price-4" <?php echo in_array('acima-200',$filtro_preco)?'checked':''; ?>><label class="form-check-label" for="price-4">Acima de R$ 200</label></div>
          </div>
          <!-- Estoque -->
          <div class="filter-group">
            <h6>Disponibilidade</h6>
            <div class="form-check"><input class="form-check-input" type="radio" name="estoque" value="disponivel" id="stock-available" <?php echo $filtro_estoque==='disponivel'?'checked':''; ?>><label class="form-check-label" for="stock-available">Em estoque</label></div>
            <div class="form-check"><input class="form-check-input" type="radio" name="estoque" value="todos" id="stock-all" <?php echo $filtro_estoque!=='disponivel'?'checked':''; ?>><label class="form-check-label" for="stock-all">Todos</label></div>
          </div>
          <button type="submit" class="btn btn-add-cart w-100">Aplicar Filtros</button>
          <?php if (!empty($filtro_categorias)||!empty($filtro_preco)||$filtro_estoque==='disponivel'): ?>
            <a href="?" class="btn btn-login-purchase w-100 mt-2">Limpar</a>
          <?php endif; ?>
        </div>
      </form>
    </div>

    <!-- Área de produtos -->
    <div class="col-lg-9">
      <div class="d-flex justify-content-between align-items-center mb-4">
        <p class="results-info">Mostrando <?php echo min($produtos_por_pagina,$total_produtos-$offset); ?> de <?php echo $total_produtos; ?> produtos</p>
        <form method="GET">
          <?php foreach ($_GET as $key=>$value){ if($key!=='ordenar' && $key!=='pagina'){ if(is_array($value)){ foreach($value as $val){ echo '<input type="hidden" name="'.$key.'[]" value="'.htmlspecialchars($val).'">'; } } else { echo '<input type="hidden" name="'.$key.'" value="'.htmlspecialchars($value).'">'; } } } ?>
          <select class="sort-select" name="ordenar" onchange="this.form.submit()">
            <option value="recentes" <?php echo $ordenacao==='recentes'?'selected':''; ?>>Mais Recentes</option>
            <option value="menor-preco" <?php echo $ordenacao==='menor-preco'?'selected':''; ?>>Menor Preço</option>
            <option value="maior-preco" <?php echo $ordenacao==='maior-preco'?'selected':''; ?>>Maior Preço</option>
            <option value="populares" <?php echo $ordenacao==='populares'?'selected':''; ?>>Mais Populares</option>
          </select>
        </form>
      </div>

      <div class="row">
        <?php
        if ($result && $result->num_rows>0){
          while ($row=$result->fetch_assoc()){
          // Buscar média de avaliações do produto
          $av_sql = "SELECT AVG(rating) as media, COUNT(*) as total 
          FROM avaliacoes 
          WHERE product_id = ".$row['id'];
          $av_result = $conn->query($av_sql);
          $media = 0; $total_av = 0;
          if ($av_result && $av_result->num_rows > 0) {
          $dados = $av_result->fetch_assoc();
          $media = $dados['media'] ? round($dados['media'], 1) : 0;
          $total_av = $dados['total'];
          }

            ?>
            <div class="col-xl-3 col-lg-4 col-md-6 mb-4">
              <div class="card h-100 product-card" onclick="abrirProduto(<?php echo $row['id']; ?>)">
                <div class="product-image-container">
                  <?php if(!empty($row['image'])): ?>
                    <img src="assets/img/<?php echo htmlspecialchars($row['image']); ?>" class="product-image" alt="<?php echo htmlspecialchars($row['name']); ?>">
                  <?php else: ?>
                    <img src="https://via.placeholder.com/300x300/DFF2E7/14592C?text=Produto" class="product-image">
                  <?php endif; ?>
                </div>
                <div class="card-body">
                  <h5 class="card-title"><?php echo htmlspecialchars($row['name']); ?></h5>
                  <p class="card-text"><?php echo htmlspecialchars($row['description']); ?></p>
                                    <div class="rating">
                      <?php
                      $full_stars = floor($media);
                      $has_half_star = ($media - $full_stars) >= 0.5;

                      for ($i = 0; $i < $full_stars; $i++) {
                          echo '<i class="bi bi-star-fill"></i>';
                      }
                      if ($has_half_star) {
                          echo '<i class="bi bi-star-half"></i>';
                          $full_stars++;
                      }
                      for ($i = $full_stars; $i < 5; $i++) {
                          echo '<i class="bi bi-star"></i>';
                      }
                      ?>
                      <span class="rating-count">(<?php echo $total_av; ?>)</span>
                  </div>

                  <div class="price">R$ <?php echo number_format($row['price'],2,',','.'); ?></div>
                  <p class="stock-info <?php echo ($row['stock']>0)?'stock-success':'stock-danger'; ?>">
                    <i class="bi <?php echo ($row['stock']>0)?'bi-check-circle':'bi-x-circle'; ?>"></i>
                    <?php echo ($row['stock']>0)?"Em estoque ({$row['stock']})":"Produto esgotado"; ?>
                  </p>
                </div>
              </div>
            </div>
            <?php
          }
        } else {
          echo '<div class="col-12"><div class="empty-state"><i class="bi bi-box-seam"></i><h4>Nenhum produto encontrado</h4></div></div>';
        }
        ?>
      </div>

      <!-- Paginação -->
      <?php if($total_paginas>1): ?>
        <nav><ul class="pagination justify-content-center">
                <li class="page-item <?php echo $pagina_atual==1?'disabled':''; ?>">
            <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET,['pagina'=>$pagina_atual-1])); ?>">&laquo;</a>
          </li>
          <?php for($i=1;$i<=$total_paginas;$i++): ?>
            <li class="page-item <?php echo $i==$pagina_atual?'active':''; ?>">
              <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET,['pagina'=>$i])); ?>"><?php echo $i; ?></a>
            </li>
          <?php endfor; ?>
          <li class="page-item <?php echo $pagina_atual==$total_paginas?'disabled':''; ?>">
            <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET,['pagina'=>$pagina_atual+1])); ?>">&raquo;</a>
          </li>
        </ul></nav>
      <?php endif; ?>
    </div>
  </div>
</div>
<!-- Modal Detalhes do Produto -->
<div class="modal fade" id="produtoModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
    <div class="modal-content modal-product-detail">
      <div class="modal-header">
        <h5 class="modal-title" id="modal-nome"></h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div class="product-detail-content">
          <!-- Seção da Imagem -->
          <div class="product-image-section">
            <img id="modal-imagem" class="product-main-image" 
                 onerror="this.src='https://via.placeholder.com/400x400/DFF2E7/14592C?text=Imagem+Indispon%C3%ADvel'">
            <div class="product-badge" id="stock-badge" style="display: none;">
              <i class="bi bi-lightning"></i> Últimas unidades!
            </div>
          </div>
          
          <!-- Seção de Informações -->
          <div class="product-info-section">
            <div class="product-category">
              <i class="bi bi-tag"></i> <span id="modal-categoria"></span>
            </div>
            
            <p class="product-description" id="modal-descricao"></p>
            
            <!-- Avaliação -->
            <div class="rating-overview" id="rating-overview">
              <div class="rating-stars" id="modal-rating-stars"></div>
              <div>
                <div class="rating-text" id="modal-rating-text">0 de 5</div>
                <div class="rating-count" id="modal-rating-count">0 avaliações</div>
              </div>
            </div>
            
            <!-- Preço -->
            <div class="product-price-section">
              <div class="product-price">
                R$ <span id="modal-preco"></span>
                <span class="product-price-small">à vista</span>
              </div>
              
              <p class="stock-info" id="modal-stock-info">
                <i class="bi bi-check-circle"></i>
                <span id="modal-stock-text"></span>
              </p>
            </div>
            
            <!-- Detalhes do Produto -->
            <div class="product-details-grid">
              <div class="detail-item">
                <i class="bi bi-upc-scan"></i>
                <span>Código: <strong id="modal-codigo"></strong></span>
              </div>
              <div class="detail-item">
                <i class="bi bi-box-seam"></i>
                <span>Categoria: <strong id="modal-categoria-strong"></strong></span>
              </div>
              <div class="detail-item">
                <i class="bi bi-truck"></i>
                <span>Entrega: <strong>Grátis</strong></span>
              </div>
              <div class="detail-item">
                <i class="bi bi-shield-check"></i>
                <span>Garantia: <strong>12 meses</strong></span>
              </div>
            </div>
            
            <!-- Adicionar ao Carrinho -->
            <div class="add-to-cart-section">
              <form method="POST" action="add_to_cart.php" id="add-to-cart-form">
                <input type="hidden" name="id" id="modal-id">
                
                <div class="quantity-controls">
                  <button type="button" class="quantity-btn" onclick="decreaseQuantityModal()">-</button>
                  <span class="quantity-number" id="modal-quantity">1</span>
                  <button type="button" class="quantity-btn" onclick="increaseQuantityModal()">+</button>
                  <input type="hidden" name="qtd" id="modal-quantity-input" value="1">
                </div>
                
                <button type="submit" class="btn-add-cart-large" id="modal-add-cart-btn">
                  <i class="bi bi-cart-plus"></i> Adicionar ao Carrinho
                </button>
              </form>
              <p class="text-center text-muted mt-2" id="out-of-stock-text" style="display: none;">Avise-me quando chegar</p>
            </div>
            
            <!-- Características -->
            <div class="product-features">
              <h6><i class="bi bi-stars"></i> Características</h6>
              <ul class="feature-list">
                <li><i class="bi bi-check-circle"></i> Alta qualidade e durabilidade</li>
                <li><i class="bi bi-check-circle"></i> Fácil aplicação</li>
                <li><i class="bi bi-check-circle"></i> Resistente à água</li>
                <li><i class="bi bi-check-circle"></i> Cobertura superior</li>
                <li><i class="bi bi-check-circle"></i> Secagem rápida</li>
                <li><i class="bi bi-check-circle"></i> Cheiro suave</li>
              </ul>
            </div>
          </div>
        </div>
        
        <!-- Avaliações -->
        <div class="reviews-section">
          <div class="reviews-header">
            <h5 class="reviews-title"><i class="bi bi-star"></i> Avaliações dos Clientes</h5>
          </div>
          
          <div id="modal-avaliacoes">
            <!-- Avaliações serão carregadas aqui -->
          </div>

          <?php if (isset($_SESSION['user_id'])): ?>
            <div class="review-form">
              <h5 class="review-form-title"><i class="bi bi-pencil"></i> Deixe sua avaliação</h5>
              <form method="POST" action="salvar_avaliacao.php" id="form-avaliacao">
                <input type="hidden" name="product_id" id="avaliacao-produto-id">
                
                <select name="rating" class="form-select-rating" required>
                  <option value="">Selecione sua nota</option>
                  <option value="5">⭐⭐⭐⭐⭐ Excelente</option>
                  <option value="4">⭐⭐⭐⭐ Muito Bom</option>
                  <option value="3">⭐⭐⭐ Bom</option>
                  <option value="2">⭐⭐ Regular</option>
                  <option value="1">⭐ Ruim</option>
                </select>
                
                <textarea name="comment" class="review-textarea" placeholder="Compartilhe sua experiência com este produto..." required></textarea>
                
                <button type="submit" class="btn-add-cart-large">
                  <i class="bi bi-send"></i> Enviar Avaliação
                </button>
              </form>
            </div>
          <?php else: ?>
            <div class="login-prompt">
              <i class="bi bi-lock"></i>
              <h5>Faça login para avaliar</h5>
              <p>Você precisa <a href="login.php">fazer login</a> para deixar sua avaliação.</p>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
// Variáveis globais para controle de quantidade
let currentStock = 0;

// Função para abrir modal e carregar dados via AJAX
function abrirProduto(id) {
  fetch('get_product.php?id=' + id)
    .then(r => r.json())
    .then(prod => {
      if (prod) {
        // Preencher dados básicos
        document.getElementById('modal-nome').innerText = prod.name;
        document.getElementById('modal-descricao').innerText = prod.description || '';
        document.getElementById('modal-preco').innerText = parseFloat(prod.price).toFixed(2).replace('.', ',');
        document.getElementById('modal-codigo').innerText = '#' + prod.id;
        document.getElementById('modal-categoria').innerText = prod.category || 'Sem categoria';
        document.getElementById('modal-categoria-strong').innerText = prod.category || 'Sem categoria';
        document.getElementById('modal-id').value = prod.id;
        document.getElementById('avaliacao-produto-id').value = prod.id;
        
        // Configurar estoque
        currentStock = parseInt(prod.stock);
        const stockText = currentStock > 0 ? `Em estoque (${currentStock} unidades)` : 'Produto esgotado';
        document.getElementById('modal-stock-text').innerText = stockText;
        
        // Configurar classes de estoque
        const stockInfo = document.getElementById('modal-stock-info');
        const stockIcon = stockInfo.querySelector('i');
        
        if (currentStock > 0) {
          stockInfo.className = 'stock-info stock-success';
          stockIcon.className = 'bi bi-check-circle';
          document.getElementById('modal-add-cart-btn').disabled = false;
          document.getElementById('out-of-stock-text').style.display = 'none';
          
          // Mostrar badge se estoque baixo
          if (currentStock < 10) {
            document.getElementById('stock-badge').style.display = 'block';
          } else {
            document.getElementById('stock-badge').style.display = 'none';
          }
        } else {
          stockInfo.className = 'stock-info stock-danger';
          stockIcon.className = 'bi bi-x-circle';
          document.getElementById('modal-add-cart-btn').disabled = true;
          document.getElementById('out-of-stock-text').style.display = 'block';
          document.getElementById('stock-badge').style.display = 'none';
        }
        
        // Forçar caminho correto da imagem
        document.getElementById('modal-imagem').src = prod.image 
          ? "assets/img/" + prod.image 
          : "https://via.placeholder.com/400x400/DFF2E7/14592C?text=Produto";

        // Carregar avaliações
        carregarAvaliacoes(id);
        
        // Resetar quantidade
        document.getElementById('modal-quantity').innerText = '1';
        document.getElementById('modal-quantity-input').value = '1';
        
        // Mostrar modal
        var modal = new bootstrap.Modal(document.getElementById('produtoModal'));
        modal.show();
      }
    });
}

// Função para carregar avaliações
function carregarAvaliacoes(productId) {
  fetch('get_avaliacoes.php?product_id=' + productId)
    .then(r => r.json())
    .then(data => {
      const avaliacoesContainer = document.getElementById('modal-avaliacoes');
      const ratingStars = document.getElementById('modal-rating-stars');
      const ratingText = document.getElementById('modal-rating-text');
      const ratingCount = document.getElementById('modal-rating-count');
      
      // Atualizar informações de avaliação
      const media = data.media || 0;
      const total = data.total || 0;
      
      ratingText.innerText = `${media} de 5`;
      ratingCount.innerText = `${total} avaliações`;
      
      // Atualizar estrelas
      ratingStars.innerHTML = '';
      const fullStars = Math.floor(media);
      const hasHalfStar = (media - fullStars) >= 0.5;
      
      for (let i = 0; i < fullStars; i++) {
        ratingStars.innerHTML += '<i class="bi bi-star-fill"></i>';
      }
      if (hasHalfStar) {
        ratingStars.innerHTML += '<i class="bi bi-star-half"></i>';
      }
      for (let i = 0; i < (5 - fullStars - (hasHalfStar ? 1 : 0)); i++) {
        ratingStars.innerHTML += '<i class="bi bi-star"></i>';
      }
      
      // Exibir avaliações
      if (data.avaliacoes && data.avaliacoes.length > 0) {
        let html = '';
        data.avaliacoes.forEach(av => {
          const stars = '⭐'.repeat(av.rating);
          const date = new Date(av.created_at).toLocaleDateString('pt-BR');
          
          html += `
          <div class="review-item">
            <div class="review-header">
              <div class="review-author">${av.name}</div>
              <div class="review-date">${date}</div>
            </div>
            <div class="review-rating">${stars}</div>
            <div class="review-comment">${av.comment}</div>
          </div>`;
        });
        avaliacoesContainer.innerHTML = html;
      } else {
        avaliacoesContainer.innerHTML = `
        <div class="no-reviews">
          <i class="bi bi-chat-quote"></i>
          <h5>Ainda não há avaliações</h5>
          <p>Seja o primeiro a avaliar este produto!</p>
        </div>`;
      }
    });
}

// Funções para controle de quantidade no modal
function increaseQuantityModal() {
  const quantityElement = document.getElementById('modal-quantity');
  const quantityInput = document.getElementById('modal-quantity-input');
  let current = parseInt(quantityElement.textContent);
  
  if (current < currentStock) {
    quantityElement.textContent = current + 1;
    quantityInput.value = current + 1;
  } else {
    alert('Quantidade máxima em estoque atingida.');
  }
}

function decreaseQuantityModal() {
  const quantityElement = document.getElementById('modal-quantity');
  const quantityInput = document.getElementById('modal-quantity-input');
  let current = parseInt(quantityElement.textContent);
  
  if (current > 1) {
    quantityElement.textContent = current - 1;
    quantityInput.value = current - 1;
  }
}

// Adicionar efeito de carregamento ao enviar formulário
document.getElementById('add-to-cart-form').addEventListener('submit', function() {
  const submitBtn = this.querySelector('button[type="submit"]');
  if (submitBtn) {
    submitBtn.innerHTML = '<i class="bi bi-arrow-repeat spinner"></i> Processando...';
    submitBtn.disabled = true;
  }
});

document.getElementById('form-avaliacao').addEventListener('submit', function() {
  const submitBtn = this.querySelector('button[type="submit"]');
  if (submitBtn) {
    submitBtn.innerHTML = '<i class="bi bi-arrow-repeat spinner"></i> Enviando...';
    submitBtn.disabled = true;
  }
});
</script>
<?php
$conn->close();
include "footer.php";
?>

<?php
session_start();
require "conexao.php";

if (!isset($_GET['id'])) {
    exit("Produto inválido.");
}

$id = intval($_GET['id']);
$sql = "SELECT * FROM products WHERE id = $id";
$result = $conn->query($sql);

if ($result && $result->num_rows > 0) {
    $produto = $result->fetch_assoc();
    ?>
    <style>
        :root {
            --primary: #14592C;       /* Verde escuro principal */
            --primary-light: #1E7A41; /* Verde mais claro */
            --secondary: #732D14;     /* Marrom escuro */
            --accent: #A7D9B8;        /* Verde claro */
            --accent-dark: #0F4020;   /* Verde muito escuro */
            --danger: #BF1B1B;        /* Vermelho */
            --danger-dark: #F20707;   /* Vermelho mais vibrante */
            --light: #DFF2E7;         /* Verde muito claro */
            --dark: #0F4020;          /* Verde escuro para textos */
        }
        
        .modal-product .modal-header {
            background: linear-gradient(135deg, var(--primary) 0%, var(--accent-dark) 100%);
            color: white;
            border-bottom: none;
            padding: 20px;
        }
        
        .modal-product .modal-title {
            font-weight: 700;
            font-size: 1.5rem;
        }
        
        .modal-product .btn-close {
            filter: invert(1);
        }
        
        .modal-product .modal-body {
            padding: 25px;
        }
        
        .product-image-container {
            position: relative;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
        }
        
        .product-image {
            width: 100%;
            height: 300px;
            object-fit: cover;
            transition: transform 0.3s;
        }
        
        .product-image:hover {
            transform: scale(1.03);
        }
        
        .product-price {
            color: var(--primary);
            font-weight: 700;
            font-size: 1.8rem;
            margin: 15px 0;
        }
        
        .product-description {
            color: var(--dark);
            line-height: 1.6;
            margin-bottom: 20px;
        }
        
        .stock-badge {
            padding: 8px 15px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.9rem;
        }
        
        .stock-success {
            background-color: rgba(20, 89, 44, 0.15);
            color: var(--primary);
        }
        
        .stock-danger {
            background-color: rgba(191, 27, 27, 0.15);
            color: var(--danger);
        }
        
        .add-to-cart-form {
            background: var(--light);
            padding: 20px;
            border-radius: 12px;
            margin: 20px 0;
        }
        
        .form-control-quantity {
            border-radius: 8px;
            padding: 10px 15px;
            border: 2px solid #c8e6d4;
            width: 80px;
            text-align: center;
            font-weight: 600;
        }
        
        .btn-add-cart {
            background: var(--primary);
            border: none;
            border-radius: 8px;
            padding: 10px 20px;
            font-weight: 600;
            transition: all 0.3s;
            color: white;
        }
        
        .btn-add-cart:hover {
            background: var(--accent-dark);
            transform: translateY(-2px);
        }
        
        .reviews-section {
            margin-top: 25px;
            padding-top: 20px;
            border-top: 2px dashed var(--accent);
        }
        
        .review-item {
            background: white;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 15px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
            border-left: 4px solid var(--accent);
        }
        
        .review-author {
            font-weight: 600;
            color: var(--dark);
            margin-bottom: 5px;
        }
        
        .review-rating {
            color: #FFC107;
            margin-bottom: 8px;
        }
        
        .review-comment {
            color: var(--dark);
            line-height: 1.5;
        }
        
        .review-form {
            background: var(--light);
            padding: 20px;
            border-radius: 12px;
            margin-top: 20px;
        }
        
        .form-select-rating {
            border-radius: 8px;
            padding: 10px 15px;
            border: 2px solid #c8e6d4;
            background: white;
        }
        
        .form-select-rating:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 0.2rem rgba(20, 89, 44, 0.25);
        }
        
        .product-details-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
            margin: 15px 0;
        }
        
        .detail-item {
            display: flex;
            align-items: center;
            background: var(--light);
            padding: 10px 15px;
            border-radius: 8px;
        }
        
        .detail-item i {
            color: var(--primary);
            margin-right: 10px;
            font-size: 1.2rem;
        }
        
        .login-prompt {
            text-align: center;
            padding: 20px;
            background: var(--light);
            border-radius: 12px;
            margin-top: 20px;
        }
        
        .login-prompt a {
            color: var(--primary);
            font-weight: 600;
            text-decoration: none;
        }
        
        .login-prompt a:hover {
            text-decoration: underline;
        }
        
        .no-reviews {
            text-align: center;
            padding: 30px;
            color: var(--dark);
        }
        
        .no-reviews i {
            font-size: 3rem;
            color: var(--accent);
            margin-bottom: 15px;
        }
        
        .product-category {
            background: var(--accent);
            color: var(--dark);
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
            display: inline-block;
            margin-bottom: 15px;
        }
        
        @media (max-width: 768px) {
            .modal-product .modal-body {
                padding: 15px;
            }
            
            .product-image {
                height: 250px;
            }
            
            .product-details-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>

    <div class="modal-content modal-product">
        <div class="modal-header">
            <h5 class="modal-title"><?php echo htmlspecialchars($produto['name']); ?></h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
            <div class="row">
                <div class="col-md-6">
                    <div class="product-image-container">
                        <?php if (!empty($produto['image']) && file_exists("assets/img/" . $produto['image'])): ?>
                            <img src="assets/img/<?php echo htmlspecialchars($produto['image']); ?>" 
                                 class="product-image" alt="<?php echo htmlspecialchars($produto['name']); ?>">
                        <?php else: ?>
                            <img src="https://via.placeholder.com/400x400/DFF2E7/14592C?text=Imagem+Indisponível" 
                                 class="product-image" alt="Imagem não disponível">
                        <?php endif; ?>
                    </div>
                    
                    <div class="product-details-grid">
                        <div class="detail-item">
                            <i class="bi bi-grid-1x2"></i>
                            <span>Categoria: <strong><?php echo htmlspecialchars($produto['category']); ?></strong></span>
                        </div>
                        <div class="detail-item">
                            <i class="bi bi-upc-scan"></i>
                            <span>Código: <strong>#<?php echo $produto['id']; ?></strong></span>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="product-category">
                        <i class="bi bi-tag"></i> <?php echo htmlspecialchars($produto['category']); ?>
                    </div>
                    
                    <p class="product-description"><?php echo htmlspecialchars($produto['description']); ?></p>
                    
                    <h4 class="product-price">R$ <?php echo number_format($produto['price'], 2, ',', '.'); ?></h4>
                    
                    <p class="mb-3">
                        <?php if ($produto['stock'] > 0): ?>
                            <span class="stock-badge stock-success">
                                <i class="bi bi-check-circle"></i> Em estoque (<?php echo $produto['stock']; ?> unidades)
                            </span>
                        <?php else: ?>
                            <span class="stock-badge stock-danger">
                                <i class="bi bi-x-circle"></i> Esgotado
                            </span>
                        <?php endif; ?>
                    </p>
                    
                    <?php if ($produto['stock'] > 0): ?>
                        <div class="add-to-cart-form">
                            <h6 class="mb-3"><i class="bi bi-cart-plus"></i> Adicionar ao Carrinho</h6>
                            <form method="POST" action="add_to_cart.php" class="d-flex align-items-center">
                                <input type="hidden" name="id" value="<?php echo $produto['id']; ?>">
                                <div class="input-group me-3" style="width: 140px;">
                                    <span class="input-group-text bg-accent">Qtd</span>
                                    <input type="number" name="qtd" value="1" min="1" max="<?php echo $produto['stock']; ?>" 
                                           class="form-control-quantity">
                                </div>
                                <button type="submit" class="btn btn-add-cart">
                                    <i class="bi bi-cart-plus"></i> Adicionar ao Carrinho
                                </button>
                            </form>
                        </div>
                    <?php endif; ?>

                    <div class="reviews-section">
                        <h6><i class="bi bi-star"></i> Avaliações dos Clientes</h6>
                        
                        <div id="avaliacoes">
                            <?php
                           $av = $conn->query("SELECT u.name, r.rating, r.comentario, r.created_at 
                           FROM avaliacoes r 
                           LEFT JOIN users u ON r.user_id = u.id
                           WHERE r.product_id = $id 
                           ORDER BY r.id DESC LIMIT 5");
                            if ($av && $av->num_rows > 0) {
                                while ($row = $av->fetch_assoc()) {
                                    $stars = str_repeat('⭐', $row['rating']);
                                    $date = date("d/m/Y", strtotime($row['created_at']));
                                    echo "
                                    <div class='review-item'>
                                        <div class='review-author'>{$row['name']} <small class='text-muted'>- {$date}</small></div>
                                        <div class='review-rating'>{$stars}</div>
                                        <div class='review-comment'>{$row['comentario']}</div>
                                    </div>";
                                }
                            } else {
                                echo "
                                <div class='no-reviews'>
                                    <i class='bi bi-chat-quote'></i>
                                    <p>Ainda não há avaliações para este produto.</p>
                                    <small class='text-muted'>Seja o primeiro a avaliar!</small>
                                </div>";
                            }
                            ?>
                        </div>

                        <?php if (isset($_SESSION['user_id'])): ?>
                            <div class="review-form">
                                <h6><i class="bi bi-pencil"></i> Deixe sua avaliação</h6>
                                <form method="POST" action="salvar_avaliacao.php">
                                    <input type="hidden" name="product_id" value="<?php echo $produto['id']; ?>">
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Sua nota:</label>
                                        <select name="rating" class="form-select-rating w-50">
                                            <option value="5">⭐⭐⭐⭐⭐ Excelente</option>
                                            <option value="4">⭐⭐⭐⭐ Muito Bom</option>
                                            <option value="3">⭐⭐⭐ Bom</option>
                                            <option value="2">⭐⭐ Regular</option>
                                            <option value="1">⭐ Ruim</option>
                                        </select>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <textarea name="comment" class="form-control" placeholder="Compartilhe sua experiência com este produto..." rows="3"></textarea>
                                    </div>
                                    
                                    <button type="submit" class="btn btn-add-cart">
                                        <i class="bi bi-send"></i> Enviar Avaliação
                                    </button>
                                </form>
                            </div>
                        <?php else: ?>
                            <div class="login-prompt">
                                <i class="bi bi-lock"></i>
                                <p>Faça <a href="login.php">login</a> para avaliar este produto.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php
} else {
    echo "
    <div class='modal-content'>
        <div class='modal-header'>
            <h5 class='modal-title'>Produto Não Encontrado</h5>
            <button type='button' class='btn-close' data-bs-dismiss='modal'></button>
        </div>
        <div class='modal-body text-center py-5'>
            <i class='bi bi-exclamation-triangle' style='font-size: 3rem; color: var(--danger);'></i>
            <p class='mt-3'>O produto solicitado não foi encontrado em nosso sistema.</p>
        </div>
    </div>";
}
$conn->close();
?>