<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

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
?>a