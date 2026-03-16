function iweb_self_func() {
    if($('#hslider-news').length > 0) {
        $('#hslider-news').slick({
            dots: false,
            arrows: true,
            infinite: true,
            autoplay: false,
            slidesToShow: 5,
            slidesToScroll: 1,
            responsive: [
                {
                    breakpoint: 1200,
                    settings: {
                        slidesToShow: 4
                    }
                },
                {
                    breakpoint: 900,
                    settings: {
                        slidesToShow: 3
                    }
                },
                {
                    breakpoint: 600,
                    settings: {
                        slidesToShow: 2
                    }
                }
            ]
        });
    }
    
    if($('#hslider-events').length > 0) {
        $('#hslider-events').slick({
            dots: false,
            arrows: true,
            infinite: true,
            autoplay: false,
            slidesToShow: 5,
            slidesToScroll: 1,
            responsive: [
                {
                    breakpoint: 1200,
                    settings: {
                        slidesToShow: 4
                    }
                },
                {
                    breakpoint: 900,
                    settings: {
                        slidesToShow: 3
                    }
                },
                {
                    breakpoint: 600,
                    settings: {
                        slidesToShow: 2
                    }
                }
            ]
        });
    }

    initHomeChatNotifications();
}

function initHomeChatNotifications() {
    const config = window.homeChatNotifyConfig || {};
    if (!config.enabled) {
        return;
    }

    const wrapEl = document.getElementById('home-chat-notify');
    const listEl = document.getElementById('home-chat-notify-list');
    const emptyEl = document.getElementById('home-chat-notify-empty');
    if (!wrapEl || !listEl || !emptyEl) {
        return;
    }

    function escapeHtml(str) {
        return String(str || '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function renderThreads(threads, totalUnread) {
        const items = Array.isArray(threads) ? threads : [];
        if (!items.length) {
            listEl.innerHTML = '';
            emptyEl.style.display = 'block';
            emptyEl.textContent = 'No unread chats.';
            wrapEl.setAttribute('data-total-unread', '0');
            return;
        }

        const html = items.map((thread) => {
            const label = escapeHtml(thread.label || 'Conversation');
            const unread = Number(thread.unread_count || 0);
            const chatUrl = escapeHtml(thread.chat_url || '/agent_chat/chat');
            const badgeLabel = unread > 99 ? '99+' : String(unread);
            return '<a class="home-chat-notify-item" href="' + chatUrl + '">' +
                '<div class="home-chat-notify-item-name">' + label + '</div>' +
                '<div class="home-chat-notify-item-right">' +
                    '<span class="home-chat-notify-item-text">new messages</span>' +
                    '<span class="home-chat-notify-badge">' + badgeLabel + '</span>' +
                '</div>' +
            '</a>';
        }).join('');

        listEl.innerHTML = html;
        emptyEl.style.display = 'none';
        wrapEl.setAttribute('data-total-unread', String(Number(totalUnread || 0)));
    }

    function fetchNotifications() {
        fetch(config.notificationsUrl || '/agent_chat/notifications', {
            credentials: 'same-origin'
        })
            .then((res) => res.json())
            .then((data) => {
                if (!data || !data.ok) {
                    return;
                }
                renderThreads(data.threads || [], data.total_unread || 0);
            })
            .catch(() => {});
    }

    fetchNotifications();
    setInterval(fetchNotifications, 10000);
}