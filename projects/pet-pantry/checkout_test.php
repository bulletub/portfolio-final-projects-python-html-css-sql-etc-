<?php
// checkout_test.php - Test checkout endpoint
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

echo "<h1>Checkout Test</h1>";
echo "<pre>";

echo "1. Testing session...\n";
if(!isset($_SESSION['user_id'])){
    die("❌ Not logged in");
}
echo "✓ User ID: " . $_SESSION['user_id'] . "\n\n";

echo "2. Testing database connection...\n";
$conn = new mysqli("localhost","u296524640_pet_admin","Petpantry123","u296524640_pet_pantry");
if($conn->connect_error) {
    die("❌ DB connection failed: " . $conn->connect_error);
}
echo "✓ Database connected\n\n";

echo "3. Testing PDO connection...\n";
if (!isset($pdo)) {
    try {
        require_once 'database.php';
        echo "✓ PDO connected\n\n";
    } catch (Exception $e) {
        echo "⚠ PDO connection issue: " . $e->getMessage() . "\n\n";
    }
}

echo "4. Testing helper files...\n";
try {
    require_once 'settings_helper.php';
    echo "✓ settings_helper.php loaded\n";
    
    if (function_exists('getActivePaymentOptions')) {
        $payments = getActivePaymentOptions();
        echo "✓ Found " . count($payments) . " payment options\n";
        print_r($payments);
    } else {
        echo "⚠ getActivePaymentOptions() not found\n";
    }
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
} catch (Error $e) {
    echo "❌ Fatal error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
}
echo "\n";

echo "5. Testing order_groups table structure...\n";
try {
    $result = $conn->query("SHOW COLUMNS FROM order_groups");
    if ($result) {
        $columns = [];
        while ($row = $result->fetch_assoc()) {
            $columns[] = $row['Field'];
        }
        echo "✓ Table columns: " . implode(', ', $columns) . "\n";
        
        $needed = ['payment_code', 'shipping_code', 'shipping_fee'];
        foreach ($needed as $col) {
            if (in_array($col, $columns)) {
                echo "✓ Column '$col' exists\n";
            } else {
                echo "⚠ Column '$col' missing (will be added)\n";
            }
        }
    }
} catch (Exception $e) {
    echo "❌ Error checking table: " . $e->getMessage() . "\n";
}
echo "\n";

echo "6. Testing cart_checkout.php syntax...\n";
$checkout_file = 'cart_checkout.php';
if (file_exists($checkout_file)) {
    $content = file_get_contents($checkout_file);
    if (strpos($content, '<?php') !== false) {
        echo "✓ File exists and has PHP tag\n";
        
        // Try to include it silently to check for syntax errors
        ob_start();
        $old_error = error_reporting(0);
        $included = @include $checkout_file;
        error_reporting($old_error);
        ob_end_clean();
        
        if ($included === false) {
            echo "⚠ Warning: File might have issues when included\n";
        } else {
            echo "✓ File can be included\n";
        }
    }
} else {
    echo "❌ File not found!\n";
}

echo "\n✅ All tests complete!\n";
echo "</pre>";
?>

