<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET");

include_once '../../config/db.php';
include_once '../../utils/auth.php';

class AdminDashboard {
    private $conn;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function getDashboardStats() {
        try {
            // Get total products
            $products_query = "SELECT COUNT(*) as total FROM products";
            $stmt = $this->conn->prepare($products_query);
            $stmt->execute();
            $total_products = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

            // Get active promotions
            $active_query = "SELECT COUNT(*) as active FROM products 
                           WHERE status = 'active' AND expires_at > CURRENT_TIMESTAMP";
            $stmt = $this->conn->prepare($active_query);
            $stmt->execute();
            $active_promotions = $stmt->fetch(PDO::FETCH_ASSOC)['active'];

            // Get total revenue
            $revenue_query = "SELECT SUM(amount) as revenue FROM payments 
                            WHERE status = 'completed'";
            $stmt = $this->conn->prepare($revenue_query);
            $stmt->execute();
            $total_revenue = $stmt->fetch(PDO::FETCH_ASSOC)['revenue'];

            // Get total views
            $views_query = "SELECT SUM(views) as total_views FROM products";
            $stmt = $this->conn->prepare($views_query);
            $stmt->execute();
            $total_views = $stmt->fetch(PDO::FETCH_ASSOC)['total_views'];

            // Get recent promotions
            $recent_query = "SELECT p.*, 
                           (SELECT COUNT(*) FROM analytics WHERE product_id = p.id) as total_views
                           FROM products p
                           ORDER BY created_at DESC
                           LIMIT 10";
            $stmt = $this->conn->prepare($recent_query);
            $stmt->execute();
            $recent_promotions = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return [
                "status" => "success",
                "data" => [
                    "totalProducts" => $total_products,
                    "activePromotions" => $active_promotions,
                    "totalRevenue" => number_format($total_revenue, 2),
                    "totalViews" => $total_views,
                    "recentPromotions" => $recent_promotions
                ]
            ];

        } catch(PDOException $e) {
            return ["status" => "error", "message" => $e->getMessage()];
        }
    }
}

// Verify admin authentication
if (!isAdminAuthenticated()) {
    http_response_code(401);
    echo json_encode(["status" => "error", "message" => "Unauthorized"]);
    exit();
}

// Handle the request
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $database = new Database();
    $db = $database->getConnection();
    
    $dashboard = new AdminDashboard($db);
    $result = $dashboard->getDashboardStats();
    
    http_response_code($result['status'] === 'success' ? 200 : 500);
    echo json_encode($result);
}
?>