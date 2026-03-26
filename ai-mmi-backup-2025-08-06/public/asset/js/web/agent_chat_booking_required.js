(function () {
    let submitted = false;
    let initialized = false;

    function initBookingGate() {
        if (initialized) {
            return;
        }

        const cfg = window.agentBookingConfig || {};
        const calendlyUrl  = cfg.calendlyUrl || 'https://calendly.com/admin-wealthskey/free-users';
        const unlockApiUrl = cfg.unlockApiUrl || '/agent_chat/booking/confirm';

        const csrfMeta  = document.querySelector('meta[name="csrf-token"]');
        const csrfToken = csrfMeta ? csrfMeta.getAttribute('content') : '';
        const statusEl  = document.getElementById('agent-booking-status');
        const openBtn   = document.getElementById('open-calendly-booking');
        const alreadyBtn = document.getElementById('already-booked-btn');

        if (!openBtn && !alreadyBtn && !statusEl) {
            return;
        }

        initialized = true;

        function setStatus(text, success) {
            if (!statusEl) return;
            statusEl.textContent = text;
            if (success) {
                statusEl.style.color = '#166534';
            }
        }

        function showBookedState() {
            // Hide the action buttons and show a success message inline
            const actionsEl = document.querySelector('.agent-booking-actions');
            if (actionsEl) {
                actionsEl.style.display = 'none';
            }
            if (statusEl) {
                statusEl.innerHTML = '<span style="color:#166534;font-weight:600;">&#10003; Meeting booked!</span> Your booking has been recorded. Please wait for the Wealthskey agent to confirm your attendance.';
                statusEl.style.background = '#f0fdf4';
                statusEl.style.border = '1px solid #bbf7d0';
                statusEl.style.borderRadius = '10px';
                statusEl.style.padding = '12px 14px';
                statusEl.style.color = '#166534';
                statusEl.style.fontSize = '15px';
            }
        }

        function showMeetingUsedAndRedirect(message) {
            // For free/all_ai: meeting is used immediately; redirect to upgrade
            const actionsEl = document.querySelector('.agent-booking-actions');
            if (actionsEl) {
                actionsEl.style.display = 'none';
            }
            if (statusEl) {
                statusEl.innerHTML = '<span style="color:#166534;font-weight:600;">&#10003; Consultation scheduled!</span> ' + (message || 'Your one-time consultation has been recorded. Upgrade your plan for continued agent access.');
                statusEl.style.background = '#f0fdf4';
                statusEl.style.border = '1px solid #bbf7d0';
                statusEl.style.borderRadius = '10px';
                statusEl.style.padding = '12px 14px';
                statusEl.style.color = '#166534';
                statusEl.style.fontSize = '15px';
            }
            // Redirect to upgrade page after short delay
            setTimeout(function () {
                window.location.href = '/upgrade';
            }, 2500);
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
                body: JSON.stringify({ source: 'schedule_click' }),
                keepalive: true
            }).catch(function () { return null; });
        }

        function confirmBooking(payload) {
            if (submitted) {
                return Promise.resolve(true);
            }

            const detail     = (payload && payload.payload) ? payload.payload : {};
            const eventUri   = detail.event   ? detail.event.uri   : '';
            const inviteeUri = detail.invitee ? detail.invitee.uri : '';
            const source     = (payload && payload.source) ? payload.source : 'calendly_event';

            setStatus(source === 'manual_continue'
                ? 'Recording your booking...'
                : 'Booking detected. Recording...');

            return fetch(unlockApiUrl, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    event_uri:   eventUri,
                    invitee_uri: inviteeUri,
                    source:      source
                })
            })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (data && data.ok) {
                    submitted = true;
                    if (data.redirect_upgrade) {
                        showMeetingUsedAndRedirect(data.message);
                    } else {
                        showBookedState();
                    }
                    return true;
                }
                if (data && data.already_used && data.redirect_upgrade) {
                    showMeetingUsedAndRedirect('You have already used your one-time consultation. Upgrade your plan for more agent access.');
                    return true;
                }
                setStatus((data && data.message)
                    ? data.message
                    : 'Could not record your booking. Please try clicking "I have already booked my meeting".');
                return false;
            })
            .catch(function () {
                setStatus('Network error. Please click "I have already booked my meeting" if you have scheduled.');
                return false;
            });
        }

        // Auto-detect Calendly booking completion via postMessage
        window.addEventListener('message', function (e) {
            if (!e || !e.data || e.data.event !== 'calendly.event_scheduled') {
                return;
            }
            confirmBooking(e.data);
        });

        if (openBtn) {
            openBtn.addEventListener('click', function () {
                setStatus('Opening Calendly... After booking, click "I have already booked my meeting".');
                markScheduleClick().then(function (r) {
                    if (!r) return;
                    r.json().then(function (data) {
                        if (data && data.ok && data.redirect_upgrade) {
                            submitted = true;
                            showMeetingUsedAndRedirect(data.message);
                        }
                    }).catch(function () {});
                }).catch(function () {});
            });
        }

        if (alreadyBtn) {
            alreadyBtn.addEventListener('click', function () {
                alreadyBtn.disabled = true;
                confirmBooking({ source: 'manual_continue' })
                    .then(function (ok) {
                        if (!ok) {
                            alreadyBtn.disabled = false;
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
