(() => {
  function initAgentChat() {
    const config = window.agentChatConfig || {};
    const listItems = document.querySelectorAll('.agent-chat-list-item');
    const messagesEl = document.getElementById('agent-chat-messages');
    const formEl = document.getElementById('agent-chat-form');
    const inputEl = document.getElementById('agent-chat-input');

    let activeTargetType = config.activeTargetType;
    let activeTargetId = config.activeTargetId;
    let lastMessageId = null;
    let pollTimer = null;

    function setActiveItem() {
      listItems.forEach(item => {
        const t = item.getAttribute('data-target-type');
        const id = item.getAttribute('data-target-id');
        if (t === activeTargetType && String(id) === String(activeTargetId)) {
          item.classList.add('active');
        } else {
          item.classList.remove('active');
        }
      });
    }

    function buildItoken() {
      if (!window.iweb || !window.md5 || !window.iweb.csrf_token) return '';
      const localTime = window.iweb.getDateTime(null, 'time');
      return window.btoa(window.md5(window.iweb.csrf_token + '#dt' + localTime) + '%' + localTime);
    }

    function getCsrfToken() {
      return (typeof _token !== 'undefined' && _token) ? _token : (document.querySelector('meta[name="csrf-token"]') || {}).content;
    }

    function renderMessages(messages) {
      if (!messagesEl) return;
      messagesEl.innerHTML = '';
      messages.forEach(msg => {
        const bubble = document.createElement('div');
        bubble.className = 'agent-chat-bubble ' + (msg.is_mine ? 'mine' : 'theirs');
        bubble.innerHTML = `<div class="agent-chat-text">${escapeHtml(msg.message)}</div>`;
        messagesEl.appendChild(bubble);
        lastMessageId = msg.id;
      });
      messagesEl.scrollTop = messagesEl.scrollHeight;
    }

    function fetchMessages() {
      if (!activeTargetType || !activeTargetId) return;
      fetch(`/agent_chat/messages/${activeTargetType}/${activeTargetId}`, {
        credentials: 'same-origin'
      })
        .then(res => res.json())
        .then(data => {
          if (!data || !data.ok) return;
          renderMessages(data.messages || []);
        })
        .catch(() => {});
    }

    function sendMessage(message) {
      if (!message || !activeTargetType || !activeTargetId) {
        if (window.iweb && typeof window.iweb.alert === 'function') {
          window.iweb.alert('Please select a conversation first.');
        }
        return;
      }
      const formData = new FormData();
      formData.append('message', message);
      formData.append('target_type', activeTargetType);
      formData.append('target_id', activeTargetId);
      formData.append('_token', getCsrfToken());
      const itoken = buildItoken();
      if (itoken) formData.append('itoken', itoken);

      fetch('/agent_chat/send', {
        method: 'POST',
        body: formData,
        credentials: 'same-origin'
      })
        .then(res => res.json().catch(() => null))
        .then(data => {
          if (data && (data.status === 200 || data.ok === true)) {
            if (inputEl) inputEl.value = '';
            fetchMessages();
            return;
          }
          if (window.iweb && typeof window.iweb.alert === 'function') {
            window.iweb.alert((data && data.message) ? data.message : 'Unable to send message.');
          }
        })
        .catch(() => {});
    }

    function escapeHtml(str) {
      return String(str || '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
    }

    listItems.forEach(item => {
      item.addEventListener('click', () => {
        activeTargetType = item.getAttribute('data-target-type');
        activeTargetId = item.getAttribute('data-target-id');
        setActiveItem();
        fetchMessages();
      });
    });

    if (formEl) {
      formEl.addEventListener('submit', (e) => {
        e.preventDefault();
        const value = (inputEl && inputEl.value || '').trim();
        if (!value) return;
        sendMessage(value);
      });
    }

    if ((!activeTargetType || !activeTargetId) && listItems.length > 0) {
      const first = listItems[0];
      activeTargetType = first.getAttribute('data-target-type');
      activeTargetId = first.getAttribute('data-target-id');
    }

    setActiveItem();
    fetchMessages();
    pollTimer = setInterval(fetchMessages, 3000);
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initAgentChat);
  } else {
    initAgentChat();
  }
})();
