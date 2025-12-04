<?php
session_start();
require 'database.php';
require 'admin_navbar.php';

// auth: reuse roles from admin_cms
$userId = $_SESSION['user_id'] ?? 0;
$name = $_SESSION['name'] ?? 'Admin';
$roleStmt = $pdo->prepare("SELECT role_name FROM admin_roles WHERE user_id=?");
$roleStmt->execute([$userId]);
$roles = $roleStmt->fetchAll(PDO::FETCH_COLUMN);
if (!$userId || (!in_array('super_admin',$roles) && !in_array('admin_dashboard',$roles))) {
	header('Location: Login_and_creating_account_fixed.php#login');
	exit;
}

// fetch chats
$chats = $pdo->query("SELECT sc.*, u.email as user_email, u.username as username FROM support_chats sc LEFT JOIN users u ON u.id=sc.user_id ORDER BY sc.last_message_at DESC LIMIT 100")->fetchAll(PDO::FETCH_ASSOC);

$activeChatId = isset($_GET['chat_id']) ? (int)$_GET['chat_id'] : (count($chats) ? (int)$chats[0]['id'] : 0);

$messages = [];
if ($activeChatId) {
	$stmt = $pdo->prepare("SELECT * FROM support_messages WHERE chat_id=? ORDER BY id ASC");
	$stmt->execute([$activeChatId]);
	$messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8" />
	<meta name="viewport" content="width=device-width, initial-scale=1" />
	<title>Support Chat | Admin</title>
	<script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 min-h-screen">
<div class="flex">
	<?php include 'admin_navbar.php'; ?>
	<div class="flex-1 p-6">
		<h1 class="text-2xl font-bold mb-4">💬 Support Chat</h1>
		<div class="grid grid-cols-1 md:grid-cols-3 gap-4">
			<!-- Chat list -->
			<div class="bg-white border rounded-lg overflow-hidden md:col-span-1">
				<div class="px-4 py-3 border-b font-semibold">Recent Chats</div>
				<div class="max-h-[70vh] overflow-y-auto">
					<?php foreach($chats as $c): ?>
					<a href="?chat_id=<?php echo (int)$c['id']; ?>" class="block px-4 py-3 border-b hover:bg-orange-50 <?php echo $activeChatId===(int)$c['id']?'bg-orange-50':''; ?>">
						<div class="flex items-center justify-between">
							<div>
								<div class="font-semibold">Chat #<?php echo (int)$c['id']; ?></div>
								<div class="text-xs text-gray-500">User: <?php echo htmlspecialchars($c['username'] ?? $c['user_email'] ?? 'Guest'); ?></div>
							</div>
							<div class="text-xs text-gray-500"><?php echo htmlspecialchars($c['last_message_at']); ?></div>
						</div>
					</a>
					<?php endforeach; ?>
				</div>
			</div>
			<!-- Message panel -->
			<div class="bg-white border rounded-lg overflow-hidden md:col-span-2 flex flex-col">
				<div class="px-4 py-3 border-b font-semibold flex items-center justify-between">
					<div>Chat #<?php echo (int)$activeChatId; ?></div>
					<?php if ($activeChatId): ?>
					<form method="post" action="" onsubmit="return false;"></form>
					<?php endif; ?>
				</div>
				<div id="adminChatMessages" class="p-4 flex-1 overflow-y-auto bg-gray-50">
					<?php foreach($messages as $m): $isAdmin = $m['sender_type']==='admin'; ?>
					<div class="mb-2 flex <?php echo $isAdmin?'justify-start':'justify-end'; ?>">
						<div class="max-w-[75%] rounded-xl border px-3 py-2 text-sm <?php echo $isAdmin?'bg-white':'bg-orange-50'; ?>"><?php echo nl2br(htmlspecialchars($m['message'])); ?></div>
					</div>
					<?php endforeach; ?>
				</div>
				<?php if ($activeChatId): ?>
				<div class="border-t p-3">
					<form id="adminChatForm" class="flex gap-2">
						<input id="adminChatInput" type="text" placeholder="Type a reply..." class="flex-1 border rounded-lg px-3 py-2" />
						<button class="bg-orange-500 text-white px-4 py-2 rounded-lg">Send</button>
					</form>
				</div>
				<?php endif; ?>
			</div>
		</div>
	</div>
</div>

<script>
const chatId = <?php echo (int)$activeChatId; ?>;
const messagesDiv = document.getElementById('adminChatMessages');
const form = document.getElementById('adminChatForm');
const input = document.getElementById('adminChatInput');
let lastId = 0;

if (messagesDiv) {
  const items = messagesDiv.querySelectorAll('[data-id]');
  items.forEach(el => { lastId = Math.max(lastId, parseInt(el.dataset.id||'0')); });
}

function appendMessage(m){
  const wrap = document.createElement('div');
  wrap.className = 'mb-2 flex ' + (m.sender_type==='admin'?'justify-start':'justify-end');
  const bubble = document.createElement('div');
  bubble.className = 'max-w-[75%] rounded-xl border px-3 py-2 text-sm ' + (m.sender_type==='admin'?'bg-white':'bg-orange-50');
  bubble.textContent = m.message;
  wrap.appendChild(bubble);
  messagesDiv.appendChild(wrap);
  messagesDiv.scrollTop = messagesDiv.scrollHeight;
}

function poll(){
  if(!chatId) return;
  fetch('chat_poll.php?chat_id='+encodeURIComponent(chatId)+'&after_id='+encodeURIComponent(lastId))
    .then(r=>r.json()).then(d=>{
      if(d.success){
        (d.messages||[]).forEach(m=>{ lastId = Math.max(lastId, parseInt(m.id)); appendMessage(m); });
      }
    }).finally(()=> setTimeout(poll, 1500));
}

form?.addEventListener('submit', function(e){
  e.preventDefault();
  const val = input.value.trim();
  if(!val) return;
  input.value='';
  const payload = { chat_id: chatId, message: val, sender_type: 'admin' };
  fetch('chat_send.php', { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify(payload) })
    .then(r=>r.json()).then(d=>{
      if(d.success){ appendMessage({ sender_type:'admin', message: val, id: d.message_id }); }
    });
});

poll();
</script>
</body>
</html>
