<?php
// init.php - Inicializa a sessão se ainda não foi iniciada
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Configurações de fuso horário
date_default_timezone_set('America/Sao_Paulo');

// Exibe erros (apenas em desenvolvimento)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Carrega o carrinho do banco se o usuário estiver logado
if (isset($_SESSION['user_id']) && (!isset($_SESSION['cart']) || empty($_SESSION['cart']))) {
    require_once "conexao.php";
    require_once "cart_functions.php";
    
    // Verifica se a conexão foi bem-sucedida
    if ($conn && $conn->connect_error === null) {
        $userId = $_SESSION['user_id'];
        $_SESSION['cart'] = loadCartFromDatabase($conn, $userId);
        
        // Fecha a conexão
        $conn->close();
    } else {
        error_log("Erro de conexão com o banco de dados em init.php");
        $_SESSION['cart'] = array(); // Inicializa carrinho vazio
    }
}
?>