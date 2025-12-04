<?php
session_start();
header('Content-Type: application/json');
require 'database.php';

try {
	$userId = $_SESSION['user_id'] ?? ($_SESSION['user']['id'] ?? null);
	$productId = (int)($_POST['product_id'] ?? 0);
	$orderGroupId = isset($_POST['order_group_id']) ? (int)$_POST['order_group_id'] : null;
	$rating = (int)($_POST['rating'] ?? 0);
	$reviewText = trim($_POST['review_text'] ?? '');
	if ($productId<=0 || $rating<1 || $rating>5) throw new Exception('Invalid input');
	
	// Upload image if present
	$imagePath = null;
	if (isset($_FILES['image']) && $_FILES['image']['error']===UPLOAD_ERR_OK) {
		$dir = __DIR__.'/uploads/reviews/';
		if (!is_dir($dir)) mkdir($dir,0755,true);
		$ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
		if (!in_array($ext,['jpg','jpeg','png','gif','webp'])) throw new Exception('Invalid image');
		$fn = 'rev_'.time().'_'.mt_rand(1000,9999).'.'.$ext;
		$dest = $dir.$fn;
		if (!move_uploaded_file($_FILES['image']['tmp_name'],$dest)) throw new Exception('Upload failed');
		$imagePath = 'uploads/reviews/'.$fn;
	}
	
	$stmt = $pdo->prepare("INSERT INTO product_reviews (product_id,user_id,order_group_id,rating,review_text,image_path,status) VALUES (?,?,?,?,?,?, 'approved')");
	$stmt->execute([$productId,$userId,$orderGroupId,$rating,$reviewText,$imagePath]);
	echo json_encode(['success'=>true]);
} catch (Throwable $e) {
	http_response_code(200);
	echo json_encode(['success'=>false,'error'=>$e->getMessage()]);
}
