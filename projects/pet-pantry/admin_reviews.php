<?php
session_start();
require 'database.php';

// RBAC (reuse admin roles policy)
$userId = $_SESSION['user_id'] ?? 0;
if (!$userId) { header('Location: Login_and_creating_account_fixed.php#login'); exit; }
$rolesStmt = $pdo->prepare("SELECT role_name FROM admin_roles WHERE user_id=?");
$rolesStmt->execute([$userId]);
$roles = $rolesStmt->fetchAll(PDO::FETCH_COLUMN);
if (!in_array('super_admin',$roles) && !in_array('admin_dashboard',$roles) && !in_array('inventory',$roles)) {
  header('Location: Login_and_creating_account_fixed.php#login'); exit;
}

// Filters
$q = trim($_GET['q'] ?? '');
$status = $_GET['status'] ?? '';
$productId = isset($_GET['product_id']) ? (int)$_GET['product_id'] : 0;

$sql = "SELECT r.*, p.name AS product_name, u.email FROM product_reviews r
        LEFT JOIN products p ON p.id=r.product_id
        LEFT JOIN users u ON u.id=r.user_id
        WHERE 1=1";
$params = [];
if ($q !== '') { $sql .= " AND (p.name LIKE ? OR u.email LIKE ? OR r.review_text LIKE ?)"; $params[]="%$q%"; $params[]="%$q%"; $params[]="%$q%"; }
if (in_array($status,['pending','approved','blocked'])) { $sql .= " AND r.status=?"; $params[]=$status; }
if ($productId>0) { $sql .= " AND r.product_id=?"; $params[]=$productId; }
$sql .= " ORDER BY r.id DESC LIMIT 300";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Admin Reviews</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 min-h-screen">
<div class="flex min-h-screen">
  <?php include 'admin_navbar.php'; ?>
  <div class="flex-1 p-6">
    <h1 class="text-2xl font-bold mb-4">📝 Product Reviews</h1>

    <form method="get" class="bg-white rounded-lg shadow p-4 mb-4 grid grid-cols-1 md:grid-cols-4 gap-3">
      <input type="text" name="q" value="<?=htmlspecialchars($q)?>" placeholder="Search product, email or text" class="border rounded px-3 py-2" />
      <select name="status" class="border rounded px-3 py-2">
        <option value="">All statuses</option>
        <option value="pending" <?=$status==='pending'?'selected':''?>>Pending</option>
        <option value="approved" <?=$status==='approved'?'selected':''?>>Approved</option>
        <option value="blocked" <?=$status==='blocked'?'selected':''?>>Blocked</option>
      </select>
      <input type="number" name="product_id" value="<?=$productId?:''?>" placeholder="Product ID" class="border rounded px-3 py-2" />
      <button class="bg-orange-500 text-white rounded px-4 py-2">Filter</button>
    </form>

    <div class="bg-white rounded-lg shadow overflow-x-auto">
      <table class="min-w-full text-sm">
        <thead class="bg-gray-100">
          <tr>
            <th class="px-3 py-2 text-left">ID</th>
            <th class="px-3 py-2 text-left">Product</th>
            <th class="px-3 py-2 text-left">Rating</th>
            <th class="px-3 py-2 text-left">Text</th>
            <th class="px-3 py-2 text-left">Image</th>
            <th class="px-3 py-2 text-left">User</th>
            <th class="px-3 py-2 text-left">Status</th>
            <th class="px-3 py-2 text-left">Actions</th>
          </tr>
        </thead>
        <tbody>
        <?php foreach($reviews as $r): ?>
          <tr class="border-t">
            <td class="px-3 py-2">#<?=$r['id']?></td>
            <td class="px-3 py-2">
              <div class="font-medium"><?=htmlspecialchars($r['product_name'] ?: ('Product #'.$r['product_id']))?></div>
              <div class="text-xs text-gray-500">ID: <?=$r['product_id']?></div>
            </td>
            <td class="px-3 py-2 text-yellow-600"><?=str_repeat('★',(int)$r['rating'])?></td>
            <td class="px-3 py-2 max-w-xs">
              <div class="truncate" title="<?=htmlspecialchars($r['review_text'] ?? '')?>"><?=htmlspecialchars($r['review_text'] ?? '')?></div>
            </td>
            <td class="px-3 py-2"><?php if($r['image_path']): ?><img src="<?=htmlspecialchars($r['image_path'])?>" class="w-12 h-12 object-cover rounded border" /><?php endif; ?></td>
            <td class="px-3 py-2"><?=htmlspecialchars($r['email'] ?? 'Guest')?></td>
            <td class="px-3 py-2"><span class="px-2 py-1 rounded text-xs <?= $r['status']==='approved'?'bg-green-100 text-green-700':($r['status']==='blocked'?'bg-red-100 text-red-700':'bg-yellow-100 text-yellow-700') ?>"><?=$r['status']?></span></td>
            <td class="px-3 py-2">
              <?php if($r['status']!=='approved'): ?>
                <button onclick="moderate(<?=$r['id']?>,'approve')" class="px-2 py-1 bg-green-600 text-white rounded text-xs">Approve</button>
              <?php endif; ?>
              <?php if($r['status']!=='blocked'): ?>
                <button onclick="moderate(<?=$r['id']?>,'block')" class="px-2 py-1 bg-red-600 text-white rounded text-xs ml-1">Block</button>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
        <?php if(empty($reviews)): ?>
          <tr><td colspan="8" class="px-3 py-6 text-center text-gray-500">No reviews found.</td></tr>
        <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
<script>
function moderate(id, action){
  if(!confirm('Are you sure you want to '+action+' this review?')) return;
  fetch('admin_review_action.php', { method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'}, body: 'id='+id+'&action='+action })
    .then(r=>r.json()).then(d=>{ if(d.success){ location.reload(); } else { alert(d.error||'Failed'); } });
}
</script>
</body>
</html>
