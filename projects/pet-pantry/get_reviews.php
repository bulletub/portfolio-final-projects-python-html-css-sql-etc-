<?php
session_start();
header('Content-Type: application/json');
require 'database.php';

try {
    $productId = (int)($_GET['product_id'] ?? 0);
    if ($productId <= 0) throw new Exception('Invalid product');

    $viewerUserId = $_SESSION['user']['id'] ?? null; // customer id if logged in

    // --- Auto-migrate legacy order_reviews -> product_reviews for this product (incremental) ---
    try {
        $hasCol = function($table,$col) use($pdo){
            $st=$pdo->prepare("SHOW COLUMNS FROM `{$table}` LIKE ?");
            $st->execute([$col]);
            return (bool)$st->fetch();
        };
        $tblOR = 'order_reviews';
        $tblRI = 'review_images';
        $colProd = $hasCol($tblOR,'product_id') ? 'product_id' : null;
        $colOrderId = $hasCol($tblOR,'order_id') ? 'order_id' : null;
        $colOG = $hasCol($tblOR,'order_group_id') ? 'order_group_id' : ($colOrderId ?: 'order_id');
        $colRate = $hasCol($tblOR,'rating') ? 'rating' : 'stars';
        $colText = $hasCol($tblOR,'review_text') ? 'review_text' : ($hasCol($tblOR,'review')?'review':'comment');
        $colCreated = $hasCol($tblOR,'created_at') ? 'created_at' : 'created';

        if ($colProd) {
            $sql = "SELECT r.id rid, r.`$colProd` product_id, r.user_id, r.`$colOG` order_group_id, r.`$colRate` rating, r.`$colText` review_text, r.`$colCreated` created_at,
                            (SELECT i.image_path FROM `$tblRI` i WHERE i.review_id=r.id ORDER BY i.review_id LIMIT 1) image_path
                    FROM `$tblOR` r WHERE r.`$colProd`=? ORDER BY r.id DESC LIMIT 100";
            $st = $pdo->prepare($sql); $st->execute([$productId]);
        } elseif ($colOrderId) {
            // derive product via orders table
            $ordersCols = $pdo->query("SHOW COLUMNS FROM `orders`")->fetchAll(PDO::FETCH_COLUMN);
            $OID = in_array('id',$ordersCols)?'id':$ordersCols[0];
            $OP  = in_array('product_id',$ordersCols)?'product_id':'productId';
            $OG  = in_array('order_group_id',$ordersCols)?'order_group_id':'orderGroupId';
            $sql = "SELECT r.id rid, o.`$OP` product_id, r.user_id, o.`$OG` order_group_id, r.`$colRate` rating, r.`$colText` review_text, r.`$colCreated` created_at,
                            (SELECT i.image_path FROM `$tblRI` i WHERE i.review_id=r.id ORDER BY i.review_id LIMIT 1) image_path
                    FROM `$tblOR` r JOIN orders o ON o.`$OID`=r.`$colOrderId`
                    WHERE o.`$OP`=? ORDER BY r.id DESC LIMIT 100";
            $st = $pdo->prepare($sql); $st->execute([$productId]);
        } else { $st=null; }

        if ($st) {
            $rows = $st->fetchAll(PDO::FETCH_ASSOC);
            if ($rows) {
                $ins = $pdo->prepare("INSERT INTO product_reviews (product_id,user_id,order_group_id,rating,review_text,image_path,status,created_at)
                                      SELECT ?,?,?,?,?,?,'approved',? FROM DUAL WHERE NOT EXISTS (
                                        SELECT 1 FROM product_reviews pr WHERE pr.product_id=? AND pr.user_id<=>? AND pr.order_group_id<=>?)");
                foreach ($rows as $r) {
                    $pid=(int)$r['product_id']; $uid=$r['user_id']!==null?(int)$r['user_id']:null; $og=$r['order_group_id']!==null?(int)$r['order_group_id']:null;
                    $rating=max(1,min(5,(int)($r['rating']?:5))); $txt=trim((string)$r['review_text']); $img=$r['image_path']?:null; $created=$r['created_at']?:date('Y-m-d H:i:s');
                    $ins->execute([$pid,$uid,$og,$rating,$txt,$img,$created,$pid,$uid,$og]);
                }
            }
        }
    } catch (Throwable $e) {
        // fail-safe: ignore migration errors
    }

    // Public API: never return blocked reviews. Show approved + viewer's own if not blocked.
    $stmt = $pdo->prepare("SELECT r.*, u.email FROM product_reviews r
                           LEFT JOIN users u ON u.id=r.user_id
                           WHERE r.product_id=? AND (r.status='approved' OR (r.user_id=? AND r.status<>'blocked'))
                           ORDER BY r.id DESC LIMIT 200");
    $stmt->execute([$productId, $viewerUserId]);

    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($rows as &$row) {
        $row['is_own'] = $viewerUserId && $row['user_id'] == $viewerUserId ? 1 : 0;
    }
    unset($row);

    echo json_encode(['success'=>true,'reviews'=>$rows]);
} catch (Throwable $e) {
    http_response_code(200);
    echo json_encode(['success'=>false,'error'=>$e->getMessage()]);
}
