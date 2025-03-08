<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

include_once '../config/db.php';

class ProductPromotion {
    private $conn;
    private $table_name = "products";

    public function __construct($db) {
        $this->conn = $db;
    }

    public function createPromotion($data) {
        $query = "INSERT INTO " . $this->table_name . "
                (company_name, product_name, description, category, images, 
                package_type, package_price, expires_at)
                VALUES
                (:company_name, :product_name, :description, :category, :images,
                :package_type, :package_price, DATE_ADD(CURRENT_TIMESTAMP, INTERVAL :duration DAY))";

        try {
            $stmt = $this->conn->prepare($query);

            // Clean and sanitize data
            $company_name = htmlspecialchars(strip_tags($data['company_name']));
            $product_name = htmlspecialchars(strip_tags($data['product_name']));
            $description = htmlspecialchars(strip_tags($data['description']));
            $category = htmlspecialchars(strip_tags($data['category']));
            $images = json_encode($data['images']);
            $package_type = htmlspecialchars(strip_tags($data['package_type']));
            $package_price = floatval($data['package_price']);
            
            // Set duration based on package type
            $duration = 30; // Default for basic
            if($package_type === 'premium') $duration = 60;
            if($package_type === 'enterprise') $duration = 90;

            // Bind parameters
            $stmt->bindParam(":company_name", $company_name);
            $stmt->bindParam(":product_name", $product_name);
            $stmt->bindParam(":description", $description);
            $stmt->bindParam(":category", $category);
            $stmt->bindParam(":images", $images);
            $stmt->bindParam(":package_type", $package_type);
            $stmt->bindParam(":package_price", $package_price);
            $stmt->bindParam(":duration", $duration);

            if($stmt->execute()) {
                return [
                    "status" => "success",
                    "message" => "Product promotion created successfully",
                    "id" => $this->conn->lastInsertId()
                ];
            }

            return ["status" => "error", "message" => "Unable to create promotion"];

        } catch(PDOException $e) {
            return ["status" => "error", "message" => $e->getMessage()];
        }
    }
}

// Handle the request
if($_SERVER['REQUEST_METHOD'] === 'POST') {
    $database = new Database();
    $db = $database->getConnection();
    
    $promotion = new ProductPromotion($db);
    
    $data = json_decode(file_get_contents("php://input"), true);
    
    $result = $promotion->createPromotion($data);
    
    http_response_code($result['status'] === 'success' ? 201 : 400);
    echo json_encode($result);
}
?>