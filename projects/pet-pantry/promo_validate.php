<?php
// promo_validate.php - Validate and apply promo codes
session_start();
header('Content-Type: application/json');
require_once 'database.php';

$action = $_POST['action'] ?? '';

if ($action === 'validate') {
    $code = strtoupper(trim($_POST['code'] ?? ''));
    $subtotal = (float)($_POST['subtotal'] ?? 0);
    
    if (empty($code)) {
        echo json_encode(['success' => false, 'message' => 'Please enter a promo code']);
        exit;
    }
    
    try {
        // Fetch promo
        $stmt = $pdo->prepare("
            SELECT * FROM promos 
            WHERE code = ? 
            AND is_active = 1
            AND (start_date IS NULL OR start_date <= NOW())
            AND (end_date IS NULL OR end_date >= NOW())
        ");
        $stmt->execute([$code]);
        $promo = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$promo) {
            echo json_encode(['success' => false, 'message' => 'Invalid or expired promo code']);
            exit;
        }
        
        // Check minimum purchase
        if ($subtotal < $promo['min_purchase']) {
            echo json_encode([
                'success' => false, 
                'message' => 'Minimum purchase of ₱' . number_format($promo['min_purchase']) . ' required'
            ]);
            exit;
        }
        
        // Check usage limit
        if ($promo['usage_limit'] && $promo['usage_count'] >= $promo['usage_limit']) {
            echo json_encode(['success' => false, 'message' => 'Promo code usage limit reached']);
            exit;
        }
        
        // Calculate discount
        $discount = 0;
        if ($promo['discount_type'] === 'percent') {
            $discount = ($subtotal * $promo['discount_value']) / 100;
            
            // Apply max discount cap if set
            if ($promo['max_discount'] && $discount > $promo['max_discount']) {
                $discount = $promo['max_discount'];
            }
        } else {
            $discount = $promo['discount_value'];
        }
        
        // Ensure discount doesn't exceed subtotal
        if ($discount > $subtotal) {
            $discount = $subtotal;
        }
        
        $newTotal = $subtotal - $discount;
        
        echo json_encode([
            'success' => true,
            'promo_id' => $promo['id'],
            'code' => $promo['code'],
            'title' => $promo['title'],
            'discount' => $discount,
            'new_total' => $newTotal,
            'message' => 'Promo code applied! You saved ₱' . number_format($discount, 2)
        ]);
        
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Error validating promo code']);
    }
    
} elseif ($action === 'apply') {
    // This is called after order is placed to increment usage count
    $promoId = (int)($_POST['promo_id'] ?? 0);
    
    if ($promoId) {
        try {
            $stmt = $pdo->prepare("UPDATE promos SET usage_count = usage_count + 1 WHERE id = ?");
            $stmt->execute([$promoId]);
            echo json_encode(['success' => true]);
        } catch (PDOException $e) {
            echo json_encode(['success' => false]);
        }
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid action']);
}
?>

