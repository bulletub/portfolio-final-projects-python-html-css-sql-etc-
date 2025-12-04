<?php
/**
 * Email Helper for PetPantry+
 * Reusable email sending functions
 */

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require __DIR__ . '/PHPMailer/src/Exception.php';
require __DIR__ . '/PHPMailer/src/PHPMailer.php';
require __DIR__ . '/PHPMailer/src/SMTP.php';

// Email Configuration
const MAIL_FROM      = 'no-reply@petpantry.space';
const MAIL_FROM_NAME = 'PetPantry+';
const MAIL_HOST      = 'smtp.hostinger.com';
const MAIL_USER      = 'no-reply@petpantry.space';
const MAIL_PASS      = 'PetP@ntry123';
const MAIL_PORT      = 465;
const MAIL_SECURE    = PHPMailer::ENCRYPTION_SMTPS;

/**
 * Send a generic email
 */
function sendEmail($to, $name, $subject, $body) {
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = MAIL_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = MAIL_USER;
        $mail->Password   = MAIL_PASS;
        $mail->SMTPSecure = MAIL_SECURE;
        $mail->Port       = MAIL_PORT;

        $mail->setFrom(MAIL_FROM, MAIL_FROM_NAME);
        $mail->addAddress($to, $name);
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $body;

        $mail->send();
        error_log("✅ Email sent to {$to}: {$subject}");
        return true;
    } catch (Exception $e) {
        error_log("❌ Email send failed ({$subject}): " . $mail->ErrorInfo);
        return false;
    }
}

/**
 * Send order status update email
 */
function sendOrderStatusEmail($customerEmail, $customerName, $orderGroupId, $newStatus, $orderDetails = []) {
    $subject = getOrderEmailSubject($newStatus, $orderGroupId);
    $body = getOrderEmailBody($customerName, $orderGroupId, $newStatus, $orderDetails);
    
    return sendEmail($customerEmail, $customerName, $subject, $body);
}

/**
 * Get email subject based on order status
 */
function getOrderEmailSubject($status, $orderGroupId) {
    $subjects = [
        'pending'   => "Order #{$orderGroupId} - Pending Confirmation",
        'shipping'  => "📦 Order #{$orderGroupId} is Now Shipping!",
        'completed' => "✅ Order #{$orderGroupId} Completed - Thank You!",
        'cancelled' => "❌ Order #{$orderGroupId} Has Been Cancelled"
    ];
    
    return $subjects[$status] ?? "Order #{$orderGroupId} Status Update";
}

/**
 * Get email body based on order status
 */
function getOrderEmailBody($customerName, $orderGroupId, $status, $orderDetails = []) {
    $brandColor = '#ff6b35';
    $siteName = 'PetPantry+';
    $siteUrl = 'https://petpantry.space';
    
    // Extract order details
    $totalAmount = $orderDetails['total'] ?? 0;
    $address = $orderDetails['address'] ?? 'Your registered address';
    $items = $orderDetails['items'] ?? [];
    
    // Status-specific content
    $statusContent = getStatusSpecificContent($status, $orderGroupId, $address);
    
    // Items list (if provided)
    $itemsHtml = '';
    if (!empty($items)) {
        $itemsHtml = '<div style="margin: 20px 0; padding: 15px; background: #f9f9f9; border-radius: 8px;">';
        $itemsHtml .= '<h3 style="margin-top: 0; color: #333;">Order Items:</h3>';
        foreach ($items as $item) {
            $itemsHtml .= '<div style="padding: 8px 0; border-bottom: 1px solid #e0e0e0;">';
            $itemsHtml .= '<strong>' . htmlspecialchars($item['name']) . '</strong><br>';
            $itemsHtml .= 'Quantity: ' . $item['quantity'] . ' × ₱' . number_format($item['price'], 2);
            $itemsHtml .= ' = ₱' . number_format($item['quantity'] * $item['price'], 2);
            $itemsHtml .= '</div>';
        }
        if ($totalAmount > 0) {
            $itemsHtml .= '<div style="margin-top: 10px; font-size: 1.1em; font-weight: bold; color: ' . $brandColor . ';">';
            $itemsHtml .= 'Total: ₱' . number_format($totalAmount, 2);
            $itemsHtml .= '</div>';
        }
        $itemsHtml .= '</div>';
    }
    
    // Email template
    $body = '
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Order Update</title>
    </head>
    <body style="margin: 0; padding: 0; font-family: Arial, sans-serif; background-color: #f5f5f5;">
        <table width="100%" cellpadding="0" cellspacing="0" style="background-color: #f5f5f5; padding: 20px;">
            <tr>
                <td align="center">
                    <table width="600" cellpadding="0" cellspacing="0" style="background-color: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
                        
                        <!-- Header -->
                        <tr>
                            <td style="background: linear-gradient(135deg, ' . $brandColor . ' 0%, #ff8c42 100%); padding: 30px; text-align: center;">
                                <h1 style="margin: 0; color: #ffffff; font-size: 28px;">' . $siteName . '</h1>
                                <p style="margin: 10px 0 0 0; color: #ffffff; font-size: 14px;">Your Premium Pet Supply Store</p>
                            </td>
                        </tr>
                        
                        <!-- Content -->
                        <tr>
                            <td style="padding: 40px 30px;">
                                <h2 style="margin: 0 0 20px 0; color: #333; font-size: 24px;">Hello ' . htmlspecialchars($customerName) . '! 👋</h2>
                                
                                ' . $statusContent . '
                                
                                ' . $itemsHtml . '
                                
                                <div style="margin: 30px 0; text-align: center;">
                                    <a href="' . $siteUrl . '/orders.php" 
                                       style="display: inline-block; padding: 14px 30px; background-color: ' . $brandColor . '; color: #ffffff; text-decoration: none; border-radius: 6px; font-weight: bold; font-size: 16px; margin-right: 10px;">
                                        View Order Details
                                    </a>
                                    <a href="' . $siteUrl . '/invoice_pdf.php?order_id=' . $orderGroupId . '" 
                                       style="display: inline-block; padding: 14px 30px; background-color: #666; color: #ffffff; text-decoration: none; border-radius: 6px; font-weight: bold; font-size: 16px;">
                                        📄 Download PDF Invoice
                                    </a>
                                </div>
                            </td>
                        </tr>
                        
                        <!-- Footer -->
                        <tr>
                            <td style="background-color: #f9f9f9; padding: 20px 30px; border-top: 1px solid #e0e0e0;">
                                <p style="margin: 0 0 10px 0; color: #666; font-size: 14px;">
                                    <strong>Need Help?</strong><br>
                                    Contact us at <a href="mailto:no-reply@petpantry.space" style="color: ' . $brandColor . ';">no-reply@petpantry.space</a>
                                </p>
                                <p style="margin: 15px 0 0 0; color: #999; font-size: 12px;">
                                    © 2025 PetPantry+. All rights reserved.<br>
                                    This is an automated email. Please do not reply directly to this message.
                                </p>
                            </td>
                        </tr>
                        
                    </table>
                </td>
            </tr>
        </table>
    </body>
    </html>
    ';
    
    return $body;
}

/**
 * Get status-specific content for email
 */
function getStatusSpecificContent($status, $orderGroupId, $address) {
    $brandColor = '#ff6b35';
    
    switch ($status) {
        case 'shipping':
            return '
                <div style="background: #e8f5e9; padding: 20px; border-radius: 8px; border-left: 4px solid #4caf50; margin-bottom: 20px;">
                    <h3 style="margin: 0 0 10px 0; color: #2e7d32; font-size: 20px;">📦 Great News! Your Order is On Its Way!</h3>
                    <p style="margin: 0; color: #555; line-height: 1.6;">
                        Your order <strong>#' . $orderGroupId . '</strong> has been shipped and is now on its way to you!
                    </p>
                </div>
                <p style="color: #555; line-height: 1.6;">
                    <strong>Shipping Address:</strong><br>
                    ' . htmlspecialchars($address) . '
                </p>
                <p style="color: #555; line-height: 1.6;">
                    Your furry friend\'s favorite supplies are coming soon! Track your order status anytime through your account.
                </p>
            ';
            
        case 'completed':
            return '
                <div style="background: #e3f2fd; padding: 20px; border-radius: 8px; border-left: 4px solid #2196f3; margin-bottom: 20px;">
                    <h3 style="margin: 0 0 10px 0; color: #1565c0; font-size: 20px;">✅ Order Completed Successfully!</h3>
                    <p style="margin: 0; color: #555; line-height: 1.6;">
                        Your order <strong>#' . $orderGroupId . '</strong> has been delivered and marked as complete.
                    </p>
                </div>
                <p style="color: #555; line-height: 1.6;">
                    We hope you and your pet are enjoying your purchase! 🐾
                </p>
                <p style="color: #555; line-height: 1.6;">
                    <strong>Thank you for choosing PetPantry+!</strong><br>
                    If you have any concerns about your order, please don\'t hesitate to contact us.
                </p>
                <div style="background: #fff3e0; padding: 15px; border-radius: 6px; margin-top: 20px;">
                    <p style="margin: 0; color: #e65100; font-size: 14px;">
                        💝 <strong>Loved your experience?</strong> We\'d appreciate your feedback! Come back soon for more premium pet supplies.
                    </p>
                </div>
            ';
            
        case 'cancelled':
            return '
                <div style="background: #ffebee; padding: 20px; border-radius: 8px; border-left: 4px solid #f44336; margin-bottom: 20px;">
                    <h3 style="margin: 0 0 10px 0; color: #c62828; font-size: 20px;">❌ Order Cancelled</h3>
                    <p style="margin: 0; color: #555; line-height: 1.6;">
                        Your order <strong>#' . $orderGroupId . '</strong> has been cancelled.
                    </p>
                </div>
                <p style="color: #555; line-height: 1.6;">
                    If you did not request this cancellation or have any questions, please contact our support team immediately.
                </p>
                <p style="color: #555; line-height: 1.6;">
                    We\'re sorry to see this order cancelled. If there was an issue, we\'d love to make it right!
                </p>
            ';
            
        case 'pending':
        default:
            return '
                <div style="background: #fff3e0; padding: 20px; border-radius: 8px; border-left: 4px solid #ff9800; margin-bottom: 20px;">
                    <h3 style="margin: 0 0 10px 0; color: #e65100; font-size: 20px;">⏳ Order Confirmation</h3>
                    <p style="margin: 0; color: #555; line-height: 1.6;">
                        Your order <strong>#' . $orderGroupId . '</strong> is currently being processed.
                    </p>
                </div>
                <p style="color: #555; line-height: 1.6;">
                    We\'ve received your order and our team is preparing it for shipment. You\'ll receive another email once your order is shipped.
                </p>
            ';
    }
}
?>

