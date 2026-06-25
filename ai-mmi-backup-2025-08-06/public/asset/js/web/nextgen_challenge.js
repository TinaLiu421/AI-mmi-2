/* NextGen AI & Talent Challenge — JS */
document.addEventListener('DOMContentLoaded', function () {

    /* ── Helpers ─────────────────────────────────────────────── */
    function post(url, data, onSuccess, onError) {
        var fd = new FormData();
        fd.append('_token', _ng_token);
        for (var k in data) fd.append(k, data[k]);
        fetch(url, { method: 'POST', body: fd, credentials: 'same-origin' })
            .then(function(r) { return r.json(); })
            .then(function(j) {
                if (j && j.status === 'ok') {
                    if (onSuccess) onSuccess(j);
                } else {
                    if (onError) onError(j);
                }
            })
            .catch(function(e) { if (onError) onError({ message: 'Network error.' }); });
    }

    function showMsg(el, type, text) {
        el.className = 'ng-form-msg ' + type;
        el.textContent = text;
        el.style.display = 'block';
    }

    /* ── Tab highlight on scroll ─────────────────────────────── */
    (function () {
        var tabs = document.querySelectorAll('.ng-tab');
        var sections = ['ng-awards','ng-judges','ng-how','ng-rules','ng-my-submission','ng-feed'];
        var tabMap = { 'ng-awards':'awards','ng-judges':'judges','ng-how':'how','ng-rules':'rules' };

        function setActive(sectionId) {
            tabs.forEach(function(t) { t.classList.remove('active'); });
            var tab = document.querySelector('.ng-tab[data-tab="' + (tabMap[sectionId] || '') + '"]');
            if (tab) tab.classList.add('active');
        }

        function onScroll() {
            var offset = 120;
            for (var i = sections.length - 1; i >= 0; i--) {
                var el = document.getElementById(sections[i]);
                if (el && el.getBoundingClientRect().top <= offset) {
                    setActive(sections[i]);
                    return;
                }
            }
        }

        window.addEventListener('scroll', onScroll, { passive: true });

        tabs.forEach(function(t) {
            t.addEventListener('click', function(e) {
                tabs.forEach(function(x) { x.classList.remove('active'); });
                t.classList.add('active');
                var targetId = 'ng-' + t.getAttribute('data-tab');
                if (targetId === 'ng-how') targetId = 'ng-how';
                if (targetId === 'ng-rules') targetId = 'ng-rules';
                var target = document.getElementById(targetId);
                if (target) {
                    e.preventDefault();
                    var top = target.getBoundingClientRect().top + window.scrollY - 80;
                    window.scrollTo({ top: top, behavior: 'smooth' });
                }
            });
        });
    })();

    /* ── Drag & Drop file zone ───────────────────────────────── */
    /* ── Modal: open / close ─────────────────────────────────── */
    var modal  = document.getElementById('ng-modal');
    var overlay = modal;

    function openModal() {
        if (modal) {
            modal.style.display = 'flex';
            document.body.style.overflow = 'hidden';
        }
    }

    function closeModal() {
        if (modal) {
            modal.style.display = 'none';
            document.body.style.overflow = '';
            var msg = document.getElementById('ng-form-msg');
            if (msg) msg.style.display = 'none';
        }
    }

    // Hero CTA button (logged-in state)
    var editBtn = document.getElementById('ng-edit-btn');
    if (editBtn) editBtn.addEventListener('click', openModal);

    // Edit-inline button in My Submission header
    var editSubBtn = document.getElementById('ng-edit-sub-btn');
    if (editSubBtn) editSubBtn.addEventListener('click', openModal);

    // Empty state submit CTA
    var ctaBtn = document.getElementById('ng-submit-cta-btn');
    if (ctaBtn) ctaBtn.addEventListener('click', openModal);

    var closeBtn = document.getElementById('ng-modal-close');
    if (closeBtn) closeBtn.addEventListener('click', closeModal);

    var cancelBtn = document.getElementById('ng-cancel-btn');
    if (cancelBtn) cancelBtn.addEventListener('click', closeModal);

    // Click outside to close
    if (modal) {
        modal.addEventListener('click', function (e) {
            if (e.target === modal) closeModal();
        });
    }

    // Escape key
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') closeModal();
    });

    /* ── Submission form submit ─────────────────────────────── */
    var form    = document.getElementById('ng-submission-form');
    var saveBtn = document.getElementById('ng-save-btn');
    var msgEl   = document.getElementById('ng-form-msg');
    var progressWrap = document.getElementById('ng-upload-progress');
    var progressBar  = document.getElementById('ng-upload-progress-bar');
    var pctEl        = document.getElementById('ng-upload-pct');

    if (form) {
        form.addEventListener('submit', function (e) {
            e.preventDefault();

            // Basic validation
            var titleVal = form.querySelector('[name="title"]');
            if (titleVal && !titleVal.value.trim()) {
                showMsg(msgEl, 'error', 'Please enter a title for your submission.');
                return;
            }
            var emailVal = form.querySelector('[name="email"]');
            if (emailVal && !emailVal.value.trim()) {
                showMsg(msgEl, 'error', 'Please enter your email address so we can contact you.');
                return;
            }
            var stream = form.querySelector('[name="stream"]:checked');
            if (!stream) {
                showMsg(msgEl, 'error', 'Please choose a stream: AI or Talent.');
                return;
            }
            var ytCons = document.getElementById('ng-youtube-consent');
            if (ytCons && !ytCons.checked) {
                showMsg(msgEl, 'error', 'Please consent to the YouTube upload to proceed.');
                return;
            }
            var ipCons = document.getElementById('ng-copyright-consent');
            if (ipCons && !ipCons.checked) {
                showMsg(msgEl, 'error', 'Please agree to the Intellectual Property Agreement to proceed.');
                return;
            }

            // If no existing submission, a Google Drive link is required
            if (!_ng_has_sub) {
                var gdriveInput = document.getElementById('ng-gdrive-input');
                var gdriveVal = gdriveInput ? gdriveInput.value.trim() : '';
                if (!gdriveVal) {
                    showMsg(msgEl, 'error', 'Please paste your Google Drive video link.');
                    return;
                }
                if (!/^https?:\/\//i.test(gdriveVal)) {
                    showMsg(msgEl, 'error', 'Please enter a valid link starting with https://');
                    return;
                }
            }

            msgEl.style.display = 'none';
            saveBtn.disabled = true;
            saveBtn.textContent = 'Saving…';

            var xhr = new XMLHttpRequest();
            var fd  = new FormData(form);

            xhr.open('POST', _ng_save_url);
            xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');

            // Upload progress
            if (progressWrap && progressBar && pctEl) {
                progressWrap.style.display = 'flex';
                xhr.upload.onprogress = function (ev) {
                    if (ev.lengthComputable) {
                        var pct = Math.round(ev.loaded / ev.total * 100);
                        progressBar.style.width = pct + '%';
                        pctEl.textContent = pct + '%';
                    }
                };
            }

            xhr.onload = function () {
                try {
                    var resp = JSON.parse(xhr.responseText);
                    if (resp.status === 'ok') {
                        showMsg(msgEl, 'success', 'Your submission has been saved! We\'ll review it and email you soon.');
                        saveBtn.innerHTML = '<i class="fa fa-check"></i> Saved!';
                        setTimeout(function () { window.location.reload(); }, 1800);
                    } else {
                        showMsg(msgEl, 'error', resp.message || 'Something went wrong. Please try again.');
                        saveBtn.disabled = false;
                        saveBtn.innerHTML = _ng_has_sub ? '<i class="fa fa-floppy-o"></i> Save Changes' : '<i class="fa fa-arrow-right"></i> Submit Entry';
                    }
                } catch (ex) {
                    showMsg(msgEl, 'error', 'Unexpected response. Please try again.');
                    saveBtn.disabled = false;
                    saveBtn.innerHTML = _ng_has_sub ? '<i class="fa fa-floppy-o"></i> Save Changes' : '<i class="fa fa-arrow-right"></i> Submit Entry';
                }
                if (progressWrap) progressWrap.style.display = 'none';
            };

            xhr.onerror = function () {
                showMsg(msgEl, 'error', 'Network error. Please check your connection and try again.');
                saveBtn.disabled = false;
                saveBtn.innerHTML = _ng_has_sub ? '<i class="fa fa-floppy-o"></i> Save Changes' : '<i class="fa fa-arrow-right"></i> Submit Entry';
                if (progressWrap) progressWrap.style.display = 'none';
            };

            xhr.send(fd);
        });
    }

    /* ── Like button ────────────────────────────────────────── */
    var likeBtn   = document.querySelector('.ng-like-btn');
    var likeCount = document.getElementById('ng-like-count');

    if (likeBtn) {
        likeBtn.addEventListener('click', function () {
            if (!_ng_is_logged_in) {
                window.location.href = _ng_base_url + '/account_login';
                return;
            }
            var subId = likeBtn.getAttribute('data-sub-id');
            post(_ng_like_url, { submission_id: subId }, function (j) {
                if (likeCount) likeCount.textContent = j.count;
                likeBtn.classList.toggle('liked', j.liked);
            });
        });
    }

    /* ── Comment focus btn ──────────────────────────────────── */
    var commentFocusBtn = document.querySelector('.ng-comment-focus-btn');
    if (commentFocusBtn) {
        commentFocusBtn.addEventListener('click', function () {
            var input = document.getElementById('ng-comment-input');
            if (input) {
                input.scrollIntoView({ behavior: 'smooth', block: 'center' });
                input.focus();
            }
        });
    }

    /* ── Comment submit ─────────────────────────────────────── */
    var commentInput  = document.getElementById('ng-comment-input');
    var commentSubmit = document.getElementById('ng-comment-submit');
    var commCount     = document.getElementById('ng-comment-count');
    var commSection   = document.getElementById('ng-comments-section');

    if (commentSubmit && commentInput) {
        commentSubmit.addEventListener('click', function () {
            var text = commentInput.value.trim();
            if (!text) return;
            if (!_ng_is_logged_in) {
                window.location.href = _ng_base_url + '/account_login';
                return;
            }
            commentSubmit.disabled = true;
            post(_ng_comment_url, { submission_id: _ng_submission_id, content: text }, function (j) {
                var row = document.createElement('div');
                row.className = 'ng-comment-row';
                row.innerHTML =
                    '<div class="ng-comment-avatar ng-av-initial"></div>' +
                    '<div class="ng-comment-body">' +
                    '<div class="ng-comment-author">' + (j.comment.alias_name || 'You') + '<span class="ng-comment-time">just now</span></div>' +
                    '<div class="ng-comment-text">' + j.comment.content + '</div>' +
                    '</div>';
                var addRow = commSection.querySelector('.ng-add-comment-row');
                if (addRow) commSection.insertBefore(row, addRow);
                commentInput.value = '';
                if (commCount) commCount.textContent = parseInt(commCount.textContent || '0') + 1;
                commentSubmit.disabled = false;
            }, function () {
                commentSubmit.disabled = false;
            });
        });

        commentInput.addEventListener('keydown', function (e) {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                commentSubmit.click();
            }
        });
    }

    /* ── Share button ───────────────────────────────────────── */
    var shareBtn = document.querySelector('.ng-share-btn');
    if (shareBtn) {
        shareBtn.addEventListener('click', function () {
            var url = window.location.href;
            if (navigator.share) {
                navigator.share({ title: 'NextGen AI & Talent Challenge', url: url });
            } else if (navigator.clipboard) {
                navigator.clipboard.writeText(url).then(function () {
                    shareBtn.querySelector('.ng-social-label').textContent = 'Copied!';
                    setTimeout(function () {
                        shareBtn.querySelector('.ng-social-label').textContent = 'Share';
                    }, 2000);
                });
            }
        });
    }

    /* ── Education institution interest ────────────────────── */
    if (_ng_is_education_institution) {
        document.querySelectorAll('.ng-feed-interest-btn').forEach(function (btn) {
            btn.addEventListener('click', function () {
                if (btn.disabled) return;
                var submissionId = btn.getAttribute('data-submission-id');
                var msgEl = document.getElementById('ng-interest-msg-' + submissionId);
                btn.disabled = true;
                btn.textContent = 'Sending...';
                post(_ng_interest_url, { submission_id: submissionId }, function (j) {
                    btn.classList.add('is-sent');
                    btn.innerHTML = '<i class="fa fa-check"></i> Interest Sent';
                    if (msgEl) {
                        msgEl.className = 'ng-feed-interest-msg ok';
                        msgEl.textContent = (j && j.message) || 'Interest sent.';
                    }
                }, function (j) {
                    btn.disabled = false;
                    btn.innerHTML = '<i class="fa fa-university"></i> Interested In This Person';
                    if (msgEl) {
                        msgEl.className = 'ng-feed-interest-msg err';
                        msgEl.textContent = (j && j.message) || 'Unable to send interest right now.';
                    }
                });
            });
        });
    }

});

/* ── Full-width dark→light bg transition for nextgen_challenge ── */
(function() {
  function applyNgBgTransition() {
    var hero = document.querySelector('.ng-hero');
    var infoArea = document.querySelector('.info-area');
    if (!hero || !infoArea) return;
    var scrollY = window.scrollY || window.pageYOffset || 0;
    var infoTop = infoArea.getBoundingClientRect().top + scrollY;
    var heroBot = hero.getBoundingClientRect().bottom + scrollY;
    var bp = Math.round(heroBot - infoTop);
    if (bp <= 0) return;
    var grad = 'linear-gradient(to bottom, #0c1445 ' + bp + 'px, #dce8f8 ' + bp + 'px)';
    infoArea.style.setProperty('background', grad, 'important');
    infoArea.style.setProperty('background-image', grad, 'important');
    infoArea.style.setProperty('background-color', 'transparent', 'important');
  }
  document.addEventListener('DOMContentLoaded', function() {
    applyNgBgTransition();
    setTimeout(applyNgBgTransition, 150);
  });
  window.addEventListener('resize', applyNgBgTransition);
})();
