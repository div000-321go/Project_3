<?php
// Database configuration
$host = 'localhost';
$username = 'root';
$password = ''; 
$database = 'spotlight_hub';

// Create database connection
try {
    $mysqli = new mysqli($host, $username, $password, $database);
    if ($mysqli->connect_error) {
        throw new Exception('Database connection failed: ' . $mysqli->connect_error);
    }
} catch (Exception $e) {
    die($e->getMessage());
}

// Check if form data and files are submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['product_image']) && isset($_FILES['logo'])) {
    try {
        // File upload settings
        $targetDir = "uploads/";
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
        $maxFileSize = 5 * 1024 * 1024; // 5MB

        // Create uploads directory if it doesn't exist
        if (!file_exists($targetDir)) {
            mkdir($targetDir, 0777, true);
        }

        // Validate and handle product image upload
        if ($_FILES["product_image"]["error"] !== UPLOAD_ERR_OK) {
            throw new Exception('Error uploading product image: ' . $_FILES["product_image"]["error"]);
        }
        if (!in_array($_FILES["product_image"]["type"], $allowedTypes)) {
            throw new Exception('Invalid product image type. Only JPG, PNG, and GIF allowed.');
        }
        if ($_FILES["product_image"]["size"] > $maxFileSize) {
            throw new Exception('Product image size too large. Maximum size is 5MB.');
        }

        // Generate unique filename for product image
        $productImagePath = $targetDir . uniqid() . '_' . basename($_FILES["product_image"]["name"]);
        if (!move_uploaded_file($_FILES["product_image"]["tmp_name"], $productImagePath)) {
            throw new Exception('Error moving product image to target directory.');
        }

        // Validate and handle logo upload
        if ($_FILES["logo"]["error"] !== UPLOAD_ERR_OK) {
            throw new Exception('Error uploading logo: ' . $_FILES["logo"]["error"]);
        }
        if (!in_array($_FILES["logo"]["type"], $allowedTypes)) {
            throw new Exception('Invalid logo type. Only JPG, PNG, and GIF allowed.');
        }
        if ($_FILES["logo"]["size"] > $maxFileSize) {
            throw new Exception('Logo size too large. Maximum size is 5MB.');
        }

        // Generate unique filename for logo
        $logoPath = $targetDir . uniqid() . '_' . basename($_FILES["logo"]["name"]);
        if (!move_uploaded_file($_FILES["logo"]["tmp_name"], $logoPath)) {
            // Clean up product image if logo upload fails
            unlink($productImagePath);
            throw new Exception('Error moving logo to target directory.');
        }

        // Sanitize and validate form data
        $companyName = filter_var(trim($_POST['company_name'] ?? ''), FILTER_SANITIZE_STRING);
        $productName = filter_var(trim($_POST['product_name'] ?? ''), FILTER_SANITIZE_STRING);
        $productDescription = filter_var(trim($_POST['product_description'] ?? ''), FILTER_SANITIZE_STRING);

        if (empty($companyName) || empty($productName) || empty($productDescription)) {
            throw new Exception('All fields are required.');
        }

        // Prepare and execute SQL query
        $stmt = $mysqli->prepare("INSERT INTO companies (company_name, product_name, product_description, product_image, logo, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
        if (!$stmt) {
            throw new Exception('SQL preparation error: ' . $mysqli->error);
        }

        $stmt->bind_param("sssss", $companyName, $productName, $productDescription, $productImagePath, $logoPath);
        
        if (!$stmt->execute()) {
            throw new Exception('Error submitting company data: ' . $stmt->error);
        }

        $stmt->close();
        echo "Company information submitted successfully!";

    } catch (Exception $e) {
        // Clean up uploaded files if they exist
        if (isset($productImagePath) && file_exists($productImagePath)) {
            unlink($productImagePath);
        }
        if (isset($logoPath) && file_exists($logoPath)) {
            unlink($logoPath);
        }
        die($e->getMessage());
    }
} else {
    die("Invalid request. Please submit the form with all required data.");
}

// Close database connection
$mysqli->close();
?>