<?php
// Error reporting (disabled in production)
error_reporting(E_ALL);
ini_set('display_errors', 0); // Hide errors in production
ini_set('log_errors', 1); // But still log them

// Catch all errors
register_shutdown_function(function() {
    $error = error_get_last();
    if ($error !== NULL && in_array($error['type'], [E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR, E_PARSE])) {
        echo "<h1>Fatal Error Detected:</h1>";
        echo "<pre>";
        echo "Type: " . $error['type'] . "\n";
        echo "Message: " . $error['message'] . "\n";
        echo "File: " . $error['file'] . "\n";
        echo "Line: " . $error['line'] . "\n";
        echo "</pre>";
    }
});

session_start();
if(!isset($_SESSION['user_id'])){
    header('Location: Login_and_creating_account_fixed.php');
    exit;
}
$user_id = $_SESSION['user_id'];

// DB connection (mysqli for cart operations)
$conn = new mysqli("localhost","u296524640_pet_admin","Petpantry123","u296524640_pet_pantry");
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

// Also load PDO connection for settings_helper (it requires PDO)
if (!isset($pdo)) {
    try {
        require_once 'database.php'; // This sets $pdo
    } catch (Exception $e) {
        // If database.php fails, create minimal PDO connection
        try {
            $pdo = new PDO("mysql:host=localhost;dbname=u296524640_pet_pantry;charset=utf8", "u296524640_pet_admin", "Petpantry123");
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            // PDO connection failed, but continue with defaults
            $pdo = null;
        }
    }
}

// Load settings helper with error handling
try {
    if (file_exists('settings_helper.php')) {
        require_once 'settings_helper.php';
    }
} catch (Exception $e) {
    error_log("Error loading settings_helper.php: " . $e->getMessage());
} catch (Error $e) {
    error_log("Fatal error loading settings_helper.php: " . $e->getMessage());
}

try {
    if (file_exists('shipping_api_helper.php')) {
        require_once 'shipping_api_helper.php';
    }
} catch (Exception $e) {
    error_log("Error loading shipping_api_helper.php: " . $e->getMessage());
}

try {
    if (file_exists('payment_api_helper.php')) {
        require_once 'payment_api_helper.php';
    }
} catch (Exception $e) {
    error_log("Error loading payment_api_helper.php: " . $e->getMessage());
}

// Get platform settings with error handling
try {
    if (function_exists('getDefaultCurrency')) {
        $currency = getDefaultCurrency();
    } else {
        $currency = 'PHP';
    }
    if (function_exists('getCurrencySymbol')) {
        $currencySymbol = getCurrencySymbol();
    } else {
        $currencySymbol = '₱';
    }
} catch (Exception $e) {
    error_log("Error getting currency settings: " . $e->getMessage());
    $currency = 'PHP';
    $currencySymbol = '₱';
} catch (Error $e) {
    error_log("Fatal error getting currency settings: " . $e->getMessage());
    $currency = 'PHP';
    $currencySymbol = '₱';
}

// Safely get payment and shipping options with error handling
if (function_exists('getActivePaymentOptions')) {
    try {
        $paymentOptions = getActivePaymentOptions();
        if (empty($paymentOptions) || !is_array($paymentOptions)) {
            $paymentOptions = [
                ['id' => 1, 'name' => 'Cash on Delivery', 'code' => 'cod', 'icon' => '💵', 'is_active' => 1, 'display_order' => 1],
                ['id' => 2, 'name' => 'PayPal', 'code' => 'paypal', 'icon' => '💳', 'is_active' => 1, 'display_order' => 2]
            ];
        }
    } catch (Exception $e) {
        $paymentOptions = [
            ['id' => 1, 'name' => 'Cash on Delivery', 'code' => 'cod', 'icon' => '💵', 'is_active' => 1, 'display_order' => 1],
            ['id' => 2, 'name' => 'PayPal', 'code' => 'paypal', 'icon' => '💳', 'is_active' => 1, 'display_order' => 2]
        ];
    } catch (Error $e) {
        $paymentOptions = [
            ['id' => 1, 'name' => 'Cash on Delivery', 'code' => 'cod', 'icon' => '💵', 'is_active' => 1, 'display_order' => 1],
            ['id' => 2, 'name' => 'PayPal', 'code' => 'paypal', 'icon' => '💳', 'is_active' => 1, 'display_order' => 2]
        ];
    }
} else {
    $paymentOptions = [
        ['id' => 1, 'name' => 'Cash on Delivery', 'code' => 'cod', 'icon' => '💵', 'is_active' => 1, 'display_order' => 1],
        ['id' => 2, 'name' => 'PayPal', 'code' => 'paypal', 'icon' => '💳', 'is_active' => 1, 'display_order' => 2]
    ];
}

if (function_exists('getActiveShippingOptions')) {
    try {
        $shippingOptions = getActiveShippingOptions();
        if (empty($shippingOptions) || !is_array($shippingOptions)) {
            $shippingOptions = [
                ['id' => 1, 'name' => 'Standard Shipping', 'code' => 'standard', 'fee' => 50.00, 'is_active' => 1, 'display_order' => 1, 'estimated_days' => 3]
            ];
        }
    } catch (Exception $e) {
        $shippingOptions = [
            ['id' => 1, 'name' => 'Standard Shipping', 'code' => 'standard', 'fee' => 50.00, 'is_active' => 1, 'display_order' => 1, 'estimated_days' => 3]
        ];
    } catch (Error $e) {
        $shippingOptions = [
            ['id' => 1, 'name' => 'Standard Shipping', 'code' => 'standard', 'fee' => 50.00, 'is_active' => 1, 'display_order' => 1, 'estimated_days' => 3]
        ];
    }
} else {
    $shippingOptions = [
        ['id' => 1, 'name' => 'Standard Shipping', 'code' => 'standard', 'fee' => 50.00, 'is_active' => 1, 'display_order' => 1, 'estimated_days' => 3]
    ];
}

// Get free shipping threshold safely
try {
    if (function_exists('getSetting')) {
        $freeShippingThreshold = (float)getSetting('free_shipping_threshold', 0);
    } else {
        $freeShippingThreshold = 0;
    }
} catch (Exception $e) {
    $freeShippingThreshold = 0;
} catch (Error $e) {
    $freeShippingThreshold = 0;
}

// Fetch cart items with error handling
$cartItems = [];
try {
    $sql = "SELECT c.id as cart_id, p.id as product_id, p.name, p.price, p.image, c.quantity, p.stock
            FROM cart c
            JOIN products p ON c.product_id=p.id
            WHERE c.user_id=?";
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param("i",$user_id);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($res) {
            $cartItems = $res->fetch_all(MYSQLI_ASSOC);
        }
    }
} catch (Exception $e) {
    error_log("Error fetching cart items: " . $e->getMessage());
    $cartItems = [];
} catch (Error $e) {
    error_log("Fatal error fetching cart items: " . $e->getMessage());
    $cartItems = [];
}

// Fetch saved addresses with error handling
$addresses = [];
try {
    $addrStmt = $conn->prepare("SELECT id, full_name, address, is_default FROM user_addresses WHERE user_id=?");
    if ($addrStmt) {
        $addrStmt->bind_param("i",$user_id);
        $addrStmt->execute();
        $addrRes = $addrStmt->get_result();
        if ($addrRes) {
            $addresses = $addrRes->fetch_all(MYSQLI_ASSOC);
        }
    }
} catch (Exception $e) {
    error_log("Error fetching addresses: " . $e->getMessage());
    $addresses = [];
} catch (Error $e) {
    error_log("Fatal error fetching addresses: " . $e->getMessage());
    $addresses = [];
}

// Fetch active promotions with error handling
$activePromos = [];
try {
    $promoStmt = $conn->prepare("
        SELECT * FROM promos 
        WHERE is_active = 1 
        AND (start_date IS NULL OR start_date <= NOW())
        AND (end_date IS NULL OR end_date >= NOW())
        ORDER BY created_at DESC
        LIMIT 5
    ");
    if ($promoStmt) {
        $promoStmt->execute();
        $result = $promoStmt->get_result();
        if ($result) {
            $activePromos = $result->fetch_all(MYSQLI_ASSOC);
        }
    }
} catch (Exception $e) {
    error_log("Error fetching promotions: " . $e->getMessage());
    $activePromos = [];
} catch (Error $e) {
    error_log("Fatal error fetching promotions: " . $e->getMessage());
    $activePromos = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Cart</title>
<script src="https://cdn.tailwindcss.com"></script>
<style>
/* Body & Layout */
html, body { 
    height: 100%;
    margin: 0;
    padding: 0;
    overflow-x: hidden;
    max-width: 100vw;
    width: 100%;
}
body {
    font-family: Arial, sans-serif;
    background: #f5f5f5;
}
main { 
    width: 100%;
    max-width: 100vw;
    overflow-x: hidden;
    min-height: 60vh;
}

/* Cart Container */
.cart-container {
    max-width: 1000px;
    width: 95%;
    margin: 100px auto 30px auto;
    background: #fff;
    border-radius: 10px;
    padding: 20px;
    box-shadow: 0 2px 6px rgba(0,0,0,0.1);
}

/* Cart Header & Items */
.cart-header,
.cart-item {
    display: grid;
    grid-template-columns: 50px 2fr 1fr 1fr 1fr 70px;
    align-items: center;
    padding: 10px 0;
}
.cart-header {
    border-bottom: 2px solid #ddd;
    font-weight: bold;
    text-align: center;
}
.cart-item {
    border-bottom: 1px solid #eee;
}
.cart-item img {
    width: 60px;
    height: 60px;
    object-fit: cover;
    border-radius: 8px;
}
.cart-item input[type="number"] {
    width: 60px;
    padding: 5px;
}
.cart-item input[type="checkbox"] {
    transform: scale(1.2);
}

/* Buttons */
.remove-btn {
    background: none;
    border: none;
    color: #ff6f00;
    cursor: pointer;
    font-weight: bold;
    text-align: center;
    transition: all 0.2s;
}
.remove-btn:hover {
    text-decoration: underline;
    color: #e65c00;
}
.checkout-btn {
    background: #ff6f00;
    color: #fff;
    padding: 12px 25px;
    border: none;
    border-radius: 8px;
    font-size: 16px;
    cursor: pointer;
}
.checkout-btn:hover {
    background: #e65c00;
}

/* Cart Summary */
.cart-summary {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 15px;
    font-size: 18px;
    background: #f9fafb;
    border-radius: 8px;
    margin-top: 20px;
    gap: 15px;
}
.cart-summary div {
    display: flex;
    gap: 4px;
    align-items: center;
}

/* Receipt Modal */
.receipt-modal {
    max-width: 600px;
    width: 100%;
    background: #fff;
    border-radius: 12px;
    padding: 20px;
}
.receipt-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-bottom: 1px solid #eee;
    padding: 10px 0;
}
.receipt-item img {
    width: 50px;
    height: 50px;
    object-fit: cover;
    border-radius: 6px;
    margin-right: 10px;
}
.receipt-item-details { flex: 1; }
.receipt-total {
    display: flex;
    justify-content: space-between;
    font-weight: bold;
    padding-top: 10px;
    font-size: 16px;
}
.receipt-info {
    margin-top: 15px;
    font-size: 14px;
}
.receipt-info strong {
    display: block;
    margin-bottom: 2px;
}

/* Responsive for mobile */
@media (max-width: 768px) {
  .cart-header,
  .cart-item {
    display: grid;
    grid-template-columns: 40px 1fr;
    grid-template-rows: auto auto auto;
    gap: 5px;
    text-align: left;
  }

  .cart-item div:nth-child(3),
  .cart-item div:nth-child(4),
  .cart-item div:nth-child(5),
  .cart-item div:nth-child(6) {
    grid-column: 2 / 3;
  }

  .cart-item img {
    width: 50px;
    height: 50px;
  }

  .cart-item input[type="number"] {
    width: 50px;
  }

  .cart-summary {
    flex-direction: column;
    align-items: flex-start;
    font-size: 14px;
    gap: 8px;
  }

  .cart-summary div {
    flex: 1 1 100%;
  }

  .checkout-btn {
    width: 100%;
    text-align: center;
  }

  /* Modals */
  #checkoutModal > div,
  #receiptModal > div {
    width: 95%;
    padding: 15px;
  }

  .receipt-item {
    flex-direction: column;
    align-items: flex-start;
  }

  .receipt-item img {
    margin-bottom: 5px;
  }

  .receipt-total {
    flex-direction: column;
    gap: 5px;
    align-items: flex-start;
  }
}

</style>

</head>
<body>
<?php 
// Ensure settings_helper is loaded before header.php includes it
if (!function_exists('getCurrencySymbol')) {
    try {
        if (file_exists('settings_helper.php')) {
            require_once 'settings_helper.php';
        }
    } catch (Exception $e) {
        echo "<p style='color:red;'>Error pre-loading settings: " . htmlspecialchars($e->getMessage()) . "</p>";
        error_log("Error pre-loading settings for header: " . $e->getMessage());
    }
}

// Try to include header with error catching
try {
    ob_start();
    include 'header.php';
    $header_output = ob_get_clean();
    echo $header_output;
} catch (Exception $e) {
    echo "<p style='color:red;'>Error including header.php: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p>File: " . htmlspecialchars($e->getFile()) . " Line: " . $e->getLine() . "</p>";
} catch (Error $e) {
    echo "<p style='color:red;'>Fatal error in header.php: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p>File: " . htmlspecialchars($e->getFile()) . " Line: " . $e->getLine() . "</p>";
}
?>
<main style="padding-top: 100px;">
<div class="cart-container">
  <div class="cart-header">
    <div><input type="checkbox" id="select-all"></div>
    <div>Product</div><div>Price</div><div>Quantity</div><div>Total</div><div>Action</div>
  </div>

  <?php foreach($cartItems as $item): ?>
  <div class="cart-item" data-cart-id="<?= $item['cart_id'] ?>">
    <div><input type="checkbox" class="item-check"></div>
    <div style="display:flex;align-items:center;gap:10px;">
      <img src="<?= htmlspecialchars($item['image'] ?: 'https://via.placeholder.com/60') ?>" alt="">
      <span class="product-name"><?= htmlspecialchars($item['name']) ?></span>
    </div>
    <div><?= function_exists('formatCurrency') ? formatCurrency($item['price']) : ($currencySymbol . number_format($item['price'], 2)) ?></div>
    <div><input type="number" class="qty" value="<?= $item['quantity'] ?>" min="1" max="<?= $item['stock'] ?>"></div>
    <div class="item-total"><?= function_exists('formatCurrency') ? formatCurrency($item['price']*$item['quantity']) : ($currencySymbol . number_format($item['price']*$item['quantity'], 2)) ?></div>
    <div><button class="remove-btn">Delete</button></div>
  </div>
  <?php endforeach; ?>

  <div class="cart-summary">
    <div><strong>Selected Items: <span id="selected-count">0</span></strong></div>
    <div><strong>Subtotal: <?= $currencySymbol ?><span id="grand-total">0</span></strong></div>
    <div><strong>Shipping: <?= $currencySymbol ?><span id="shipping-fee">0</span></strong></div>
    <div><strong>Total: <?= $currencySymbol ?><span id="final-total">0</span></strong></div>
    <button class="checkout-btn">Check out (0)</button>
  </div>
</div>

<!-- Checkout Modal -->
<div id="checkoutModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex items-center justify-center">
  <div class="bg-white rounded-lg w-11/12 max-w-lg p-6 relative">
    <h2 class="text-xl font-bold mb-4">Checkout</h2>
    <form id="checkoutForm" class="space-y-4">
      <div>
        <label class="block font-semibold">Choose Address</label>
        <?php if(count($addresses) > 0): ?>
          <select id="addressSelect" name="address_id" class="w-full p-2 border rounded">
            <?php foreach($addresses as $addr): ?>
              <option value="<?= $addr['id'] ?>" <?= $addr['is_default']?'selected':'' ?>>
                <?= htmlspecialchars($addr['full_name']).' - '.htmlspecialchars($addr['address']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        <?php else: ?>
          <p class="text-sm text-gray-500">No saved addresses yet. Please add one.</p>
        <?php endif; ?>
      </div>
      <div>
        <label class="block font-semibold">Or Add New Address</label>
        <input type="text" name="new_fullName" placeholder="Full Name" class="w-full p-2 border rounded mb-2">
        <textarea name="new_address" placeholder="Delivery Address" class="w-full p-2 border rounded" rows="3"></textarea>
      </div>
      <div>
        <label class="block font-semibold">Shipping Method</label>
        <select id="shippingMethod" name="shippingMethod" class="w-full p-2 border rounded" required>
          <option value="">Select Shipping Method</option>
          <?php foreach ($shippingOptions as $shipping): ?>
            <option value="<?= htmlspecialchars($shipping['code']) ?>" 
                    data-fee="<?= $shipping['fee'] ?>"
                    <?= $shipping['code'] === 'standard' ? 'selected' : '' ?>>
              <?= htmlspecialchars($shipping['name']) ?> - <?= function_exists('formatCurrency') ? formatCurrency($shipping['fee']) : ($currencySymbol . number_format($shipping['fee'], 2)) ?>
              <?php if (!empty($shipping['estimated_days'])): ?>
                (<?= $shipping['estimated_days'] ?> days)
              <?php endif; ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div>
        <label class="block font-semibold">Payment Method</label>
        <select id="paymentMethod" name="paymentMethod" class="w-full p-2 border rounded" required>
          <option value="">Select Payment Method</option>
          <?php foreach ($paymentOptions as $payment): ?>
            <option value="<?= htmlspecialchars($payment['code']) ?>" 
                    data-name="<?= htmlspecialchars($payment['name']) ?>">
              <?= htmlspecialchars($payment['icon']) ?> <?= htmlspecialchars($payment['name']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <!-- PayPal Container -->
      <div id="paypal-container" class="hidden mt-4">
        <div id="paypal-button-container"></div>
      </div>

      <!-- GCash Container -->
      <div id="gcash-container" class="hidden mt-4 p-4 bg-purple-50 border border-purple-200 rounded">
        <div class="mb-3">
          <strong class="text-purple-700">📱 GCash Payment (Demo Mode)</strong>
        </div>
        <div class="text-sm text-gray-600 mb-3">
          <p>Amount to pay: <strong id="gcash-amount">₱0.00</strong></p>
          <p class="text-xs mt-2">💡 In demo mode, payment will be simulated. For production, integrate actual GCash API.</p>
        </div>
        <button type="button" id="gcash-pay-btn" 
                class="w-full px-4 py-2 bg-purple-600 text-white rounded hover:bg-purple-700">
          Pay with GCash
        </button>
      </div>

      <!-- Credit/Debit Card Container -->
      <div id="card-container" class="hidden mt-4 p-4 bg-blue-50 border border-blue-200 rounded">
        <div class="mb-3">
          <strong class="text-blue-700">💳 Card Payment (Demo Mode)</strong>
        </div>
        <div class="text-sm text-gray-600 mb-3">
          <p>💡 Use test card: <code class="bg-white px-2 py-1 rounded">4242 4242 4242 4242</code></p>
          <p class="text-xs">Expiry: Any future date | CVC: Any 3 digits</p>
        </div>
        <div id="card-element" class="mb-3">
          <!-- Stripe Elements will be inserted here if Stripe is integrated -->
          <div class="text-sm text-gray-500 p-3 bg-white rounded border">
            💳 Card payment form will appear here when Stripe API keys are configured.
          </div>
        </div>
        <button type="button" id="card-pay-btn" 
                class="w-full px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
          Pay with Card (Demo)
        </button>
      </div>

      <!-- Bank Transfer Container -->
      <div id="bank-container" class="hidden mt-4 p-4 bg-gray-50 border border-gray-200 rounded">
        <div class="mb-3">
          <strong class="text-gray-700">🏦 Bank Transfer Instructions</strong>
        </div>
        <div id="bank-details" class="text-sm text-gray-700 space-y-1">
          <!-- Bank details will be loaded via API -->
        </div>
        <div class="mt-3 text-xs text-gray-500">
          <p>⚠️ After transferring, your order will be held until payment is verified by admin.</p>
        </div>
      </div>

      <div class="flex justify-end space-x-2">
        <button type="button" id="cancelCheckout" class="px-4 py-2 border rounded">Cancel</button>
        <button type="button" id="toReceiptBtn" class="px-4 py-2 bg-orange-500 text-white rounded">Next</button>
      </div>
    </form>
    <button id="closeCheckoutModal" class="absolute top-2 right-2">&times;</button>
  </div>
</div>

<!-- Receipt Modal -->
<div id="receiptModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex items-center justify-center">
  <div class="receipt-modal relative">
    <h2 class="text-xl font-bold mb-4">Order Summary</h2>
    <div id="receiptItems" class="max-h-60 overflow-y-auto"></div>

    <!-- Promo Code Section -->
    <div class="bg-gradient-to-r from-orange-50 to-yellow-50 border-2 border-orange-200 rounded-lg p-4 mb-4">
      <h3 class="font-bold text-gray-800 mb-3 flex items-center gap-2">
        🎁 Have a Promo Code?
      </h3>
      
      <!-- Manual Input -->
      <div class="flex gap-2 mb-3">
        <input type="text" 
               id="promoCodeInput" 
               placeholder="Enter code (e.g., WELCOME10)" 
               class="flex-1 border-2 border-orange-300 rounded-lg p-2 uppercase focus:border-orange-500 focus:outline-none"
               style="text-transform: uppercase;">
        <button onclick="applyPromoCode()" 
                class="bg-orange-500 hover:bg-orange-600 text-white px-6 py-2 rounded-lg font-semibold transition-colors whitespace-nowrap">
          Apply
        </button>
      </div>
      
      <div id="promoMessage" class="text-sm mb-2"></div>
      
      <!-- Available Promotions (Clickable) -->
      <?php if (!empty($activePromos)): ?>
        <div class="border-t border-orange-200 pt-3 mt-3">
          <p class="text-xs text-gray-600 mb-2">Or click to apply:</p>
          <div class="space-y-2 max-h-48 overflow-y-auto">
            <?php foreach ($activePromos as $promo): ?>
              <div class="bg-white border border-orange-200 rounded-lg p-2 cursor-pointer hover:bg-orange-50 transition-colors"
                   onclick="applyPromoCode('<?php echo htmlspecialchars($promo['code']); ?>')">
                <div class="flex items-center justify-between">
                  <div class="flex-1">
                    <div class="flex items-center gap-2">
                      <span class="font-mono font-bold text-orange-600 text-sm">
                        <?php echo htmlspecialchars($promo['code']); ?>
                      </span>
                      <span class="bg-orange-500 text-white px-2 py-0.5 rounded text-xs font-bold">
                        <?php 
                          echo $promo['discount_type'] === 'percent' 
                            ? $promo['discount_value'] . '% OFF' 
                            : (function_exists('formatCurrency') ? formatCurrency($promo['discount_value']) : ($currencySymbol . number_format($promo['discount_value'], 2))) . ' OFF';
                        ?>
                      </span>
                    </div>
                    <p class="text-xs text-gray-600 mt-0.5">
                      <?php echo htmlspecialchars($promo['title']); ?>
                      <?php if ($promo['min_purchase'] > 0): ?>
                        • Min: <?php echo function_exists('formatCurrency') ? formatCurrency($promo['min_purchase']) : ($currencySymbol . number_format($promo['min_purchase'], 2)); ?>
                      <?php endif; ?>
                    </p>
                  </div>
                  <svg class="w-5 h-5 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                  </svg>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
      <?php endif; ?>
    </div>

    <div class="receipt-total">
      <span>Subtotal</span><span><?= $currencySymbol ?><span id="receiptSubtotal">0</span></span>
    </div>
    
    <!-- Discount Row (hidden by default) -->
    <div id="discountRow" class="receipt-total text-green-600 hidden">
      <span>Discount (<span id="appliedPromoCode"></span>)</span>
      <span>-<?= $currencySymbol ?><span id="discountAmount">0.00</span></span>
    </div>
    
    <div class="receipt-total">
      <span>Shipping</span><span><?= $currencySymbol ?><span id="receiptShipping">0</span></span>
    </div>
    <div class="receipt-total border-t-2 border-gray-300 pt-2 text-lg">
      <span class="font-bold">Total</span><span class="font-bold"><?= $currencySymbol ?><span id="receiptTotal"></span></span>
    </div>

    <div class="receipt-info">
      <strong>Delivery Address</strong>
      <p id="receiptAddress"></p>
      <strong>Payment Method</strong>
      <p id="receiptPayment"></p>
    </div>

    <div class="flex justify-end gap-2 mt-4">
      <button id="backToCheckout" class="px-4 py-2 border rounded">Back</button>
      <button id="confirmReceipt" class="px-4 py-2 bg-orange-500 text-white rounded">Place Order</button>
    </div>
  </div>
</div>

</main>
<?php include 'footer.php'; ?>

<script src="https://www.paypal.com/sdk/js?client-id=AVeMJ4UPAQ0Mfsuu1uQwWLXWFfypk1iTTJloTyvUANRj3g_ACcg5o-qzZY1by8-a4qMan8u78Q1NkTNm&currency=<?= $currency ?>"></script>
<script>
  // Pass PHP settings to JavaScript
  window.PLATFORM_SETTINGS = {
    currency: '<?= addslashes($currency) ?>',
    currencySymbol: '<?= addslashes($currencySymbol) ?>',
    freeShippingThreshold: <?= (float)$freeShippingThreshold ?>,
    shippingOptions: <?= json_encode($shippingOptions, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>
  };
  
  // Debug: Check if settings loaded
  console.log('PLATFORM_SETTINGS loaded:', window.PLATFORM_SETTINGS);
</script>

<script>
document.addEventListener("DOMContentLoaded", () => {
  console.log('Cart script loading...');
  
  // Get default shipping fee from selected option
  const getShippingFee = () => {
    const shippingSelect = document.getElementById("shippingMethod");
    if (shippingSelect && shippingSelect.selectedOptions[0]) {
      const fee = parseFloat(shippingSelect.selectedOptions[0].dataset.fee) || 0;
      // Check free shipping threshold
      const grandTotalEl = document.getElementById("grand-total");
      const subtotal = grandTotalEl ? parseFloat(grandTotalEl.textContent.replace(/[^\d.]/g, '')) || 0 : 0;
      if (window.PLATFORM_SETTINGS?.freeShippingThreshold > 0 && subtotal >= window.PLATFORM_SETTINGS.freeShippingThreshold) {
        return 0;
      }
      return fee;
    }
    return 50; // Fallback
  };
  
  let SHIPPING_FEE = getShippingFee();

  // DOM Elements - with null checks
  const selectAll = document.getElementById("select-all");
  const checkoutBtn = document.querySelector(".checkout-btn");
  const selectedCount = document.getElementById("selected-count");
  const grandTotal = document.getElementById("grand-total");
  const shippingElem = document.getElementById("shipping-fee");
  const finalTotal = document.getElementById("final-total");

  // Debug: Check if elements exist
  if (!selectAll) console.warn('select-all element not found');
  if (!checkoutBtn) console.warn('checkout-btn element not found');
  if (!selectedCount) console.warn('selected-count element not found');
  if (!grandTotal) console.warn('grand-total element not found');
  if (!shippingElem) console.warn('shipping-fee element not found');
  if (!finalTotal) console.warn('final-total element not found');

  const headerCartBadge = document.querySelector('a[aria-label="Cart"] span');
  const checkoutModal = document.getElementById("checkoutModal");
  const receiptModal = document.getElementById("receiptModal");
  const toReceiptBtn = document.getElementById("toReceiptBtn");
  const backToCheckout = document.getElementById("backToCheckout");
  const confirmReceipt = document.getElementById("confirmReceipt");
  const checkoutForm = document.getElementById("checkoutForm");

  const receiptItems = document.getElementById("receiptItems");
  const receiptAddress = document.getElementById("receiptAddress");
  const receiptPayment = document.getElementById("receiptPayment");
  const receiptTotal = document.getElementById("receiptTotal");
  const receiptSubtotal = document.getElementById("receiptSubtotal");
  const receiptShipping = document.getElementById("receiptShipping");

  const paymentMethod = document.getElementById("paymentMethod");
  const paypalContainer = document.getElementById("paypal-container");

  const formatCurrency = num => {
    const symbol = window.PLATFORM_SETTINGS?.currencySymbol || '₱';
    return `${symbol}${num.toLocaleString(undefined, {minimumFractionDigits:2, maximumFractionDigits:2})}`;
  };

  // Promo code variables
  let appliedPromo = null;
  let originalSubtotal = 0;

  const getSelectedCartItems = () => 
    Array.from(document.querySelectorAll(".cart-item .item-check:checked:not(:disabled)"))
      .map(checkbox => checkbox.closest(".cart-item"));

  function updateCart() {
    try {
      let count = 0, subtotal = 0;
      
      document.querySelectorAll(".cart-item").forEach(item => {
        const checkbox = item.querySelector(".item-check");
        const qtyInput = item.querySelector(".qty");
        const itemTotal = item.querySelector(".item-total");
        
        if (!qtyInput || !checkbox) return;
        
        const qty = Math.min(Math.max(parseInt(qtyInput.value) || 1, 1), parseInt(qtyInput.max) || 999);
        qtyInput.value = qty;

        // Get price from the price column (3rd child div, index 2)
        const priceEl = item.children[2];
        const price = priceEl ? parseFloat(priceEl.textContent.replace(/[₱,]/g, "")) || 0 : 0;
        
        if (itemTotal) {
          itemTotal.textContent = formatCurrency(price * qty);
        }

        if (checkbox.checked && !checkbox.disabled) {
          count++;
          subtotal += price * qty;
        }

        if (parseInt(qtyInput.max) === 0) {
          qtyInput.disabled = true;
          checkbox.disabled = true;
        }
      });

      // Update display elements with null checks
      if (selectedCount) selectedCount.textContent = count;
      if (grandTotal) grandTotal.textContent = subtotal.toLocaleString();
      
      SHIPPING_FEE = getShippingFee();
      const shipping = subtotal > 0 ? SHIPPING_FEE : 0;
      
      if (shippingElem) shippingElem.textContent = shipping.toLocaleString();
      if (finalTotal) finalTotal.textContent = (subtotal + shipping).toLocaleString();
      if (checkoutBtn) checkoutBtn.textContent = `Check out (${count})`;
      if (toReceiptBtn) toReceiptBtn.disabled = count === 0;

      if (headerCartBadge) {
        const totalQty = Array.from(document.querySelectorAll(".cart-item .qty")).reduce((sum, q) => {
          return sum + (parseInt(q.value) || 0);
        }, 0);
        headerCartBadge.textContent = totalQty;
      }
    } catch (e) {
      console.error('Error in updateCart:', e);
    }
  }

  // Attach event listeners with error handling
  try {
    document.querySelectorAll(".item-check").forEach(cb => {
      if (cb) {
        cb.addEventListener("change", updateCart);
      }
    });
    
    if (selectAll) {
      selectAll.addEventListener("change", () => {
        document.querySelectorAll(".item-check").forEach(cb => {
          if (cb && !cb.disabled) {
            cb.checked = selectAll.checked;
          }
        });
        updateCart();
      });
    }
  } catch (e) {
    console.error('Error attaching checkbox listeners:', e);
  }

  // Quantity input handlers
  try {
    document.querySelectorAll(".qty").forEach(input => {
      if (input) {
        input.addEventListener("input", () => {
          try {
            const qty = Math.min(Math.max(parseInt(input.value) || 1, 1), parseInt(input.max) || 999);
            input.value = qty;
            const cartItem = input.closest(".cart-item");
            if (cartItem && cartItem.dataset.cartId) {
              const cartId = cartItem.dataset.cartId;
              fetch("cart_action.php", {
                method: "POST",
                headers: {"Content-Type": "application/x-www-form-urlencoded"},
                body: `update_id=${cartId}&quantity=${qty}`
              })
              .then(res => res.json())
              .then(data => {
                if (data.status !== "success") {
                  alert(data.message);
                }
                updateCart();
              })
              .catch(err => {
                console.error('Error updating quantity:', err);
                alert('Error updating quantity. Please try again.');
              });
            }
          } catch (e) {
            console.error('Error in quantity handler:', e);
          }
        });
      }
    });
  } catch (e) {
    console.error('Error attaching quantity listeners:', e);
  }

  // Remove button handlers
  try {
    document.querySelectorAll(".remove-btn").forEach(btn => {
      if (btn) {
        btn.addEventListener("click", () => {
          try {
            const cartItem = btn.closest(".cart-item");
            if (cartItem && cartItem.dataset.cartId) {
              const cartId = cartItem.dataset.cartId;
              if (confirm('Are you sure you want to remove this item?')) {
                fetch("cart_action.php", {
                  method: "POST",
                  headers: {"Content-Type": "application/x-www-form-urlencoded"},
                  body: `remove_id=${cartId}`
                })
                .then(res => res.json())
                .then(data => {
                  if (data.status === "success") {
                    cartItem.remove();
                    updateCart();
                  } else {
                    alert(data.message);
                  }
                })
                .catch(err => {
                  console.error('Error removing item:', err);
                  alert('Error removing item. Please try again.');
                });
              }
            }
          } catch (e) {
            console.error('Error in remove handler:', e);
          }
        });
      }
    });
  } catch (e) {
    console.error('Error attaching remove listeners:', e);
  }

  // Checkout button handler
  if (checkoutBtn) {
    checkoutBtn.addEventListener("click", () => {
      try {
        if (getSelectedCartItems().length === 0) {
          alert("No items selected!");
          return;
        }
        if (checkoutModal) {
          checkoutModal.classList.remove("hidden");
          updateCart();
        } else {
          console.error('Checkout modal not found');
          alert('Error opening checkout. Please refresh the page.');
        }
      } catch (e) {
        console.error('Error opening checkout:', e);
        alert('Error opening checkout. Please try again.');
      }
    });
  } else {
    console.warn('Checkout button not found');
  }

  ["closeCheckoutModal","cancelCheckout"].forEach(id=>document.getElementById(id).onclick=()=>checkoutModal.classList.add("hidden"));

 toReceiptBtn.addEventListener("click", ()=>{
    const selectedItems = getSelectedCartItems();
    if(selectedItems.length===0) return alert("No items selected!");
    receiptItems.innerHTML = "";
    let subtotal = 0;

    selectedItems.forEach(item=>{
        const name = item.querySelector(".product-name").textContent;
        const qty = parseInt(item.querySelector(".qty").value)||1;
        const price = parseFloat(item.children[2].textContent.replace(/[₱,]/g,''))||0;
        subtotal += price * qty;
        const div = document.createElement("div");
        div.className="flex justify-between border-b py-1";
        div.innerHTML=`<span>${name} x${qty}</span><span>${formatCurrency(price*qty)}</span>`;
        receiptItems.appendChild(div);
    });

    // Set originalSubtotal for promo calculations
    originalSubtotal = subtotal;

    const addrSelect = checkoutForm.querySelector("#addressSelect");
    const newAddr = checkoutForm.querySelector("[name='new_address']").value.trim();
    const fullName = checkoutForm.querySelector("[name='new_fullName']").value.trim();
    let addressText="N/A";
    if(newAddr && fullName) addressText=`${fullName} - ${newAddr}`;
    else if(addrSelect && addrSelect.selectedOptions.length>0) addressText=addrSelect.selectedOptions[0].text;

    receiptAddress.textContent = addressText;
    receiptPayment.textContent = checkoutForm.querySelector("#paymentMethod").value || "N/A";

    // Fix: set the subtotal element
    document.getElementById("receiptSubtotal").textContent = subtotal.toLocaleString();

    SHIPPING_FEE = getShippingFee();
    const shipping = subtotal > 0 ? SHIPPING_FEE : 0;
    document.getElementById("receiptShipping").textContent = shipping.toLocaleString();
    receiptTotal.textContent = (subtotal + shipping).toLocaleString();

    // Reset promo when opening receipt modal
    appliedPromo = null;
    document.getElementById('discountRow').classList.add('hidden');
    document.getElementById('promoCodeInput').value = '';
    document.getElementById('promoCodeInput').disabled = false;
    document.getElementById('promoMessage').innerHTML = '';

    checkoutModal.classList.add("hidden");
    receiptModal.classList.remove("hidden");
});

// Apply Promo Code Function
window.applyPromoCode = function(code) {
  const inputField = document.getElementById('promoCodeInput');
  const messageDiv = document.getElementById('promoMessage');
  
  // If code is passed as parameter (clicked), use it. Otherwise get from input
  const promoCode = code || inputField.value.trim();
  
  if (!promoCode) {
    messageDiv.innerHTML = '<span class="text-red-600">Please enter a promo code</span>';
    return;
  }
  
  // If code was clicked, also populate input field
  if (code) {
    inputField.value = code;
  }
  
  // Show loading
  messageDiv.innerHTML = '<span class="text-gray-600">⏳ Validating...</span>';
  
  // Call validation API
  fetch('promo_validate.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: `action=validate&code=${encodeURIComponent(promoCode)}&subtotal=${originalSubtotal}`
  })
  .then(r => r.json())
  .then(data => {
    if (data.success) {
      // Store promo data
      appliedPromo = data;
      
      // Update UI
      document.getElementById('discountRow').classList.remove('hidden');
      document.getElementById('appliedPromoCode').textContent = data.code;
      document.getElementById('discountAmount').textContent = data.discount.toFixed(2);
      
      // Update total
      SHIPPING_FEE = getShippingFee();
      const shipping = originalSubtotal > 0 ? SHIPPING_FEE : 0;
      const newTotal = data.new_total + shipping;
      receiptTotal.textContent = newTotal.toLocaleString(undefined, {minimumFractionDigits:2, maximumFractionDigits:2});
      
      // Show success message
      messageDiv.innerHTML = `<span class="text-green-600">✅ ${data.message}</span>`;
      
      // Disable promo input
      inputField.disabled = true;
    } else {
      messageDiv.innerHTML = `<span class="text-red-600">❌ ${data.message}</span>`;
      appliedPromo = null;
    }
  })
  .catch(err => {
    console.error(err);
    messageDiv.innerHTML = '<span class="text-red-600">Error applying promo code. Please try again.</span>';
  });
};


  backToCheckout.addEventListener("click",()=>{receiptModal.classList.add("hidden");checkoutModal.classList.remove("hidden");});

  confirmReceipt.addEventListener("click", async()=>{
    receiptModal.classList.add("hidden");
    await handleCheckout();
  });

  async function handleCheckout(extra={}) {
    const selectedItems = getSelectedCartItems().map(item=>item.dataset.cartId);
    if(selectedItems.length===0){ alert("No items selected!"); return; }

    const formData = new FormData();
    formData.append("cart_ids",JSON.stringify(selectedItems));

    const newAddr = checkoutForm.querySelector("[name='new_address']").value.trim();
    if(newAddr){ formData.append("new_address",newAddr); formData.append("new_fullName",checkoutForm.querySelector("[name='new_fullName']").value.trim()); }
    else { const addrSelect = checkoutForm.querySelector("#addressSelect"); if(addrSelect) formData.append("address_id",addrSelect.value); }

    // Get payment method info
    const paymentMethodSelect = checkoutForm.querySelector("#paymentMethod");
    const selectedPaymentOption = paymentMethodSelect.options[paymentMethodSelect.selectedIndex];
    const paymentCode = selectedPaymentOption ? selectedPaymentOption.value : '';
    const paymentName = selectedPaymentOption ? selectedPaymentOption.dataset.name || selectedPaymentOption.textContent : paymentCode;
    
    formData.append("payment_method", paymentName);
    formData.append("payment_code", paymentCode);
    if(extra.paypal_order_id) formData.append("paypal_order_id",extra.paypal_order_id);
    
    // Get shipping method info
    const shippingSelect = checkoutForm.querySelector("#shippingMethod");
    const selectedShipping = shippingSelect ? shippingSelect.selectedOptions[0] : null;
    const shippingCode = selectedShipping ? selectedShipping.value : 'standard';
    formData.append("shipping_code", shippingCode);
    formData.append("shipping_fee", SHIPPING_FEE);

    // Include promo code data if applied
    if (appliedPromo) {
      formData.append("promo_id", appliedPromo.promo_id);
      formData.append("promo_code", appliedPromo.code);
      formData.append("discount_amount", appliedPromo.discount);
    }

    try{
      const res = await fetch("cart_checkout.php",{method:"POST",body:formData});
      
      // Check if response is OK
      if (!res.ok) {
        throw new Error(`HTTP error! status: ${res.status}`);
      }
      
      const data = await res.json();
      
      console.log('Checkout response:', data);
      
      if(data.status==="success"){ 
        // Increment promo usage if promo was applied
        if (appliedPromo) {
          fetch('promo_validate.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `action=apply&promo_id=${appliedPromo.promo_id}`
          }).catch(err => console.error('Error applying promo:', err));
        }
        alert("Order placed successfully!"); 
        location.reload(); 
      } else {
        console.error('Checkout error:', data.message);
        alert(data.message || "Checkout failed!");
      }
    }catch(err){ 
      console.error('Checkout exception:', err); 
      alert("Checkout failed! Please check the browser console for details."); 
    }
  }

  // Shipping method change handler
  const shippingMethod = document.getElementById("shippingMethod");
  if (shippingMethod) {
    shippingMethod.addEventListener("change", function() {
      SHIPPING_FEE = getShippingFee();
      updateCart();
      // Update receipt if open
      if (!receiptModal.classList.contains('hidden')) {
        const subtotal = parseFloat(receiptSubtotal.textContent.replace(/[^\d.]/g, '')) || 0;
        const shipping = subtotal > 0 ? SHIPPING_FEE : 0;
        receiptShipping.textContent = shipping.toLocaleString();
        receiptTotal.textContent = (subtotal + shipping).toLocaleString(undefined, {minimumFractionDigits:2, maximumFractionDigits:2});
      }
    });
  }

  paymentMethod.addEventListener("change", function(){
    const selectedOption = this.options[this.selectedIndex];
    const paymentCode = selectedOption ? selectedOption.value : '';
    const paymentName = selectedOption ? selectedOption.dataset.name || selectedOption.textContent : '';
    
    // Hide all payment containers
    paypalContainer.classList.add("hidden");
    document.getElementById("gcash-container").classList.add("hidden");
    document.getElementById("card-container").classList.add("hidden");
    document.getElementById("bank-container").classList.add("hidden");
    toReceiptBtn.classList.remove("hidden");
    
    if(paymentCode === "paypal"){
      paypalContainer.classList.remove("hidden");
      toReceiptBtn.classList.add("hidden");
      document.getElementById("paypal-button-container").innerHTML="";
      paypal.Buttons({
        createOrder: (data, actions)=>{
          const total = parseFloat(finalTotal.textContent.replace(/,/g,''))||0;
          const currency = window.PLATFORM_SETTINGS?.currency || 'PHP';
          return actions.order.create({ purchase_units:[{amount:{value:total.toFixed(2),currency_code:currency}}] });
        },
        onApprove: (data, actions)=>{
          return actions.order.capture().then(async details=>{
            alert("✅ Payment completed by "+details.payer.name.given_name);
            await handleCheckout({paypal_order_id:data.orderID});
          });
        }
      }).render("#paypal-button-container");
    }
    else if(paymentCode === "gcash"){
      const gcashContainer = document.getElementById("gcash-container");
      gcashContainer.classList.remove("hidden");
      const total = parseFloat(finalTotal.textContent.replace(/,/g,''))||0;
      document.getElementById("gcash-amount").textContent = formatCurrency(total);
      
      // GCash Pay Button
      const gcashBtn = document.getElementById("gcash-pay-btn");
      gcashBtn.onclick = async function(){
        if(confirm(`Pay ${formatCurrency(total)} using GCash (Demo Mode)?`)){
          this.disabled = true;
          this.textContent = "Processing...";
          await new Promise(resolve => setTimeout(resolve, 1500));
          alert("✅ GCash payment successful (Demo Mode)!");
          await handleCheckout({gcash_transaction_id: 'GCASH-DEMO-' + Date.now()});
        }
      };
    }
    else if(paymentCode === "card"){
      const cardContainer = document.getElementById("card-container");
      cardContainer.classList.remove("hidden");
      
      const cardBtn = document.getElementById("card-pay-btn");
      cardBtn.onclick = async function(){
        const total = parseFloat(finalTotal.textContent.replace(/,/g,''))||0;
        if(confirm(`Pay ${formatCurrency(total)} using Card (Demo Mode)?\n\nUse test card: 4242 4242 4242 4242`)){
          this.disabled = true;
          this.textContent = "Processing...";
          await new Promise(resolve => setTimeout(resolve, 1500));
          alert("✅ Card payment successful (Demo Mode)!");
          await handleCheckout({card_transaction_id: 'CARD-DEMO-' + Date.now()});
        }
      };
    }
    else if(paymentCode === "bank" || paymentCode === "bank_transfer"){
      const bankContainer = document.getElementById("bank-container");
      bankContainer.classList.remove("hidden");
      
      fetch('get_bank_details.php')
        .then(r => r.json())
        .then(data => {
          if(data.success){
            const bankDiv = document.getElementById("bank-details");
            bankDiv.innerHTML = `<p><strong>Bank:</strong> ${data.bank_name}</p>
              <p><strong>Account Name:</strong> ${data.account_name}</p>
              <p><strong>Account Number:</strong> ${data.account_number}</p>
              <p><strong>Swift Code:</strong> ${data.swift_code}</p>
              <p><strong>Branch:</strong> ${data.branch}</p>`;
          }
        })
        .catch(err => {
          document.getElementById("bank-details").innerHTML = `<p><strong>Bank:</strong> Demo Bank</p>
            <p><strong>Account Name:</strong> PetPantry+</p>
            <p><strong>Account Number:</strong> 1234-5678-9012</p>`;
        });
    }
    else{
      toReceiptBtn.classList.remove("hidden");
    }
  });

  // Initialize cart
  try {
    updateCart();
    console.log('Cart initialized successfully');
  } catch (e) {
    console.error('Error initializing cart:', e);
    alert('Error initializing cart. Please refresh the page.');
  }
});
</script>

</body>
</html>
