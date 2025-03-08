<?php
class EmailService {
    private $from_email = "noreply@promohub.com";
    private $from_name = "PromoHub";

    public function sendPaymentConfirmation($to_email, $transaction_id) {
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
        $headers .= "From: {$this->from_name} <{$this->from_email}>" . "\r\n";

        return mail($to_email, $subject, $message, $headers);
    }
}
?>