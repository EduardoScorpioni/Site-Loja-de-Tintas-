<?php
include_once ("conexao.php");

$sql = "SELECT sales.id, products.name AS product_name, sales.quantity, sales.total, sales.sale_date 
        FROM sales 
        JOIN products ON sales.product_id = products.id 
        ORDER BY sales.sale_date DESC";

$result = $conn->query($sql);

if ($result->num_rows > 0) {
    echo "<table border='1'>
            <tr>
                <th>ID da Venda</th>
                <th>Produto</th>
                <th>Quantidade</th>
                <th>Total</th>
                <th>Data da Venda</th>
            </tr>";
    while($row = $result->fetch_assoc()) {
        echo "<tr>
                <td>" . $row["id"] . "</td>
                <td>" . $row["product_name"] . "</td>
                <td>" . $row["quantity"] . "</td>
                <td>" . $row["total"] . "</td>
                <td>" . $row["sale_date"] . "</td>
              </tr>";
    }
    echo "</table>";
} else {
    echo "Nenhuma venda encontrada.";
}

$conn->close();
?>
