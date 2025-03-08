<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php'; // Make sure you have PHPMailer installed via Composer

header('Content-Type: application/json');

try {
    $mail = new PHPMailer(true);
    
    // Server settings
    $mail->isSMTP();
    $mail->Host = 'your-smtp-host';
    $mail->SMTPAuth = true;
    $mail->Username = 'your-email@example.com';
    $mail->Password = 'your-password';
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = 587;

    // Recipients
    $mail->setFrom('your-email@example.com', 'PromoHub');
    $mail->addAddress($_POST['to']);

    // Attachments
    if (isset($_FILES['pdf'])) {
        $mail->addAttachment($_FILES['pdf']['tmp_name'], 'payment_receipt.pdf');
    }

    // Content
    $mail->isHTML(true);
    $mail->Subject = $_POST['subject'];
    $mail->Body = "
        <h2>Payment Receipt</h2>
        <p>Dear {$_POST['company_name']},</p>
        <p>Thank you for your payment. Please find your receipt attached.</p>
        <p><strong>Package:</strong> {$_POST['package_type']}</p>
        <p><strong>Amount Paid:</strong> ${$_POST['amount']}</p>
        <br>
        <p>Best regards,<br>PromoHub Team</p>
    ";

    $mail->send();
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => "Message could not be sent. Mailer Error: {$mail->ErrorInfo}"
    ]);
}
?>
