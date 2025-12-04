<?php
// Drop-in floating support chat widget
?>
<div id="pp-chat-widget" style="position:fixed; bottom:20px; right:20px; z-index:2147483647; font-family: Inter, system-ui, -apple-system, Segoe UI, Roboto, Arial;">
	<!-- Minimized Button -->
    <button id="pp-chat-toggle" style="width:56px; height:56px; border-radius:50%; background:linear-gradient(135deg,#ff7a18,#ffb347); color:#fff; border:none; box-shadow:0 8px 20px rgba(0,0,0,0.2); cursor:pointer; display:flex; align-items:center; justify-content:center;">
		<span style="font-size:22px;">💬</span>
	</button>

	<!-- Chat Panel -->
    <div id="pp-chat-panel" style="display:none; position:absolute; bottom:70px; right:0; width:320px; max-height:70vh; background:#fff; border-radius:16px; box-shadow:0 12px 32px rgba(0,0,0,0.25); overflow:hidden; border:1px solid #eee;">
		<div style="background:linear-gradient(135deg,#ff7a18,#ffb347); color:#fff; padding:12px 14px; display:flex; align-items:center; justify-content:space-between;">
			<div>
				<div style="font-weight:700;">Support Chat</div>
				<div style="font-size:12px; opacity:0.9;">We typically reply in a few minutes</div>
			</div>
			<button id="pp-chat-close" style="background:transparent; color:#fff; border:none; font-size:18px; cursor:pointer;">×</button>
		</div>
		<div id="pp-chat-messages" style="padding:12px; height:380px; overflow-y:auto; background:#fafafa;"></div>
		<div style="border-top:1px solid #eee; padding:8px; background:#fff;">
			<form id="pp-chat-form" style="display:flex; gap:8px;">
				<input id="pp-chat-input" type="text" placeholder="Type your message..." autocomplete="off" style="flex:1; padding:10px 12px; border:1px solid #ddd; border-radius:10px; outline:none;">
				<button type="submit" style="background:#ff7a18; color:#fff; border:none; padding:10px 12px; border-radius:10px; cursor:pointer;">Send</button>
			</form>
		</div>
	</div>
</div>

<script src="support_chat.js"></script>
