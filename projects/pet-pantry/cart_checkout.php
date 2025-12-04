<?php
// Set JSON header FIRST, before any output
header('Content-Type: application/json');

// Enable error logging but DON'T display errors (they break JSON)
error_reporting(E_ALL);
ini_set('log_errors', 1);
ini_set('display_errors', 0); // Must be 0 to prevent HTML errors breaking JSON
ini_set('html_errors', 0);

// Catch fatal errors and return as JSON
register_shutdown_function(function() {
    $error = error_get_last();
    if ($error !== NULL && in_array($error['type'], [E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR, E_PARSE])) {
        // Make sure we output JSON, not HTML
        header('Content-Type: application/json');
        echo json_encode([
            'status' => 'error',
            'message' => 'Server error: ' . $error['message'],
            'file' => basename($error['file']),
            'line' => $error['line']
        ]);
    }
});

// Turn on output buffering to catch any unexpected output
ob_start();

session_start();
if(!isset($_SESSION['user_id'])){
    ob_clean();
    echo json_encode(['status'=>'error','message'=>'Not logged in']);
    exit;
}
$user_id = $_SESSION['user_id'];

// ✅ Validate POST - Check for either payment_method OR payment_code
if(!isset($_POST['cart_ids'])) {
    ob_clean();
    echo json_encode(['status'=>'error','message'=>'Missing cart items']);
    exit;
}

if(!isset($_POST['payment_method']) && !isset($_POST['payment_code'])) {
    ob_clean();
    echo json_encode(['status'=>'error','message'=>'Missing payment method']);
    exit;
}

$cart_ids = json_decode($_POST['cart_ids'], true);
$payment_method = $_POST['payment_method'] ?? '';
$paypal_order_id = $_POST['paypal_order_id'] ?? null; // ✅ store PayPal order ID

if(!is_array($cart_ids) || empty($cart_ids)){
    ob_clean();
    echo json_encode(['status'=>'error','message'=>'Invalid cart items']);
    exit;
}

// ✅ Validate payment method against active payment options
// Ensure PDO connection exists first
if (!isset($pdo)) {
    try {
        require_once 'database.php';
    } catch (Exception $e) {
        error_log("Error loading database.php: " . $e->getMessage());
    }
}

try {
    if (file_exists('settings_helper.php')) {
        require_once 'settings_helper.php';
    } else {
        throw new Exception('settings_helper.php not found');
    }
    
    if (file_exists('payment_api_helper.php')) {
        require_once 'payment_api_helper.php';
    }
    
    if (file_exists('shipping_api_helper.php')) {
        require_once 'shipping_api_helper.php';
    }
} catch (Exception $e) {
    error_log("Error loading helpers in cart_checkout: " . $e->getMessage());
    ob_clean();
    echo json_encode(['status'=>'error','message'=>'System error: Failed to load payment options - ' . $e->getMessage()]);
    exit;
} catch (Error $e) {
    error_log("Fatal error loading helpers in cart_checkout: " . $e->getMessage());
    ob_clean();
    echo json_encode(['status'=>'error','message'=>'Fatal error: ' . $e->getMessage()]);
    exit;
}

$payment_code = $_POST['payment_code'] ?? '';

// If payment_code is empty, try to extract from payment_method
if (empty($payment_code)) {
    if (!empty($payment_method)) {
        // Try to extract code from method name
        $payment_code = strtolower($payment_method);
        $payment_code = str_replace([' ', 'on', 'delivery'], '', $payment_code);
        if ($payment_code === 'cash') $payment_code = 'cod';
    } else {
        ob_clean();
        echo json_encode(['status'=>'error','message'=>'Payment method not specified']);
        exit;
    }
}

// Handle legacy payment codes (Cash -> cod)
if(strtolower($payment_code) === 'cash' || strtolower($payment_method) === 'cash') {
    $payment_code = 'cod';
    if (empty($payment_method)) {
        $payment_method = 'Cash on Delivery';
    }
}

// Get active payment options with error handling
try {
    $activePayments = getActivePaymentOptions();
    if (empty($activePayments) || !is_array($activePayments)) {
        // Fallback to default payment options
        $activePayments = [
            ['code' => 'cod', 'name' => 'Cash on Delivery'],
            ['code' => 'paypal', 'name' => 'PayPal']
        ];
    }
    $validPaymentCodes = array_column($activePayments, 'code');
    
    if(!in_array($payment_code, $validPaymentCodes)){
        error_log("Invalid payment code: $payment_code. Valid codes: " . implode(', ', $validPaymentCodes));
        ob_clean();
        echo json_encode(['status'=>'error','message'=>"Invalid payment method: $payment_code"]);
        exit;
    }
    
    // If payment_method is empty, get it from activePayments
    if (empty($payment_method)) {
        foreach ($activePayments as $pay) {
            if ($pay['code'] === $payment_code) {
                $payment_method = $pay['name'] ?? $payment_code;
                break;
            }
        }
        if (empty($payment_method)) {
            $payment_method = ucfirst($payment_code);
        }
    }
} catch (Exception $e) {
    error_log("Error validating payment method: " . $e->getMessage());
    ob_clean();
    echo json_encode(['status'=>'error','message'=>'Error validating payment method']);
    exit;
}

// Process payment transaction IDs for demo APIs
$gcash_transaction_id = $_POST['gcash_transaction_id'] ?? null;
$card_transaction_id = $_POST['card_transaction_id'] ?? null;

// ✅ Database connection
$conn = new mysqli("localhost","u296524640_pet_admin","Petpantry123","u296524640_pet_pantry");
if($conn->connect_error) {
    ob_clean();
    echo json_encode(['status'=>'error','message'=>'DB connection failed: ' . $conn->connect_error]);
    exit;
}

$address = "";

// ✅ Existing address
if(!empty($_POST['address_id'])) {
    $addr_id = intval($_POST['address_id']);
    $stmt = $conn->prepare("SELECT address FROM user_addresses WHERE id=? AND user_id=? LIMIT 1");
    $stmt->bind_param("ii", $addr_id, $user_id);
    $stmt->execute();
    $res = $stmt->get_result();
    if($row = $res->fetch_assoc()){
        $address = $row['address'];
    }
    $stmt->close();
}

// ✅ New address
if(empty($address) && !empty(trim($_POST['new_address'] ?? ''))) {
    $newAddr = trim($_POST['new_address']);
    $fullName = trim($_POST['new_fullName'] ?? "");

    // Save only if not duplicate
    $stmtAddr = $conn->prepare("SELECT id FROM user_addresses WHERE user_id=? AND address=? LIMIT 1");
    $stmtAddr->bind_param("is", $user_id, $newAddr);
    $stmtAddr->execute();
    $res = $stmtAddr->get_result();

    if ($res->num_rows === 0) {
        $stmtInsertAddr = $conn->prepare("INSERT INTO user_addresses (user_id, full_name, address, is_default) VALUES (?, ?, ?, 0)");
        $stmtInsertAddr->bind_param("iss", $user_id, $fullName, $newAddr);
        $stmtInsertAddr->execute();
        $stmtInsertAddr->close();
    }
    $stmtAddr->close();
    $address = $newAddr;
}

// ✅ Must have valid address
if(empty($address)){
    ob_clean();
    echo json_encode(['status'=>'error','message'=>'No valid address provided']);
    exit;
}

// ✅ Get promo code data if provided
$promo_id = isset($_POST['promo_id']) ? intval($_POST['promo_id']) : null;
$promo_code = isset($_POST['promo_code']) ? trim($_POST['promo_code']) : null;
$discount_amount = isset($_POST['discount_amount']) ? floatval($_POST['discount_amount']) : 0;

// ✅ Get shipping info
$shipping_code = $_POST['shipping_code'] ?? 'standard';
$shipping_fee = isset($_POST['shipping_fee']) ? floatval($_POST['shipping_fee']) : 0;

// ✅ Step 1: Insert one main order group (add columns if they don't exist)
// Check and add columns if needed (MySQL doesn't support IF NOT EXISTS in ALTER TABLE)
try {
    // Check if payment_code column exists
    $result = $conn->query("SHOW COLUMNS FROM order_groups LIKE 'payment_code'");
    if ($result && $result->num_rows == 0) {
        $conn->query("ALTER TABLE order_groups ADD COLUMN payment_code VARCHAR(50) NULL");
    }
    
    // Check if shipping_code column exists
    $result = $conn->query("SHOW COLUMNS FROM order_groups LIKE 'shipping_code'");
    if ($result && $result->num_rows == 0) {
        $conn->query("ALTER TABLE order_groups ADD COLUMN shipping_code VARCHAR(50) NULL");
    }
    
    // Check if shipping_fee column exists
    $result = $conn->query("SHOW COLUMNS FROM order_groups LIKE 'shipping_fee'");
    if ($result && $result->num_rows == 0) {
        $conn->query("ALTER TABLE order_groups ADD COLUMN shipping_fee DECIMAL(10,2) DEFAULT 0.00");
    }
} catch (mysqli_sql_exception $e) {
    // Columns might already exist or there's an error, log and continue
    error_log("Error checking/adding columns: " . $e->getMessage());
} catch (Exception $e) {
    // Any other error, log and continue
    error_log("Error checking/adding columns (general): " . $e->getMessage());
}

// Prepare the insert statement - handle columns that might not exist
try {
    // Ensure all values are properly set
    $payment_code = $payment_code ?? '';
    $payment_method = $payment_method ?? '';
    $shipping_code = $shipping_code ?? 'standard';
    $shipping_fee = $shipping_fee ?? 0.00;
    $paypal_order_id = $paypal_order_id ?? null;
    $promo_code = $promo_code ?? null;
    $discount_amount = $discount_amount ?? 0.00;
    
    // Convert nulls to empty strings for binding
    $paypal_order_id_bind = $paypal_order_id ? $paypal_order_id : '';
    $promo_code_bind = $promo_code ? $promo_code : '';
    
    $stmtGroup = $conn->prepare("INSERT INTO order_groups (user_id, status, address, payment_method, payment_code, shipping_code, shipping_fee, paypal_order_id, promo_code, discount_amount, created_at) 
                                 VALUES (?, 'Pending', ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
    if (!$stmtGroup) {
        throw new Exception("Prepare failed: " . $conn->error);
    }
    
    // Bind parameters: i (int), s (string), s (string), s (string), s (string), s (string), d (double), s (string), s (string), d (double)
    // user_id, status ('Pending'), address, payment_method, payment_code, shipping_code, shipping_fee, paypal_order_id, promo_code, discount_amount
    // Actually: status is hardcoded, so we have 9 params: user_id, address, payment_method, payment_code, shipping_code, shipping_fee, paypal_order_id, promo_code, discount_amount
    $stmtGroup->bind_param("issssdssd", 
        $user_id,        // i
        $address,        // s
        $payment_method, // s
        $payment_code,   // s
        $shipping_code,  // s
        $shipping_fee,   // d
        $paypal_order_id_bind, // s
        $promo_code_bind,      // s
        $discount_amount       // d
    );
    
    if (!$stmtGroup->execute()) {
        throw new Exception("Execute failed: " . $stmtGroup->error . " | SQL State: " . $conn->sqlstate);
    }
    
    $order_group_id = $stmtGroup->insert_id;
    $stmtGroup->close();
    
    if(!$order_group_id){
        throw new Exception("Failed to get order_group_id - insert_id is 0 or false");
    }
} catch (Exception $e) {
    error_log("Error creating order group: " . $e->getMessage());
    ob_clean();
    echo json_encode([
        'status'=>'error',
        'message'=>'Failed to create order: ' . $e->getMessage(),
        'debug' => [
            'payment_code' => $payment_code ?? 'NOT SET',
            'shipping_code' => $shipping_code ?? 'NOT SET',
            'user_id' => $user_id ?? 'NOT SET'
        ]
    ]);
    exit;
} catch (Error $e) {
    error_log("Fatal error creating order group: " . $e->getMessage());
    ob_clean();
    echo json_encode(['status'=>'error','message'=>'Fatal error creating order: ' . $e->getMessage()]);
    exit;
}

// ✅ Step 2: Loop through cart items → Insert into orders
foreach($cart_ids as $cart_id){
    $stmt = $conn->prepare("SELECT product_id, quantity FROM cart WHERE id=? AND user_id=?");
    $stmt->bind_param("ii",$cart_id,$user_id);
    $stmt->execute();
    $item = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if(!$item) continue;

    $product_id = $item['product_id'];
    $quantity = $item['quantity'];

    // Get product price + stock
    $stmtPrice = $conn->prepare("SELECT price, stock FROM products WHERE id=?");
    $stmtPrice->bind_param("i",$product_id);
    $stmtPrice->execute();
    $prod = $stmtPrice->get_result()->fetch_assoc();
    $stmtPrice->close();

    if(!$prod || $prod['stock'] < $quantity) continue;

    $price = $prod['price'];

    // Reduce stock
    $stmt2 = $conn->prepare("UPDATE products SET stock = stock - ? WHERE id=? AND stock >= ?");
    $stmt2->bind_param("iii",$quantity,$product_id,$quantity);
    $stmt2->execute();
    $affected = $stmt2->affected_rows;
    $stmt2->close();

    if($affected > 0){ 
        // ✅ Insert order item (child of order group)
        $stmt3 = $conn->prepare("INSERT INTO orders (order_group_id, product_id, quantity, price) 
                                 VALUES (?, ?, ?, ?)");
        $stmt3->bind_param("iiid",$order_group_id,$product_id,$quantity,$price);
        $stmt3->execute();
        $stmt3->close();

        // ✅ Delete from cart
        $stmt4 = $conn->prepare("DELETE FROM cart WHERE id=? AND user_id=?");
        $stmt4->bind_param("ii",$cart_id,$user_id);
        $stmt4->execute();
        $stmt4->close();
    }
}
// --- Add Notification for the customer ---
$stmtNotif = $conn->prepare("
    SELECT p.name, o.quantity
    FROM orders o
    JOIN products p ON o.product_id = p.id
    WHERE o.order_group_id = ?
    LIMIT 3
");
$stmtNotif->bind_param("i", $order_group_id);
$stmtNotif->execute();
$resNotif = $stmtNotif->get_result();
$items = $resNotif->fetch_all(MYSQLI_ASSOC);
$stmtNotif->close();

$itemNames = array_map(function($i){
    return $i['quantity']."x ".$i['name'];
}, $items);

$message = "You ordered: " . implode(", ", $itemNames);
if(count($items) >= 3){
    $message .= " and more...";
}

$stmtInsertNotif = $conn->prepare("
    INSERT INTO notifications (user_id, order_group_id, account_type, message, type, created_at)
    VALUES (?, ?, 'customer', ?, 'order', NOW())
");
$stmtInsertNotif->bind_param("iis", $user_id, $order_group_id, $message);
$stmtInsertNotif->execute();
$stmtInsertNotif->close();

// ✅ Return success
// Clear any unexpected output
ob_clean();
echo json_encode(['status'=>'success','message'=>'Checkout complete','order_group_id'=>$order_group_id]);
ob_end_flush();
exit;
?>
