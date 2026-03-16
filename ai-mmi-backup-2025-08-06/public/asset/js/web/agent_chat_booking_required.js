(function () {
    let unlocked = false;
    let initialized = false;

    function ensureCalendlyScript() {
        if (window.Calendly) {
            return;
        }

        if (document.querySelector('script[data-calendly-widget="1"]')) {
            return;
        }

        const script = document.createElement('script');
        script.src = 'https://assets.calendly.com/assets/external/widget.js';
        script.async = true;
        script.setAttribute('data-calendly-widget', '1');
        document.head.appendChild(script);
    }

    function initBookingGate() {
        if (initialized) {
            return;
        }

        const cfg = window.agentBookingConfig || {};
        const calendlyUrl = cfg.calendlyUrl || 'https://calendly.com/admin-wealthskey/30min';
        const unlockApiUrl = cfg.unlockApiUrl || '/agent_chat/booking/confirm';
        const continueUrl = cfg.continueUrl || '/agent_chat';

        const csrfMeta = document.querySelector('meta[name="csrf-token"]');
        const csrfToken = csrfMeta ? csrfMeta.getAttribute('content') : '';
        const statusEl = document.getElementById('agent-booking-status');
        const openBtn = document.getElementById('open-calendly-booking');
        const proceedBtn = document.querySelector('.agent-booking-btn.secondary');

        if (!statusEl && !openBtn && !proceedBtn) {
            return;
        }

        initialized = true;

        function setStatus(text) {
            if (statusEl) {
                statusEl.textContent = text;
            }
        }

        function markScheduleClick() {
            return fetch(unlockApiUrl, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    source: 'schedule_click'
                }),
                keepalive: true
            }).catch(function () {
                return null;
            });
        }

        function confirmBooking(payload) {
            if (unlocked) {
                return Promise.resolve(true);
            }

            const detail = (payload && payload.payload) ? payload.payload : {};
            const eventUri = detail.event ? detail.event.uri : '';
            const inviteeUri = detail.invitee ? detail.invitee.uri : '';
            const source = (payload && payload.source) ? payload.source : 'calendly_event';

            setStatus(source === 'manual_continue'
                ? 'Checking your booking and unlocking chat access...'
                : 'Booking detected. Unlocking chat access...');

            return fetch(unlockApiUrl, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    event_uri: eventUri,
                    invitee_uri: inviteeUri,
                    source: source
                })
            })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (data && data.ok) {
                    unlocked = true;
                    setStatus('Booking confirmed. Redirecting to chat...');
                    setTimeout(function () {
                        window.location.href = continueUrl;
                    }, 600);
                    return true;
                }

                setStatus((data && data.message)
                    ? data.message
                    : 'Booking was detected, but unlock failed. Please click "I already booked, continue to chat".');
                return false;
            })
            .catch(function () {
                setStatus('Booking was detected, but unlock failed. Please click "I already booked, continue to chat".');
                return false;
            });
        }

        window.addEventListener('message', function (e) {
            if (!e || !e.data || e.data.event !== 'calendly.event_scheduled') {
                return;
            }
            confirmBooking(e.data);
        });

        // Open Calendly as a plain new-tab link (works on localhost and production).
        // The popup widget is unreliable in development environments.
        if (openBtn) {
            openBtn.addEventListener('click', function () {
                setStatus('Opening booking page... Please complete booking, then click continue to chat.');
                markScheduleClick();
            });
        }

        if (proceedBtn) {
            proceedBtn.__bookingBindMarker = true;
            proceedBtn.addEventListener('click', function (e) {
                e.preventDefault();

                confirmBooking({ source: 'manual_continue' })
                    .then(function (ok) {
                        if (!ok) {
                            return;
                        }
                    });
            });
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initBookingGate);
    } else {
        initBookingGate();
    }
})();
