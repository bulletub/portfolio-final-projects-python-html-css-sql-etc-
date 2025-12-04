<?php
// migrate_order_reviews.php
// One-time or re-runnable migration from legacy order_reviews + review_images
// to the unified product_reviews table used by the new Reviews system.
//
// HOW TO USE (Hostinger):
// 1) Upload this file to public_html
// 2) Visit https://YOUR_DOMAIN/migrate_order_reviews.php while logged in as admin
// 3) It will upsert reviews and print a summary
// 4) You can safely re-run; it only inserts missing rows

session_start();
require 'database.php';

// Optional simple guard - require admin session
if (!isset($_SESSION['user_id'])) {
  http_response_code(403);
  echo 'Forbidden: admin session required';
  exit;
}

// === CONFIG: Adjust these column names if your legacy schema differs ===
$TBL_ORDER_REVIEWS   = 'order_reviews';
$TBL_REVIEW_IMAGES   = 'review_images';

$COL_REVIEW_ID       = 'id';
$COL_PRODUCT_ID      = 'product_id';
$COL_ORDER_ID        = 'order_id';
$COL_USER_ID         = 'user_id';
$COL_ORDER_GROUP_ID  = 'order_group_id';     // sometimes named order_group_id/order_id
$COL_RATING          = 'rating';             // sometimes named stars/rate
$COL_REVIEW_TEXT     = 'review_text';        // sometimes named review/comment/text
$COL_CREATED_AT      = 'created_at';

$COL_IMG_REVIEW_ID   = 'review_id';          // foreign key to order_reviews.id
$COL_IMG_PATH        = 'image_path';         // path or url
// =====================================================================

function safeCol($pdo, $table, $col, $fallbacks = []) {
  // Return first existing column name from candidates
  $cands = array_merge([$col], $fallbacks);
  $cols = $pdo->query("SHOW COLUMNS FROM `{$table}`")->fetchAll(PDO::FETCH_COLUMN);
  foreach ($cands as $c) if (in_array($c, $cols)) return $c;
  return null;
}

try {
  // Resolve columns if different
  $COL_REVIEW_TEXT = safeCol($pdo, $TBL_ORDER_REVIEWS, $COL_REVIEW_TEXT, ['review','comment','text','content']) ?: $COL_REVIEW_TEXT;
  $COL_RATING      = safeCol($pdo, $TBL_ORDER_REVIEWS, $COL_RATING, ['stars','rate','score']) ?: $COL_RATING;
  $COL_ORDER_GROUP_ID = safeCol($pdo, $TBL_ORDER_REVIEWS, $COL_ORDER_GROUP_ID, ['order_id','orderGroupId']) ?: $COL_ORDER_GROUP_ID;
  $COL_CREATED_AT  = safeCol($pdo, $TBL_ORDER_REVIEWS, $COL_CREATED_AT, ['created','timestamp','createdOn']) ?: $COL_CREATED_AT;
  $COL_PRODUCT_ID  = safeCol($pdo, $TBL_ORDER_REVIEWS, $COL_PRODUCT_ID, ['prod_id','pid']);
  $COL_ORDER_ID    = safeCol($pdo, $TBL_ORDER_REVIEWS, $COL_ORDER_ID, ['orders_id','o_id','orderItemId']);

  // Build SQL depending on available columns
  if ($COL_PRODUCT_ID) {
    // order_reviews already has product_id
    $sql = "SELECT r.`$COL_REVIEW_ID` AS rid,
                   r.`$COL_PRODUCT_ID` AS product_id,
                   r.`$COL_USER_ID` AS user_id,
                   r.`$COL_ORDER_GROUP_ID` AS order_group_id,
                   r.`$COL_RATING` AS rating,
                   r.`$COL_REVIEW_TEXT` AS review_text,
                   r.`$COL_CREATED_AT` AS created_at,
                   (SELECT i.`$COL_IMG_PATH` FROM `$TBL_REVIEW_IMAGES` i WHERE i.`$COL_IMG_REVIEW_ID`=r.`$COL_REVIEW_ID` ORDER BY i.`$COL_IMG_REVIEW_ID` LIMIT 1) AS image_path
            FROM `$TBL_ORDER_REVIEWS` r
            ORDER BY r.`$COL_REVIEW_ID` ASC";
  } elseif ($COL_ORDER_ID) {
    // No product_id on order_reviews; derive via orders table
    // Detect orders table columns
    $ordersCols = $pdo->query("SHOW COLUMNS FROM `orders`")->fetchAll(PDO::FETCH_COLUMN);
    $ORDERS_ID_COL = in_array('id',$ordersCols)?'id':$ordersCols[0];
    $ORDERS_P_COL  = in_array('product_id',$ordersCols)?'product_id':'productId';
    $ORDERS_G_COL  = in_array('order_group_id',$ordersCols)?'order_group_id':'orderGroupId';
    $sql = "SELECT r.`$COL_REVIEW_ID` AS rid,
                   o.`$ORDERS_P_COL` AS product_id,
                   r.`$COL_USER_ID` AS user_id,
                   o.`$ORDERS_G_COL` AS order_group_id,
                   r.`$COL_RATING` AS rating,
                   r.`$COL_REVIEW_TEXT` AS review_text,
                   r.`$COL_CREATED_AT` AS created_at,
                   (SELECT i.`$COL_IMG_PATH` FROM `$TBL_REVIEW_IMAGES` i WHERE i.`$COL_IMG_REVIEW_ID`=r.`$COL_REVIEW_ID` ORDER BY i.`$COL_IMG_REVIEW_ID` LIMIT 1) AS image_path
            FROM `$TBL_ORDER_REVIEWS` r
            JOIN `orders` o ON o.`$ORDERS_ID_COL` = r.`$COL_ORDER_ID`
            ORDER BY r.`$COL_REVIEW_ID` ASC";
  } else {
    throw new Exception('order_reviews does not contain product_id or order_id. Please tell me the correct column so I can map product_id.');
  }
  $rows = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);

  $ins = $pdo->prepare("INSERT INTO product_reviews (product_id,user_id,order_group_id,rating,review_text,image_path,status,created_at)
                        SELECT ?,?,?,?,?,?,'approved',? FROM DUAL
                        WHERE NOT EXISTS (
                          SELECT 1 FROM product_reviews pr
                          WHERE pr.product_id=? AND pr.user_id<=>? AND pr.order_group_id<=>?
                        )");

  $ok = 0; $skipped = 0;
  foreach ($rows as $r) {
    // Normalize
    $pid = (int)$r['product_id'] ?: null;
    $uid = $r['user_id'] !== null ? (int)$r['user_id'] : null;
    $og  = $r['order_group_id'] !== null ? (int)$r['order_group_id'] : null;
    $rating = max(1, min(5, (int)($r['rating'] ?: 5)));
    $txt = trim((string)$r['review_text']);
    $img = $r['image_path'] ?: null;
    $created = $r['created_at'] ?: date('Y-m-d H:i:s');

    $ins->execute([$pid,$uid,$og,$rating,$txt,$img,$created,$pid,$uid,$og]);
    if ($ins->rowCount()>0) $ok++; else $skipped++;
  }

  echo "Migrated: $ok, Skipped (already present): $skipped";
} catch (Throwable $e) {
  http_response_code(500);
  echo 'Error: '.$e->getMessage();
}
