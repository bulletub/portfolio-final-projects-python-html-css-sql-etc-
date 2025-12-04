<?php
// Admin floating chat head with list + conversation
?>
<div id="admin-chat-widget" style="position:fixed; bottom:20px; right:90px; z-index:2147483647; font-family: Inter, system-ui, -apple-system, Segoe UI, Roboto, Arial;">
	<button id="admin-chat-toggle" title="Support Chats" style="width:52px; height:52px; border-radius:50%; background:#1f2937; color:#fff; border:none; box-shadow:0 8px 20px rgba(0,0,0,0.25); cursor:pointer; display:flex; align-items:center; justify-content:center;">
		<span style="font-size:18px;">🗨️</span>
	</button>
	<div id="admin-chat-panel" style="display:none; position:absolute; bottom:62px; right:0; width:380px; height:520px; background:#fff; border-radius:12px; overflow:hidden; border:1px solid #e5e7eb; box-shadow:0 20px 40px rgba(0,0,0,0.25);">
		<div style="background:#111827; color:#fff; padding:10px 12px; display:flex; align-items:center; justify-content:space-between;">
			<div style="font-weight:700;">Admin Support</div>
			<button id="admin-chat-close" style="background:transparent; border:none; color:#fff; font-size:18px; cursor:pointer;">×</button>
		</div>
		<div style="display:flex; height:calc(100% - 44px);">
			<!-- List -->
			<div style="width:45%; border-right:1px solid #eee; display:flex; flex-direction:column;">
				<div style="padding:8px 10px; border-bottom:1px solid #f3f4f6; font-size:12px; color:#6b7280;">Chats</div>
				<div id="admin-chat-list" style="flex:1; overflow-y:auto;">
					<div style="padding:10px; font-size:12px; color:#6b7280;">Loading...</div>
				</div>
			</div>
			<!-- Conversation -->
			<div style="width:55%; display:flex; flex-direction:column;">
				<div id="admin-chat-title" style="padding:8px 10px; border-bottom:1px solid #f3f4f6; font-size:12px; color:#6b7280;">Select a chat</div>
				<div id="admin-chat-messages" style="flex:1; overflow-y:auto; background:#fafafa; padding:10px;"></div>
				<div style="border-top:1px solid #eee; padding:8px;">
					<form id="admin-chat-form" style="display:flex; gap:6px;">
						<input id="admin-chat-input" type="text" placeholder="Type reply..." style="flex:1; border:1px solid #ddd; border-radius:8px; padding:8px 10px;">
						<button style="background:#111827; color:#fff; border:none; border-radius:8px; padding:8px 12px;">Send</button>
					</form>
				</div>
			</div>
		</div>
	</div>
</div>
<script src="admin_support_chat.js"></script>
