(function () {
    'use strict';
    var cfg = window.jpConfig || {};
    var base = cfg.baseUrl || '';
    var pendingJobId = null;

    function toast(msg) {
        var el = document.getElementById('jp-toast');
        if (!el) return;
        el.textContent = msg;
        el.classList.add('show');
        setTimeout(function () { el.classList.remove('show'); }, 3200);
    }

    function post(url, body) {
        body = body || {};
        body._token = cfg.csrf;
        return fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': cfg.csrf,
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify(body)
        }).then(function (r) { return r.json(); });
    }

    function openModal(id) {
        var m = document.getElementById(id);
        if (m) m.style.display = 'flex';
    }
    function closeModals() {
        document.querySelectorAll('.jp-modal-bg').forEach(function (m) {
            m.style.display = 'none';
        });
        pendingJobId = null;
    }

    document.querySelectorAll('[data-close-modal]').forEach(function (btn) {
        btn.addEventListener('click', closeModals);
    });
    document.querySelectorAll('.jp-modal-bg').forEach(function (bg) {
        bg.addEventListener('click', function (e) {
            if (e.target === bg) closeModals();
        });
    });

    document.querySelectorAll('.jp-job-detail-toggle').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var card = btn.closest('.jp-job-card');
            if (!card) return;
            var detail = card.querySelector('.jp-job-detail');
            if (!detail) return;
            var show = detail.style.display === 'none';
            detail.style.display = show ? 'block' : 'none';
            if (btn.classList.contains('jp-btn-ghost')) {
                btn.textContent = show ? 'Hide details' : 'View details';
            }
        });
    });

    document.querySelectorAll('.jp-apply-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
            pendingJobId = parseInt(btn.getAttribute('data-job-id'), 10);
            document.getElementById('jp-cover-letter').value = '';
            openModal('jp-apply-modal');
        });
    });

    var confirmApply = document.getElementById('jp-confirm-apply');
    if (confirmApply) {
        confirmApply.addEventListener('click', function () {
            if (!pendingJobId) return;
            confirmApply.disabled = true;
            post(base + '/job_applications/apply', {
                job_id: pendingJobId,
                cover_letter: document.getElementById('jp-cover-letter').value
            }).then(function (res) {
                confirmApply.disabled = false;
                closeModals();
                if (res.status === 200) {
                    toast(res.message || 'Applied!');
                    setTimeout(function () { location.reload(); }, 800);
                } else {
                    toast(res.message || 'Could not apply.');
                }
            }).catch(function () {
                confirmApply.disabled = false;
                toast('Network error. Try again.');
            });
        });
    }

    var openPost = document.getElementById('jp-open-post-modal');
    if (openPost) {
        openPost.addEventListener('click', function () { openModal('jp-post-modal'); });
    }

    var postForm = document.getElementById('jp-post-form');
    if (postForm) {
        postForm.addEventListener('submit', function (e) {
            e.preventDefault();
            var fd = new FormData(postForm);
            var body = {};
            fd.forEach(function (v, k) { body[k] = v; });
            body.visa_sponsorship = postForm.querySelector('[name=visa_sponsorship]')?.checked ? 1 : 0;
            post(base + '/job_applications/post_job', body).then(function (res) {
                if (res.status === 200) {
                    toast(res.message || 'Posted!');
                    setTimeout(function () { location.reload(); }, 800);
                } else {
                    toast(res.message || 'Failed to post.');
                }
            }).catch(function () { toast('Network error.'); });
        });
    }

    document.querySelectorAll('.jp-delete-job').forEach(function (btn) {
        btn.addEventListener('click', function () {
            if (!confirm('Remove this job posting?')) return;
            var jobId = parseInt(btn.getAttribute('data-job-id'), 10);
            post(base + '/job_applications/delete_job', { job_id: jobId }).then(function (res) {
                if (res.status === 200) {
                    var card = btn.closest('.jp-job-card');
                    if (card) card.remove();
                    toast('Job removed.');
                } else {
                    toast(res.message || 'Could not remove.');
                }
            });
        });
    });
})();
