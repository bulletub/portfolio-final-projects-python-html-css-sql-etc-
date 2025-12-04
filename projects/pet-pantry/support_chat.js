(function(){
  let chatId = null;
  let lastId = 0;
  let isOpen = false;
  const toggleBtn = document.getElementById('pp-chat-toggle');
  const panel = document.getElementById('pp-chat-panel');
  const closeBtn = document.getElementById('pp-chat-close');
  const messagesDiv = document.getElementById('pp-chat-messages');
  const form = document.getElementById('pp-chat-form');
  const input = document.getElementById('pp-chat-input');

  // badge
  let badge = document.createElement('span');
  badge.style.position='absolute';
  badge.style.right='-2px';
  badge.style.top='-2px';
  badge.style.width='14px';
  badge.style.height='14px';
  badge.style.borderRadius='50%';
  badge.style.background='#ef4444';
  badge.style.display='none';
  if (toggleBtn) {
    toggleBtn.style.position='relative';
    toggleBtn.appendChild(badge);
  }

  const myMsgs = new Map(); // id -> element for status
  const renderedIds = new Set();

  function bubble(msg){
    const wrap = document.createElement('div');
    const isAdmin = msg.sender_type === 'admin';
    wrap.style.margin = '8px 0';
    wrap.style.display = 'flex';
    wrap.style.justifyContent = isAdmin ? 'flex-start' : 'flex-end';

    const bubble = document.createElement('div');
    bubble.style.maxWidth = '80%';
    bubble.style.padding = '8px 10px';
    bubble.style.borderRadius = '12px';
    bubble.style.background = isAdmin ? '#fff' : '#ffedd5';
    bubble.style.border = '1px solid #eee';
    bubble.style.fontSize = '14px';
    bubble.style.lineHeight = '1.4';

    const text = document.createElement('span');
    text.textContent = msg.message;
    bubble.appendChild(text);

    if (!isAdmin) {
      const status = document.createElement('span');
      status.style.fontSize='11px';
      status.style.marginLeft='8px';
      status.style.color='#6b7280';
      status.textContent = statusLabel(msg.status);
      bubble.appendChild(status);
      if (msg.id) myMsgs.set(parseInt(msg.id), status);
    }

    wrap.appendChild(bubble);
    return wrap;
  }

  function statusLabel(s){
    if (s==='seen') return 'Seen';
    if (s==='delivered') return 'Delivered';
    if (s==='sent') return 'Sent';
    if (s==='failed') return 'Not sent';
    return '';
  }

  function appendMessage(msg){
    const mid = parseInt(msg.id || 0);
    if (mid && renderedIds.has(mid)) return; // avoid duplicates
    if (!messagesDiv) return; // safety check
    const wrap = bubble(msg);
    messagesDiv.appendChild(wrap);
    messagesDiv.scrollTop = messagesDiv.scrollHeight;
    if (mid) { renderedIds.add(mid); lastId = Math.max(lastId, mid); }
  }

  function updateBadge(){
    // If there are any admin messages not seen yet, show badge on next poll; backend marks delivered, seen happens when open
    // Simple rule: show badge if panel is closed
    if (!badge) return;
    badge.style.display = isOpen ? 'none' : 'block';
  }

  function markSeen(){
    if (!chatId) return;
    fetch('chat_mark_read.php', { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({ chat_id: chatId, viewer_type:'user' }) })
      .then(()=>{ if (badge) badge.style.display='none'; });
  }

  function init(){
    fetch('chat_init.php').then(r=>r.json()).then(d=>{
      if(d.success){ chatId = d.chat_id; poll(); }
    }).catch(()=>{});
  }

  function applyStatuses(messages){
    messages.forEach(m => {
      if (m.sender_type==='user' && myMsgs.has(parseInt(m.id))) {
        const el = myMsgs.get(parseInt(m.id));
        el.textContent = statusLabel(m.status);
      }
    });
  }

  function poll(){
    if(!chatId) return setTimeout(poll, 2000);
    fetch(`chat_poll.php?chat_id=${encodeURIComponent(chatId)}&after_id=${encodeURIComponent(lastId)}&viewer_type=user`)
      .then(r=>r.json())
      .then(d=>{
        if(d.success && Array.isArray(d.messages)){
          if (!isOpen && d.messages.some(m=>m.sender_type==='admin') && badge) { badge.style.display='block'; }
          d.messages.forEach(m=>{ appendMessage(m); });
          applyStatuses(d.messages);
        }
      })
      .finally(()=> setTimeout(poll, 2000));
  }

  function send(message){
    if(!chatId || !message.trim()) return;
    const payload = { chat_id: chatId, message: message.trim(), sender_type: 'user' };
    fetch('chat_send.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(payload) })
      .then(r=>r.json())
      .then(d=>{
        if(d.success){
          appendMessage({ sender_type:'user', message: message.trim(), id:d.message_id, status:'sent' });
        }
      })
      .catch(()=>{})
  }

  if (toggleBtn) toggleBtn.addEventListener('click', function(){
    isOpen = !isOpen;
    panel.style.display = isOpen ? 'block' : 'none';
    if (isOpen) { markSeen(); }
  });

  if (closeBtn) closeBtn.addEventListener('click', function(){
    isOpen = false;
    panel.style.display = 'none';
  });

  if (form) form.addEventListener('submit', function(e){
    e.preventDefault();
    const val = input.value;
    if(!val.trim()) return;
    input.value='';
    send(val);
  });

  // Only initialize if all required elements exist
  if (toggleBtn && panel && messagesDiv && form && input) {
    init();
  } else {
    console.warn('Chat widget elements not found');
  }
})();
