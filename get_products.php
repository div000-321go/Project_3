<?php
header('Content-Type: application/json');
require_once 'config.php';

try {
    $stmt = $pdo->query("SELECT p.*, t.transaction_id, t.payment_method 
        FROM products p 
        LEFT JOIN transactions t ON p.id = t.product_id 
        ORDER BY p.created_at DESC");
    
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(['success' => true, 'products' => $products]);
} catch(Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>