<?php
require "init.php";
require "conexao.php";

// Verifica login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$userId = $_SESSION['user_id'];

function getUserData($conn, $userId)
{
    $stmt = $conn->prepare("SELECT name, email FROM users WHERE id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows > 0) {
        return $result->fetch_assoc();
    } else {
        // Se nada foi encontrado, força logout (evita bugs)
        session_destroy();
        header("Location: login.php");
        exit;
    }
}

// Carrega os dados do usuário
$user = getUserData($conn, $userId);
// Buscar dados do usuário
$user = ['name' => '', 'email' => ''];

$stmt = $conn->prepare("SELECT name, email FROM users WHERE id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    $user = [
        'name'  => $row['name'],
        'email' => $row['email']
    ];
} else {
    session_destroy();
    header("Location: login.php");
    exit;
}
$stmt->close();

// Atualizar dados do perfil
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $nome = trim($_POST['name']);
    $email = trim($_POST['email']);
    $senha = !empty($_POST['password']) ? password_hash($_POST['password'], PASSWORD_BCRYPT) : null;

    if ($senha) {
        $up = $conn->prepare("UPDATE users SET name=?, email=?, password=? WHERE id=?");
        $up->bind_param("sssi", $nome, $email, $senha, $userId);
    } else {
        $up = $conn->prepare("UPDATE users SET name=?, email=? WHERE id=?");
        $up->bind_param("ssi", $nome, $email, $userId);
    }
    
    if ($up->execute()) {
        $_SESSION['user_name'] = $nome;
        $_SESSION['success_message'] = "Perfil atualizado com sucesso!";
    } else {
        $_SESSION['error_message'] = "Erro ao atualizar perfil!";
    }
    $up->close();
    
    header("Location: perfil.php");
    exit;
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {

    $nome = trim($_POST['name']);
    $email = trim($_POST['email']);
    $senha = !empty($_POST['password']) ? password_hash($_POST['password'], PASSWORD_BCRYPT) : null;

    if ($senha) {
        $up = $conn->prepare("UPDATE users SET name=?, email=?, password=? WHERE id=?");
        $up->bind_param("sssi", $nome, $email, $senha, $userId);
    } else {
        $up = $conn->prepare("UPDATE users SET name=?, email=? WHERE id=?");
        $up->bind_param("ssi", $nome, $email, $userId);
    }

    if ($up->execute()) {
        $_SESSION['user_name'] = $nome;
        $_SESSION['success_message'] = "Perfil atualizado com sucesso!";
    } else {
        $_SESSION['error_message'] = "Erro ao atualizar perfil!";
    }

    header("Location: perfil.php");
    exit;
}
// Adicionar novo endereço
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_address'])) {
    $endereco = trim($_POST['novo_endereco']);
    if (!empty($endereco)) {
        $ins = $conn->prepare("INSERT INTO enderecos (cliente_id, endereco) VALUES (?, ?)");
        $ins->bind_param("is", $userId, $endereco);
        if ($ins->execute()) {
            $_SESSION['success_message'] = "Endereço adicionado com sucesso!";
        } else {
            $_SESSION['error_message'] = "Erro ao adicionar endereço!";
        }
        $ins->close();
    } else {
        $_SESSION['error_message'] = "Por favor, preencha o endereço!";
    }
    header("Location: perfil.php");
    exit;
}

// Editar endereço
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_address'])) {
    $enderecoId = intval($_POST['endereco_id']);
    $novo = trim($_POST['endereco']);
    
    if (!empty($novo)) {
        $up = $conn->prepare("UPDATE enderecos SET endereco=? WHERE id=? AND cliente_id=?");
        $up->bind_param("sii", $novo, $enderecoId, $userId);
        if ($up->execute()) {
            $_SESSION['success_message'] = "Endereço atualizado com sucesso!";
        } else {
            $_SESSION['error_message'] = "Erro ao atualizar endereço!";
        }
        $up->close();
    } else {
        $_SESSION['error_message'] = "Por favor, preencha o endereço!";
    }
    header("Location: perfil.php");
    exit;
}

// Excluir endereço
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_address'])) {
    $enderecoId = intval($_POST['endereco_id']);
    $del = $conn->prepare("DELETE FROM enderecos WHERE id=? AND cliente_id=?");
    $del->bind_param("ii", $enderecoId, $userId);
    if ($del->execute()) {
        $_SESSION['success_message'] = "Endereço excluído com sucesso!";
    } else {
        $_SESSION['error_message'] = "Erro ao excluir endereço!";
    }
    $del->close();
    header("Location: perfil.php");
    exit;
}

// Buscar endereços do cliente
$enderecos = [];
$res = $conn->prepare("SELECT id, endereco FROM enderecos WHERE cliente_id=?");
$res->bind_param("i", $userId);
$res->execute();
$resResult = $res->get_result();

while ($row = $resResult->fetch_assoc()) {
    $enderecos[] = [
        'id' => $row['id'],
        'endereco' => $row['endereco']
    ];
}
$res->close();
// FILTROS PARA HISTÓRICO DE COMPRAS
$filtro_data = isset($_GET['filtro_data']) ? $_GET['filtro_data'] : 'todos';
$filtro_pagamento = isset($_GET['filtro_pagamento']) ? $_GET['filtro_pagamento'] : 'todos';

// Construir a query base
$query = "
    SELECT s.id, s.sale_date, s.total, s.metodo_pagamento, p.name as product_name, s.quantity 
    FROM sales s 
    LEFT JOIN products p ON s.product_id = p.id 
    WHERE s.cliente_id = ? 
";

$params = [$userId];
$types = "i";

// Aplicar filtro de data
if ($filtro_data === 'hoje') {
    $query .= " AND DATE(s.sale_date) = CURDATE()";
} elseif ($filtro_data === 'ontem') {
    $query .= " AND DATE(s.sale_date) = DATE_SUB(CURDATE(), INTERVAL 1 DAY)";
} elseif ($filtro_data === '7_dias') {
    $query .= " AND s.sale_date >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
} elseif ($filtro_data === '30_dias') {
    $query .= " AND s.sale_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
} elseif ($filtro_data === 'mes_atual') {
    $query .= " AND YEAR(s.sale_date) = YEAR(CURDATE()) AND MONTH(s.sale_date) = MONTH(CURDATE())";
}

// Aplicar filtro de método de pagamento
if ($filtro_pagamento !== 'todos') {
    $query .= " AND s.metodo_pagamento = ?";
    $params[] = $filtro_pagamento;
    $types .= "s";
}

// Ordenação e limite
$query .= " ORDER BY s.sale_date DESC LIMIT 50";

// Buscar histórico de compras com filtros
$compras = [];
$compraRes = $conn->prepare($query);

if ($filtro_pagamento !== 'todos') {
    // Prepara array com o tipo + valores
    $bind = array_merge([$types], $params);

    // Cria array de referências (necessário para bind_param)
    $refs = [];
    foreach ($bind as $key => $value) {
        $refs[$key] = &$bind[$key];
    }

    // Chama bind_param com os parâmetros por referência
    call_user_func_array([$compraRes, 'bind_param'], $refs);
} else {
    $compraRes->bind_param($types, $userId);
}


$compraRes->execute();
$compraResult = $compraRes->get_result();
while ($row = $compraResult->fetch_assoc()) $compras[] = $row;
$compraRes->close();

// Buscar métodos de pagamento únicos para o filtro
$metodos_pagamento = [];
$metodosRes = $conn->prepare("
    SELECT DISTINCT metodo_pagamento 
    FROM sales 
    WHERE cliente_id = ? AND metodo_pagamento IS NOT NULL AND metodo_pagamento != ''
    ORDER BY metodo_pagamento
");
$metodosRes->bind_param("i", $userId);
$metodosRes->execute();
$metodosResult = $metodosRes->get_result();
while ($row = $metodosResult->fetch_assoc()) {
    $metodos_pagamento[] = $row['metodo_pagamento'];
}
$metodosRes->close();

include "header.php";
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

.profile-container {
    max-width: 1000px;
    margin: 0 auto;
}

.profile-header {
    background: linear-gradient(135deg, var(--primary) 0%, var(--accent-dark) 100%);
    color: white;
    padding: 2.5rem 0;
    margin-bottom: 2rem;
    border-radius: 0 0 20px 20px;
    position: relative;
    overflow: hidden;
}

.profile-header::before {
    content: '';
    position: absolute;
    top: -50px;
    right: -50px;
    width: 150px;
    height: 150px;
    background: rgba(255, 255, 255, 0.1);
    border-radius: 50%;
}

.profile-card {
    background: white;
    border-radius: 15px;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
    overflow: hidden;
    margin-bottom: 2rem;
}

.profile-card-header {
    background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%);
    color: white;
    padding: 1.5rem;
    font-weight: 700;
    font-size: 1.2rem;
    display: flex;
    align-items: center;
}

.profile-card-header i {
    margin-right: 10px;
    font-size: 1.5rem;
}

.profile-card-body {
    padding: 2rem;
}

.form-group {
    margin-bottom: 1.5rem;
}

.form-label {
    color: var(--dark);
    font-weight: 600;
    margin-bottom: 0.5rem;
    display: block;
}

.form-control {
    width: 100%;
    padding: 12px 15px;
    border: 2px solid var(--accent);
    border-radius: 10px;
    font-size: 1rem;
    transition: all 0.3s;
    background: white;
}

.form-control:focus {
    border-color: var(--primary);
    outline: none;
    box-shadow: 0 0 0 0.2rem rgba(20, 89, 44, 0.25);
}

.btn-primary {
    background: var(--primary);
    border: none;
    padding: 12px 25px;
    border-radius: 10px;
    font-weight: 600;
    transition: all 0.3s;
    color: white;
}

.btn-primary:hover {
    background: var(--accent-dark);
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(20, 89, 44, 0.3);
}

.btn-outline {
    background: white;
    border: 2px solid var(--primary);
    color: var(--primary);
    padding: 10px 20px;
    border-radius: 8px;
    font-weight: 600;
    transition: all 0.3s;
}

.btn-outline:hover {
    background: var(--primary);
    color: white;
    transform: translateY(-2px);
}

.btn-danger {
    background: var(--danger);
    border: none;
    padding: 8px 15px;
    border-radius: 6px;
    color: white;
    font-weight: 600;
    transition: all 0.3s;
}

.btn-danger:hover {
    background: var(--danger-dark);
    transform: translateY(-2px);
}

.btn-filter {
    background: var(--light);
    border: 2px solid var(--accent);
    color: var(--dark);
    padding: 8px 16px;
    border-radius: 8px;
    font-weight: 600;
    transition: all 0.3s;
    margin: 0 5px 5px 0;
}

.btn-filter.active {
    background: var(--primary);
    border-color: var(--primary);
    color: white;
}

.btn-filter:hover {
    background: var(--primary);
    border-color: var(--primary);
    color: white;
    transform: translateY(-2px);
}

.address-card {
    background: var(--light);
    border-radius: 12px;
    padding: 1.5rem;
    margin-bottom: 1rem;
    border-left: 4px solid var(--primary);
    transition: all 0.3s;
}

.address-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
}

.address-actions {
    display: flex;
    gap: 10px;
    margin-top: 1rem;
}

.purchase-history {
    margin-top: 2rem;
}

.purchase-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1rem;
    border-bottom: 1px solid var(--accent);
    transition: all 0.3s;
}

.purchase-item:hover {
    background: var(--light);
}

.purchase-item:last-child {
    border-bottom: none;
}

.purchase-date {
    color: #6c757d;
    font-size: 0.9rem;
}

.purchase-product {
    font-weight: 600;
    color: var(--dark);
}

.purchase-total {
    color: var(--primary);
    font-weight: 700;
}

.purchase-method {
    background: var(--accent);
    color: var(--dark);
    padding: 4px 8px;
    border-radius: 15px;
    font-size: 0.8rem;
    font-weight: 600;
}

.empty-state {
    text-align: center;
    padding: 3rem 1rem;
    color: #6c757d;
}

.empty-state i {
    font-size: 3rem;
    color: var(--accent);
    margin-bottom: 1rem;
}

.alert-success {
    background: rgba(20, 89, 44, 0.1);
    border: 2px solid var(--primary);
    color: var(--primary);
    padding: 1rem;
    border-radius: 10px;
    margin-bottom: 2rem;
    font-weight: 600;
}

.alert-error {
    background: rgba(191, 27, 27, 0.1);
    border: 2px solid var(--danger);
    color: var(--danger);
    padding: 1rem;
    border-radius: 10px;
    margin-bottom: 2rem;
    font-weight: 600;
}

.tab-container {
    margin-bottom: 2rem;
}

.tab-buttons {
    display: flex;
    border-bottom: 2px solid var(--accent);
    margin-bottom: 2rem;
}

.tab-button {
    padding: 1rem 2rem;
    background: none;
    border: none;
    font-weight: 600;
    color: var(--dark);
    cursor: pointer;
    transition: all 0.3s;
    position: relative;
}

.tab-button.active {
    color: var(--primary);
}

.tab-button.active::after {
    content: '';
    position: absolute;
    bottom: -2px;
    left: 0;
    width: 100%;
    height: 3px;
    background: var(--primary);
}

.tab-button:hover {
    color: var(--primary);
}

.tab-content {
    display: none;
}

.tab-content.active {
    display: block;
    animation: fadeIn 0.5s ease-out;
}

.filters-container {
    background: var(--light);
    padding: 1.5rem;
    border-radius: 12px;
    margin-bottom: 2rem;
}

.filter-group {
    margin-bottom: 1rem;
}

.filter-group:last-child {
    margin-bottom: 0;
}

.filter-label {
    font-weight: 600;
    color: var(--dark);
    margin-bottom: 0.5rem;
    display: block;
}

.filter-buttons {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
}

.filter-results {
    background: var(--primary);
    color: white;
    padding: 8px 16px;
    border-radius: 20px;
    font-size: 0.9rem;
    font-weight: 600;
    display: inline-block;
    margin-bottom: 1rem;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(20px); }
    to { opacity: 1; transform: translateY(0); }
}

@media (max-width: 768px) {
    .profile-card-body {
        padding: 1.5rem;
    }
    
    .tab-buttons {
        flex-direction: column;
    }
    
    .tab-button {
        padding: 0.8rem 1rem;
        text-align: left;
    }
    
    .address-actions {
        flex-direction: column;
    }
    
    .purchase-item {
        flex-direction: column;
        align-items: flex-start;
        gap: 10px;
    }
    
    .filter-buttons {
        flex-direction: column;
    }
    
    .btn-filter {
        margin: 0 0 5px 0;
        text-align: center;
    }
}

.spinner {
    animation: spin 1s linear infinite;
}

@keyframes spin {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}
</style>

<div class="profile-container">
    <div class="profile-header">
        <div class="container">
            <h1 class="display-5 fw-bold"><i class="bi bi-person-circle me-3"></i>Meu Perfil</h1>
            <p class="lead">Gerencie suas informações pessoais e endereços</p>
        </div>
    </div>

    <div class="container">
        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="alert-success">
                <i class="bi bi-check-circle-fill me-2"></i>
                <?php echo $_SESSION['success_message']; unset($_SESSION['success_message']); ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['error_message'])): ?>
            <div class="alert-error">
                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                <?php echo $_SESSION['error_message']; unset($_SESSION['error_message']); ?>
            </div>
        <?php endif; ?>

        <div class="tab-container">
            <div class="tab-buttons">
                <button class="tab-button active" onclick="openTab('dados')">
                    <i class="bi bi-person me-2"></i>Dados Pessoais
                </button>
                <button class="tab-button" onclick="openTab('enderecos')">
                    <i class="bi bi-geo-alt me-2"></i>Endereços
                </button>
                <button class="tab-button" onclick="openTab('compras')">
                    <i class="bi bi-bag me-2"></i>Histórico de Compras
                </button>
            </div>

            <!-- Aba de Dados Pessoais -->
            <div id="dados" class="tab-content active">
                <div class="profile-card">
                    <div class="profile-card-header">
                        <i class="bi bi-person-badge"></i>Informações Pessoais
                    </div>
                    <div class="profile-card-body">
                        <form method="POST">
                            <div class="form-group">
                                <label class="form-label">Nome Completo</label>
                                <input type="text" name="name" class="form-control" 
                                       value="<?php echo htmlspecialchars($user['nome']); ?>" required>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Email</label>
                                <input type="email" name="email" class="form-control" 
                                       value="<?php echo htmlspecialchars($user['email']); ?>" required>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Nova Senha (deixe em branco para não alterar)</label>
                                <input type="password" name="password" class="form-control" 
                                       placeholder="Digite uma nova senha">
                                <small class="text-muted">Mínimo de 6 caracteres</small>
                            </div>
                            
                            <button type="submit" name="update_profile" class="btn-primary">
                                <i class="bi bi-check-circle me-2"></i>Salvar Alterações
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Aba de Endereços -->
            <div id="enderecos" class="tab-content">
                <div class="profile-card">
                    <div class="profile-card-header">
                        <i class="bi bi-house-add"></i>Adicionar Novo Endereço
                    </div>
                    <div class="profile-card-body">
                        <form method="POST">
                            <div class="form-group">
                                <label class="form-label">Endereço Completo</label>
                                <textarea name="novo_endereco" class="form-control" 
                                          placeholder="Ex: Rua das Flores, 123 - Apt 101, Centro, São Paulo - SP, 01234-567"
                                          rows="3" required></textarea>
                            </div>
                            <button type="submit" name="add_address" class="btn-primary">
                                <i class="bi bi-plus-circle me-2"></i>Adicionar Endereço
                            </button>
                        </form>
                    </div>
                </div>

                <div class="profile-card">
                    <div class="profile-card-header">
                        <i class="bi bi-bookmark-check"></i>Meus Endereços
                    </div>
                    <div class="profile-card-body">
                        <?php if (!empty($enderecos)): ?>
                            <?php foreach ($enderecos as $index => $e): ?>
                                <div class="address-card">
                                    <div class="form-group">
                                        <form method="POST" class="d-flex align-items-start gap-2 flex-column">
                                            <input type="hidden" name="endereco_id" value="<?php echo $e['id']; ?>">
                                            <input type="text" name="endereco" class="form-control mb-2" 
                                                   value="<?php echo htmlspecialchars($e['endereco']); ?>" required>
                                            <div class="address-actions">
                                                <button type="submit" name="edit_address" class="btn-outline">
                                                    <i class="bi bi-pencil"></i> Editar
                                                </button>
                                                <button type="submit" name="delete_address" class="btn-danger" 
                                                        onclick="return confirm('Tem certeza que deseja excluir este endereço?')">
                                                    <i class="bi bi-trash"></i> Excluir
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="empty-state">
                                <i class="bi bi-geo-alt"></i>
                                <h5>Nenhum endereço cadastrado</h5>
                                <p>Adicione seu primeiro endereço para facilitar suas compras!</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Aba de Histórico de Compras -->
            <div id="compras" class="tab-content">
                <div class="profile-card">
                    <div class="profile-card-header">
                        <i class="bi bi-receipt"></i>Histórico de Compras
                    </div>
                    <div class="profile-card-body">
                        <!-- Filtros -->
                        <div class="filters-container">
                            <form method="GET" id="filtersForm">
                                <input type="hidden" name="tab" value="compras">
                                
                                <div class="filter-group">
                                    <label class="filter-label">Filtrar por Data:</label>
                                    <div class="filter-buttons">
                                        <button type="button" class="btn-filter <?php echo $filtro_data === 'todos' ? 'active' : ''; ?>" 
                                                onclick="setFilter('data', 'todos')">
                                            Todos
                                        </button>
                                        <button type="button" class="btn-filter <?php echo $filtro_data === 'hoje' ? 'active' : ''; ?>" 
                                                onclick="setFilter('data', 'hoje')">
                                            Hoje
                                        </button>
                                        <button type="button" class="btn-filter <?php echo $filtro_data === 'ontem' ? 'active' : ''; ?>" 
                                                onclick="setFilter('data', 'ontem')">
                                            Ontem
                                        </button>
                                        <button type="button" class="btn-filter <?php echo $filtro_data === '7_dias' ? 'active' : ''; ?>" 
                                                onclick="setFilter('data', '7_dias')">
                                            7 Dias
                                        </button>
                                        <button type="button" class="btn-filter <?php echo $filtro_data === '30_dias' ? 'active' : ''; ?>" 
                                                onclick="setFilter('data', '30_dias')">
                                            30 Dias
                                        </button>
                                        <button type="button" class="btn-filter <?php echo $filtro_data === 'mes_atual' ? 'active' : ''; ?>" 
                                                onclick="setFilter('data', 'mes_atual')">
                                            Mês Atual
                                        </button>
                                    </div>
                                </div>
                                
                                <div class="filter-group">
                                    <label class="filter-label">Filtrar por Pagamento:</label>
                                    <div class="filter-buttons">
                                        <button type="button" class="btn-filter <?php echo $filtro_pagamento === 'todos' ? 'active' : ''; ?>" 
                                                onclick="setFilter('pagamento', 'todos')">
                                            Todos
                                        </button>
                                        <?php foreach ($metodos_pagamento as $metodo): ?>
                                            <button type="button" class="btn-filter <?php echo $filtro_pagamento === $metodo ? 'active' : ''; ?>" 
                                                    onclick="setFilter('pagamento', '<?php echo htmlspecialchars($metodo); ?>')">
                                                <?php echo htmlspecialchars($metodo); ?>
                                            </button>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                                
                                <input type="hidden" name="filtro_data" id="filtro_data" value="<?php echo htmlspecialchars($filtro_data); ?>">
                                <input type="hidden" name="filtro_pagamento" id="filtro_pagamento" value="<?php echo htmlspecialchars($filtro_pagamento); ?>">
                                
                                <div class="d-flex gap-2 mt-3">
                                    <button type="submit" class="btn-primary">
                                        <i class="bi bi-funnel me-2"></i>Aplicar Filtros
                                    </button>
                                    <button type="button" class="btn-outline" onclick="resetFilters()">
                                        <i class="bi bi-arrow-clockwise me-2"></i>Limpar Filtros
                                    </button>
                                </div>
                            </form>
                        </div>

                        <!-- Resultados -->
                        <div class="filter-results">
                            <i class="bi bi-graph-up me-2"></i>
                            <?php echo count($compras); ?> compra(s) encontrada(s)
                        </div>

                        <!-- Lista de Compras -->
                        <?php if (!empty($compras)): ?>
                            <?php foreach ($compras as $compra): ?>
                                <div class="purchase-item">
                                    <div>
                                        <div class="purchase-date">
                                            <?php echo date('d/m/Y H:i', strtotime($compra['sale_date'])); ?>
                                        </div>
                                        <div class="purchase-product">
                                            <?php echo htmlspecialchars($compra['product_name'] ); ?> 
                                            (<?php echo $compra['quantity']; ?> un.)
                                        </div>
                                    </div>
                                    <div class="d-flex align-items-center gap-3">
                                        <span class="purchase-total">
                                            R$ <?php echo number_format($compra['total'], 2, ',', '.'); ?>
                                        </span>
                                        <span class="purchase-method">
                                            <?php echo htmlspecialchars($compra['metodo_pagamento']); ?>
                                        </span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="empty-state">
                                <i class="bi bi-search"></i>
                                <h5>Nenhuma compra encontrada</h5>
                                <p>Tente ajustar os filtros ou <a href="loja.php">faça sua primeira compra</a></p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
   
function setFilter(type, value) {
    const form = document.getElementById('filtersForm');

    if (type === 'data') {
        form.filtro_data.value = value;
    }
    if (type === 'pagamento') {
        form.filtro_pagamento.value = value;
    }

    form.submit();
}

function openTab(tabName) {
    // Esconder todas as abas
    document.querySelectorAll('.tab-content').forEach(tab => {
        tab.classList.remove('active');
    });
    
    // Mostrar a aba selecionada
    document.getElementById(tabName).classList.add('active');
    
    // Atualizar botões ativos
    document.querySelectorAll('.tab-button').forEach(btn => {
        btn.classList.remove('active');
    });
    
    // Ativar o botão clicado
    event.currentTarget.classList.add('active');
}

// Funções para os filtros
function setFilter(tipo, valor) {
    if (tipo === 'data') {
        document.getElementById('filtro_data').value = valor;
        // Remover active de todos os botões de data
        document.querySelectorAll('[onclick^="setFilter(\'data\'"]').forEach(btn => {
            btn.classList.remove('active');
        });
        // Adicionar active ao botão clicado
        event.target.classList.add('active');
    } else if (tipo === 'pagamento') {
        document.getElementById('filtro_pagamento').value = valor;
        // Remover active de todos os botões de pagamento
        document.querySelectorAll('[onclick^="setFilter(\'pagamento\'"]').forEach(btn => {
            btn.classList.remove('active');
        });
        // Adicionar active ao botão clicado
        event.target.classList.add('active');
    }
}

function resetFilters() {
    document.getElementById('filtro_data').value = 'todos';
    document.getElementById('filtro_pagamento').value = 'todos';
    
    // Resetar classes active
    document.querySelectorAll('.btn-filter').forEach(btn => {
        btn.classList.remove('active');
    });
    
    // Ativar botões "Todos"
    document.querySelectorAll('[onclick="setFilter(\'data\', \'todos\')"]').forEach(btn => {
        btn.classList.add('active');
    });
    document.querySelectorAll('[onclick="setFilter(\'pagamento\', \'todos\')"]').forEach(btn => {
        btn.classList.add('active');
    });
    
    // Submeter formulário
    document.getElementById('filtersForm').submit();
}

// Validação de formulário
document.addEventListener('DOMContentLoaded', function() {
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            const submitBtn = this.querySelector('button[type="submit"]');
            if (submitBtn) {
                submitBtn.innerHTML = '<i class="bi bi-arrow-repeat spinner"></i> Processando...';
                submitBtn.disabled = true;
            }
        });
    });
    
    // Abrir aba de compras se veio com parâmetros de filtro
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.has('filtro_data') || urlParams.has('filtro_pagamento')) {
        openTab('compras');
    }
});
</script>

<?php 
$conn->close();
include "footer.php"; 
?>