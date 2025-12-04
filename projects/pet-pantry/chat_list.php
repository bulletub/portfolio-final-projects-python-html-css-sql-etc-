<?php
session_start();
header('Content-Type: application/json');
require 'database.php';

try {
    // Return latest chats regardless of session (widget-only data)
    // Some installs don't have a username column; select only email to avoid 1054 errors
    $stmt = $pdo->query("SELECT sc.id, sc.status, sc.last_message_at, sc.user_id, u.email FROM support_chats sc LEFT JOIN users u ON u.id=sc.user_id ORDER BY sc.last_message_at DESC LIMIT 200");
	$chats = $stmt->fetchAll(PDO::FETCH_ASSOC);
	foreach ($chats as &$c) {
		$m = $pdo->prepare("SELECT id, sender_type, message, created_at, status FROM support_messages WHERE chat_id=? ORDER BY id DESC LIMIT 1");
		$m->execute([$c['id']]);
		$c['last_message'] = $m->fetch(PDO::FETCH_ASSOC) ?: null;
		$u = $pdo->prepare("SELECT COUNT(*) FROM support_messages WHERE chat_id=? AND sender_type='user' AND status<>'seen'");
		$u->execute([$c['id']]);
		$c['unread_count'] = (int)$u->fetchColumn();
	}
	unset($c);
	
echo json_encode(['success'=>true,'chats'=>$chats]);
} catch (Throwable $e) {
    http_response_code(200);
    echo json_encode(['success'=>false,'error'=>$e->getMessage()]);
}
