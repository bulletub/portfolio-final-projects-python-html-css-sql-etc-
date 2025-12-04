<?php
require_once 'database.php';
header('Content-Type: application/json');

// Disable caching to always fetch fresh data
header("Cache-Control: no-cache, must-revalidate");
header("Expires: 0");

try {
    $query = "
        SELECT 
            id AS product_id,
            name AS product_name,
            stock
        FROM products
        WHERE CAST(stock AS SIGNED) <= 5
        ORDER BY stock ASC, product_name ASC
    ";
    $stmt = $pdo->prepare($query);
    $stmt->execute();
    $lowStocks = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'status' => 'success',
        'count'  => count($lowStocks),
        'data'   => $lowStocks
    ]);

} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
