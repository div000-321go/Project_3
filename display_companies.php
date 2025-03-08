<?php
// Database connection details
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "spotlight_hub";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// SQL query to fetch companies
$sql = "SELECT * FROM companies";
$result = $conn->query($sql);

// HTML and CSS to display companies
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Company List</title>
    <style>
        #companyList {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
        }
        .company {
            border: 1px solid #ddd;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
            background-color: #fff;
        }
        .company img {
            max-width: 100%;
            height: auto;
            border-radius: 8px;
        }
    </style>
</head>
<body>

<h2>List of Companies</h2>
<div id="companyList">
    <?php
    if ($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            echo "<div class='company'>";
            echo "<img src='uploads/" . $row['logo'] . "' alt='" . $row['company_name'] . " Logo'>";
            echo "<h3>" . $row['company_name'] . "</h3>";
            echo "<p>" . $row['product_name'] . "</p>";
            echo "</div>";
        }
    } else {
        echo "No companies found.";
    }
    ?>
</div>

</body>
</html>

<?php
$conn->close();
?>
