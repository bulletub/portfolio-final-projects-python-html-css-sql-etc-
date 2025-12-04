<?php
session_start();
header('Content-Type: application/json');
require 'database.php';

try {
	$userId = $_SESSION['user_id'] ?? 0;
	if (!$userId) throw new Exception('Unauthorized');
	$id = (int)($_POST['id'] ?? 0);
	$action = $_POST['action'] ?? '';
	if ($id<=0 || !in_array($action,['approve','block'])) throw new Exception('Invalid');
	$status = $action==='approve' ? 'approved' : 'blocked';
	$stmt = $pdo->prepare("UPDATE product_reviews SET status=? WHERE id=?");
	$stmt->execute([$status,$id]);
	echo json_encode(['success'=>true]);
} catch (Throwable $e) {
	echo json_encode(['success'=>false,'error'=>$e->getMessage()]);
}
