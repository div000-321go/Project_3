<?php
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");

// Database configuration
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

class Payment {
    private $conn;
    private $table_name = "payments";

    public function __construct($db) {
        $this->conn = $db;
    }

    public function processPayment($data) {
        try {
            // Start transaction
            $this->conn->beginTransaction();

            // Validate product exists
            $product_query = "SELECT id, status FROM products WHERE id = :product_id";
            $stmt = $this->conn->prepare($product_query);
            $stmt->bindParam(":product_id", $data['product_id']);
            $stmt->execute();
            
            $product = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$product) {
                throw new Exception("Product not found");
            }

            // Generate transaction ID
            $transaction_id = 'TXN' . time() . rand(1000, 9999);

            // Insert payment record
            $payment_query = "INSERT INTO " . $this->table_name . "
                            (product_id, amount, payment_method, transaction_id, status)
                            VALUES
                            (:product_id, :amount, :payment_method, :transaction_id, 'completed')";

            $stmt = $this->conn->prepare($payment_query);

            $stmt->bindParam(":product_id", $data['product_id']);
            $stmt->bindParam(":amount", $data['amount']);
            $stmt->bindParam(":payment_method", $data['payment_method']);
            $stmt->bindParam(":transaction_id", $transaction_id);

            $stmt->execute();

            // Update product status to active
            $update_query = "UPDATE products 
                           SET status = 'active',
                               updated_at = CURRENT_TIMESTAMP
                           WHERE id = :product_id";

            $stmt = $this->conn->prepare($update_query);
            $stmt->bindParam(":product_id", $data['product_id']);
            $stmt->execute();

            // Commit transaction
            $this->conn->commit();

            // Send confirmation email
            $this->sendConfirmationEmail($data['email'], $transaction_id, $data['amount']);

            return [
                "status" => "success",
                "message" => "Payment processed successfully",
                "transaction_id" => $transaction_id
            ];

        } catch(Exception $e) {
            // Rollback transaction on error
            if ($this->conn->inTransaction()) {
                $this->conn->rollBack();
            }
            
            // Log error
            error_log("Payment Error: " . $e->getMessage());
            
            return [
                "status" => "error",
                "message" => "Payment processing failed: " . $e->getMessage()
            ];
        }
    }

    private function sendConfirmationEmail($email, $transaction_id, $amount) {
        $to = $email;
        $subject = "Payment Confirmation - PromoHub";
        
        $message = "
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: #4f46e5; color: white; padding: 20px; text-align: center; }
                .content { padding: 20px; }
                .footer { text-align: center; padding: 20px; color: #666; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>Payment Confirmed!</h1>
                </div>
                <div class='content'>
                    <p>Thank you for choosing PromoHub for your product promotion!</p>
                    <p>Your payment has been successfully processed.</p>
                    <p><strong>Transaction ID:</strong> {$transaction_id}</p>
                    <p><strong>Amount Paid:</strong> ${$amount}</p>
                    <p>Your promotion will be active shortly. You can track your promotion performance through our dashboard.</p>
                </div>
                <div class='footer'>
                    <p>© " . date('Y') . " PromoHub. All rights reserved.</p>
                </div>
            </div>
        </body>
        </html>
        ";

        $headers = "MIME-Version: 1.0" . "\r\n";
        $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
        $headers .= "From: PromoHub <noreply@promohub.com>" . "\r\n";

        try {
            mail($to, $subject, $message, $headers);
        } catch(Exception $e) {
            error_log("Email Error: " . $e->getMessage());
        }
    }
}

// Handle the request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Get posted data
        $data = json_decode(file_get_contents("php://input"), true);

        if (!$data) {
            throw new Exception("Invalid input data");
        }

        // Validate required fields
        $required_fields = ['product_id', 'amount', 'payment_method', 'email'];
        foreach ($required_fields as $field) {
            if (!isset($data[$field])) {
                throw new Exception("Missing required field: {$field}");
            }
        }

        // Validate amount format
        if (!is_numeric($data['amount'])) {
            throw new Exception("Invalid amount format");
        }

        // Validate email format
        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            throw new Exception("Invalid email format");
        }

        // Initialize database connection
        $database = new Database();
        $db = $database->getConnection();
        
        if (!$db) {
            throw new Exception("Database connection failed");
        }

        // Process payment
        $payment = new Payment($db);
        $result = $payment->processPayment($data);
        
        // Set response code
        http_response_code($result['status'] === 'success' ? 200 : 400);
        
        // Return result
        echo json_encode($result);
        
    } catch (Exception $e) {
        error_log("Payment Error: " . $e->getMessage());
        http_response_code(400);
        echo json_encode([
            "status" => "error",
            "message" => $e->getMessage()
        ]);
    }
} else {
    http_response_code(405);
    echo json_encode([
        "status" => "error",
        "message" => "Method not allowed"
    ]);
}
?>