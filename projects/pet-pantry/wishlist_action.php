<?php
session_start();
header('Content-Type: application/json');

// ✅ Ensure the user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        'status' => 'error',
        'message' => 'You must be logged in to manage your wishlist.'
    ]);
    exit;
}

$user_id = intval($_SESSION['user_id']);

// ✅ Database connection
$servername = "localhost";
$username   = "u296524640_pet_admin";
$password   = "Petpantry123";
$dbname     = "u296524640_pet_pantry";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Database connection failed'
    ]);
    exit;
}

// ---------------------------
// Add product to wishlist
// ---------------------------
if (isset($_POST['add_id'])) {
    $product_id = intval($_POST['add_id']);

    // Prevent duplicates
    $check = $conn->prepare("SELECT id FROM wishlist WHERE user_id = ? AND product_id = ?");
    $check->bind_param("ii", $user_id, $product_id);
    $check->execute();
    $check->store_result();

    if ($check->num_rows === 0) {
        $stmt = $conn->prepare("INSERT INTO wishlist (user_id, product_id) VALUES (?, ?)");
        $stmt->bind_param("ii", $user_id, $product_id);
        $stmt->execute();
        $stmt->close();
    }
    $check->close();

    echo json_encode([
        'status' => 'success',
        'message' => 'Product added to wishlist',
        'wishlist_count' => getWishlistCount($conn, $user_id)
    ]);
    exit;
}

// ---------------------------
// Remove product from wishlist
// ---------------------------
if (isset($_POST['remove_id'])) {
    $wishlist_id = intval($_POST['remove_id']); // ✅ this is wishlist.id, not product_id

    $stmt = $conn->prepare("DELETE FROM wishlist WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $wishlist_id, $user_id);
    $stmt->execute();
    $stmt->close();

    echo json_encode([
        'status' => 'success',
        'message' => 'Product removed from wishlist',
        'wishlist_count' => getWishlistCount($conn, $user_id)
    ]);
    exit;
}

// ---------------------------
// Get wishlist count only
// ---------------------------
if (isset($_GET['count'])) {
    echo json_encode([
        'status' => 'success',
        'wishlist_count' => getWishlistCount($conn, $user_id)
    ]);
    exit;
}

// ---------------------------
// Default: Always return count
// ---------------------------
echo json_encode([
    'status' => 'success',
    'message' => 'Wishlist status loaded',
    'wishlist_count' => getWishlistCount($conn, $user_id)
]);

$conn->close();
exit;

// ===========================
// Helper function
// ===========================
function getWishlistCount($conn, $user_id) {
    $stmt = $conn->prepare("SELECT COUNT(*) FROM wishlist WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->bind_result($count);
    $stmt->fetch();
    $stmt->close();
    return $count;
}
?>
