(function(){
  const toggle = document.getElementById('admin-chat-toggle');
  const panel = document.getElementById('admin-chat-panel');
  const closeBtn = document.getElementById('admin-chat-close');
  const listDiv = document.getElementById('admin-chat-list');
  const titleDiv = document.getElementById('admin-chat-title');
  const msgsDiv = document.getElementById('admin-chat-messages');
  const form = document.getElementById('admin-chat-form');
  const input = document.getElementById('admin-chat-input');

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
  if (toggle) {
    toggle.style.position='relative';
    toggle.appendChild(badge);
  }

  let currentChatId = null;
  let lastId = 0;
  let pollTimer = null;
  const myMsgs = new Map(); // id -> status element
  const renderedIds = new Set();
  let unreadTimer = null;

  function fmt(str){ return (str||'').length>60 ? str.slice(0,60)+'…' : (str||''); }

  function renderList(chats){
    let totalUnread = 0;
    if(!Array.isArray(chats) || chats.length===0){
      listDiv.innerHTML = '<div style="padding:10px; font-size:12px; color:#6b7280;">No chats yet</div>';
      badge.style.display = 'none';
      return;
    }
    listDiv.innerHTML = chats.map(c=>{
      totalUnread += (c.unread_count||0);
      const label = c.username || c.email || ('Guest #'+c.id);
      const last = c.last_message ? fmt(c.last_message.message) : 'No messages yet';
      const unread = c.unread_count ? `<span style=\"background:#ef4444;color:#fff;border-radius:10px;padding:0 6px;font-size:11px;margin-left:6px;\">${c.unread_count}</span>` : '';
      return `<a href=\"#\" data-chatid=\"${c.id}\" class=\"admin-chat-item\" style=\"display:block; padding:10px; border-bottom:1px solid #f3f4f6; text-decoration:none; color:#111827;\">
        <div style=\"font-weight:600; font-size:13px; display:flex; align-items:center; justify-content:space-between;\">
          <span>${label}</span>
          ${unread}
        </div>
        <div style=\"font-size:12px; color:#6b7280;\">${last}</div>
      </a>`;
    }).join('');

    badge.style.display = totalUnread>0 ? 'block' : 'none';

    listDiv.querySelectorAll('.admin-chat-item').forEach(a=>{
      a.addEventListener('click', function(e){
        e.preventDefault();
        const id = parseInt(this.getAttribute('data-chatid'));
        openChat(id, true);
      });
    });
  }

  function statusLabel(s){
    if (s==='seen') return 'Seen';
    if (s==='delivered') return 'Delivered';
    if (s==='sent') return 'Sent';
    if (s==='failed') return 'Not sent';
    return '';
  }

  function appendMessage(m){
    const mid = parseInt(m.id||0);
    if (mid && renderedIds.has(mid)) return; // avoid duplicates
    const isAdmin = m.sender_type==='admin';
    const wrap = document.createElement('div');
    wrap.style.display = 'flex';
    // Admin replies on the right, user messages on the left
    wrap.style.justifyContent = isAdmin ? 'flex-end' : 'flex-start';
    wrap.style.margin = '6px 0';
    const b = document.createElement('div');
    b.style.maxWidth = '85%';
    b.style.padding = '6px 8px';
    b.style.borderRadius = '10px';
    b.style.border = '1px solid #eee';
    b.style.background = isAdmin ? '#fff' : '#ffedd5';
    b.style.fontSize = '13px';
    const text = document.createElement('span');
    text.textContent = m.message;
    b.appendChild(text);
    if (isAdmin) {
      const status = document.createElement('span');
      status.style.fontSize='11px';
      status.style.marginLeft='8px';
      status.style.color='#6b7280';
      status.textContent = statusLabel(m.status);
      b.appendChild(status);
      if (mid) myMsgs.set(mid, status);
    }
    wrap.appendChild(b);
    msgsDiv.appendChild(wrap);
    msgsDiv.scrollTop = msgsDiv.scrollHeight;
    if (mid) { renderedIds.add(mid); lastId = Math.max(lastId, mid); }
  }

  function poll(){
    if(!currentChatId) return;
    fetch('chat_poll.php?chat_id='+encodeURIComponent(currentChatId)+'&after_id='+encodeURIComponent(lastId)+'&viewer_type=admin')
      .then(r=>r.json()).then(d=>{
        if(d.success){
          (d.messages||[]).forEach(m=>{ appendMessage(m); });
          // Update statuses for own messages
          (d.messages||[]).forEach(m=>{
            if (m.sender_type==='admin' && myMsgs.has(parseInt(m.id||0))) {
              myMsgs.get(parseInt(m.id)).textContent = statusLabel(m.status);
            }
          });
        }
      }).finally(()=>{ pollTimer = setTimeout(poll, 1200); });
  }

  function markSeen(){
    if(!currentChatId) return;
    fetch('chat_mark_read.php', { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({ chat_id: currentChatId, viewer_type:'admin' }) })
      .then(()=> loadList());
  }

  function openChat(id, doSeen){
    currentChatId = id; lastId = 0; msgsDiv.innerHTML = '';
    titleDiv.textContent = 'Chat #'+id;
    fetch('chat_poll.php?chat_id='+encodeURIComponent(currentChatId)+'&after_id=0&viewer_type=admin')
      .then(r=>r.json()).then(d=>{
        if(d.success){ (d.messages||[]).forEach(m=>{ lastId = Math.max(lastId, parseInt(m.id)); appendMessage(m); }); }
        clearTimeout(pollTimer); poll();
        if (doSeen) markSeen();
      });
  }

  function loadList(){
    listDiv.innerHTML = '<div style="padding:10px; font-size:12px; color:#6b7280;">Loading...</div>';
    fetch('chat_list.php')
      .then(function(r){ return r.text(); })
      .then(function(txt){ var d; try { d = JSON.parse(txt); } catch(e){ throw new Error(txt); } return d; })
      .then(function(d){
        if(d && d.success){ renderList(d.chats); }
        else { listDiv.innerHTML = '<div style="padding:10px; font-size:12px; color:#ef4444;">'+((d && d.error) || 'Error loading chats')+'</div>'; }
      })
      .catch(function(err){
        listDiv.innerHTML = '<div style="padding:10px; font-size:12px; color:#ef4444;">'+(err && err.message ? err.message : 'Error loading chats')+'</div>';
      });
  }

  if (toggle) toggle.addEventListener('click', function(){
    const show = panel.style.display !== 'block';
    panel.style.display = show ? 'block' : 'none';
    if (show) { loadList(); }
  });

  if (closeBtn) closeBtn.addEventListener('click', function(){ panel.style.display = 'none'; });

  if (form) form.addEventListener('submit', function(e){
    e.preventDefault();
    const val = input.value.trim();
    if(!val || !currentChatId) return;
    input.value='';
    fetch('chat_send.php', { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({ chat_id: currentChatId, message: val, sender_type:'admin' }) })
      .then(r=>r.json()).then(d=>{ if(d.success){ appendMessage({ sender_type:'admin', message: val, id:d.message_id, status:'sent' }); } });
  });

  // Background unread badge check when panel is closed
  function checkUnread(){
    if (panel.style.display === 'block') return; // only when closed
    fetch('chat_list.php')
      .then(r=>r.json())
      .then(d=>{
        if (d && d.success && Array.isArray(d.chats)) {
          const total = d.chats.reduce((sum,c)=> sum + (parseInt(c.unread_count||0) || 0), 0);
          badge.style.display = total > 0 ? 'block' : 'none';
        }
      })
      .catch(()=>{});
  }

  // start polling unread indicator
  unreadTimer = setInterval(checkUnread, 4000);
})();
