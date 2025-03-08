<?php
header('Content-Type: application/json');
require_once 'config.php';

try {
    $data = json_decode(file_get_contents('php://input'), true);
    
    // Begin transaction
    $pdo->beginTransaction();

    // Debug log
    error_log("Received image data: " . substr($data['image_url'], 0, 100) . "...");

    // Insert into products table
    $stmt = $pdo->prepare("INSERT INTO products (id, product_name, company_name, email, description, 
        package_type, package_price, duration, image_url, status) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    
    $stmt->execute([
        $data['id'],
        $data['product_name'],
        $data['company_name'],
        $data['email'],
        $data['description'],
        $data['package_type'],
        $data['package_price'],
        $data['duration'],
        $data['image'],
        'Active'
    ]);

    $pdo->commit();
    echo json_encode(['success' => true]);
} catch(Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Error saving product: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>