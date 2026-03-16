(() => {
  function initAgentChat() {
    const config = window.agentChatConfig || {};
    const listEl = document.querySelector('.agent-chat-list');
    const messagesEl = document.getElementById('agent-chat-messages');
    const formEl = document.getElementById('agent-chat-form');
    const inputEl = document.getElementById('agent-chat-input');
    const fileBtnEl = document.getElementById('agent-chat-file-btn');
    const fileInputEl = document.getElementById('agent-chat-file');
    const fileNameEl = document.getElementById('agent-chat-file-name');
    const targetTypeInputEl = document.getElementById('agent-chat-target-type');
    const targetIdInputEl = document.getElementById('agent-chat-target-id');

    let activeTargetType = config.activeTargetType;
    let activeTargetId = config.activeTargetId;
    let lastMessageId = null;
    let pollTimer = null;
    let threadTimer = null;
    let presenceTimer = null;
    const presenceEl = document.getElementById('agent-chat-presence');
    const presenceAgentId = config.presenceAgentId || null;

    function getListItems() {
      if (!listEl) {
        return [];
      }
      return Array.from(listEl.querySelectorAll('.agent-chat-list-item'));
    }

    function setActiveItem() {
      getListItems().forEach(item => {
        const t = item.getAttribute('data-target-type');
        const id = item.getAttribute('data-target-id');
        if (t === activeTargetType && String(id) === String(activeTargetId)) {
          item.classList.add('active');
        } else {
          item.classList.remove('active');
        }
      });

      if (targetTypeInputEl) targetTypeInputEl.value = activeTargetType || '';
      if (targetIdInputEl) targetIdInputEl.value = activeTargetId || '';
    }

    function ensureActiveTargetFromDom() {
      if (activeTargetType && activeTargetId) {
        return;
      }

      const listItems = getListItems();
      const activeItem = listEl ? listEl.querySelector('.agent-chat-list-item.active') : null;
      const fallbackItem = activeItem || (listItems.length > 0 ? listItems[0] : null);
      if (!fallbackItem) {
        return;
      }

      activeTargetType = fallbackItem.getAttribute('data-target-type') || activeTargetType;
      activeTargetId = fallbackItem.getAttribute('data-target-id') || activeTargetId;
      setActiveItem();
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
        const msgText = escapeHtml(msg.message || '');
        let attachmentHtml = '';
        const attachments = Array.isArray(msg.attachments) ? msg.attachments : [];
        if (attachments.length > 0) {
          const items = attachments.map(att => {
            const name = escapeHtml(att.file_name || 'Attachment');
            const size = Number(att.file_size || 0);
            const sizeLabel = size > 0 ? ` (${formatFileSize(size)})` : '';
            const url = escapeHtml(att.download_url || '#');
            return `<div><a class="agent-chat-attachment" href="${url}" target="_blank" rel="noopener">📎 ${name}${sizeLabel}</a></div>`;
          }).join('');
          attachmentHtml = `<div class="agent-chat-attachments">${items}</div>`;
        }
        bubble.innerHTML = `<div class="agent-chat-text">${msgText}</div>${attachmentHtml}`;
        messagesEl.appendChild(bubble);
        lastMessageId = msg.id;
      });
      messagesEl.scrollTop = messagesEl.scrollHeight;
    }

    function fetchMessages() {
      if (!activeTargetType || !activeTargetId) return;
      const lang = (window.agentChatConfig && window.agentChatConfig.langCode) || 'en';
      fetch(`/${lang}/agent_chat/messages/${activeTargetType}/${activeTargetId}`, {
        credentials: 'same-origin'
      })
        .then(res => res.json())
        .then(data => {
          if (!data || !data.ok) return;
          renderMessages(data.messages || []);
          if (config.isAgent) {
            fetchThreads();
          }
        })
        .catch(() => {});
    }

    function renderThreadList(threads) {
      if (!config.isAgent || !listEl) {
        return;
      }

      const threadList = Array.isArray(threads) ? threads : [];
      if (threadList.length === 0) {
        listEl.innerHTML = '<div class="agent-chat-empty">No conversations yet.</div>';
        activeTargetType = null;
        activeTargetId = null;
        setActiveItem();
        return;
      }

      const html = threadList.map(thread => {
        const targetType = escapeHtml(thread.target_type || 'member');
        const targetId = escapeHtml(String(thread.target_id || ''));
        const label = escapeHtml(thread.label || 'Conversation');
        const lastMessage = escapeHtml(thread.last_message || '');
        const unreadCount = Number(thread.unread_count || 0);
        const unreadBadge = unreadCount > 0
          ? `<span class="agent-chat-unread-badge">${unreadCount > 99 ? '99+' : unreadCount}</span>`
          : '';

        return `<div class="agent-chat-list-item" data-target-type="${targetType}" data-target-id="${targetId}">
          <div class="agent-chat-list-head">
            <div class="agent-name">${label}</div>
            ${unreadBadge}
          </div>
          <div class="agent-meta">${lastMessage}</div>
        </div>`;
      }).join('');

      listEl.innerHTML = html;

      const stillExists = threadList.some(thread => (
        String(thread.target_type || '') === String(activeTargetType || '')
        && String(thread.target_id || '') === String(activeTargetId || '')
      ));

      if (!stillExists) {
        activeTargetType = threadList[0].target_type || 'member';
        activeTargetId = threadList[0].target_id || null;
      }

      setActiveItem();
    }

    function fetchThreads() {
      if (!config.isAgent) {
        return;
      }

      fetch('/agent_chat/threads', {
        credentials: 'same-origin'
      })
        .then(res => res.json())
        .then(data => {
          if (!data || !data.ok) {
            return;
          }
          renderThreadList(data.threads || []);
        })
        .catch(() => {});
    }

    function setPresenceState(state, text) {
      if (!presenceEl) return;
      presenceEl.setAttribute('data-state', state || 'unknown');
      const textEl = presenceEl.querySelector('.agent-chat-presence-text');
      if (textEl) {
        textEl.textContent = text || 'Checking status...';
      }
    }

    function fetchPresence() {
      if (!presenceEl || config.isAgent || !presenceAgentId) return;

      fetch(`/agent_chat/availability/${presenceAgentId}`, {
        credentials: 'same-origin'
      })
        .then(res => res.json())
        .then(data => {
          if (!data || !data.ok) {
            setPresenceState('unknown', 'Status unavailable');
            return;
          }

          if (data.online) {
            setPresenceState('online', 'Online');
            return;
          }

          if (typeof data.seconds_ago === 'number' && data.seconds_ago > 0) {
            const mins = Math.floor(data.seconds_ago / 60);
            if (mins > 0) {
              setPresenceState('offline', `Last seen ${mins} min ago`);
              return;
            }
          }

          setPresenceState('offline', 'Offline');
        })
        .catch(() => {
          setPresenceState('unknown', 'Status unavailable');
        });
    }

    function sendMessage(message, attachmentFile) {
      ensureActiveTargetFromDom();
      if ((!message && !attachmentFile) || !activeTargetType || !activeTargetId) {
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
      if (attachmentFile) formData.append('attachment', attachmentFile);
      const itoken = buildItoken();
      if (itoken) formData.append('itoken', itoken);

      const lang = (window.agentChatConfig && window.agentChatConfig.langCode) || 'en';
      fetch(`/${lang}/agent_chat/send`, {
        method: 'POST',
        body: formData,
        credentials: 'same-origin'
      })
        .then(res => res.json().catch(() => null))
        .then(data => {
          if (data && (data.status === 200 || data.ok === true)) {
            if (inputEl) inputEl.value = '';
            if (fileInputEl) fileInputEl.value = '';
            if (fileNameEl) {
              fileNameEl.textContent = '';
              fileNameEl.classList.remove('has-file');
            }
            if (inputEl) {
              inputEl.removeAttribute('readonly');
              inputEl.removeAttribute('disabled');
              inputEl.focus();
            }
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

    function formatFileSize(bytes) {
      const num = Number(bytes || 0);
      if (num <= 0) return '0 B';
      if (num < 1024) return num + ' B';
      if (num < 1024 * 1024) return (num / 1024).toFixed(1) + ' KB';
      return (num / (1024 * 1024)).toFixed(1) + ' MB';
    }

    if (listEl) {
      listEl.addEventListener('click', (event) => {
        const item = event.target.closest('.agent-chat-list-item');
        if (!item || !listEl.contains(item)) {
          return;
        }

        activeTargetType = item.getAttribute('data-target-type');
        activeTargetId = item.getAttribute('data-target-id');
        setActiveItem();
        fetchMessages();
      });
    }

    if (formEl) {
      formEl.addEventListener('submit', (e) => {
        e.preventDefault();
        ensureActiveTargetFromDom();
        const value = (inputEl && inputEl.value || '').trim();
        const file = fileInputEl && fileInputEl.files && fileInputEl.files[0] ? fileInputEl.files[0] : null;
        if (!value && !file) return;
        sendMessage(value, file);
      });
    }

    if (fileBtnEl && fileInputEl) {
      const fileBtnTag = (fileBtnEl.tagName || '').toLowerCase();
      if (fileBtnTag === 'button') {
        fileBtnEl.addEventListener('click', () => fileInputEl.click());
      }
      fileInputEl.addEventListener('change', () => {
        const file = fileInputEl.files && fileInputEl.files[0] ? fileInputEl.files[0] : null;
        if (fileNameEl) {
          if (file) {
            fileNameEl.textContent = `${file.name} (${formatFileSize(file.size)})`;
            fileNameEl.classList.add('has-file');
          } else {
            fileNameEl.textContent = '';
            fileNameEl.classList.remove('has-file');
          }
        }
        if (inputEl) inputEl.focus();
      });
    }

    const initialListItems = getListItems();
    if ((!activeTargetType || !activeTargetId) && initialListItems.length > 0) {
      const first = initialListItems[0];
      activeTargetType = first.getAttribute('data-target-type');
      activeTargetId = first.getAttribute('data-target-id');
    }

    setActiveItem();
    fetchMessages();
    pollTimer = setInterval(fetchMessages, 3000);

    if (config.isAgent) {
      fetchThreads();
      threadTimer = setInterval(fetchThreads, 5000);
    }

    fetchPresence();
    presenceTimer = setInterval(fetchPresence, 10000);
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initAgentChat);
  } else {
    initAgentChat();
  }
})();
