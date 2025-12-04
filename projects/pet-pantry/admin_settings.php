<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
require 'database.php';
require 'settings_helper.php';

// Role-based Access Control
$userId = $_SESSION['user_id'] ?? 0;
$stmt = $pdo->prepare("SELECT role_name FROM admin_roles WHERE user_id=?");
$stmt->execute([$userId]);
$roles = $stmt->fetchAll(PDO::FETCH_COLUMN);

if (!$userId || (!in_array('super_admin', $roles) && !in_array('admin_dashboard', $roles))) {
    header("Location: Login_and_creating_account_fixed.php#login");
    exit;
}

$message = '';
$messageType = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_settings'])) {
        // Update platform settings
        $saved = false;
        $currencyChanged = false;
        $oldCurrency = getDefaultCurrency();
        
        foreach ($_POST as $key => $value) {
            if (strpos($key, 'setting_') === 0) {
                $settingKey = str_replace('setting_', '', $key);
                
                // Check if default_currency is being changed
                if ($settingKey === 'default_currency' && trim($value) !== $oldCurrency) {
                    $currencyChanged = true;
                    $newCurrency = trim($value);
                }
                
                $result = setSetting($settingKey, trim($value));
                if ($result) {
                    $saved = true;
                }
            }
        }
        
        // Automatically update currency symbol when default currency changes
        if ($currencyChanged && isset($newCurrency)) {
            $currencyMap = [
                'USD' => '$',
                'PHP' => '₱',
                'EUR' => '€',
                'GBP' => '£',
                'JPY' => '¥',
                'CNY' => '¥',
                'AUD' => 'A$',
                'CAD' => 'C$'
            ];
            $newSymbol = $currencyMap[$newCurrency] ?? '₱';
            setSetting('currency_symbol', $newSymbol);
            $message = "Platform settings updated successfully! Currency symbol automatically updated to match " . $newCurrency . " (" . $newSymbol . ")";
        } elseif ($saved) {
            $message = "Platform settings updated successfully!";
        } else {
            $message = "Error: Failed to save some settings. Please try again.";
        }
        
        if ($saved || $currencyChanged) {
            $messageType = "success";
            
            // Set base currency if not set (first time setup)
            $baseCurrency = getSetting('base_currency', null);
            if (empty($baseCurrency)) {
                setSetting('base_currency', 'PHP'); // Products stored in PHP by default
            }
            
            // Reload settings after save
            $currentCurrency = getDefaultCurrency();
            $currencySymbol = getCurrencySymbol();
            $freeShippingThreshold = getSetting('free_shipping_threshold', '0.00');
            $taxRate = getSetting('tax_rate', '0.00');
            $storeName = getSetting('store_name', 'PetPantry+');
            $storeEmail = getSetting('store_email', '');
            $storePhone = getSetting('store_phone', '');
            $storeAddress = getSetting('store_address', '');
        } else {
            $messageType = "error";
        }
    }
    
    elseif (isset($_POST['add_payment'])) {
        $name = trim($_POST['payment_name']);
        $code = trim($_POST['payment_code']);
        $icon = trim($_POST['payment_icon']);
        $order = intval($_POST['payment_order']);
        $active = isset($_POST['payment_active']) ? 1 : 0;
        
        try {
            $stmt = $pdo->prepare("INSERT INTO payment_options (name, code, icon, is_active, display_order) 
                                   VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$name, $code, $icon, $active, $order]);
            $message = "Payment option added successfully!";
            $messageType = "success";
        } catch (PDOException $e) {
            $message = "Error: " . $e->getMessage();
            $messageType = "error";
        }
    }
    
    elseif (isset($_POST['update_payment'])) {
        $id = intval($_POST['payment_id']);
        $name = trim($_POST['payment_name']);
        $code = trim($_POST['payment_code']);
        $icon = trim($_POST['payment_icon']);
        $order = intval($_POST['payment_order']);
        $active = isset($_POST['payment_active']) ? 1 : 0;
        
        try {
            $stmt = $pdo->prepare("UPDATE payment_options SET name=?, code=?, icon=?, is_active=?, display_order=? 
                                   WHERE id=?");
            $stmt->execute([$name, $code, $icon, $active, $order, $id]);
            $message = "Payment option updated successfully!";
            $messageType = "success";
        } catch (PDOException $e) {
            $message = "Error: " . $e->getMessage();
            $messageType = "error";
        }
    }
    
    elseif (isset($_POST['delete_payment'])) {
        $id = intval($_POST['payment_id']);
        try {
            $stmt = $pdo->prepare("DELETE FROM payment_options WHERE id=?");
            $stmt->execute([$id]);
            $message = "Payment option deleted successfully!";
            $messageType = "success";
        } catch (PDOException $e) {
            $message = "Error: " . $e->getMessage();
            $messageType = "error";
        }
    }
    
    elseif (isset($_POST['add_shipping'])) {
        $name = trim($_POST['shipping_name']);
        $code = trim($_POST['shipping_code']);
        $fee = floatval($_POST['shipping_fee']);
        $order = intval($_POST['shipping_order']);
        $active = isset($_POST['shipping_active']) ? 1 : 0;
        $description = trim($_POST['shipping_description'] ?? '');
        $estimatedDays = intval($_POST['shipping_days'] ?? 0);
        
        try {
            $stmt = $pdo->prepare("INSERT INTO shipping_options (name, code, fee, is_active, display_order, description, estimated_days) 
                                   VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$name, $code, $fee, $active, $order, $description, $estimatedDays]);
            $message = "Shipping option added successfully!";
            $messageType = "success";
        } catch (PDOException $e) {
            $message = "Error: " . $e->getMessage();
            $messageType = "error";
        }
    }
    
    elseif (isset($_POST['update_shipping'])) {
        $id = intval($_POST['shipping_id']);
        $name = trim($_POST['shipping_name']);
        $code = trim($_POST['shipping_code']);
        $fee = floatval($_POST['shipping_fee']);
        $order = intval($_POST['shipping_order']);
        $active = isset($_POST['shipping_active']) ? 1 : 0;
        $description = trim($_POST['shipping_description'] ?? '');
        $estimatedDays = intval($_POST['shipping_days'] ?? 0);
        
        try {
            $stmt = $pdo->prepare("UPDATE shipping_options SET name=?, code=?, fee=?, is_active=?, display_order=?, description=?, estimated_days=? 
                                   WHERE id=?");
            $stmt->execute([$name, $code, $fee, $active, $order, $description, $estimatedDays, $id]);
            $message = "Shipping option updated successfully!";
            $messageType = "success";
        } catch (PDOException $e) {
            $message = "Error: " . $e->getMessage();
            $messageType = "error";
        }
    }
    
    elseif (isset($_POST['delete_shipping'])) {
        $id = intval($_POST['shipping_id']);
        try {
            $stmt = $pdo->prepare("DELETE FROM shipping_options WHERE id=?");
            $stmt->execute([$id]);
            $message = "Shipping option deleted successfully!";
            $messageType = "success";
        } catch (PDOException $e) {
            $message = "Error: " . $e->getMessage();
            $messageType = "error";
        }
    }
    
    elseif (isset($_POST['refresh_exchange_rates'])) {
        // Clear cached rates to force refresh
        try {
            $pdo->exec("DELETE FROM currency_rates WHERE cached_at < DATE_SUB(NOW(), INTERVAL 1 HOUR)");
            $message = "Exchange rates refreshed!";
            $messageType = "success";
        } catch (PDOException $e) {
            $message = "Error refreshing rates: " . $e->getMessage();
            $messageType = "error";
        }
    }
}

// Fetch current settings
$currentCurrency = getDefaultCurrency();
$currencySymbol = getCurrencySymbol();
$freeShippingThreshold = getSetting('free_shipping_threshold', '0.00');
$taxRate = getSetting('tax_rate', '0.00');
$storeName = getSetting('store_name', 'PetPantry+');
$storeEmail = getSetting('store_email', '');
$storePhone = getSetting('store_phone', '');
$storeAddress = getSetting('store_address', '');

// Fetch payment and shipping options
$paymentOptions = getAllPaymentOptions();
$shippingOptions = getAllShippingOptions();

// Get exchange rates for display
$commonCurrencies = ['PHP', 'USD', 'EUR', 'GBP', 'JPY', 'AUD', 'CAD'];
$exchangeRates = [];
foreach ($commonCurrencies as $curr) {
    if ($curr !== $currentCurrency) {
        $exchangeRates[$curr] = getExchangeRate($currentCurrency, $curr);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Platform Settings | Admin</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <script src="https://cdn.tailwindcss.com"></script>
  <style>
    html, body {
      margin: 0;
      padding: 0;
      overflow-x: hidden;
    }
  </style>
</head>
<body class="bg-gradient-to-br from-orange-50 to-white min-h-screen font-sans text-gray-800">
<div class="flex min-h-screen">
  <?php include('admin_navbar.php'); ?>

  <div class="flex-1 p-8">
    <header class="mb-6 py-4 border-b border-gray-200">
      <h1 class="text-2xl font-bold text-gray-800">⚙️ Platform Settings & Configuration</h1>
      <p class="text-gray-600 mt-1">Manage payment options, currency, shipping, and platform-wide settings</p>
    </header>

    <?php if ($message): ?>
    <div class="mb-4 p-4 rounded <?= $messageType === 'success' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' ?>">
      <?= htmlspecialchars($message) ?>
    </div>
    <?php endif; ?>

    <!-- Settings Tabs -->
    <div class="mb-6 border-b border-gray-200">
      <button onclick="showTab('general')" class="tab-btn px-4 py-2 font-medium border-b-2 border-orange-500 text-orange-600">
        📋 General Settings
      </button>
      <button onclick="showTab('payments')" class="tab-btn px-4 py-2 font-medium text-gray-600 hover:text-orange-600">
        💳 Payment Options
      </button>
      <button onclick="showTab('shipping')" class="tab-btn px-4 py-2 font-medium text-gray-600 hover:text-orange-600">
        🚚 Shipping Options
      </button>
      <button onclick="showTab('currency')" class="tab-btn px-4 py-2 font-medium text-gray-600 hover:text-orange-600">
        💱 Currency & Exchange
      </button>
    </div>

    <!-- General Settings Tab -->
    <div id="general-tab" class="tab-content">
      <div class="bg-white rounded-lg shadow p-6">
        <h2 class="text-xl font-semibold mb-6">General Platform Settings</h2>
        <form method="POST" class="space-y-6">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Store Name</label>
              <input type="text" name="setting_store_name" value="<?= htmlspecialchars($storeName) ?>" 
                     class="w-full border rounded px-3 py-2 focus:ring-1 focus:ring-orange-500">
            </div>
            
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Store Email</label>
              <input type="email" name="setting_store_email" value="<?= htmlspecialchars($storeEmail) ?>" 
                     class="w-full border rounded px-3 py-2 focus:ring-1 focus:ring-orange-500">
            </div>
            
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Store Phone</label>
              <input type="text" name="setting_store_phone" value="<?= htmlspecialchars($storePhone) ?>" 
                     class="w-full border rounded px-3 py-2 focus:ring-1 focus:ring-orange-500">
            </div>
            
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Free Shipping Threshold</label>
              <input type="number" step="0.01" name="setting_free_shipping_threshold" value="<?= htmlspecialchars($freeShippingThreshold) ?>" 
                     class="w-full border rounded px-3 py-2 focus:ring-1 focus:ring-orange-500"
                     placeholder="0.00">
              <p class="text-xs text-gray-500 mt-1">Order amount that qualifies for free shipping</p>
            </div>
            
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Tax Rate (%)</label>
              <input type="number" step="0.01" name="setting_tax_rate" value="<?= htmlspecialchars($taxRate) ?>" 
                     class="w-full border rounded px-3 py-2 focus:ring-1 focus:ring-orange-500"
                     placeholder="0.00">
            </div>
            
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Default Currency</label>
              <select name="setting_default_currency" class="w-full border rounded px-3 py-2 focus:ring-1 focus:ring-orange-500">
                <?php foreach ($commonCurrencies as $curr): ?>
                  <option value="<?= $curr ?>" <?= $curr === $currentCurrency ? 'selected' : '' ?>><?= $curr ?></option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>
          
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Store Address</label>
            <textarea name="setting_store_address" rows="3" 
                      class="w-full border rounded px-3 py-2 focus:ring-1 focus:ring-orange-500"><?= htmlspecialchars($storeAddress) ?></textarea>
          </div>
          
          <button type="submit" name="update_settings" 
                  class="px-6 py-2 bg-orange-500 text-white rounded hover:bg-orange-600 font-medium">
            Save General Settings
          </button>
        </form>
      </div>
    </div>

    <!-- Payment Options Tab -->
    <div id="payments-tab" class="tab-content hidden">
      <div class="bg-white rounded-lg shadow p-6 mb-6">
        <div class="flex justify-between items-center mb-6">
          <h2 class="text-xl font-semibold">Payment Options Management</h2>
          <button onclick="showPaymentModal()" 
                  class="px-4 py-2 bg-green-500 text-white rounded hover:bg-green-600">
            + Add Payment Option
          </button>
        </div>
        
        <div class="overflow-x-auto">
          <table class="w-full border-collapse">
            <thead>
              <tr class="bg-gray-100">
                <th class="border px-4 py-2 text-left">Icon</th>
                <th class="border px-4 py-2 text-left">Name</th>
                <th class="border px-4 py-2 text-left">Code</th>
                <th class="border px-4 py-2 text-left">Order</th>
                <th class="border px-4 py-2 text-left">Status</th>
                <th class="border px-4 py-2 text-left">Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($paymentOptions as $payment): ?>
              <tr>
                <td class="border px-4 py-2 text-2xl"><?= htmlspecialchars($payment['icon']) ?></td>
                <td class="border px-4 py-2"><?= htmlspecialchars($payment['name']) ?></td>
                <td class="border px-4 py-2"><code class="bg-gray-100 px-2 py-1 rounded"><?= htmlspecialchars($payment['code']) ?></code></td>
                <td class="border px-4 py-2"><?= $payment['display_order'] ?></td>
                <td class="border px-4 py-2">
                  <span class="px-2 py-1 rounded <?= $payment['is_active'] ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-700' ?>">
                    <?= $payment['is_active'] ? 'Active' : 'Inactive' ?>
                  </span>
                </td>
                <td class="border px-4 py-2">
                  <button onclick="editPayment(<?= $payment['id'] ?>, '<?= htmlspecialchars($payment['name'], ENT_QUOTES) ?>', '<?= htmlspecialchars($payment['code'], ENT_QUOTES) ?>', '<?= htmlspecialchars($payment['icon'], ENT_QUOTES) ?>', <?= $payment['display_order'] ?>, <?= $payment['is_active'] ?>)" 
                          class="px-3 py-1 bg-blue-500 text-white rounded hover:bg-blue-600 text-sm">Edit</button>
                  <form method="POST" class="inline" onsubmit="return confirm('Delete this payment option?')">
                    <input type="hidden" name="payment_id" value="<?= $payment['id'] ?>">
                    <button type="submit" name="delete_payment" 
                            class="px-3 py-1 bg-red-500 text-white rounded hover:bg-red-600 text-sm">Delete</button>
                  </form>
                </td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- Shipping Options Tab -->
    <div id="shipping-tab" class="tab-content hidden">
      <div class="bg-white rounded-lg shadow p-6 mb-6">
        <div class="flex justify-between items-center mb-6">
          <h2 class="text-xl font-semibold">Shipping Options Management</h2>
          <button onclick="showShippingModal()" 
                  class="px-4 py-2 bg-green-500 text-white rounded hover:bg-green-600">
            + Add Shipping Option
          </button>
        </div>
        
        <div class="overflow-x-auto">
          <table class="w-full border-collapse">
            <thead>
              <tr class="bg-gray-100">
                <th class="border px-4 py-2 text-left">Name</th>
                <th class="border px-4 py-2 text-left">Code</th>
                <th class="border px-4 py-2 text-left">Fee</th>
                <th class="border px-4 py-2 text-left">Days</th>
                <th class="border px-4 py-2 text-left">Order</th>
                <th class="border px-4 py-2 text-left">Status</th>
                <th class="border px-4 py-2 text-left">Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($shippingOptions as $shipping): ?>
              <tr>
                <td class="border px-4 py-2"><?= htmlspecialchars($shipping['name']) ?></td>
                <td class="border px-4 py-2"><code class="bg-gray-100 px-2 py-1 rounded"><?= htmlspecialchars($shipping['code']) ?></code></td>
                <td class="border px-4 py-2"><?= formatCurrency($shipping['fee']) ?></td>
                <td class="border px-4 py-2"><?= $shipping['estimated_days'] ? $shipping['estimated_days'] . ' days' : '-' ?></td>
                <td class="border px-4 py-2"><?= $shipping['display_order'] ?></td>
                <td class="border px-4 py-2">
                  <span class="px-2 py-1 rounded <?= $shipping['is_active'] ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-700' ?>">
                    <?= $shipping['is_active'] ? 'Active' : 'Inactive' ?>
                  </span>
                </td>
                <td class="border px-4 py-2">
                  <button onclick="editShipping(<?= $shipping['id'] ?>, '<?= htmlspecialchars($shipping['name'], ENT_QUOTES) ?>', '<?= htmlspecialchars($shipping['code'], ENT_QUOTES) ?>', <?= $shipping['fee'] ?>, <?= $shipping['display_order'] ?>, <?= $shipping['is_active'] ?>, <?= $shipping['estimated_days'] ?>, '<?= htmlspecialchars($shipping['description'] ?? '', ENT_QUOTES) ?>')" 
                          class="px-3 py-1 bg-blue-500 text-white rounded hover:bg-blue-600 text-sm">Edit</button>
                  <form method="POST" class="inline" onsubmit="return confirm('Delete this shipping option?')">
                    <input type="hidden" name="shipping_id" value="<?= $shipping['id'] ?>">
                    <button type="submit" name="delete_shipping" 
                            class="px-3 py-1 bg-red-500 text-white rounded hover:bg-red-600 text-sm">Delete</button>
                  </form>
                </td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- Currency & Exchange Tab -->
    <div id="currency-tab" class="tab-content hidden">
      <div class="bg-white rounded-lg shadow p-6">
        <div class="flex justify-between items-center mb-6">
          <h2 class="text-xl font-semibold">Currency & Exchange Rates</h2>
          <form method="POST" class="inline">
            <button type="submit" name="refresh_exchange_rates" 
                    class="px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600">
              🔄 Refresh Rates
            </button>
          </form>
        </div>
        
        <div class="mb-6">
          <label class="block text-sm font-medium text-gray-700 mb-2">Currency Symbol</label>
          <form method="POST" class="flex gap-4">
            <input type="text" name="setting_currency_symbol" value="<?= htmlspecialchars($currencySymbol) ?>" 
                   class="border rounded px-3 py-2 focus:ring-1 focus:ring-orange-500 w-32">
            <button type="submit" name="update_settings" 
                    class="px-4 py-2 bg-orange-500 text-white rounded hover:bg-orange-600">
              Update Symbol
            </button>
          </form>
        </div>
        
        <div class="mb-6">
          <h3 class="text-lg font-semibold mb-4">Current Exchange Rates (Base: <?= $currentCurrency ?>)</h3>
          <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <?php foreach ($exchangeRates as $curr => $rate): ?>
            <div class="border rounded p-4">
              <div class="font-semibold text-lg"><?= $curr ?></div>
              <div class="text-gray-600">1 <?= $currentCurrency ?> = <?= number_format($rate, 4) ?> <?= $curr ?></div>
              <div class="text-sm text-gray-500 mt-1">Last updated: <?= date('H:i') ?></div>
            </div>
            <?php endforeach; ?>
          </div>
        </div>
        
        <div class="bg-blue-50 border border-blue-200 rounded p-4">
          <p class="text-sm text-blue-800">
            <strong>Note:</strong> Exchange rates are automatically fetched from exchangerate-api.io and cached for 1 hour. 
            Rates are updated automatically when needed.
          </p>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Payment Modal -->
<div id="paymentModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex items-center justify-center">
  <div class="bg-white rounded-lg w-11/12 max-w-md p-6">
    <h3 class="text-xl font-semibold mb-4" id="paymentModalTitle">Add Payment Option</h3>
    <form method="POST" id="paymentForm">
      <input type="hidden" name="payment_id" id="payment_id">
      <div class="space-y-4">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Name</label>
          <input type="text" name="payment_name" id="payment_name" required
                 class="w-full border rounded px-3 py-2">
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Code (unique identifier)</label>
          <input type="text" name="payment_code" id="payment_code" required
                 class="w-full border rounded px-3 py-2">
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Icon (emoji or text)</label>
          <input type="text" name="payment_icon" id="payment_icon" placeholder="💳"
                 class="w-full border rounded px-3 py-2">
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Display Order</label>
          <input type="number" name="payment_order" id="payment_order" value="0" min="0"
                 class="w-full border rounded px-3 py-2">
        </div>
        <div>
          <label class="flex items-center space-x-2">
            <input type="checkbox" name="payment_active" id="payment_active" checked>
            <span>Active</span>
          </label>
        </div>
        <div class="flex justify-end space-x-3">
          <button type="button" onclick="closePaymentModal()" 
                  class="px-4 py-2 bg-gray-300 text-gray-700 rounded hover:bg-gray-400">
            Cancel
          </button>
          <button type="submit" name="add_payment" id="paymentSubmitBtn" 
                  class="px-4 py-2 bg-green-500 text-white rounded hover:bg-green-600">
            Add
          </button>
          <button type="submit" name="update_payment" id="paymentUpdateBtn" class="hidden
                  px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600">
            Update
          </button>
        </div>
      </div>
    </form>
  </div>
</div>

<!-- Shipping Modal -->
<div id="shippingModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex items-center justify-center">
  <div class="bg-white rounded-lg w-11/12 max-w-md p-6">
    <h3 class="text-xl font-semibold mb-4" id="shippingModalTitle">Add Shipping Option</h3>
    <form method="POST" id="shippingForm">
      <input type="hidden" name="shipping_id" id="shipping_id">
      <div class="space-y-4">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Name</label>
          <input type="text" name="shipping_name" id="shipping_name" required
                 class="w-full border rounded px-3 py-2">
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Code (unique identifier)</label>
          <input type="text" name="shipping_code" id="shipping_code" required
                 class="w-full border rounded px-3 py-2">
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Fee</label>
          <input type="number" step="0.01" name="shipping_fee" id="shipping_fee" value="0.00" min="0"
                 class="w-full border rounded px-3 py-2">
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Estimated Days</label>
          <input type="number" name="shipping_days" id="shipping_days" min="0"
                 class="w-full border rounded px-3 py-2">
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
          <textarea name="shipping_description" id="shipping_description" rows="2"
                    class="w-full border rounded px-3 py-2"></textarea>
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Display Order</label>
          <input type="number" name="shipping_order" id="shipping_order" value="0" min="0"
                 class="w-full border rounded px-3 py-2">
        </div>
        <div>
          <label class="flex items-center space-x-2">
            <input type="checkbox" name="shipping_active" id="shipping_active" checked>
            <span>Active</span>
          </label>
        </div>
        <div class="flex justify-end space-x-3">
          <button type="button" onclick="closeShippingModal()" 
                  class="px-4 py-2 bg-gray-300 text-gray-700 rounded hover:bg-gray-400">
            Cancel
          </button>
          <button type="submit" name="add_shipping" id="shippingSubmitBtn" 
                  class="px-4 py-2 bg-green-500 text-white rounded hover:bg-green-600">
            Add
          </button>
          <button type="submit" name="update_shipping" id="shippingUpdateBtn" class="hidden
                  px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600">
            Update
          </button>
        </div>
      </div>
    </form>
  </div>
</div>

<script>
function showTab(tab) {
  // Hide all tabs
  document.querySelectorAll('.tab-content').forEach(t => t.classList.add('hidden'));
  document.querySelectorAll('.tab-btn').forEach(b => {
    b.classList.remove('border-b-2', 'border-orange-500', 'text-orange-600');
    b.classList.add('text-gray-600');
  });
  
  // Show selected tab
  document.getElementById(tab + '-tab').classList.remove('hidden');
  event.target.classList.add('border-b-2', 'border-orange-500', 'text-orange-600');
  event.target.classList.remove('text-gray-600');
}

function showPaymentModal() {
  document.getElementById('paymentModal').classList.remove('hidden');
  document.getElementById('paymentModalTitle').textContent = 'Add Payment Option';
  document.getElementById('paymentForm').reset();
  document.getElementById('payment_id').value = '';
  document.getElementById('paymentSubmitBtn').classList.remove('hidden');
  document.getElementById('paymentUpdateBtn').classList.add('hidden');
  document.getElementById('payment_active').checked = true;
}

function editPayment(id, name, code, icon, order, active) {
  document.getElementById('paymentModal').classList.remove('hidden');
  document.getElementById('paymentModalTitle').textContent = 'Edit Payment Option';
  document.getElementById('payment_id').value = id;
  document.getElementById('payment_name').value = name;
  document.getElementById('payment_code').value = code;
  document.getElementById('payment_icon').value = icon;
  document.getElementById('payment_order').value = order;
  document.getElementById('payment_active').checked = active == 1;
  document.getElementById('paymentSubmitBtn').classList.add('hidden');
  document.getElementById('paymentUpdateBtn').classList.remove('hidden');
}

function closePaymentModal() {
  document.getElementById('paymentModal').classList.add('hidden');
}

function showShippingModal() {
  document.getElementById('shippingModal').classList.remove('hidden');
  document.getElementById('shippingModalTitle').textContent = 'Add Shipping Option';
  document.getElementById('shippingForm').reset();
  document.getElementById('shipping_id').value = '';
  document.getElementById('shipping_fee').value = '0.00';
  document.getElementById('shippingSubmitBtn').classList.remove('hidden');
  document.getElementById('shippingUpdateBtn').classList.add('hidden');
  document.getElementById('shipping_active').checked = true;
}

function editShipping(id, name, code, fee, order, active, days, description) {
  document.getElementById('shippingModal').classList.remove('hidden');
  document.getElementById('shippingModalTitle').textContent = 'Edit Shipping Option';
  document.getElementById('shipping_id').value = id;
  document.getElementById('shipping_name').value = name;
  document.getElementById('shipping_code').value = code;
  document.getElementById('shipping_fee').value = fee;
  document.getElementById('shipping_order').value = order;
  document.getElementById('shipping_active').checked = active == 1;
  document.getElementById('shipping_days').value = days || '';
  document.getElementById('shipping_description').value = description || '';
  document.getElementById('shippingSubmitBtn').classList.add('hidden');
  document.getElementById('shippingUpdateBtn').classList.remove('hidden');
}

function closeShippingModal() {
  document.getElementById('shippingModal').classList.add('hidden');
}

// Close modals on outside click
document.getElementById('paymentModal').addEventListener('click', function(e) {
  if (e.target === this) closePaymentModal();
});

document.getElementById('shippingModal').addEventListener('click', function(e) {
  if (e.target === this) closeShippingModal();
});
</script>
</body>
</html>

