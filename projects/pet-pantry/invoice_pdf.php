<?php
// Enable full error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
include('database.php');
require_once 'settings_helper.php';
require_once 'payment_api_helper.php';
require_once __DIR__ . '/dompdf-3.1.0/dompdf/vendor/autoload.php';

use Dompdf\Dompdf;

// Check user session
if (!isset($_SESSION['user_id'])) exit("Access denied. Please login.");

$userId = $_SESSION['user_id'];
$isAdmin = $_SESSION['account_type'] === 'admin';

if (!isset($_GET['order_id'])) exit("No order specified.");
$orderId = intval($_GET['order_id']);

// Fetch order group
if ($isAdmin) {
    $stmt = $pdo->prepare("
        SELECT og.*, u.name AS username, u.email
        FROM order_groups og
        JOIN users u ON og.user_id = u.id
        WHERE og.id = ?
    ");
    $stmt->execute([$orderId]);
} else {
    $stmt = $pdo->prepare("
        SELECT og.*, u.name AS username, u.email
        FROM order_groups og
        JOIN users u ON og.user_id = u.id
        WHERE og.id = ? AND og.user_id = ?
    ");
    $stmt->execute([$orderId, $userId]);
}

$orderGroup = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$orderGroup) exit("Order not found or access denied.");

// Fetch order items
$stmt2 = $pdo->prepare("
    SELECT o.*, p.name AS product_name, p.image
    FROM orders o
    JOIN products p ON o.product_id = p.id
    WHERE o.order_group_id = ?
");
$stmt2->execute([$orderId]);
$orderItems = $stmt2->fetchAll(PDO::FETCH_ASSOC);

// Calculate totals
$subtotal = 0;
foreach ($orderItems as $item) $subtotal += $item['price'] * $item['quantity'];

// Get discount from order group
$discount = floatval($orderGroup['discount_amount'] ?? 0);
$promoCode = $orderGroup['promo_code'] ?? null;

// Get shipping fee from order group or use default
$shippingFee = floatval($orderGroup['shipping_fee'] ?? 0);
if ($shippingFee == 0) {
    $shippingFee = calculateShippingFee($subtotal);
}
$total = $subtotal - $discount + $shippingFee;

// Get currency settings
$currencySymbol = getCurrencySymbol();

// Fetch payment details based on payment method
$paymentMethod = $orderGroup['payment_method'] ?? '';
$paymentCode = $orderGroup['payment_code'] ?? null;
$paypalOrderId = $orderGroup['paypal_order_id'] ?? null;

// If payment_code doesn't exist in order, try to determine it from payment_method
if (empty($paymentCode) && !empty($paymentMethod)) {
    // Map common payment method names to codes
    $methodMap = [
        'Cash' => 'cod',
        'Cash on Delivery' => 'cod',
        'COD' => 'cod',
        'PayPal' => 'paypal',
        'Credit Card' => 'card',
        'Debit Card' => 'card',
        'Bank Transfer' => 'bank',
        'GCash' => 'gcash'
    ];
    
    $paymentCode = $methodMap[$paymentMethod] ?? strtolower(str_replace(' ', '_', $paymentMethod));
}

// Get detailed payment information from payment_options table if payment_code exists
$paymentInfo = null;
if ($paymentCode) {
    try {
        $stmt3 = $pdo->prepare("SELECT * FROM payment_options WHERE code = ? LIMIT 1");
        $stmt3->execute([$paymentCode]);
        $paymentInfo = $stmt3->fetch(PDO::FETCH_ASSOC);
        
        // If we found payment info from database, use that name
        if ($paymentInfo && !empty($paymentInfo['name'])) {
            $paymentMethod = $paymentInfo['name'];
        }
    } catch (PDOException $e) {
        // payment_options table might not exist, continue with payment_method from order
        error_log("Could not fetch payment info: " . $e->getMessage());
    }
}

// Ensure paymentMethod has a value (fallback to Cash on Delivery if empty)
if (empty($paymentMethod)) {
    $paymentMethod = 'Cash on Delivery';
    $paymentCode = 'cod';
}

// Get bank details if payment method is bank transfer
$bankDetails = null;
if (in_array(strtolower($paymentCode), ['bank', 'bank_transfer'])) {
    $bankDetails = getBankDetails();
}

// Helper: get Dompdf-compatible image path
function getDompdfImagePath($url) {
    $url = str_replace('http://', 'https://', $url);
    if (@getimagesize($url)) return $url;
    $localPath = $_SERVER['DOCUMENT_ROOT'] . parse_url($url, PHP_URL_PATH);
    return file_exists($localPath) ? 'file://' . $localPath : '';
}

// Logo path
$logoUrl = getDompdfImagePath('https://petpantry.space/images/logo.png');
if (!$logoUrl) exit("Logo image not found.");

// Generate QR code as base64
require_once __DIR__ . '/phpqrcode/qrlib.php';
ob_start();
$invoiceUrl = "https://petpantry.space/invoice.php?order_id=$orderId";
QRcode::png($invoiceUrl, null, QR_ECLEVEL_L, 3);
$qrData = ob_get_contents();
ob_end_clean();
$qrBase64 = 'data:image/png;base64,' . base64_encode($qrData);

// Generate HTML
ob_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Invoice #<?= $orderId ?></title>
<style>
body { font-family: 'DejaVu Sans', sans-serif; background: #f4f6f8; padding: 20px; margin: 0; }
.invoice-container { max-width: 900px; margin: 0 auto; background: #fff; border-radius: 8px; padding: 25px; }
.invoice-header { display: table; width: 100%; margin-bottom: 20px; }
.invoice-header img { width: 60px; height: 60px; border-radius: 50%; border: 2px solid #FF8C00; object-fit: cover; vertical-align: middle; }
.invoice-header span { font-weight: bold; font-size: 1.75rem; color: #FF8C00; vertical-align: middle; display: inline-block; margin-left: 15px; }
.invoice-title { text-align: center; font-size: 1.5rem; font-weight: 600; margin: 10px 0; }
.invoice-flex { display: table; width: 100%; margin-bottom: 20px; }
.qr-code { display: table-cell; width:130px; vertical-align: top; text-align: center; }
.invoice-info { display: table-cell; padding-left: 20px; vertical-align: top; }
table { width: 100%; border-collapse: collapse; margin-bottom: 15px; }
th, td { padding: 8px; border-bottom: 1px solid #e5e7eb; vertical-align: middle; }
th { background: #f9fafb; text-align: left; }
td img { width: 40px; height: 40px; object-fit: cover; border-radius: 4px; margin-right: 5px; vertical-align: middle; }
.total-row { font-weight: 600; text-align: right; margin-top: 15px; }
.payment-section { margin-top: 20px; padding: 15px; background: #f9fafb; border-radius: 8px; border-left: 4px solid #ff6b35; }
.payment-details { margin-top: 10px; }
.payment-details div { padding: 5px 0; }
.bank-info { margin-top: 10px; padding: 10px; background: white; border-radius: 4px; border: 1px solid #e5e7eb; }
</style>
</head>
<body>
<div class="invoice-container">

    <div class="invoice-header">
        <img src="<?= $logoUrl ?>" alt="PetPantry+ Logo">
        <span>PetPantry+</span>
    </div>

    <div class="invoice-title">Invoice / Packing Slip</div>

    <!-- QR + Info -->
    <div class="invoice-flex">
        <div class="qr-code">
            <img src="<?= $qrBase64 ?>" alt="QR Code" style="width:120px; height:120px;"><br>
            Scan to view online
        </div>
        <div class="invoice-info">
            <div><strong>Order ID:</strong> <?= $orderId ?></div>
            <div><strong>Order Date:</strong> <?= date("F j, Y g:i A", strtotime($orderGroup['created_at'])) ?></div>
            <div><strong>Customer:</strong> <?= htmlspecialchars($orderGroup['username']) ?></div>
            <div><strong>Email:</strong> <?= htmlspecialchars($orderGroup['email']) ?></div>
            <div><strong>Shipping Address:</strong> <?= htmlspecialchars($orderGroup['address'] ?? 'N/A') ?></div>
            <div><strong>Payment Method:</strong> <?= htmlspecialchars($paymentMethod ?? 'Cash on Delivery') ?></div>
        </div>
    </div>

    <h4>Items</h4>
    <table>
        <thead>
            <tr>
                <th>Product</th>
                <th style="text-align:right;">Price</th>
                <th style="text-align:center;">Qty</th>
                <th style="text-align:right;">Total</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($orderItems as $item):
            $itemTotal = $item['price'] * $item['quantity'];
            $itemImage = getDompdfImagePath($item['image']);
        ?>
            <tr>
                <td>
                    <?php if ($itemImage): ?>
                        <img src="<?= $itemImage ?>" alt="Product">
                    <?php endif; ?>
                    <?= htmlspecialchars($item['product_name']) ?>
                </td>
                <td style="text-align:right;"><?=$currencySymbol?><?= number_format($item['price'],2) ?></td>
                <td style="text-align:center;"><?= $item['quantity'] ?></td>
                <td style="text-align:right;"><?=$currencySymbol?><?= number_format($itemTotal,2) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>

    <div class="total-row">
        <div>Subtotal: <?=$currencySymbol?><?= number_format($subtotal,2) ?></div>
        <?php if ($discount > 0 && $promoCode): ?>
        <div style="color: #10b981;">Discount (<?= htmlspecialchars($promoCode) ?>): -<?=$currencySymbol?><?= number_format($discount,2) ?></div>
        <?php endif; ?>
        <div>Shipping: <?=$currencySymbol?><?= number_format($shippingFee,2) ?></div>
        <div style="font-size:1.1rem; border-top:2px solid #e5e7eb; padding-top:10px; margin-top:10px;">
            Total: <?=$currencySymbol?><?= number_format($total,2) ?>
        </div>
    </div>

    <!-- Payment Details Section -->
    <?php if ($paymentMethod): ?>
    <div class="payment-section">
        <h4 style="margin: 0 0 10px 0; color: #ff6b35;">Payment Information</h4>
        <div class="payment-details">
            <div><strong>Payment Method:</strong> <?= htmlspecialchars($paymentMethod) ?></div>
            
            <?php if ($paypalOrderId): ?>
                <div><strong>PayPal Transaction ID:</strong> <?= htmlspecialchars($paypalOrderId) ?></div>
            <?php endif; ?>
            
            <?php if ($bankDetails): ?>
            <div style="margin-top: 10px;"><strong>Bank Transfer Details:</strong></div>
            <div class="bank-info">
                <div><strong>Bank Name:</strong> <?= htmlspecialchars($bankDetails['bank_name']) ?></div>
                <div><strong>Account Name:</strong> <?= htmlspecialchars($bankDetails['account_name']) ?></div>
                <div><strong>Account Number:</strong> <?= htmlspecialchars($bankDetails['account_number']) ?></div>
                <?php if (!empty($bankDetails['swift_code'])): ?>
                <div><strong>SWIFT Code:</strong> <?= htmlspecialchars($bankDetails['swift_code']) ?></div>
                <?php endif; ?>
                <?php if (!empty($bankDetails['branch'])): ?>
                <div><strong>Branch:</strong> <?= htmlspecialchars($bankDetails['branch']) ?></div>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>

</div>
</body>
</html>
<?php
$html = ob_get_clean();

try {
    $dompdf = new Dompdf();
    $dompdf->set_option('isRemoteEnabled', true);
    $dompdf->set_option('defaultFont', 'DejaVu Sans');
    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();
    $dompdf->stream("Invoice_Order_{$orderId}.pdf", ["Attachment" => true]);
} catch (Exception $e) {
    exit("Dompdf error: " . $e->getMessage());
}
?>
