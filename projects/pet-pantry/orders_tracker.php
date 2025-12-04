<?php
session_start();
include('database.php');
require_once 'settings_helper.php';
$currencySymbol = getCurrencySymbol();

// ✅ Role-based admin check
$userId = $_SESSION['user_id'] ?? 0;
if (!$userId) {
    header("Location: Login_and_creating_account_fixed.php#login");
    exit;
}

$stmt = $pdo->prepare("SELECT role_name FROM admin_roles WHERE user_id=?");
$stmt->execute([$userId]);
$roles = $stmt->fetchAll(PDO::FETCH_COLUMN);

// ✅ Only allow super_admin OR orders role
if (!in_array('super_admin', $roles) && !in_array('orders', $roles)) {
    header("Location: Login_and_creating_account_fixed.php#login");
    exit;
}

// ✅ Update order status
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    // Include email helper
    require_once 'email_helper.php';
    
    // Get order and user info
    $stmt = $pdo->prepare("
        SELECT og.user_id, og.status, og.address, og.discount_amount, 
               u.name AS customer_name, u.email AS customer_email
        FROM order_groups og
        JOIN users u ON og.user_id = u.id
        WHERE og.id = ?
    ");
    $stmt->execute([$_POST['order_group_id']]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($order && $order['status'] !== $_POST['status']) {
        $newStatus = $_POST['status'];
        $orderGroupId = (int)$_POST['order_group_id'];

        // Update order_groups table
        $stmt = $pdo->prepare("UPDATE order_groups SET status=? WHERE id=?");
        $stmt->execute([$newStatus, $orderGroupId]);

        // Build customer notification message
        $message = "Your order #" . $orderGroupId .
                   " status has been updated to " . ucfirst(htmlspecialchars($newStatus));

        // Insert notification for the customer
        $stmt = $pdo->prepare("
            INSERT INTO notifications (user_id, order_group_id, account_type, message, type, created_at)
            VALUES (?, ?, 'customer', ?, 'order', NOW())
        ");
        $stmt->execute([$order['user_id'], $orderGroupId, $message]);
        
        // ✅ Send email notification
        try {
            // Get order items for email
            $stmt = $pdo->prepare("
                SELECT o.quantity, o.price, p.name
                FROM orders o
                JOIN products p ON o.product_id = p.id
                WHERE o.order_group_id = ?
            ");
            $stmt->execute([$orderGroupId]);
            $orderItems = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Calculate total
            $subtotal = 0;
            $items = [];
            foreach ($orderItems as $item) {
                $subtotal += $item['price'] * $item['quantity'];
                $items[] = [
                    'name' => $item['name'],
                    'quantity' => $item['quantity'],
                    'price' => $item['price']
                ];
            }
            
            $discount = floatval($order['discount_amount'] ?? 0);
            $shipping = 50;
            $total = $subtotal - $discount + $shipping;
            
            // Prepare order details for email
            $orderDetails = [
                'items' => $items,
                'total' => $total,
                'address' => $order['address']
            ];
            
            // Send email (only for shipping, completed, and cancelled statuses)
            if (in_array($newStatus, ['shipping', 'completed', 'cancelled'])) {
                sendOrderStatusEmail(
                    $order['customer_email'],
                    $order['customer_name'],
                    $orderGroupId,
                    $newStatus,
                    $orderDetails
                );
            }
            
        } catch (Exception $e) {
            error_log("❌ Failed to send order status email: " . $e->getMessage());
            // Don't fail the status update if email fails
        }
    }
    exit;
}

// =========================
// ✅ REFUND STATUS UPDATE (Stable Version)
// =========================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_refund'])) {
    try {
        // 1️⃣ Fetch refund info
        $stmt = $pdo->prepare("
            SELECT rr.user_id, rr.status, rr.order_id, o.order_group_id
            FROM refund_requests rr
            JOIN orders o ON rr.order_id = o.id
            WHERE rr.id = ?
        ");
        $stmt->execute([$_POST['refund_id']]);
        $refund = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$refund) {
            error_log("⚠️ Refund not found for ID: " . $_POST['refund_id']);
            exit;
        }

        // 2️⃣ Prevent redundant update
        if ($refund['status'] === $_POST['refund_status']) {
            error_log("ℹ️ Refund status unchanged for refund_id: " . $_POST['refund_id']);
            exit;
        }

        // 3️⃣ Update refund status
        $newStatus = $_POST['refund_status'];
        $update = $pdo->prepare("UPDATE refund_requests SET status = ? WHERE id = ?");
        $update->execute([$newStatus, $_POST['refund_id']]);

        // 4️⃣ Prepare notification
        $userId = (int)$refund['user_id'];
        $orderGroupId = (int)$refund['order_group_id'];
        $message = "Your refund request for Order #" . $orderGroupId .
                   " has been updated to " . ucfirst(htmlspecialchars($newStatus));

        // 5️⃣ Insert notification
        $insert = $pdo->prepare("
            INSERT INTO notifications (user_id, order_group_id, account_type, type, message, is_read, created_at)
            VALUES (?, ?, 'customer', 'refund', ?, 0, NOW())
        ");
        $insert->execute([$userId, $orderGroupId, $message]);

        error_log("✅ Refund notification inserted for user_id {$userId}, order_group_id {$orderGroupId}");

    } catch (PDOException $e) {
        error_log("🔥 REFUND UPDATE ERROR: " . $e->getMessage());
    }

    exit;
}




// ✅ Get counts for each status
$totalCount = $pdo->query("SELECT COUNT(*) FROM order_groups")->fetchColumn();
$pendingCount = $pdo->query("SELECT COUNT(*) FROM order_groups WHERE LOWER(status)='pending'")->fetchColumn();
$shippingCount = $pdo->query("SELECT COUNT(*) FROM order_groups WHERE LOWER(status)='shipping'")->fetchColumn();
$completedCount = $pdo->query("SELECT COUNT(*) FROM order_groups WHERE LOWER(status)='completed'")->fetchColumn();
$cancelledCount = $pdo->query("SELECT COUNT(*) FROM order_groups WHERE LOWER(status)='cancelled'")->fetchColumn();

// ✅ Filtering
$filterStatus = $_GET['status'] ?? 'all';
$where = '';
$params = [];
if ($filterStatus !== 'all') {
    $where = "WHERE LOWER(og.status)=?";
    $params[] = strtolower($filterStatus);
}

// ✅ Fetch orders with user info (including promo data)
$sql = "SELECT og.*, u.name AS username, u.email
        FROM order_groups og
        JOIN users u ON og.user_id = u.id
        $where
        ORDER BY og.created_at DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$orderGroups = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ✅ Fetch order items with refund info
$sqlItems = "SELECT o.*, p.name AS product_name, p.image, o.order_group_id,
                    r.id AS refund_id, r.status AS refund_status, r.reason, r.other_reason
             FROM orders o
             JOIN products p ON o.product_id = p.id
             LEFT JOIN refund_requests r 
             ON r.order_id=o.id AND r.product_id=o.product_id";
$stmt2 = $pdo->query($sqlItems);
$items = [];
while ($row = $stmt2->fetch(PDO::FETCH_ASSOC)) {
    $items[$row['order_group_id']][] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin - Orders Tracker</title>
<script src="https://cdn.tailwindcss.com"></script>
<style>
html, body {
  margin: 0;
  padding: 0;
  overflow-x: hidden;
  max-width: 100vw;
  width: 100%;
}
body {
  font-family: 'Inter', sans-serif;
  background: #f4f6f8;
}

/* === Order Cards === */
.order-card {
  background: white;
  border-radius: 12px;
  padding: 20px;
  margin-bottom: 20px;
  box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
  transition: all 0.2s;
}
.order-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 6px 20px rgba(0, 0, 0, 0.08);
}
.order-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  flex-wrap: wrap;
  gap: 10px;
}
.order-info { font-size: 0.95rem; color: #4b5563; }

/* === Status Badges === */
.status-badge {
  padding: 4px 12px;
  border-radius: 9999px;
  font-weight: 500;
  font-size: 0.875rem;
  color: white;
  display: inline-block;
}
.status-pending { background: #f97316; }
.status-shipping { background: #3b82f6; }
.status-completed { background: #10b981; }
.status-cancelled { background: #ef4444; }

.order-items {
  display: none;
  margin-top: 15px;
  border-top: 1px solid #e5e7eb;
  padding-top: 10px;
}
.order-item {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 10px;
  padding: 8px 0;
  border-bottom: 1px solid #e5e7eb;
}
.order-item img {
  width: 60px;
  height: 60px;
  border-radius: 8px;
  object-fit: cover;
}
.total-row {
  display: flex;
  flex-wrap: wrap;
  gap: 10px;
  margin-top: 10px;
  font-weight: 600;
  color: #111827;
}

/* === Buttons and Selects === */
select.status-select,
select.refund-select {
  padding: 6px 10px;
  border-radius: 6px;
  border: 1px solid #d1d5db;
  font-size: 0.875rem;
}
.btn-update,
.btn-print {
  padding: 6px 14px;
  border-radius: 8px;
  border: none;
  cursor: pointer;
  font-size: 0.875rem;
  background: #f97316;
  color: white;
  transition: all 0.2s;
}
.btn-update:hover,
.btn-print:hover { background: #ea580c; }

.collapsible {
  cursor: pointer;
  color: #f97316;
  font-size: 0.875rem;
  font-weight: 500;
}

/* === Modals === */
.modal {
  display: none;
  position: fixed;
  top: 0; left: 0;
  width: 100%; height: 100%;
  background: rgba(0,0,0,0.5);
  justify-content: center;
  align-items: center;
  z-index: 100;
}
.modal-content {
  background: white;
  padding: 20px;
  border-radius: 12px;
  max-width: 400px;
  width: 90%;
}
.modal-close { float: right; cursor: pointer; font-weight: bold; }

/* === Filter Buttons === */
.status-filter a { transition: all 0.2s; }
.status-filter a.bg-orange-500:hover { background: #ea580c; }

/* === Notification Badge Animation === */
@keyframes pulse {
  0%, 100% { transform: scale(1); }
  50% { transform: scale(1.15); }
}
#refundNotifCount:not(.hidden) {
  animation: pulse 1s infinite ease-in-out;
}
</style>
</head>

<body class="bg-gradient-to-br from-orange-50 to-white min-h-screen text-gray-800">

<div class="flex min-h-screen">
<?php include('admin_navbar.php'); ?>

<main class="flex-1 p-6 mt-6">
  <!-- Header -->
  <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-3">
    <h1 class="text-3xl font-extrabold text-gray-800">Orders Tracker</h1>

    <div class="flex flex-col md:flex-row items-start md:items-center gap-3">
      <span class="text-gray-600 text-base">
        Welcome, <strong class="text-orange-600 font-bold"><?=htmlspecialchars($_SESSION['name'] ?? 'Admin')?></strong>
      </span>

      <!-- 🔔 Refund Notification Dropdown -->
      <div class="relative">
        <button id="refundNotifBtn" class="relative bg-orange-100 p-2 rounded-full hover:bg-orange-200">
          🔔
          <span id="refundNotifCount"
            class="absolute -top-1 -right-1 bg-red-600 text-white text-xs font-bold px-1 rounded-full hidden">0</span>
        </button>

        <div id="refundNotifDropdown"
          class="hidden absolute right-0 mt-2 w-72 bg-white rounded-lg shadow-lg border border-gray-200 z-50">
          <div class="px-4 py-2 font-semibold bg-orange-50 border-b">Refund Requests</div>
          <div id="refundNotifList" class="max-h-60 overflow-y-auto"></div>
        </div>
      </div>
    </div>
  </div>

  <!-- Status Filter -->
  <div class="mb-4 flex flex-wrap gap-2 status-filter">
    <?php
    $statuses = [
      'all' => ['label' => 'All', 'count' => $totalCount],
      'pending' => ['label' => 'Pending', 'count' => $pendingCount],
      'shipping' => ['label' => 'Shipping', 'count' => $shippingCount],
      'completed' => ['label' => 'Completed', 'count' => $completedCount],
      'cancelled' => ['label' => 'Cancelled', 'count' => $cancelledCount]
    ];
    foreach($statuses as $key => $data): 
      $isActive = ($filterStatus === $key);
      $badgeClass = $isActive ? 'bg-white text-orange-500' : 'bg-gray-400 text-white';
    ?>
      <a href="?status=<?=$key?>" 
         class="flex items-center gap-2 px-3 py-2 rounded transition-all <?=$isActive ? 'bg-orange-500 text-white shadow-md' : 'bg-gray-200 text-gray-700 hover:bg-gray-300'?>">
        <span><?=$data['label']?></span>
        <span class="<?=$badgeClass?> text-xs font-bold px-2 py-0.5 rounded-full min-w-[24px] text-center">
          <?=$data['count']?>
        </span>
      </a>
    <?php endforeach; ?>
  </div>

  <!-- Orders Cards -->
  <?php foreach($orderGroups as $group):
    $groupId = $group['id'];
    $orderItems = $items[$groupId] ?? [];
    $subtotal = 0;
    foreach($orderItems as $it) $subtotal += $it['price']*$it['quantity'];
    
    // Calculate discount and final total
    $discount = floatval($group['discount_amount'] ?? 0);
    $orderTotal = $subtotal - $discount;
    $promoCode = $group['promo_code'] ?? null;
    
    $status = strtolower($group['status']);
    switch($status) {
      case 'pending': $statusClass='status-pending'; break;
      case 'shipping': $statusClass='status-shipping'; break;
      case 'completed': $statusClass='status-completed'; break;
      case 'cancelled': $statusClass='status-cancelled'; break;
      default: $statusClass='status-pending';
    }
  ?>
  <div class="order-card" id="order-card-<?=$groupId?>">
    <div class="order-header">
      <div>
        <div class="font-semibold">Order #<?=$groupId?></div>
        <div class="order-info"><?=htmlspecialchars($group['username'])?> | <?=htmlspecialchars($group['email'])?></div>
        <div class="order-info text-sm"><?=date("F j, Y g:i A", strtotime($group['created_at']))?></div>
        <?php if($promoCode): ?>
          <div class="mt-1">
            <span class="inline-flex items-center gap-1 px-2 py-1 bg-green-100 text-green-700 text-xs font-semibold rounded-full">
              🎁 Promo: <?=htmlspecialchars($promoCode)?> (-<?=$currencySymbol?><?=number_format($discount,2)?>)
            </span>
          </div>
        <?php endif; ?>
      </div>
      <div class="flex items-center gap-4">
        <span class="status-badge <?=$statusClass?>"><?=ucfirst($status)?></span>
        <span class="font-bold"><?=$currencySymbol?><?=number_format($orderTotal,2)?></span>
        <span class="collapsible" onclick="toggleItems(<?=$groupId?>)">▼ View Details</span>
      </div>
    </div>

    <div class="order-items" id="items-<?=$groupId?>">
      <?php foreach($orderItems as $item):
        $itemTotal = $item['price'] * $item['quantity']; ?>
        <div class="order-item">
          <img src="<?=htmlspecialchars($item['image'])?>" alt="">
          <div class="flex-1"><?=htmlspecialchars($item['product_name'])?></div>
          <div><?=$currencySymbol?><?=number_format($item['price'],2)?></div>
          <div>x<?=$item['quantity']?></div>
          <div>= <?=$currencySymbol?><?=number_format($itemTotal,2)?></div>
          <?php if($item['refund_id']): ?>
            <button class="btn-update ml-2" onclick="openRefundModal('<?=addslashes($item['product_name'])?>','<?=addslashes($item['reason'])?>','<?=addslashes($item['other_reason'])?>',<?=$item['refund_id']?>,'<?=$item['refund_status']?>')">Refund</button>
          <?php endif; ?>
        </div>
        <?php if (strtolower($group['status'])==='completed'): ?>
        <?php
          // Fetch review tied to this order if any
          $revStmt = $pdo->prepare("SELECT * FROM product_reviews WHERE order_group_id=? AND product_id=? ORDER BY id DESC LIMIT 1");
          $revStmt->execute([$groupId, $item['product_id']]);
          $rev = $revStmt->fetch(PDO::FETCH_ASSOC);
          // Fallback: show the most recent review for this product (even if posted from a different order)
          if (!$rev) {
            $revStmt2 = $pdo->prepare("SELECT * FROM product_reviews WHERE product_id=? ORDER BY id DESC LIMIT 1");
            $revStmt2->execute([$item['product_id']]);
            $rev = $revStmt2->fetch(PDO::FETCH_ASSOC);
          }
          // Count total reviews for quick visibility
          $revCountStmt = $pdo->prepare("SELECT COUNT(*) FROM product_reviews WHERE product_id=?");
          $revCountStmt->execute([$item['product_id']]);
          $revCount = (int)$revCountStmt->fetchColumn();
        ?>
        <div class="ml-16 mr-2 mb-2 p-3 rounded border bg-gray-50">
          <div class="text-sm text-gray-600"><strong>Review:</strong> 
          <?php if($rev): ?>
            <span class="text-yellow-600"><?php echo str_repeat('★', (int)$rev['rating']); ?></span>
            <span class="ml-2"><?=htmlspecialchars($rev['review_text'] ?? '')?></span>
            <?php if(!empty($rev['image_path'])): ?>
              <div class="mt-2"><img src="<?=htmlspecialchars($rev['image_path'])?>" alt="review" style="width:80px;height:80px;object-fit:cover;border-radius:6px;border:1px solid #e5e7eb;"></div>
            <?php endif; ?>
            <div class="mt-2 text-xs">Status: <strong><?=htmlspecialchars($rev['status'])?></strong>
            <?php if($rev['status']!=='approved'): ?>
              <button class="ml-2 text-white bg-green-600 px-2 py-1 rounded" onclick="moderateReview(<?=$rev['id']?>,'approve')">Approve</button>
            <?php endif; ?>
            <?php if($rev['status']!=='blocked'): ?>
              <button class="ml-1 text-white bg-red-600 px-2 py-1 rounded" onclick="moderateReview(<?=$rev['id']?>,'block')">Block</button>
            <?php endif; ?>
            </div>
            <div class="mt-1 text-xs text-gray-500">Total reviews for this product: <strong><?=$revCount?></strong>
              <?php if($revCount>1): ?>
                <button class="ml-2 underline" onclick="viewAllReviews(<?=$item['product_id']?>)">View all</button>
              <?php endif; ?>
            </div>
          <?php else: ?>
            <span class="text-gray-400">No review yet.</span>
            <?php if($revCount>0): ?>
              <span class="ml-2 text-xs"><button class="underline" onclick="viewAllReviews(<?=$item['product_id']?>)">(View <?=$revCount?> existing review<?=$revCount>1?'s':''?>)</button></span>
            <?php endif; ?>
          <?php endif; ?></div>
        </div>
        <?php endif; ?>
      <?php endforeach; ?>

      <!-- Order Summary -->
      <div class="mt-3 p-3 bg-gray-50 rounded-lg border border-gray-200">
        <div class="flex justify-between text-sm mb-1">
          <span class="text-gray-600">Subtotal:</span>
          <span class="font-semibold"><?=$currencySymbol?><?=number_format($subtotal,2)?></span>
        </div>
        
        <?php if($discount > 0 && $promoCode): ?>
          <div class="flex justify-between text-sm mb-1 text-green-600">
            <span>Discount (<?=htmlspecialchars($promoCode)?>):</span>
            <span class="font-semibold">-<?=$currencySymbol?><?=number_format($discount,2)?></span>
          </div>
        <?php endif; ?>
        
        <div class="flex justify-between text-base font-bold pt-2 border-t border-gray-300">
          <span>Total:</span>
          <span class="text-orange-600"><?=$currencySymbol?><?=number_format($orderTotal,2)?></span>
        </div>
        
        <div class="mt-2 pt-2 border-t border-gray-200 text-xs text-gray-600">
          <div><strong>Address:</strong> <?=htmlspecialchars($group['address'] ?? 'N/A')?></div>
          <div><strong>Payment:</strong> <?=htmlspecialchars($group['payment_method'] ?? 'N/A')?></div>
          <?php if($group['paypal_order_id']): ?>
            <div><strong>PayPal Order ID:</strong> <?=htmlspecialchars($group['paypal_order_id'])?></div>
          <?php endif; ?>
        </div>
      </div>

      <div class="total-row">
        <form method="post" class="flex gap-2 flex-wrap items-center" onsubmit="updateOrderStatus(event, <?=$groupId?>)">
          <input type="hidden" name="order_group_id" value="<?=$groupId?>">
          <select name="status" class="status-select" id="status-<?=$groupId?>">
            <option value="pending" <?=$status==='pending'?'selected':''?>>Pending</option>
            <option value="shipping" <?=$status==='shipping'?'selected':''?>>Shipping</option>
            <option value="completed" <?=$status==='completed'?'selected':''?>>Completed</option>
            <option value="cancelled" <?=$status==='cancelled'?'selected':''?>>Cancelled</option>
          </select>
          <button type="submit" class="btn-update">Update</button>
        </form>
        <button class="btn-print" onclick="openInvoiceModal(<?=$groupId?>)">Print Invoice</button>
      </div>
    </div>
  </div>
  <?php endforeach; ?>
</main>
</div>

<!-- Refund Modal -->
<div id="modal-refund" class="modal">
  <div class="modal-content">
    <span class="modal-close" onclick="closeRefundModal()">×</span>
    <h3>Refund Request</h3>
    <p><strong>Product:</strong> <span id="modal-product-name"></span></p>
    <p><strong>Reason:</strong> <span id="modal-reason"></span></p>
    <p><strong>Other:</strong> <span id="modal-other-reason"></span></p>
    <form id="refund-form">
      <input type="hidden" id="modal-refund-id">
      <label for="modal-refund-status">Status:</label>
      <select id="modal-refund-status" class="refund-select">
        <option value="pending">Pending</option>
        <option value="approved">Approved</option>
        <option value="rejected">Rejected</option>
      </select>
      <button type="submit" class="btn-update">Update Refund</button>
    </form>
  </div>
</div>

<!-- Invoice Modal -->
<div id="modal-invoice" class="modal">
  <div class="modal-content" style="max-width:800px; width:90%; height:90%;">
    <span class="modal-close" onclick="closeInvoiceModal()">×</span>
    <h3>Invoice Preview</h3>
    <iframe id="invoice-frame" style="width:100%; height:80%; border:1px solid #e5e7eb; border-radius:6px;"></iframe>
    <div class="flex justify-end gap-2 mt-2">
      <button class="btn-update" onclick="printInvoice()">Print</button>
      <button class="btn-update" onclick="downloadInvoice()">Download PDF</button>
    </div>
  </div>
</div>

<script>
/* ===== Toggle Order Details ===== */
function toggleItems(id){
  const el=document.getElementById(`items-${id}`);
  if(el) el.style.display = el.style.display==='block' ? 'none' : 'block';
}

/* ===== Refund Modal ===== */
function openRefundModal(p,r,o,id,s){
  document.getElementById('modal-product-name').innerText=p;
  document.getElementById('modal-reason').innerText=r;
  document.getElementById('modal-other-reason').innerText=o||'-';
  document.getElementById('modal-refund-id').value=id;
  document.getElementById('modal-refund-status').value=s;
  document.getElementById('modal-refund').style.display='flex';
}
function closeRefundModal(){document.getElementById('modal-refund').style.display='none';}
document.getElementById('refund-form').addEventListener('submit',e=>{
  e.preventDefault();
  const id=document.getElementById('modal-refund-id').value;
  const s=document.getElementById('modal-refund-status').value;
  if(!id||!s) return;
  if(!confirm(`Change refund status to "${s}"?`)) return;
  fetch('orders_tracker.php',{
    method:'POST',
    headers:{'Content-Type':'application/x-www-form-urlencoded'},
    body:`update_refund=1&refund_id=${id}&refund_status=${s}`
  }).then(()=>{closeRefundModal(); loadRefundNotifications(); alert('Refund updated'); location.reload();});
});

/* ===== Update Order Status ===== */
function updateOrderStatus(e,id){
  e.preventDefault();
  const s=document.getElementById(`status-${id}`).value;
  if(!confirm(`Change order status to "${s}"?`)) return;
  fetch('orders_tracker.php',{
    method:'POST',
    headers:{'Content-Type':'application/x-www-form-urlencoded'},
    body:`update_status=1&order_group_id=${id}&status=${s}`
  }).then(()=>{loadRefundNotifications(); alert('Order updated'); location.reload();});
}

/* ===== Refund Notifications (Shopee-style) ===== */
const refundNotifBtn=document.getElementById('refundNotifBtn');
const refundNotifDropdown=document.getElementById('refundNotifDropdown');
refundNotifBtn.addEventListener('click',e=>{
  e.stopPropagation();
  refundNotifDropdown.classList.toggle('hidden');
});
document.addEventListener('click',e=>{
  if(!refundNotifDropdown.contains(e.target)&&!refundNotifBtn.contains(e.target))
    refundNotifDropdown.classList.add('hidden');
});

function loadRefundNotifications(){
  fetch('fetch_refund_notifs.php')
    .then(r=>r.json())
    .then(data=>{
      const list=document.getElementById('refundNotifList');
      const count=document.getElementById('refundNotifCount');
      list.innerHTML='';
      count.classList.add('hidden');
      if(data.length){
        count.textContent=data.length;
        count.classList.remove('hidden');
        data.forEach(n=>{
          const div=document.createElement('div');
          div.className="px-4 py-2 hover:bg-orange-50 cursor-pointer text-sm border-b";
          div.innerHTML=`💸 <strong>${n.customer_name}</strong> requested 
            <span class='text-orange-600 font-bold'>${n.refund_count}</span> refund(s)
            for Order #<strong>${n.order_group_id}</strong>`;
          div.onclick=()=>window.location.href=`orders_tracker.php?open_order=${n.order_group_id}`;
          list.appendChild(div);
        });
      } else {
        list.innerHTML=`<div class='px-4 py-2 text-gray-500 text-sm'>No refund requests.</div>`;
      }
    }).catch(err=>console.error('Error:',err));
}
loadRefundNotifications();
setInterval(loadRefundNotifications,10000);

/* ===== Auto Open Order ===== */
window.addEventListener('DOMContentLoaded',()=>{
  const params=new URLSearchParams(window.location.search);
  const id=params.get('open_order');
  if(!id) return;
  const row=document.getElementById(`items-${id}`);
  if(row){ row.style.display='block'; row.scrollIntoView({behavior:'smooth',block:'center'}); }
  history.replaceState(null,'',window.location.pathname);
});

/* ===== Invoice Modal ===== */
let currentInvoiceOrderId=null;
function openInvoiceModal(id){
  currentInvoiceOrderId=id;
  document.getElementById('invoice-frame').src=`invoice.php?order_id=${id}`;
  document.getElementById('modal-invoice').style.display='flex';
}
function closeInvoiceModal(){
  document.getElementById('invoice-frame').src='';
  document.getElementById('modal-invoice').style.display='none';
  currentInvoiceOrderId=null;
}
function printInvoice(){
  const iframe=document.getElementById('invoice-frame');
  if(iframe?.contentWindow) iframe.contentWindow.print();
}
function downloadInvoice(){
  if(!currentInvoiceOrderId) return;
  window.location.href=`invoice_pdf.php?order_id=${currentInvoiceOrderId}`;
}
</script>
<script>
function moderateReview(id, action){
  if(!confirm('Are you sure you want to '+action+' this review?')) return;
  fetch('admin_review_action.php', { method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'}, body:`id=${id}&action=${action}` })
    .then(r=>r.json()).then(d=>{ if(d.success){ alert('Updated'); location.reload(); } else { alert(d.error||'Failed'); } });
}

function viewAllReviews(productId){
  fetch('get_reviews.php?product_id='+encodeURIComponent(productId))
    .then(r=>r.json())
    .then(d=>{
      if(!d.success){ alert(d.error||'Failed'); return; }
      if(!d.reviews.length){ alert('No reviews yet.'); return; }
      const rows = d.reviews.map(rv=>{
        const stars = '★★★★★'.slice(0,rv.rating);
        return `${stars} - ${rv.review_text||''}`;
      }).join('\n\n');
      alert(rows);
    })
    .catch(()=>alert('Failed to load reviews'));
}
</script>
</body>
</html>

