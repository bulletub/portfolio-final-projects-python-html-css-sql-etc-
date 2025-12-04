<?php
session_start();
header('Content-Type: application/json');
require 'database.php';

try {
	$input = json_decode(file_get_contents('php://input'), true);
	$chatId = (int)($input['chat_id'] ?? 0);
	$viewer = ($input['viewer_type'] ?? 'user') === 'admin' ? 'admin' : 'user';
	if ($chatId<=0) throw new Exception('Invalid chat');
	
	$stmt = $pdo->prepare("UPDATE support_messages SET is_read=1, status='seen' WHERE chat_id=? AND sender_type<>? AND status<>'seen'");
	$stmt->execute([$chatId, $viewer]);
	
	echo json_encode(['success'=>true, 'updated'=>$stmt->rowCount()]);
} catch (Throwable $e) {
	http_response_code(500);
	echo json_encode(['success'=>false,'error'=>$e->getMessage()]);
}
