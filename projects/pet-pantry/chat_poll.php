<?php
session_start();
header('Content-Type: application/json');
require 'database.php';

try {
	$chatId = (int)($_GET['chat_id'] ?? 0);
	$afterId = (int)($_GET['after_id'] ?? 0);
	$viewer = ($_GET['viewer_type'] ?? 'user') === 'admin' ? 'admin' : 'user';
	
	if ($chatId <= 0) throw new Exception('Invalid chat');
	
	// Mark as delivered for messages sent to this viewer
	$mark = $pdo->prepare("UPDATE support_messages SET status='delivered' WHERE chat_id=? AND sender_type<>? AND status='sent'");
	$mark->execute([$chatId, $viewer]);
	
	$stmt = $pdo->prepare("SELECT * FROM support_messages WHERE chat_id=? AND id>? ORDER BY id ASC");
	$stmt->execute([$chatId, $afterId]);
	$messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
	
	echo json_encode(['success' => true, 'messages' => $messages]);
} catch (Throwable $e) {
	http_response_code(500);
	echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
