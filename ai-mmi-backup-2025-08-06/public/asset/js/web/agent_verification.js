(function () {
    var cfg        = window.agentVerifConfig || {};
    var apiUrl     = cfg.attendanceUrl || '/agent_chat/booking/attendance';
    var deleteUrl  = cfg.deleteUrl || '/agent_chat/booking/delete';
    var csrfToken  = cfg.csrfToken || '';
    var toastEl    = document.getElementById('verif-toast');
    var toastTimer = null;

    /* ---- Toast ---- */
    function showToast(msg, type) {
        if (!toastEl) return;
        toastEl.textContent = msg;
        toastEl.className = 'agent-verif-toast show ' + (type === 'success' ? 'toast-success' : 'toast-error');
        clearTimeout(toastTimer);
        toastTimer = setTimeout(function () { toastEl.classList.remove('show'); }, 3500);
    }

    /* ---- Row state update ---- */
    function updateRow(row, verified, verifiedAt) {
        if (!row) return;
        var label = row.querySelector('.verif-toggle-label');
        if (label) {
            label.textContent = verified ? 'Verified' : 'Pending';
            label.style.color  = verified ? '#166534' : '';
            label.style.fontWeight = verified ? '600' : '';
        }
        var atCell = row.querySelector('.cell-verif-at');
        if (atCell) {
            atCell.innerHTML = (verified && verifiedAt)
                ? verifiedAt
                : '<span class="cell-pending">—</span>';
        }
        if (verified) {
            row.classList.add('row-verified');
            row.dataset.status = 'verified';
        } else {
            row.classList.remove('row-verified');
            row.dataset.status = 'pending';
        }
    }

    /* ---- Checkbox handler ---- */
    function handleChange(checkbox) {
        var memberId = checkbox.getAttribute('data-member-id');
        var verified = checkbox.checked;
        var toggle   = checkbox.closest('.verif-toggle');
        var row      = checkbox.closest('tr');

        if (toggle) toggle.classList.add('saving');
        checkbox.disabled = true;

        fetch(apiUrl, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json'
            },
            body: JSON.stringify({ member_id: parseInt(memberId, 10), attended: verified })
        })
        .then(function (r) { return r.json(); })
        .then(function (data) {
            if (data && data.ok) {
                var nowStr = verified
                    ? (new Date()).toLocaleString('en-AU', { day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' })
                    : null;
                updateRow(row, verified, nowStr);
                updateStats();
                showToast(
                    verified ? '✓ Member marked as verified.' : 'Verification removed.',
                    'success'
                );
            } else {
                checkbox.checked = !verified;
                showToast((data && data.message) ? data.message : 'Failed to update. Please try again.', 'error');
            }
        })
        .catch(function () {
            checkbox.checked = !verified;
            showToast('Network error. Please try again.', 'error');
        })
        .finally(function () {
            if (toggle) toggle.classList.remove('saving');
            checkbox.disabled = false;
        });
    }

    /* ---- Update summary pills ---- */
    function updateStats() {
        var rows     = document.querySelectorAll('#verif-table tbody tr.verif-row');
        var total    = rows.length;
        var verified = 0;
        rows.forEach(function (r) { if (r.dataset.status === 'verified') verified++; });
        var pending  = total - verified;

        var statTotal   = document.querySelector('.stat-total');
        var statDone    = document.querySelector('.stat-done');
        var statPending = document.querySelector('.stat-pending');

        if (statTotal)   statTotal.textContent   = total    + ' Members';
        if (statDone)    statDone.textContent     = verified + ' Verified';
        if (statPending) statPending.textContent  = pending  + ' Pending';
    }

    /* ---- Filters ---- */
    function applyFilters() {
        var search  = (document.getElementById('member-search') || {}).value || '';
        var plan    = (document.getElementById('plan-filter')   || {}).value || '';
        var status  = (document.getElementById('status-filter') || {}).value || '';
        search = search.toLowerCase().trim();

        var rows    = document.querySelectorAll('#verif-table tbody tr.verif-row');
        var visible = 0;

        rows.forEach(function (row) {
            var matchSearch = !search || (row.dataset.search || '').indexOf(search) !== -1;
            var matchPlan   = !plan   || row.dataset.plan   === plan;
            var matchStatus = !status || row.dataset.status === status;

            if (matchSearch && matchPlan && matchStatus) {
                row.classList.remove('row-hidden');
                visible++;
            } else {
                row.classList.add('row-hidden');
            }
        });

        var noResults = document.getElementById('no-results');
        if (noResults) noResults.style.display = (visible === 0 && rows.length > 0) ? 'block' : 'none';
    }

    /* ---- Delete / reset booking ---- */
    function handleDeleteBooking(btn) {
        var memberId = btn.getAttribute('data-member-id');
        if (!memberId) return;
        if (!window.confirm('Reset booking record for this member? This allows them to book again.')) return;

        btn.disabled = true;
        btn.textContent = '...';

        fetch(deleteUrl, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json'
            },
            body: JSON.stringify({ member_id: parseInt(memberId, 10) })
        })
        .then(function (r) { return r.json(); })
        .then(function (data) {
            if (data && data.ok) {
                // Update cell: hide booked status, remove button
                var row = btn.closest('tr');
                if (row) {
                    var bookedCell = row.querySelector('.cell-booked');
                    if (bookedCell) {
                        bookedCell.innerHTML = '<span class="booked-no">Not yet</span>';
                    }
                    // Reset verified state too (booking was the basis of verification)
                    var checkbox = row.querySelector('.verif-checkbox');
                    if (checkbox && checkbox.checked) {
                        checkbox.checked = false;
                        updateRow(row, false, null);
                    }
                    row.dataset.hasBooked = '0';
                }
                updateStats();
                showToast('Booking record deleted. Member can book again.', 'success');
            } else {
                btn.disabled = false;
                btn.textContent = 'Reset';
                showToast((data && data.message) ? data.message : 'Failed to delete booking.', 'error');
            }
        })
        .catch(function () {
            btn.disabled = false;
            btn.textContent = 'Reset';
            showToast('Network error. Please try again.', 'error');
        });
    }

    /* ---- Init ---- */
    function init() {
        // Checkboxes
        document.querySelectorAll('.verif-checkbox').forEach(function (cb) {
            cb.addEventListener('change', function () { handleChange(cb); });
        });

        // Delete booking buttons
        document.querySelectorAll('.btn-delete-booking').forEach(function (btn) {
            btn.addEventListener('click', function () { handleDeleteBooking(btn); });
        });

        // Filters
        var searchEl = document.getElementById('member-search');
        var planEl   = document.getElementById('plan-filter');
        var statusEl = document.getElementById('status-filter');

        if (searchEl)  searchEl.addEventListener('input',  applyFilters);
        if (planEl)    planEl.addEventListener('change',   applyFilters);
        if (statusEl)  statusEl.addEventListener('change', applyFilters);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
