<?php
session_start();
header('Content-Type: application/json');
require 'database.php';

try {
	$sessionId = session_id();
	$userId = $_SESSION['user_id'] ?? null;
	
	// Find existing open chat for this session or user
	if ($userId) {
		$stmt = $pdo->prepare("SELECT * FROM support_chats WHERE user_id = ? AND status='open' ORDER BY id DESC LIMIT 1");
		$stmt->execute([$userId]);
		$chat = $stmt->fetch(PDO::FETCH_ASSOC);
	} else {
		$stmt = $pdo->prepare("SELECT * FROM support_chats WHERE session_id = ? AND status='open' ORDER BY id DESC LIMIT 1");
		$stmt->execute([$sessionId]);
		$chat = $stmt->fetch(PDO::FETCH_ASSOC);
	}
	
	if (!$chat) {
		$stmt = $pdo->prepare("INSERT INTO support_chats (session_id, user_id) VALUES (?, ?)");
		$stmt->execute([$sessionId, $userId]);
		$chatId = (int)$pdo->lastInsertId();
	} else {
		$chatId = (int)$chat['id'];
	}
	
	echo json_encode(['success' => true, 'chat_id' => $chatId]);
} catch (Throwable $e) {
	http_response_code(500);
	echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
