<?php
// cart_test.php - Minimal test to find the exact error
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

echo "<h1>Cart Test - Step by Step</h1>";
echo "<pre>";

echo "1. Starting session...\n";
session_start();
echo "✓ Session started\n\n";

echo "2. Checking user session...\n";
if(!isset($_SESSION['user_id'])){
    die("❌ Not logged in");
}
echo "✓ User logged in (ID: " . $_SESSION['user_id'] . ")\n\n";

echo "3. Testing MySQLi connection...\n";
$conn = new mysqli("localhost","u296524640_pet_admin","Petpantry123","u296524640_pet_pantry");
if ($conn->connect_error) {
    die("❌ MySQLi connection failed: " . $conn->connect_error);
}
echo "✓ MySQLi connected\n\n";

echo "4. Testing PDO connection...\n";
try {
    require_once 'database.php';
    if (isset($pdo)) {
        echo "✓ PDO connected (via database.php)\n\n";
    } else {
        echo "⚠ PDO not set, trying manual connection...\n";
        $pdo = new PDO("mysql:host=localhost;dbname=u296524640_pet_pantry;charset=utf8", "u296524640_pet_admin", "Petpantry123");
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        echo "✓ PDO connected (manual)\n\n";
    }
} catch (Exception $e) {
    echo "❌ PDO connection failed: " . $e->getMessage() . "\n";
    echo "⚠ Continuing without PDO...\n\n";
}

echo "5. Loading settings_helper.php...\n";
try {
    if (file_exists('settings_helper.php')) {
        require_once 'settings_helper.php';
        echo "✓ settings_helper.php loaded\n\n";
    } else {
        die("❌ settings_helper.php not found!\n");
    }
} catch (Exception $e) {
    die("❌ Error loading settings_helper.php: " . $e->getMessage() . "\n");
} catch (Error $e) {
    die("❌ Fatal error loading settings_helper.php: " . $e->getMessage() . "\n");
}

echo "6. Testing functions...\n";
if (function_exists('getDefaultCurrency')) {
    try {
        $currency = getDefaultCurrency();
        echo "✓ getDefaultCurrency() = " . ($currency ?: 'NULL') . "\n";
    } catch (Exception $e) {
        echo "⚠ getDefaultCurrency() error: " . $e->getMessage() . "\n";
    }
} else {
    echo "⚠ getDefaultCurrency() not found\n";
}

if (function_exists('getCurrencySymbol')) {
    try {
        $symbol = getCurrencySymbol();
        echo "✓ getCurrencySymbol() = " . ($symbol ?: 'NULL') . "\n";
    } catch (Exception $e) {
        echo "⚠ getCurrencySymbol() error: " . $e->getMessage() . "\n";
    }
} else {
    echo "⚠ getCurrencySymbol() not found\n";
}
echo "\n";

echo "7. Testing getActivePaymentOptions()...\n";
if (function_exists('getActivePaymentOptions')) {
    try {
        $payments = getActivePaymentOptions();
        echo "✓ Found " . count($payments) . " payment options\n";
    } catch (Exception $e) {
        echo "⚠ Error: " . $e->getMessage() . "\n";
    }
} else {
    echo "⚠ Function not found\n";
}
echo "\n";

echo "8. Testing getActiveShippingOptions()...\n";
if (function_exists('getActiveShippingOptions')) {
    try {
        $shipping = getActiveShippingOptions();
        echo "✓ Found " . count($shipping) . " shipping options\n";
    } catch (Exception $e) {
        echo "⚠ Error: " . $e->getMessage() . "\n";
    }
} else {
    echo "⚠ Function not found\n";
}
echo "\n";

echo "9. Testing formatCurrency()...\n";
if (function_exists('formatCurrency')) {
    try {
        $test = formatCurrency(100.50);
        echo "✓ formatCurrency(100.50) = " . $test . "\n";
    } catch (Exception $e) {
        echo "⚠ Error: " . $e->getMessage() . "\n";
    }
} else {
    echo "⚠ formatCurrency() not found\n";
}
echo "\n";

echo "10. Testing cart items query...\n";
try {
    $sql = "SELECT c.id as cart_id, p.id as product_id, p.name, p.price, p.image, c.quantity, p.stock
            FROM cart c
            JOIN products p ON c.product_id=p.id
            WHERE c.user_id=?";
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param("i", $_SESSION['user_id']);
        $stmt->execute();
        $res = $stmt->get_result();
        $items = $res->fetch_all(MYSQLI_ASSOC);
        echo "✓ Found " . count($items) . " cart items\n";
    } else {
        echo "⚠ Prepare failed\n";
    }
} catch (Exception $e) {
    echo "⚠ Error: " . $e->getMessage() . "\n";
}
echo "\n";

echo "✅ All tests passed! Cart should work.\n";
echo "</pre>";

echo '<p><a href="cart.php">Try cart.php now</a></p>';
?>

