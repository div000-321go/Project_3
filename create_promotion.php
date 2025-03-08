<?php
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");

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

class Promotion {
    private $conn;
    private $upload_path = "uploads/"; // Make sure this directory exists and is writable

    public function __construct($db) {
        $this->conn = $db;
        // Create uploads directory if it doesn't exist
        if (!file_exists($this->upload_path)) {
            mkdir($this->upload_path, 0777, true);
        }
    }

    public function createPromotion($data, $files) {
        try {
            $this->conn->beginTransaction();

            // Insert product details
            $query = "INSERT INTO products 
                    (company_name, email, product_name, description, category, 
                     package_type, package_price, status, created_at) 
                    VALUES 
                    (:company_name, :email, :product_name, :description, :category,
                     :package_type, :package_price, 'pending', NOW())";

            $stmt = $this->conn->prepare($query);

            $stmt->bindParam(":company_name", $data['company_name']);
            $stmt->bindParam(":email", $data['email']);
            $stmt->bindParam(":product_name", $data['product_name']);
            $stmt->bindParam(":description", $data['description']);
            $stmt->bindParam(":category", $data['category']);
            $stmt->bindParam(":package_type", $data['package_type']);
            $stmt->bindParam(":package_price", $data['package_price']);

            $stmt->execute();
            $product_id = $this->conn->lastInsertId();

            // Handle image uploads
            $uploaded_images = [];
            if (!empty($files['product_images']['name'][0])) {
                $allowed_types = ['image/jpeg', 'image/png', 'image/webp'];
                $max_size = 5 * 1024 * 1024; // 5MB
                $max_files = 5;

                // Process each uploaded file
                for ($i = 0; $i < count($files['product_images']['name']); $i++) {
                    if ($i >= $max_files) break;

                    $file_type = $files['product_images']['type'][$i];
                    $file_size = $files['product_images']['size'][$i];
                    $file_tmp = $files['product_images']['tmp_name'][$i];
                    $file_error = $files['product_images']['error'][$i];

                    // Validate file
                    if ($file_error !== UPLOAD_ERR_OK) {
                        throw new Exception("Error uploading file: " . $file_error);
                    }

                    if (!in_array($file_type, $allowed_types)) {
                        throw new Exception("Invalid file type: " . $file_type);
                    }

                    if ($file_size > $max_size) {
                        throw new Exception("File too large: " . $files['product_images']['name'][$i]);
                    }

                    // Generate unique filename
                    $extension = pathinfo($files['product_images']['name'][$i], PATHINFO_EXTENSION);
                    $filename = uniqid() . '_' . $product_id . '.' . $extension;
                    $filepath = $this->upload_path . $filename;

                    // Move uploaded file
                    if (move_uploaded_file($file_tmp, $filepath)) {
                        $uploaded_images[] = $filename;
                    } else {
                        throw new Exception("Failed to move uploaded file");
                    }
                }
            }

            // Update product with image paths
            if (!empty($uploaded_images)) {
                $images_json = json_encode($uploaded_images);
                $update_query = "UPDATE products SET images = :images WHERE id = :id";
                $update_stmt = $this->conn->prepare($update_query);
                $update_stmt->bindParam(":images", $images_json);
                $update_stmt->bindParam(":id", $product_id);
                $update_stmt->execute();
            }

            // Send confirmation email
            $this->sendConfirmationEmail($data['email'], [
                'company_name' => $data['company_name'],
                'product_name' => $data['product_name'],
                'package_type' => $data['package_type'],
                'package_price' => $data['package_price']
            ]);

            $this->conn->commit();

            return [
                "status" => "success",
                "message" => "Promotion created successfully",
                "product_id" => $product_id
            ];

        } catch (Exception $e) {
            $this->conn->rollBack();
            
            // Delete any uploaded files if there was an error
            if (!empty($uploaded_images)) {
                foreach ($uploaded_images as $image) {
                    if (file_exists($this->upload_path . $image)) {
                        unlink($this->upload_path . $image);
                    }
                }
            }

            return [
                "status" => "error",
                "message" => $e->getMessage()
            ];
        }
    }

    private function sendConfirmationEmail($email, $data) {
        $subject = "Promotion Submission Confirmation - PromoHub";
        
        $message = "
            <html>
            <head>
                <style>
                    body { font-family: Arial, sans-serif; line-height: 1.6; }
                    .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                    .header { background: #4f46e5; color: white; padding: 20px; text-align: center; }
                    .content { padding: 20px; }
                    .footer { text-align: center; padding: 20px; color: #666; }
                </style>
            </head>
            <body>
                <div class='container'>
                    <div class='header'>
                        <h2>Thank You for Your Submission</h2>
                    </div>
                    <div class='content'>
                        <p>Dear {$data['company_name']},</p>
                        <p>Your promotion submission for <strong>{$data['product_name']}</strong> has been received.</p>
                        <p>Package Details:</p>
                        <ul>
                            <li>Package Type: {$data['package_type']}</li>
                            <li>Price: ${$data['package_price']}</li>
                        </ul>
                        <p>Please complete the payment process to activate your promotion.</p>
                    </div>
                    <div class='footer'>
                        <p>PromoHub - Your Product Promotion Platform</p>
                    </div>
                </div>
            </body>
            </html>
        ";

        $headers = "MIME-Version: 1.0" . "\r\n";
        $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
        $headers .= "From: PromoHub <noreply@promohub.com>" . "\r\n";

        mail($email, $subject, $message, $headers);
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Validate required fields
        $required_fields = ['company_name', 'email', 'product_name', 'description', 'category', 'package_type', 'package_price'];
        
        $data = [];
        foreach ($required_fields as $field) {
            if (!isset($_POST[$field]) || empty($_POST[$field])) {
                throw new Exception("Missing required field: {$field}");
            }
            $data[$field] = $_POST[$field];
        }

        // Validate email
        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            throw new Exception("Invalid email format");
        }

        // Initialize database
        $database = new Database();
        $db = $database->getConnection();
        
        if (!$db) {
            throw new Exception("Database connection failed");
        }

        // Create promotion
        $promotion = new Promotion($db);
        $result = $promotion->createPromotion($data, $_FILES);
        
        http_response_code($result['status'] === 'success' ? 200 : 400);
        echo json_encode($result);
        
    } catch (Exception $e) {
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