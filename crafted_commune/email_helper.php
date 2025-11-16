<?php
/**
 * Email Helper using PHPMailer + Gmail SMTP
 * FREE and reliable email sending
 */

// Include PHPMailer
require_once __DIR__ . '/PHPMailer/PHPMailer.php';
require_once __DIR__ . '/PHPMailer/SMTP.php';
require_once __DIR__ . '/PHPMailer/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

/**
 * Send email using Gmail SMTP
 * 
 * @param string $to Recipient email
 * @param string $subject Email subject
 * @param string $htmlBody HTML content
 * @param string $toName Recipient name (optional)
 * @return bool Success status
 */
function sendEmail($to, $subject, $htmlBody, $toName = '') {
    // Check if emails are enabled
    if (!defined('ENABLE_EMAILS') || !ENABLE_EMAILS) {
        error_log("Email disabled in config. Would send to: $to");
        return false;
    }
    
    try {
        $mail = new PHPMailer(true);
        
        // Server settings
        $mail->isSMTP();
        $mail->Host       = SMTP_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = SMTP_USERNAME;
        $mail->Password   = SMTP_PASSWORD;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = SMTP_PORT;
        
        // Recipients
        $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
        $mail->addAddress($to, $toName);
        
        // Content
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $htmlBody;
        $mail->CharSet = 'UTF-8';
        
        // Send
        $mail->send();
        
        // Log success
        error_log("Email sent successfully to: $to");
        return true;
        
    } catch (Exception $e) {
        // Log error
        error_log("Email failed to: $to. Error: {$mail->ErrorInfo}");
        return false;
    }
}

/**
 * Send product rating email
 */
function sendRatingEmail($toEmail, $customerName, $pointsEarned, $totalPoints, $ratingUrl, $orderNumber) {
    $subject = "🎉 You earned {$pointsEarned} points! Rate your products";
    
    $message = "
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; background: #f4f4f4; margin: 0; padding: 0; }
            .container { max-width: 600px; margin: 20px auto; background: white; border-radius: 10px; overflow: hidden; }
            .header { background: linear-gradient(135deg, #273B08 0%, #305001ff 100%); color: white; padding: 30px; text-align: center; }
            .content { padding: 30px; }
            .points-box { background: #f8f9fa; border-left: 4px solid #273B08; padding: 20px; margin: 20px 0; }
            .points-earned { font-size: 36px; color: #273B08; font-weight: bold; }
            .total-points { font-size: 24px; color: #333; margin-top: 10px; }
            .cta-button { display: inline-block; background: #273B08; color: white; padding: 15px 30px; text-decoration: none; border-radius: 5px; margin: 20px 0; }
            .footer { background: #f8f9fa; padding: 20px; text-align: center; font-size: 12px; color: #666; }
            .product-rating-note { background: #fff3cd; padding: 15px; border-radius: 8px; margin: 15px 0; border-left: 4px solid #ffc107; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1>☕ Crafted Commune</h1>
                <p>Thank you for your purchase!</p>
            </div>
            <div class='content'>
                <h2>Hi {$customerName}! 🎉</h2>
                <p>Thank you for your recent purchase (Order #{$orderNumber})!</p>
                
                <div class='points-box'>
                    <div>Points Earned:</div>
                    <div class='points-earned'>{$pointsEarned} points</div>
                    <div class='total-points'>Total Points: {$totalPoints}</div>
                </div>
                
                <div class='product-rating-note'>
                    <strong>⭐ Help us improve!</strong><br>
                    Rate each product you purchased and get <strong>5 bonus points!</strong>
                </div>
                
                <p><strong>Your feedback matters!</strong></p>
                <p>Rate the products you ordered - it takes just 30 seconds:</p>
                
                <a href='{$ratingUrl}' class='cta-button' style='color: white;'>⭐ Rate Your Products</a>
                
                <p style='font-size: 12px; color: #666; margin-top: 20px;'>
                    Or copy this link:<br>
                    <a href='{$ratingUrl}'>{$ratingUrl}</a><br>
                    This link expires in 30 days.
                </p>
                
                <p style='font-size: 14px; color: #666; margin-top: 20px;'>
                    <strong>Why rate?</strong><br>
                    • Get 5 bonus points instantly<br>
                    • Help other customers discover great products<br>
                    • Shape our menu based on what you love
                </p>
            </div>
            <div class='footer'>
                <p>Crafted Commune Café | Where Every Cup Tells a Story</p>
                <p>21 Aurea, Mabalacat City, Pampanga, Philippines</p>
            </div>
        </div>
    </body>
    </html>
    ";
    
    return sendEmail($toEmail, $subject, $message, $customerName);
}
?>