
<<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require "conexao.php";

// SE O USUÁRIO ESTIVER LOGADO, ATUALIZA user_type E user_name
if (isset($_SESSION['user_id'])) {
    $stmt = $conn->prepare("SELECT name, user_type FROM users WHERE id = ? LIMIT 1");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $stmt->bind_result($nameDB, $typeDB);

    if ($stmt->fetch()) {
        $_SESSION['user_name'] = $nameDB;
        $_SESSION['user_type'] = $typeDB;
    } else {
        // usuário não existe mais, força logout
        session_destroy();
        header("Location: login.php");
        exit;
    }

    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rosa Cores e Tintas</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/OwlCarousel2/2.3.4/assets/owl.carousel.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/OwlCarousel2/2.3.4/assets/owl.theme.default.min.css">

    <style>
    :root {
        --primary: #14592C;       /* Verde escuro principal */
        --primary-light: #1E7A41; /* Verde mais claro */
        --secondary: #732D14;     /* Marrom escuro */
        --accent: #A7D9B8;        /* Verde claro */
        --accent-dark: #0F4020;   /* Verde muito escuro */
        --danger: #BF1B1B;        /* Vermelho */
        --danger-dark: #F20707;   /* Vermelho mais  vibrante */
        --light: #DFF2E7;         /* Verde muito claro */
        --dark: #0F4020;          /* Verde escuro para textos */
        --gold: #FFD700;          /* Dourado para destaques */
        --silver: #C0C0C0;        /* Prata para vendedor */
    }
    
    body {
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        background-color: var(--light);
        color: var(--dark);
        padding-top: 140px; /* Ajuste para header fixo */
    }
    
    /* Banner Promocional */
    .promo-banner {
        background: linear-gradient(135deg, var(--secondary) 0%, var(--danger) 100%);
        color: white;
        padding: 12px 0;
        text-align: center;
        font-weight: 700;
        font-size: 0.95rem;
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        z-index: 1040;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        animation: pulseBanner 3s infinite;
    }
    
    @keyframes pulseBanner {
        0%, 100% { opacity: 1; }
        50% { opacity: 0.9; }
    }
    
    /* Navbar Principal */
    .navbar {
        background: linear-gradient(135deg, var(--primary) 0%, var(--accent-dark) 100%) !important;
        box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        padding: 12px 0;
        position: fixed;
        top: 40px;
        left: 0;
        right: 0;
        z-index: 1030;
        transition: all 0.3s ease;
    }
    
    .navbar-brand {
        display: flex;
        align-items: center;
        font-weight: 800;
        font-size: 1.4rem;
        color: white !important;
        text-shadow: 1px 1px 2px rgba(0,0,0,0.2);
    }
    
    .logo {
        height: 45px;
        margin-right: 12px;
        border-radius: 8px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.2);
        transition: transform 0.3s ease;
    }
    
    .logo:hover {
        transform: scale(1.05);
    }
    
    /* Navegação */
    .navbar-nav {
        gap: 6px;
    }
    
    .nav-link {
        color: rgba(255, 255, 255, 0.9) !important;
        font-weight: 600;
        padding: 8px 14px !important;
        border-radius: 8px;
        transition: all 0.3s ease;
        position: relative;
        display: flex;
        align-items: center;
        gap: 6px;
        font-size: 0.9rem;
    }
    
    .nav-link:hover {
        color: white !important;
        background: rgba(255, 255, 255, 0.15);
        transform: translateY(-2px);
    }
    
    /* Botões Específicos por Tipo de Usuário */
    
    /* Cliente - Verde suave */
    .nav-item.cliente .nav-link {
        background: rgba(167, 217, 184, 0.2);
        border: 1px solid rgba(167, 217, 184, 0.3);
    }
    
    .nav-item.cliente .nav-link:hover {
        background: rgba(167, 217, 184, 0.3);
        border-color: var(--accent);
    }
    
    /* Vendedor - Prata profissional */
    .nav-item.vendedor .nav-link {
        background: linear-gradient(135deg, rgba(192, 192, 192, 0.2) 0%, rgba(169, 169, 169, 0.3) 100%);
        border: 1px solid rgba(192, 192, 192, 0.4);
        color: #f8f9fa !important;
    }
    
    .nav-item.vendedor .nav-link:hover {
        background: linear-gradient(135deg, rgba(192, 192, 192, 0.3) 0%, rgba(169, 169, 169, 0.4) 100%);
        border-color: var(--silver);
    }
    
    /* Gerente - Dourado premium (mais compacto) */
    .nav-item.gerente .nav-link {
        background: linear-gradient(135deg, rgba(255, 215, 0, 0.15) 0%, rgba(218, 165, 32, 0.25) 100%);
        border: 1px solid rgba(255, 215, 0, 0.3);
        color: #fffacd !important;
        font-weight: 600;
        padding: 6px 12px !important;
        font-size: 0.85rem;
    }
    
    .nav-item.gerente .nav-link:hover {
        background: linear-gradient(135deg, rgba(255, 215, 0, 0.25) 0%, rgba(218, 165, 32, 0.35) 100%);
        border-color: var(--gold);
    }
    
    /* Botões de Ação */
    .btn-outline-light {
        border: 2px solid var(--accent);
        color: var(--accent);
        font-weight: 600;
        padding: 8px 20px;
        border-radius: 8px;
        transition: all 0.3s ease;
        font-size: 0.9rem;
    }
    
    .btn-outline-light:hover {
        background: var(--accent);
        color: var(--dark);
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(167, 217, 184, 0.3);
    }
    
    .btn-warning {
        background: linear-gradient(135deg, var(--accent) 0%, #8BC34A 100%) !important;
        border: none;
        color: var(--dark) !important;
        font-weight: 700;
        padding: 8px 20px;
        border-radius: 8px;
        transition: all 0.3s ease;
        font-size: 0.9rem;
    }
    
    .btn-warning:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 15px rgba(139, 195, 74, 0.4);
        background: linear-gradient(135deg, #8BC34A 0%, var(--accent) 100%) !important;
    }
    
    /* Badge do Carrinho */
    .badge.bg-danger {
        background: linear-gradient(135deg, var(--danger) 0%, var(--danger-dark) 100%) !important;
        font-size: 0.65rem;
        padding: 3px 6px;
        border-radius: 10px;
        box-shadow: 0 2px 6px rgba(191, 27, 27, 0.3);
        animation: pulseBadge 2s infinite;
        min-width: 18px;
        height: 18px;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    
    @keyframes pulseBadge {
        0%, 100% { transform: scale(1); }
        50% { transform: scale(1.1); }
    }
    
    /* Dropdown Menu */
    .dropdown-menu {
        background: white;
        border: none;
        border-radius: 12px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.15);
        padding: 8px;
        margin-top: 8px !important;
        min-width: 200px;
    }
    
    .dropdown-item {
        color: var(--dark);
        font-weight: 500;
        padding: 8px 14px;
        border-radius: 6px;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 0.9rem;
    }
    
    .dropdown-item:hover {
        background: var(--light);
        color: var(--primary);
        transform: translateX(5px);
    }
    
    .dropdown-divider {
        border-color: var(--accent);
        opacity: 0.3;
        margin: 6px 0;
    }
    
    /* Ícones - Tamanhos consistentes */
    .bi {
        font-size: 1em;
        width: 16px;
        text-align: center;
    }
    
    /* Navbar Toggler */
    .navbar-toggler {
        border: 2px solid var(--accent);
        padding: 6px 10px;
    }
    
    .navbar-toggler:focus {
        box-shadow: 0 0 0 0.2rem rgba(167, 217, 184, 0.5);
    }
    
    .navbar-toggler-icon {
        background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 30 30'%3e%3cpath stroke='rgba%28255, 255, 255, 0.8%29' stroke-linecap='round' stroke-miterlimit='10' stroke-width='2' d='M4 7h22M4 15h22M4 23h22'/%3e%3c/svg%3e");
    }
    
    /* Indicador de Tipo de Usuário */
    .user-badge {
        font-size: 0.7rem;
        padding: 2px 6px;
        border-radius: 10px;
        font-weight: 600;
        margin-left: 6px;
    }
    
    .user-badge.cliente {
        background: var(--accent);
        color: var(--dark);
    }
    
    .user-badge.vendedor {
        background: var(--silver);
        color: #333;
    }
    
    .user-badge.gerente {
        background: var(--gold);
        color: #333;
        font-size: 0.65rem;
        padding: 1px 5px;
    }
    
    /* Menu compacto para gerente */
    .gerente-menu-compact .nav-link {
        padding: 5px 10px !important;
        font-size: 0.82rem;
    }
    
    .gerente-menu-compact .bi {
        font-size: 0.9em;
    }
    
    /* Responsividade */
    @media (max-width: 991.98px) {
        body {
            padding-top: 160px;
        }
        
        .navbar-nav {
            gap: 4px;
            margin-top: 12px;
        }
        
        .nav-link {
            padding: 10px 14px !important;
            margin: 2px 0;
            font-size: 0.9rem;
        }
        
        .btn-outline-light,
        .btn-warning {
            width: 100%;
            margin: 4px 0;
            text-align: center;
        }
        
        .dropdown-menu {
            border-radius: 8px;
            margin: 4px 0 !important;
        }
        
        /* Menu do gerente em mobile */
        .nav-item.gerente .nav-link {
            padding: 8px 12px !important;
            font-size: 0.88rem;
        }
    }
    
    @media (max-width: 575.98px) {
        .navbar-brand {
            font-size: 1.1rem;
        }
        
        .logo {
            height: 35px;
        }
        
        .promo-banner {
            font-size: 0.85rem;
            padding: 10px 0;
        }
        
        .nav-link {
            font-size: 0.85rem;
            padding: 8px 12px !important;
        }
        
        .user-badge {
            font-size: 0.65rem;
            padding: 1px 4px;
        }
    }
    
    /* Alinhamento vertical para todos os itens */
    .navbar-nav .nav-item {
        display: flex;
        align-items: center;
    }
    </style>
    <!-- Script do Bootstrap no HEAD para garantir que funcione -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</head>
<body>
    <!-- Promo Banner Fixo -->
    <div class="promo-banner">
        🎉 Frete Grátis em compras acima de R$ 200,00 | Use o cupom TINTAS10 para 10% de desconto 🎉
    </div>
<?php
require "conexao.php";

$totalItems = 0;

if (isset($_SESSION['user_id'])) {
    $stmt = $conn->prepare("SELECT SUM(quantity) FROM carts WHERE user_id = ?");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $stmt->bind_result($totalItemsBD);
    $stmt->fetch();
    $stmt->close();

    $totalItems = $totalItemsBD ? $totalItemsBD : 0;
}
?>

    <!-- Navbar Principal -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <img src="logo.jpg" alt="Rosa Cores e Tintas" class="logo">
                Rosa Cores e Tintas
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" 
                    data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" 
                    aria-label="Alternar navegação">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto mb-2 mb-lg-0 align-items-lg-center">

                <?php if (isset($_SESSION['user_id'])): ?>
                    
                    <?php if ($_SESSION['user_type'] === 'cliente'): ?>
                        <!-- Menu Cliente (mantido perfeito) -->
                        <li class="nav-item cliente">
                            <a class="nav-link" href="loja.php">
                                <i class="bi bi-shop"></i>Comprar
                            </a>
                        </li>
                       <li class="nav-item cliente">
    <a class="nav-link" href="view_cart.php">
        <i class="bi bi-cart3"></i> Carrinho
        <span class="badge bg-danger rounded-pill">
            <?php echo $totalItems; ?>
        </span>
    </a>
</li>
<li class="nav-item dropdown cliente">
    <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
        <i class="bi bi-person-circle"></i>
        <?php echo htmlspecialchars($_SESSION['user_name']); ?>
        <span class="user-badge cliente">Cliente</span>
    </a>
    <ul class="dropdown-menu dropdown-menu-end">
        <li><a class="dropdown-item" href="view_cart.php"><i class="bi bi-cart3"></i>Meu Carrinho</a></li>
        <li><a class="dropdown-item" href="perfil.php"><i class="bi bi-person"></i>Meu Perfil</a></li>
        <li><a class="dropdown-item" href="fale_conosco.php"><i class="bi bi-envelope"></i>Fale Conosco</a></li>
        <li><hr class="dropdown-divider"></li>
        <li><a class="dropdown-item" href="logout.php"><i class="bi bi-box-arrow-right"></i>Sair</a></li>
    </ul>
</li>

                    <?php elseif ($_SESSION['user_type'] === 'funcionario'): ?>
                        <!-- Menu Vendedor (mantido perfeito) -->
                        <li class="nav-item vendedor">
                            <a class="nav-link" href="realizar_venda.php">
                                <i class="bi bi-cash-coin"></i>Realizar Venda
                            </a>
                        </li>
                        <li class="nav-item vendedor">
                            <a class="nav-link" href="relatorio_vendedor.php">
                                <i class="bi bi-list-check"></i>Minhas Vendas
                            </a>
                        </li>
                        <li class="nav-item dropdown vendedor">
                            <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                                <i class="bi bi-person-gear"></i>
                                <?php echo htmlspecialchars($_SESSION['user_name']); ?>
                                <span class="user-badge vendedor">Vendedor</span>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><a class="dropdown-item" href="perfil.php"><i class="bi bi-person"></i>Meu Perfil</a></li>
                                <li><a class="dropdown-item" href="vendas_lista.php"><i class="bi bi-graph-up"></i>Relatórios</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="logout.php"><i class="bi bi-box-arrow-right"></i>Sair</a></li>
                            </ul>
                        </li>

                    <?php elseif ($_SESSION['user_type'] === 'gerente'): ?>
                        <!-- Menu Gerente - COMPACTO E HARMONIOSO -->
                            <li class="nav-item gerente">
                                <a class="nav-link" href="realizar_venda.php" title="Realizar Venda">
                                    <i class="bi bi-cart-plus"></i>Vender
                                </a>
                            </li>
                            <li class="nav-item gerente">
                                <a class="nav-link" href="products.php" title="Painel Administrativo">
                                    <i class="bi bi-gear"></i>Admin
                                </a>
                            </li>
                            <!-- ADICIONAR ESTES DOIS LINKS NOVOS -->
                            <li class="nav-item gerente">
                                <a class="nav-link" href="contas_pagar.php" title="Contas a Pagar">
                                    <i class="bi bi-credit-card"></i>Contas Pagar
                                </a>
                            </li>
                            <li class="nav-item gerente">
                                <a class="nav-link" href="contas_receber.php" title="Contas a Receber">
                                    <i class="bi bi-cash-coin"></i>Contas Receber
                                </a>
                            </li>
                            <!-- FIM DOS LINKS NOVOS -->
                            <li class="nav-item gerente">
                                <a class="nav-link" href="relatorios_vendas.php" title="Relatórios de Vendas">
                                    <i class="bi bi-graph-up"></i>Relatórios
                                </a>
                            </li>
                        <li class="nav-item dropdown gerente">
                            <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                                <i class="bi bi-person-fill-gear"></i>
                                <?php echo htmlspecialchars($_SESSION['user_name']); ?>
                                <span class="user-badge gerente">Gerente</span>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><a class="dropdown-item" href="perfil.php"><i class="bi bi-person"></i>Meu Perfil</a></li>
                                <li><a class="dropdown-item" href="products.php"><i class="bi bi-gear"></i>Painel Admin</a></li>
                                <li><a class="dropdown-item" href="cadastro_usuario.php"><i class="bi bi-person-plus"></i>Cad. Usuário</a></li>
                                <li><a class="dropdown-item" href="relatorios_vendas.php"><i class="bi bi-graph-up-arrow"></i>Relatórios</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="logout.php"><i class="bi bi-box-arrow-right"></i>Sair</a></li>
                            </ul>
                        </li>
                    <?php endif; ?>

                <?php else: ?>
                    <!-- Menu Visitante -->
                    <li class="nav-item">
                        <a class="btn btn-outline-light me-2" href="login.php">
                            <i class="bi bi-box-arrow-in-right"></i>Login
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="btn btn-warning" href="register.php">
                            <i class="bi bi-person-plus"></i>Cadastro
                        </a>
                    </li>
                <?php endif; ?>

                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
