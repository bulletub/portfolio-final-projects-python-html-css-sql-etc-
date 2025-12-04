<?php
// cart_error_test.php - Minimal test to catch the exact error
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

echo "START - Testing cart.php execution...<br>";

// Step 1: Session
echo "1. Starting session...<br>";
session_start();
echo "✓ Session OK<br><br>";

// Step 2: Check user
echo "2. Checking user...<br>";
if(!isset($_SESSION['user_id'])){
    die("❌ Not logged in");
}
echo "✓ User ID: " . $_SESSION['user_id'] . "<br><br>";

// Step 3: Database connection
echo "3. MySQLi connection...<br>";
$conn = new mysqli("localhost","u296524640_pet_admin","Petpantry123","u296524640_pet_pantry");
if ($conn->connect_error) {
    die("❌ MySQLi failed: " . $conn->connect_error);
}
echo "✓ MySQLi OK<br><br>";

// Step 4: PDO connection
echo "4. PDO connection...<br>";
if (!isset($pdo)) {
    try {
        require_once 'database.php';
        echo "✓ PDO loaded from database.php<br>";
    } catch (Exception $e) {
        echo "⚠ database.php error, trying manual...<br>";
        try {
            $pdo = new PDO("mysql:host=localhost;dbname=u296524640_pet_pantry;charset=utf8", "u296524640_pet_admin", "Petpantry123");
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            echo "✓ PDO manual OK<br>";
        } catch (PDOException $e) {
            echo "❌ PDO failed: " . $e->getMessage() . "<br>";
            $pdo = null;
        }
    }
} else {
    echo "✓ PDO already exists<br>";
}
echo "<br>";

// Step 5: Load settings_helper
echo "5. Loading settings_helper.php...<br>";
try {
    if (file_exists('settings_helper.php')) {
        require_once 'settings_helper.php';
        echo "✓ settings_helper.php loaded<br>";
    } else {
        die("❌ settings_helper.php not found!");
    }
} catch (Exception $e) {
    die("❌ Error: " . $e->getMessage());
} catch (Error $e) {
    die("❌ Fatal: " . $e->getMessage());
}
echo "<br>";

// Step 6: Test all functions
echo "6. Testing functions...<br>";
try {
    $currency = getDefaultCurrency();
    echo "✓ getDefaultCurrency() = $currency<br>";
} catch (Exception $e) {
    die("❌ getDefaultCurrency failed: " . $e->getMessage());
}

try {
    $symbol = getCurrencySymbol();
    echo "✓ getCurrencySymbol() = $symbol<br>";
} catch (Exception $e) {
    die("❌ getCurrencySymbol failed: " . $e->getMessage());
}
echo "<br>";

// Step 7: Load header.php
echo "7. Testing header.php include...<br>";
try {
    // Pre-load settings before header
    if (!function_exists('getCurrencySymbol')) {
        require_once 'settings_helper.php';
    }
    ob_start();
    include 'header.php';
    $header_output = ob_get_clean();
    echo "✓ header.php loaded (length: " . strlen($header_output) . " bytes)<br>";
} catch (Exception $e) {
    die("❌ header.php failed: " . $e->getMessage());
} catch (Error $e) {
    die("❌ header.php fatal: " . $e->getMessage() . " in " . $e->getFile() . ":" . $e->getLine());
}
echo "<br>";

// Step 8: Test cart queries
echo "8. Testing cart queries...<br>";
$user_id = $_SESSION['user_id'];
try {
    $sql = "SELECT c.id as cart_id, p.id as product_id, p.name, p.price, p.image, c.quantity, p.stock
            FROM cart c
            JOIN products p ON c.product_id=p.id
            WHERE c.user_id=?";
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $res = $stmt->get_result();
        $cartItems = $res->fetch_all(MYSQLI_ASSOC);
        echo "✓ Cart query OK - " . count($cartItems) . " items<br>";
    } else {
        die("❌ Prepare failed");
    }
} catch (Exception $e) {
    die("❌ Cart query failed: " . $e->getMessage());
}
echo "<br>";

echo "<h2 style='color: green;'>✅ ALL TESTS PASSED!</h2>";
echo "<p>If you see this, the cart.php should work. If cart.php still fails, the error is in the HTML/CSS/JS section.</p>";
echo "<p><a href='cart.php'>Try cart.php now</a></p>";
?>

