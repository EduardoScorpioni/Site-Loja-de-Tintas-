<?php
// cart_functions.php - Funções para gerenciar o carrinho no banco de dados

if (!function_exists('saveCartToDatabase')) {
    function saveCartToDatabase($conn, $userId, $cart) {
        // Primeiro, limpa o carrinho atual do usuário
        $stmt = $conn->prepare("DELETE FROM carts WHERE user_id = ?");
        if ($stmt) {
            $stmt->bind_param("i", $userId);
            $stmt->execute();
            $stmt->close();
        } else {
            error_log("Erro ao preparar DELETE em saveCartToDatabase: " . $conn->error);
            return false;
        }
        
        // Insere os itens do carrinho no banco
        if (!empty($cart)) {
            foreach ($cart as $productId => $item) {
                $stmt = $conn->prepare("INSERT INTO carts (user_id, product_id, quantity) VALUES (?, ?, ?)");
                if ($stmt) {
                    $stmt->bind_param("iii", $userId, $productId, $item['quantity']);
                    $stmt->execute();
                    $stmt->close();
                } else {
                    error_log("Erro ao preparar INSERT em saveCartToDatabase: " . $conn->error);
                }
            }
        }
        return true;
    }
}

if (!function_exists('loadCartFromDatabase')) {
    function loadCartFromDatabase($conn, $userId) {
        $cart = array();
        
        $stmt = $conn->prepare("
            SELECT c.product_id, c.quantity, p.name, p.price 
            FROM carts c 
            JOIN products p ON c.product_id = p.id 
            WHERE c.user_id = ?
        ");
        
        if (!$stmt) {
            error_log("Erro ao preparar SELECT em loadCartFromDatabase: " . $conn->error);
            return $cart;
        }
        
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $cart[$row['product_id']] = array(
                    'name' => $row['name'],
                    'price' => $row['price'],
                    'quantity' => $row['quantity']
                );
            }
        }
        
        $stmt->close();
        return $cart;
    }
}

if (!function_exists('clearUserCart')) {
    function clearUserCart($conn, $userId) {
        $stmt = $conn->prepare("DELETE FROM carts WHERE user_id = ?");
        if ($stmt) {
            $stmt->bind_param("i", $userId);
            $stmt->execute();
            $stmt->close();
            return true;
        } else {
            error_log("Erro ao preparar DELETE em clearUserCart: " . $conn->error);
            return false;
        }
    }
}

if (!function_exists('getCartItemCount')) {
    function getCartItemCount($conn, $userId) {
        $stmt = $conn->prepare("SELECT SUM(quantity) as total FROM carts WHERE user_id = ?");
        if (!$stmt) {
            error_log("Erro ao preparar SELECT em getCartItemCount: " . $conn->error);
            return 0;
        }
        
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result) {
            $row = $result->fetch_assoc();
            $stmt->close();
            return $row['total'] ? $row['total'] : 0;
        }
        
        $stmt->close();
        return 0;
    }
}
?>