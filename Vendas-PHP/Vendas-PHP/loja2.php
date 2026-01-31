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
/* (Mantive todo o seu CSS original sem alterações) */
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
  <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header bg-success text-white">
        <h5 class="modal-title">Detalhes do Produto</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="row">
          <!-- Imagem -->
          <div class="col-md-5 text-center">
            <img id="modal-imagem" src="" class="img-fluid rounded shadow-sm mb-3"
                 onerror="this.src='https://via.placeholder.com/400x400/DFF2E7/14592C?text=Produto';">
          </div>
          <!-- Infos -->
          <div class="col-md-7">
            <h3 id="modal-nome"></h3>
            <p id="modal-descricao" class="text-muted"></p>
            <div class="rating mb-2" id="modal-avaliacao"></div>
            <h4 class="text-success mb-3">R$ <span id="modal-preco"></span></h4>
            <p><strong>Estoque:</strong> <span id="modal-estoque"></span></p>
            <form method="POST" action="add_to_cart.php">
              <input type="hidden" name="id" id="modal-id">
              <div class="mb-3">
                <label for="modal-qtd" class="form-label">Quantidade</label>
                <input type="number" name="quantidade" id="modal-qtd" value="1" min="1" class="form-control" required>
              </div>
              <button type="submit" class="btn btn-success w-100">
                <i class="bi bi-cart-plus"></i> Comprar Agora
              </button>
            </form>
          </div>
        </div>

        <!-- Avaliações -->
        <hr>
        <h5 class="mb-3">Avaliações dos clientes</h5>
        <div id="modal-avaliacoes">
          <!-- Avaliações serão carregadas aqui -->
        </div>

        <?php if (isset($_SESSION['user_id'])): ?>
        <form class="mt-3" id="form-avaliacao" method="POST" action="salvar_avaliacao2.php">
          <input type="hidden" name="product_id" id="avaliacao-produto-id">
          <div class="mb-2">
            <label class="form-label">Sua avaliação</label>
            <select name="nota" class="form-select" required>
              <option value="">Selecione...</option>
              <option value="5">⭐⭐⭐⭐⭐ - Excelente</option>
              <option value="4">⭐⭐⭐⭐ - Muito Bom</option>
              <option value="3">⭐⭐⭐ - Bom</option>
              <option value="2">⭐⭐ - Regular</option>
              <option value="1">⭐ - Ruim</option>
            </select>
          </div>
          <div class="mb-2">
            <textarea name="comentario" class="form-control" placeholder="Escreva seu comentário" rows="3" required></textarea>
          </div>
          <button type="submit" class="btn btn-primary">Enviar Avaliação</button>
        </form>
        <?php else: ?>
        <p class="text-muted">Faça <a href="login.php">login</a> para avaliar.</p>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<script>
// Função para abrir modal e carregar dados via AJAX
function abrirProduto(id){
  fetch('get_product2.php?id='+id)
  .then(r=>r.json())
  .then(prod=>{
    if(prod){
      // Preencher dados
      document.getElementById('modal-nome').innerText = prod.name;
      document.getElementById('modal-descricao').innerText = prod.description || '';
      document.getElementById('modal-preco').innerText = parseFloat(prod.price).toFixed(2).replace('.',',');
      document.getElementById('modal-estoque').innerText = prod.stock+" unidades";
      document.getElementById('modal-id').value = prod.id;
      document.getElementById('avaliacao-produto-id').value = prod.id;
      document.getElementById('modal-qtd').max = prod.stock;

      // Forçar caminho correto da imagem
      document.getElementById('modal-imagem').src = prod.image 
        ? "assets/img/"+prod.image 
        : "https://via.placeholder.com/400x400/DFF2E7/14592C?text=Produto";

      // Carregar avaliações reais via AJAX
      fetch('get_avaliacoes2.php?product_id='+id)
      .then(r=>r.text())
      .then(html=>{
        document.getElementById('modal-avaliacoes').innerHTML = html;
      });

      var modal = new bootstrap.Modal(document.getElementById('produtoModal'));
      modal.show();
    }
  });
}
</script>


<?php
$conn->close();
include "footer.php";
?>
 é a parte do modal detalhes do produto
e este é o produto_detalhes.php
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
                            $av = $conn->query("SELECT u.name, r.rating, r.comment, r.created_at 
                                                FROM reviews r 
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
                                        <div class='review-comment'>{$row['comment']}</div>
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
                                <form method="POST" action="salvar_avaliacao2.php">
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