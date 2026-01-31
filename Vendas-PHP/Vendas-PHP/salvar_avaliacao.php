<?php
session_start();
require "conexao.php";

// Redirecionamento de fallback
function redirect_with_error($msg, $product_id = null) {
    $_SESSION['error_message'] = $msg;
    $url = "loja.php";
    if ($product_id) $url .= "?id=" . intval($product_id);
    header("Location: $url");
    exit;
}

// 1) Verifica login
if (!isset($_SESSION['user_id'])) {
    redirect_with_error("Você precisa estar logado para avaliar um produto.");
}

// 2) Validar método e dados
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect_with_error("Requisição inválida.");
}

$user_id = intval($_SESSION['user_id']);
$product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
$rating = isset($_POST['rating']) ? intval($_POST['rating']) : 0;    // formulário usa "rating"
$comment = isset($_POST['comment']) ? trim($_POST['comment']) : ''; // formulário usa "comment"

if ($product_id <= 0 || $rating < 1 || $rating > 5 || $comment === '') {
    redirect_with_error("Preencha todos os campos corretamente.", $product_id);
}

// 3) Funções utilitárias para detecção de colunas/tabelas
function table_exists($conn, $table) {
    $t = $conn->real_escape_string($table);
    $res = $conn->query("SHOW TABLES LIKE '$t'");
    return $res && $res->num_rows > 0;
}

function find_first_column($conn, $table, $candidates = []) {
    $tbl = $conn->real_escape_string($table);
    if (!table_exists($conn, $table)) return false;
    $res = $conn->query("SHOW COLUMNS FROM `$tbl`");
    if (!$res) return false;
    $cols = [];
    while ($row = $res->fetch_assoc()) $cols[] = $row['Field'];
    foreach ($candidates as $c) {
        if (in_array($c, $cols)) return $c;
    }
    return false;
}

// 4) Verificar existência e estrutura da tabela 'sales' (para checar compra)
if (!table_exists($conn, 'sales')) {
    // Se não existe a tabela sales, bloqueamos — evita avaliações fraudulentas.
    redirect_with_error("Não foi possível verificar suas compras: tabela de vendas ausente. Contate o administrador.", $product_id);
}

// descobrir nome da coluna que guarda o id do usuário na tabela sales
$user_col_in_sales = find_first_column($conn, 'sales', ['user_id','cliente_id','cliente','customer_id','client_id']);
$product_col_in_sales = find_first_column($conn, 'sales', ['product_id','produto_id','product','id_produto']);

if (!$user_col_in_sales || !$product_col_in_sales) {
    redirect_with_error("Estrutura da tabela de vendas inválida. Contate o administrador.", $product_id);
}

// 5) Checar se o usuário comprou o produto (prepared statement seguro)
// Montamos a query dinamicamente com colunas validadas
$sql_check = "SELECT COUNT(*) FROM `sales` WHERE `$user_col_in_sales` = ? AND `$product_col_in_sales` = ?";
$stmt_check = $conn->prepare($sql_check);
if (!$stmt_check) {
    error_log("Erro prepare checkCompra: " . $conn->error . " -- SQL: $sql_check");
    redirect_with_error("Erro ao verificar compra. Contate o administrador.", $product_id);
}
$stmt_check->bind_param("ii", $user_id, $product_id);
$stmt_check->execute();
$stmt_check->bind_result($comprou);
$stmt_check->fetch();
$stmt_check->close();

if (intval($comprou) === 0) {
    redirect_with_error("Você só pode avaliar produtos que já comprou.", $product_id);
}

// 6) Garantir que exista a tabela 'avaliacoes' — caso não exista, criar uma tabela mínima
if (!table_exists($conn, 'avaliacoes')) {
    $sql_create = "
        CREATE TABLE `avaliacoes` (
            id INT AUTO_INCREMENT PRIMARY KEY,
            product_id INT NOT NULL,
            user_id INT NOT NULL,
            rating TINYINT NOT NULL,
            comentario TEXT NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ";
    if (!$conn->query($sql_create)) {
        error_log("Erro criando tabela avaliacoes: " . $conn->error);
        redirect_with_error("Erro no servidor ao preparar avaliações. Contate o administrador.", $product_id);
    }
}

// 7) Detectar nomes de colunas corretos na tabela avaliacoes (pode ser 'comentario' ou 'comment', por exemplo)
$rating_col_in_av = find_first_column($conn, 'avaliacoes', ['rating','nota']);
$text_col_in_av   = find_first_column($conn, 'avaliacoes', ['comentario','comment','texto']);

if (!$rating_col_in_av || !$text_col_in_av) {
    // tabela existe, mas colunas esperadas não — erro crítico
    error_log("Colunas esperadas em avaliacoes não encontradas.");
    redirect_with_error("Estrutura de avaliações inválida. Contate o administrador.", $product_id);
}

// 8) Impedir várias avaliações do mesmo usuário para o mesmo produto (opcional)
$checkAvaliacaoSQL = "SELECT id FROM `avaliacoes` WHERE `user_id` = ? AND `product_id` = ? LIMIT 1";
$chk = $conn->prepare($checkAvaliacaoSQL);
if (!$chk) {
    error_log("Erro prepare checkAvaliacao: " . $conn->error . " -- SQL: $checkAvaliacaoSQL");
    redirect_with_error("Erro ao verificar avaliação existente. Contate o administrador.", $product_id);
}
$chk->bind_param("ii", $user_id, $product_id);
$chk->execute();
$chk->store_result();
if ($chk->num_rows > 0) {
    $chk->close();
    redirect_with_error("Você já avaliou este produto.", $product_id);
}
$chk->close();

// 9) Inserir avaliação (usando nomes de colunas detectados)
$insertSQL = "INSERT INTO `avaliacoes` (`product_id`, `user_id`, `{$rating_col_in_av}`, `{$text_col_in_av}`, `created_at`) VALUES (?, ?, ?, ?, NOW())";
$stmt = $conn->prepare($insertSQL);
if (!$stmt) {
    error_log("Erro prepare inserir avaliacao: " . $conn->error . " -- SQL: $insertSQL");
    redirect_with_error("Erro ao salvar avaliação. Contate o administrador.", $product_id);
}
$stmt->bind_param("iiis", $product_id, $user_id, $rating, $comment);

if ($stmt->execute()) {
    $_SESSION['success_message'] = "Obrigado pela sua avaliação!";
} else {
    error_log("Erro execute inserir avaliacao: " . $stmt->error);
    $_SESSION['error_message'] = "Erro ao salvar avaliação: " . $stmt->error;
}
$stmt->close();
$conn->close();

// Redireciona de volta para a página do produto (ou loja)
header("Location: loja.php?id=" . intval($product_id));
exit;
?>
