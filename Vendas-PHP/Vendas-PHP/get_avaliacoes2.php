
<?php
require "conexao.php";
session_start();

$product_id = intval($_GET['product_id']);

$sql = "SELECT a.rating, a.comentario, a.created_at, u.name 
        FROM avaliacoes a 
        JOIN users u ON a.user_id = u.id 
        WHERE a.product_id = ? 
        ORDER BY a.created_at DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $product_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()){
        // Montar estrelas
        $stars = "";
        for($i=1;$i<=5;$i++){
            if ($i <= $row['rating']){
                $stars .= '<i class="bi bi-star-fill text-warning"></i>';
            } else {
                $stars .= '<i class="bi bi-star text-warning"></i>';
            }
        }

        echo '<div class="border rounded p-2 mb-2">';
        echo '<strong>'.htmlspecialchars($row['name']).':</strong> '.$stars.'<br>';
        echo '<span>'.htmlspecialchars($row['comentario']).'</span><br>';
        echo '<small class="text-muted">'.date("d/m/Y H:i", strtotime($row['created_at'])).'</small>';
        echo '</div>';
    }
} else {
    echo "<p class='text-muted'>Ainda não há avaliações para este produto.</p>";
}

$stmt->close();
$conn->close();
?>
