<?php
session_start();
if(!isset($_SESSION['user_id'])){
    header('Location: Login_and_creating_account_fixed.php');
    exit;
}
$user_id = $_SESSION['user_id'];

// DB connection
$conn = new mysqli("localhost","u296524640_pet_admin","Petpantry123","u296524640_pet_pantry");
if ($conn->connect_error) {
    die("DB Connection failed: " . $conn->connect_error);
}

// Load currency settings
require_once 'settings_helper.php';
$currencySymbol = getCurrencySymbol();
$currencyCode = getDefaultCurrency();

$sql = "SELECT w.id as wishlist_id, p.id as product_id, p.name, p.price, p.image, p.stock
        FROM wishlist w
        JOIN products p ON w.product_id = p.id
        WHERE w.user_id=?";
$stmt = $conn->prepare($sql);
if(!$stmt){
    die("SQL Prepare failed: " . $conn->error);
}
$stmt->bind_param("i",$user_id);
$stmt->execute();
$res = $stmt->get_result();
$wishlistItems = $res->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>My Wishlist</title>
<script src="https://cdn.tailwindcss.com"></script>
<style>
body {
    font-family: Arial, sans-serif;
    margin: 0;
    padding: 0;
    background: #f5f5f5;
    min-height: 100vh;
    display: flex;
    flex-direction: column;
}
main { 
    flex: 1;
    max-width: 1100px;
    margin: 100px auto 40px auto;
    padding: 15px;
    background: #fff;
    border-radius: 8px;
    box-shadow: 0 2px 6px rgba(0,0,0,0.08);
}
.wishlist-header {
    font-size: 22px;
    font-weight: bold;
    margin-bottom: 20px;
    color: #333;
}
.table {
    width: 100%;
    border-collapse: collapse;
}
.table th, .table td {
    padding: 12px 10px;
    border-bottom: 1px solid #eee;
    text-align: left;
    vertical-align: middle;
}
.table th {
    background: #fafafa;
    font-weight: 600;
    font-size: 14px;
    color: #555;
}
.table img {
    width: 70px;
    height: 70px;
    object-fit: cover;
    border-radius: 6px;
    border: 1px solid #eee;
}
.table .price {
    color: #ff6f00;
    font-weight: bold;
}
.table .stock {
    font-size: 13px;
    color: #777;
}
.btn {
    padding: 6px 12px;
    border-radius: 6px;
    cursor: pointer;
    font-size: 13px;
    transition: all 0.2s ease;
    border: none;
}
.add-cart {
    background: #ff6f00;
    color: #fff;
    font-weight: 600;
}
.add-cart:hover {
    background: #e65c00;
}
.remove-btn {
    background: #f1f1f1;
    color: #444;
    font-weight: 500;
    margin-left: 5px;
}
.remove-btn:hover {
    background: #ddd;
}
.empty-msg {
    text-align: center;
    color: #777;
    padding: 50px;
    font-size: 18px;
}
</style>
</head>
<body>
<?php include 'header.php'; ?>
<main style="padding-top: 100px;">
    <div class="wishlist-header">My Wishlist </div>
    <?php if(count($wishlistItems) > 0): ?>
    <table class="table">
        <thead>
            <tr>
                <th>Product</th>
                <th>Name</th>
                <th>Price</th>
                <th>Stock</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach($wishlistItems as $item): ?>
            <tr data-wishlist-id="<?= $item['wishlist_id'] ?>">
                <td><img src="<?= htmlspecialchars($item['image'] ?: 'https://via.placeholder.com/70') ?>" alt=""></td>
                <td><?= htmlspecialchars($item['name']) ?></td>
                <td class="price"><?= formatCurrency($item['price']) ?></td>
                <td class="stock"><?= $item['stock'] > 0 ? "In Stock" : "Out of Stock" ?></td>
                <td>
                    <button class="btn add-cart" data-id="<?= $item['product_id'] ?>">Add to Cart</button>
                    <button class="btn remove-btn" data-id="<?= $item['wishlist_id'] ?>">Remove</button>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php else: ?>
        <div class="empty-msg">Your wishlist is empty </div>
    <?php endif; ?>
</main>
<?php include 'footer.php'; ?>
<script>
document.addEventListener("DOMContentLoaded", () => {

  // 🔸 Update wishlist counter
  function updateWishlistCount(count) {
    const notif = document.getElementById("wishlist-count");
    if (notif) notif.textContent = count;
  }

  // 🔸 Update cart counter
  function updateCartCount(count) {
    const cartDesktop = document.getElementById("cart-count");
    const cartMobile = document.getElementById("mobile-cart-count");
    if (cartDesktop) cartDesktop.textContent = count ?? 0;
    if (cartMobile) cartMobile.textContent = count ?? 0;
  }

  // 🔹 Remove item from wishlist
  document.querySelectorAll(".remove-btn").forEach(btn => {
    btn.addEventListener("click", async () => {
      const wishlistId = btn.dataset.id;
      try {
        const res = await fetch("wishlist_action.php", {
          method: "POST",
          headers: { "Content-Type": "application/x-www-form-urlencoded" },
          body: "remove_id=" + wishlistId
        });
        const data = await res.json();

        if (data.status === "success") {
          btn.closest("tr").remove();
          updateWishlistCount(data.wishlist_count);

          if (document.querySelectorAll("tbody tr").length === 0) {
            document.querySelector("main").innerHTML =
              '<div class="empty-msg">Your wishlist is empty </div>';
          }
        } else {
          alert(data.message);
        }
      } catch (err) {
        console.error("Remove wishlist error:", err);
      }
    });
  });

  // 🔹 Add to cart and remove from wishlist automatically
  document.querySelectorAll(".add-cart").forEach(btn => {
    btn.addEventListener("click", async () => {
      const productId = btn.dataset.id;
      const wishlistRow = btn.closest("tr");
      const wishlistId = wishlistRow.dataset.wishlistId;

      try {
        // 1️⃣ Add to cart
        const cartRes = await fetch("cart_action.php", {
          method: "POST",
          headers: { "Content-Type": "application/x-www-form-urlencoded" },
          body: "add_id=" + productId
        });
        const cartData = await cartRes.json();

        if (cartData.status === "success") {
          updateCartCount(cartData.cart_count);

          // 2️⃣ Remove from wishlist after adding to cart
          const wishRes = await fetch("wishlist_action.php", {
            method: "POST",
            headers: { "Content-Type": "application/x-www-form-urlencoded" },
            body: "remove_id=" + wishlistId
          });
          const wishData = await wishRes.json();

          if (wishData.status === "success") {
            wishlistRow.remove();
            updateWishlistCount(wishData.wishlist_count);
            if (document.querySelectorAll("tbody tr").length === 0) {
              document.querySelector("main").innerHTML =
                '<div class="empty-msg">Your wishlist is empty </div>';
            }
          }

          // ✅ Optional feedback
          console.log("✅ Added to cart and removed from wishlist");
        } else {
          alert(cartData.message);
        }
      } catch (err) {
        console.error("Add to cart error:", err);
      }
    });
  });
});
</script>

</body>
</html>
