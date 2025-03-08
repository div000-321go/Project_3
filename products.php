<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

class Database {
    private $host = "localhost";
    private $db_name = "promohub";
    private $username = "root";
    private $password = "";
    public $conn;

    public function getConnection() {
        $this->conn = null;
        try {
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->db_name,
                $this->username,
                $this->password
            );
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch(PDOException $e) {
            echo "Connection Error: " . $e->getMessage();
        }
        return $this->conn;
    }
}

class Products {
    private $conn;
    private $upload_path = "../uploads/"; // Adjust this path to match your upload directory

    public function __construct($db) {
        $this->conn = $db;
    }

    public function getProducts($category = null) {
        try {
            $query = "SELECT p.*, 
                            (SELECT COUNT(*) FROM analytics WHERE product_id = p.id) as views 
                     FROM products p 
                     WHERE p.status = 'active'";
            
            if ($category && $category !== 'all') {
                $query .= " AND p.category = :category";
            }
            
            $query .= " ORDER BY p.featured DESC, p.created_at DESC";
            
            $stmt = $this->conn->prepare($query);
            
            if ($category && $category !== 'all') {
                $stmt->bindParam(':category', $category);
            }
            
            $stmt->execute();
            $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Process images paths
            foreach ($products as &$product) {
                if (!empty($product['images'])) {
                    $images = json_decode($product['images'], true);
                    $processed_images = [];
                    foreach ($images as $image) {
                        $processed_images[] = '/uploads/' . $image; // Adjust path as needed
                    }
                    $product['images'] = json_encode($processed_images);
                } else {
                    $product['images'] = json_encode(['/images/default-product.jpg']); // Default image
                }
            }

            return $products;

        } catch (Exception $e) {
            throw new Exception("Error fetching products: " . $e->getMessage());
        }
    }

    public function incrementViews($product_id) {
        try {
            $date = date('Y-m-d');
            
            // First try to update existing record
            $query = "INSERT INTO analytics (product_id, view_date, view_count) 
                     VALUES (:product_id, :view_date, 1) 
                     ON DUPLICATE KEY UPDATE view_count = view_count + 1";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':product_id', $product_id);
            $stmt->bindParam(':view_date', $date);
            $stmt->execute();

            return true;
        } catch (Exception $e) {
            error_log("Error incrementing views: " . $e->getMessage());
            return false;
        }
    }
}

try {
    $database = new Database();
    $db = $database->getConnection();
    $products = new Products($db);

    // Get category from query parameter
    $category = isset($_GET['category']) ? $_GET['category'] : 'all';
    
    // Get products
    $result = $products->getProducts($category);
    
    // Return JSON response
    echo json_encode($result);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        "status" => "error",
        "message" => $e->getMessage()
    ]);
}
?>