(function () {
    var cfg = window.agentDashboardConfig || {};
    var attendanceUrl = cfg.attendanceUrl || '/agent_chat/booking/attendance';
    var csrfToken = cfg.csrfToken || '';
    var toastEl = document.getElementById('dashboard-toast');
    var toastTimer = null;

    function showToast(message, type) {
        if (!toastEl) return;
        toastEl.textContent = message;
        toastEl.className = 'agent-dashboard-toast show ' + (type === 'success' ? 'toast-success' : 'toast-error');
        clearTimeout(toastTimer);
        toastTimer = setTimeout(function () {
            toastEl.classList.remove('show');
        }, 3000);
    }

    function updateRowState(row, attended, attendedAt) {
        if (!row) return;
        var label = row.querySelector('.attendance-toggle-label');
        if (label) {
            label.textContent = attended ? 'Yes' : 'No';
            label.style.color = attended ? '#166534' : '';
            label.style.fontWeight = attended ? '600' : '';
        }
        var attendedAtCell = row.querySelector('.cell-attended-at');
        if (attendedAtCell) {
            attendedAtCell.textContent = (attended && attendedAt) ? attendedAt : '—';
        }
        if (attended) {
            row.classList.add('row-attended');
        } else {
            row.classList.remove('row-attended');
        }
    }

    function handleCheckboxChange(checkbox) {
        var memberId = checkbox.getAttribute('data-member-id');
        var attended = checkbox.checked;
        var toggle = checkbox.closest('.attendance-toggle');
        var row = checkbox.closest('tr');

        if (toggle) toggle.classList.add('saving');
        checkbox.disabled = true;

        fetch(attendanceUrl, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json'
            },
            body: JSON.stringify({ member_id: parseInt(memberId, 10), attended: attended })
        })
        .then(function (r) { return r.json(); })
        .then(function (data) {
            if (data && data.ok) {
                var attendedAt = attended ? (new Date()).toLocaleString('en-AU', { day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' }) : null;
                updateRowState(row, attended, attendedAt);
                showToast(attended ? 'Attendance confirmed.' : 'Attendance removed.', 'success');
            } else {
                // Revert checkbox
                checkbox.checked = !attended;
                showToast((data && data.message) ? data.message : 'Failed to update attendance.', 'error');
            }
        })
        .catch(function () {
            checkbox.checked = !attended;
            showToast('Network error. Please try again.', 'error');
        })
        .finally(function () {
            if (toggle) toggle.classList.remove('saving');
            checkbox.disabled = false;
        });
    }

    function init() {
        var checkboxes = document.querySelectorAll('.attendance-checkbox');
        checkboxes.forEach(function (cb) {
            cb.addEventListener('change', function () {
                handleCheckboxChange(cb);
            });
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
