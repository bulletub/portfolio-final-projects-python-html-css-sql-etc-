<?php
session_start();
header('Content-Type: application/json');
require 'database.php';

try {
	$input = json_decode(file_get_contents('php://input'), true);
	$chatId = (int)($input['chat_id'] ?? 0);
	$message = trim($input['message'] ?? '');
	$senderType = ($input['sender_type'] ?? 'user') === 'admin' ? 'admin' : 'user';
	$userId = $_SESSION['user_id'] ?? null;
	
	if ($chatId <= 0 || $message === '') {
		throw new Exception('Invalid payload');
	}
	
	$stmt = $pdo->prepare("SELECT * FROM support_chats WHERE id=? AND status='open'");
	$stmt->execute([$chatId]);
	$chat = $stmt->fetch(PDO::FETCH_ASSOC);
	if (!$chat) throw new Exception('Chat not found');
	
	$stmt = $pdo->prepare("INSERT INTO support_messages (chat_id, sender_type, sender_id, message, status) VALUES (?, ?, ?, ?, 'sent')");
	$stmt->execute([$chatId, $senderType, $userId, $message]);
	
	$pdo->prepare("UPDATE support_chats SET last_message_at = NOW() WHERE id=?")->execute([$chatId]);
	
	echo json_encode(['success' => true, 'message_id' => (int)$pdo->lastInsertId()]);
} catch (Throwable $e) {
	http_response_code(500);
	echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
