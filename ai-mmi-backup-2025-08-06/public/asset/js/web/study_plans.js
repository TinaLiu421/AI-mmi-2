/* study_plans.js */
document.addEventListener('DOMContentLoaded', function () {
    'use strict';

    // ── Helpers ──────────────────────────────────────────────
    function csrf() { return typeof _sp_token !== 'undefined' ? _sp_token : ''; }

    function post(url, formData, onSuccess, onError, onProgress) {
        var xhr = new XMLHttpRequest();
        xhr.open('POST', url, true);
        xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
        if (onProgress) {
            xhr.upload.onprogress = function (e) {
                if (e.lengthComputable) onProgress(Math.round(e.loaded / e.total * 100));
            };
        }
        xhr.onload = function () {
            try {
                var data = JSON.parse(xhr.responseText);
                if (xhr.status === 200 && data.status === 'ok') {
                    onSuccess(data);
                } else {
                    onError(data.message || 'An error occurred.');
                }
            } catch (e) {
                onError('Invalid server response.');
            }
        };
        xhr.onerror = function () { onError('Network error.'); };
        xhr.send(formData);
    }

    function getText(url, onSuccess, onError) {
        var xhr = new XMLHttpRequest();
        xhr.open('GET', url, true);
        xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
        xhr.onload = function () {
            try {
                var data = JSON.parse(xhr.responseText);
                if (xhr.status === 200 && data.status === 'ok') {
                    onSuccess(data);
                } else {
                    onError(data.message || 'Error loading data.');
                }
            } catch (e) { onError('Invalid response.'); }
        };
        xhr.onerror = function () { onError('Network error.'); };
        xhr.send();
    }

    function escHtml(str) {
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function showMsg(el, text, type) {
        if (!el) return;
        el.textContent = text;
        el.className = 'sp-form-msg ' + (type || 'error');
        el.style.display = 'block';
    }

    function hideMsg(el) {
        if (!el) return;
        el.style.display = 'none';
        el.textContent = '';
    }

    // ── Gallery delete tracking ──────────────────────────────
    var deleteGallery = [];

    function refreshDeleteInput() {
        var inp = document.getElementById('sp-delete-gallery-input');
        if (inp) inp.value = JSON.stringify(deleteGallery);
    }

    // ── Modal open/close ─────────────────────────────────────
    var modal = document.getElementById('sp-dream-modal') || document.getElementById('sp-modal');

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
        }
    }

    var editBtn   = document.getElementById('sp-edit-dream-btn');
    var createBtn = document.getElementById('sp-create-dream-btn');
    var closeBtn  = document.getElementById('sp-modal-close');
    var cancelBtn = document.getElementById('sp-cancel-dream-btn');

    if (editBtn)   editBtn.addEventListener('click', openModal);
    if (createBtn) createBtn.addEventListener('click', openModal);
    if (closeBtn)  closeBtn.addEventListener('click', closeModal);
    if (cancelBtn) cancelBtn.addEventListener('click', closeModal);

    if (modal) {
        modal.addEventListener('click', function (e) {
            if (e.target === modal) closeModal();
        });
    }

    // ── Gallery delete buttons ───────────────────────────────
    document.addEventListener('click', function (e) {
        if (e.target && e.target.classList.contains('sp-gallery-delete-btn')) {
            var filename = e.target.getAttribute('data-filename');
            if (filename) {
                deleteGallery.push(filename);
                refreshDeleteInput();
                var item = e.target.closest('.sp-gallery-edit-item');
                if (item) item.remove();
            }
        }
    });

    // ── Dream form submit ────────────────────────────────────
    var dreamForm    = document.getElementById('sp-dream-form');
    var formMsg      = document.getElementById('sp-form-msg');
    var saveBtn      = document.getElementById('sp-save-dream-btn');
    var progressWrap = document.getElementById('sp-upload-progress');
    var progressBar  = document.getElementById('sp-upload-progress-bar');

    if (dreamForm) {
        dreamForm.addEventListener('submit', function (e) {
            e.preventDefault();

            var title = dreamForm.querySelector('[name="title"]');
            if (!title || title.value.trim() === '') {
                showMsg(formMsg, 'Please enter a title for your dream.', 'error');
                return;
            }

            var fd = new FormData(dreamForm);
            fd.append('_token', csrf());

            if (saveBtn) { saveBtn.disabled = true; saveBtn.textContent = 'Saving…'; }
            hideMsg(formMsg);

            if (progressWrap) {
                progressWrap.style.display = 'block';
                if (progressBar) progressBar.style.width = '0%';
            }

            post(
                typeof _sp_save_url !== 'undefined' ? _sp_save_url : '',
                fd,
                function (data) {
                    showMsg(formMsg, 'Dream saved!', 'success');
                    if (saveBtn) { saveBtn.disabled = false; saveBtn.textContent = 'Save Changes'; }
                    if (progressWrap) progressWrap.style.display = 'none';
                    setTimeout(function () { window.location.reload(); }, 800);
                },
                function (msg) {
                    showMsg(formMsg, msg || 'Failed to save. Please try again.', 'error');
                    if (saveBtn) { saveBtn.disabled = false; saveBtn.textContent = 'Save Changes'; }
                    if (progressWrap) progressWrap.style.display = 'none';
                },
                function (pct) {
                    if (progressBar) progressBar.style.width = pct + '%';
                }
            );
        });
    }

    // ── Like toggle ──────────────────────────────────────────
    var likeBtn = document.querySelector('.sp-like-btn');

    if (likeBtn) {
        likeBtn.addEventListener('click', function () {
            var dreamId = likeBtn.getAttribute('data-dream-id');
            if (!dreamId) return;

            var fd = new FormData();
            fd.append('_token', csrf());
            fd.append('dream_id', dreamId);

            post(
                typeof _sp_like_url !== 'undefined' ? _sp_like_url : '',
                fd,
                function (data) {
                    likeBtn.classList.toggle('liked', !!data.liked);
                    var countEl = document.getElementById('sp-like-count');
                    if (countEl) countEl.textContent = data.count !== undefined ? data.count : countEl.textContent;
                },
                function () {}
            );
        });
    }

    // ── Comment focus button ─────────────────────────────────
    var commentFocusBtn = document.querySelector('.sp-comment-focus-btn');
    var commentInput    = document.getElementById('sp-comment-input');

    if (commentFocusBtn && commentInput) {
        commentFocusBtn.addEventListener('click', function () {
            commentInput.scrollIntoView({ behavior: 'smooth', block: 'center' });
            commentInput.focus();
        });
    }

    // ── Add comment ──────────────────────────────────────────
    var commentSubmitBtn = document.getElementById('sp-comment-submit');
    var commentsSection  = document.querySelector('.sp-comments-section');
    var addCommentRow    = document.getElementById('sp-comment-form-row');

    if (commentSubmitBtn && commentInput) {
        commentSubmitBtn.addEventListener('click', function () {
            var text = commentInput.value.trim();
            if (!text) return;

            var dreamId = likeBtn ? likeBtn.getAttribute('data-dream-id') : null;
            if (!dreamId) return;

            var fd = new FormData();
            fd.append('_token', csrf());
            fd.append('dream_id', dreamId);
            fd.append('content', text);

            commentSubmitBtn.disabled = true;

            post(
                typeof _sp_comment_url !== 'undefined' ? _sp_comment_url : '',
                fd,
                function (data) {
                    commentInput.value = '';
                    commentSubmitBtn.disabled = false;

                    // Update count
                    var countEl = document.getElementById('sp-comment-count');
                    if (countEl && data.count !== undefined) countEl.textContent = data.count;

                    // Prepend new comment
                    if (data.comment && commentsSection && addCommentRow) {
                        var row = document.createElement('div');
                        row.className = 'sp-comment-row';
                        row.innerHTML =
                            '<div class="sp-comment-avatar"><div class="sp-av-initial"></div></div>' +
                            '<div class="sp-comment-body">' +
                            '<div class="sp-comment-author">' + escHtml(data.comment.alias_name || 'You') +
                            ' <span class="sp-comment-time">Just now</span></div>' +
                            '<div class="sp-comment-text">' + escHtml(data.comment.content || text) + '</div>' +
                            '</div>';
                        commentsSection.insertBefore(row, addCommentRow);
                    }
                },
                function (msg) {
                    commentSubmitBtn.disabled = false;
                    alert(msg || 'Failed to post comment.');
                }
            );
        });

        // Allow Enter to submit
        commentInput.addEventListener('keydown', function (e) {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                commentSubmitBtn.click();
            }
        });
    }

    // ── Share button ─────────────────────────────────────────
    var shareBtn = document.querySelector('.sp-share-btn');

    if (shareBtn) {
        shareBtn.addEventListener('click', function () {
            var dreamId = shareBtn.getAttribute('data-dream-id');
            var url = (typeof _sp_base_url !== 'undefined' ? _sp_base_url : '') + '/study_plans/view/' + (dreamId || '');

            if (navigator.share) {
                navigator.share({ title: 'My Dream on AI-mmi', url: url }).catch(function () {});
            } else {
                navigator.clipboard && navigator.clipboard.writeText(url).then(function () {
                    alert('Link copied to clipboard!');
                });
            }
        });
    }

    // ── AI Dream Card button ──────────────────────────────────
    var aiCardBtn   = document.getElementById('sp-ai-card-btn');
    var aiCardModal = document.getElementById('sp-ai-card-modal');

    function showAiCardModal() {
        if (!aiCardModal) return;
        // Reset to loading state
        document.getElementById('sp-ai-card-loading').style.display = 'block';
        document.getElementById('sp-ai-card-result').style.display  = 'none';
        document.getElementById('sp-ai-card-error').style.display   = 'none';
        aiCardModal.style.display = 'flex';
        generateDreamCard();
    }

    function generateDreamCard() {
        if (typeof _sp_generate_url === 'undefined' || !_sp_generate_url) return;

        var fd = new FormData();
        fd.append('_token', typeof _sp_token !== 'undefined' ? _sp_token : '');

        fetch(_sp_generate_url, { method: 'POST', body: fd })
            .then(function (r) { return r.json(); })
            .then(function (d) {
                document.getElementById('sp-ai-card-loading').style.display = 'none';
                if (d.status === 'ok' && d.card_url) {
                    var img  = document.getElementById('sp-ai-card-img');
                    var dl   = document.getElementById('sp-ai-card-download');
                    var fbBtn = document.getElementById('sp-ai-card-share-fb');
                    var copyBtn = document.getElementById('sp-ai-card-copy');

                    img.src  = d.card_url;
                    dl.href  = d.card_url;

                    fbBtn.onclick = function () {
                        window.open('https://www.facebook.com/sharer/sharer.php?u=' + encodeURIComponent(d.share_url), '_blank');
                    };
                    copyBtn.onclick = function () {
                        navigator.clipboard && navigator.clipboard.writeText(d.share_url).then(function () {
                            copyBtn.innerHTML = '<i class="fa fa-check"></i> Copied!';
                            setTimeout(function () { copyBtn.innerHTML = '<i class="fa fa-link"></i> Copy Link'; }, 2000);
                        });
                    };

                    document.getElementById('sp-ai-card-result').style.display = 'block';
                } else {
                    document.getElementById('sp-ai-card-error-msg').textContent = d.message || 'Something went wrong.';
                    document.getElementById('sp-ai-card-error').style.display   = 'block';
                }
            })
            .catch(function () {
                document.getElementById('sp-ai-card-loading').style.display = 'none';
                document.getElementById('sp-ai-card-error-msg').textContent = 'Network error. Please try again.';
                document.getElementById('sp-ai-card-error').style.display   = 'block';
            });
    }

    if (aiCardBtn) {
        aiCardBtn.addEventListener('click', showAiCardModal);
    }

    // Close AI card modal
    var aiCardClose = document.getElementById('sp-ai-card-modal-close');
    if (aiCardClose) {
        aiCardClose.addEventListener('click', function () {
            aiCardModal.style.display = 'none';
        });
    }
    if (aiCardModal) {
        aiCardModal.addEventListener('click', function (e) {
            if (e.target === aiCardModal) aiCardModal.style.display = 'none';
        });
    }

    // Regenerate button
    var regenBtn = document.getElementById('sp-ai-card-regen');
    if (regenBtn) {
        regenBtn.addEventListener('click', function () {
            document.getElementById('sp-ai-card-loading').style.display = 'block';
            document.getElementById('sp-ai-card-result').style.display  = 'none';
            document.getElementById('sp-ai-card-error').style.display   = 'none';
            generateDreamCard();
        });
    }

    // Retry button
    var retryBtn = document.getElementById('sp-ai-card-retry');
    if (retryBtn) {
        retryBtn.addEventListener('click', function () {
            document.getElementById('sp-ai-card-loading').style.display = 'block';
            document.getElementById('sp-ai-card-error').style.display   = 'none';
            generateDreamCard();
        });
    }

    // ── Copy dream link (Share with Parents & Friends) ──────────
    var copyDreamLinkBtn = document.getElementById('sp-copy-dream-link');
    if (copyDreamLinkBtn) {
        copyDreamLinkBtn.addEventListener('click', function () {
            var urlInput = document.getElementById('sp-dream-public-url');
            if (!urlInput) return;
            var url = urlInput.value;
            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(url).then(function () {
                    copyDreamLinkBtn.innerHTML = '<i class="fa fa-check"></i> Copied!';
                    setTimeout(function () {
                        copyDreamLinkBtn.innerHTML = '<i class="fa fa-copy"></i> Copy Link';
                    }, 2000);
                }).catch(function () {
                    urlInput.select();
                    document.execCommand('copy');
                    copyDreamLinkBtn.innerHTML = '<i class="fa fa-check"></i> Copied!';
                    setTimeout(function () {
                        copyDreamLinkBtn.innerHTML = '<i class="fa fa-copy"></i> Copy Link';
                    }, 2000);
                });
            } else {
                urlInput.select();
                document.execCommand('copy');
                copyDreamLinkBtn.innerHTML = '<i class="fa fa-check"></i> Copied!';
                setTimeout(function () {
                    copyDreamLinkBtn.innerHTML = '<i class="fa fa-copy"></i> Copy Link';
                }, 2000);
            }
        });
    }

    // ── Study Action Plan ────────────────────────────────────────
    var genPlanBtn       = document.getElementById('sp-gen-plan-btn');
    var planLoading      = document.getElementById('sp-action-plan-loading');
    var planEmpty        = document.getElementById('sp-action-plan-empty');
    var planResult       = document.getElementById('sp-action-plan-result');
    var planActions      = document.getElementById('sp-action-plan-actions');
    var planEmailBtn     = document.getElementById('sp-plan-email-btn');
    var planWaBtn        = document.getElementById('sp-plan-wa-btn');
    var planRegenBtn     = document.getElementById('sp-plan-regen-btn');

    var _storedPlanText  = '';
    var _storedDreamName = '';
    var _storedDreamTitle= '';

    function fetchActionPlan() {
        if (!planLoading || !planResult || !planEmpty) return;
        planEmpty.style.display   = 'none';
        planLoading.style.display = 'block';
        planResult.style.display  = 'none';
        if (planActions) planActions.style.display = 'none';

        fetch(_sp_action_plan_url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'X-CSRF-TOKEN': _sp_token
            },
            body: new URLSearchParams({ _token: _sp_token })
        })
        .then(function (r) { return r.json(); })
        .then(function (d) {
            planLoading.style.display = 'none';
            if (d && (d.status === 200 || d.status === 'ok') && d.plan) {
                _storedPlanText  = d.plan;
                _storedDreamName = d.name || '';
                _storedDreamTitle= d.dream || '';

                // Render markdown-style bold (**text**) → <strong>
                var rendered = d.plan
                    .replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>')
                    .replace(/\n/g, '<br>');
                planResult.innerHTML = rendered;
                planResult.style.display = 'block';

                // Wire up share buttons with the plan content
                if (planEmailBtn) {
                    var emailSubject = encodeURIComponent((_storedDreamName || 'Student') + "'s Study Action Plan");
                    var emailBody    = encodeURIComponent(
                        "Hi!\n\n" + (_storedDreamName || 'A student') +
                        " has shared their study action plan with you.\n\nDream: \"" +
                        _storedDreamTitle + "\"\n\n" + _storedPlanText +
                        "\n\nPowered by AI-mmi"
                    );
                    planEmailBtn.href = 'mailto:?subject=' + emailSubject + '&body=' + emailBody;
                }
                if (planWaBtn) {
                    planWaBtn.href = 'https://wa.me/?text=' + encodeURIComponent(
                        (_storedDreamName || 'A student') +
                        "'s Study Action Plan for \"" + _storedDreamTitle + "\":\n\n" +
                        _storedPlanText + "\n\nPowered by AI-mmi"
                    );
                }

                if (planActions) planActions.style.display = 'flex';
            } else {
                planEmpty.style.display = 'block';
                planEmpty.querySelector('p').textContent = (d && d.message) || 'Could not generate plan. Please try again.';
            }
        })
        .catch(function () {
            planLoading.style.display = 'none';
            planEmpty.style.display   = 'block';
            planEmpty.querySelector('p').textContent = 'Network error. Please try again.';
        });
    }

    if (genPlanBtn) {
        genPlanBtn.addEventListener('click', fetchActionPlan);
    }
    if (planRegenBtn) {
        planRegenBtn.addEventListener('click', function () {
            planResult.style.display = 'none';
            fetchActionPlan();
        });
    }

    // ── Scholarship Finder Modal ─────────────────────────────────
    var schModal    = document.getElementById('sp-scholarship-modal');
    var schLoading  = document.getElementById('sp-sch-loading');
    var schResults  = document.getElementById('sp-sch-results');
    var schList     = document.getElementById('sp-sch-list');
    var schEmpty    = document.getElementById('sp-sch-empty');
    var schEmptyMsg = document.getElementById('sp-sch-empty-msg');
    var schFallback = document.getElementById('sp-sch-fallback-btn');
    var schFallbackLabel = document.getElementById('sp-sch-fallback-label');
    var schOpenBtn  = document.getElementById('sp-scholarship-btn');
    var schCloseBtn = document.getElementById('sp-sch-close');

    function openSchModal() {
        if (!schModal) return;
        schModal.style.display = 'flex';
        document.body.style.overflow = 'hidden';
        if (schResults) schResults.style.display = 'none';
        if (schEmpty)   schEmpty.style.display   = 'none';
        if (schLoading) schLoading.style.display = 'block';
        if (schList)    schList.innerHTML = '';

        fetch(_sp_scholarship_url, {
            method: 'GET',
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(function (r) { return r.json(); })
        .then(function (d) {
            if (schLoading) schLoading.style.display = 'none';
            var list = d.scholarships || [];
            if (list.length > 0) {
                // Build result cards
                var html = '';
                list.forEach(function (s) {
                    var logoHtml = s.logo
                        ? '<img src="' + escHtml(s.logo) + '" alt="' + escHtml(s.institute_name) + '" class="sp-sch-logo">'
                        : '<div class="sp-sch-logo-placeholder">' + escHtml((s.institute_name || 'I').charAt(0)) + '</div>';
                    var meta = [s.delivery, s.duration, s.fee_tuition].filter(Boolean).join(' &bull; ');
                    html += '<div class="sp-sch-card">'
                        + '<div class="sp-sch-card-header">'
                        + logoHtml
                        + '<div class="sp-sch-card-info">'
                        + '<div class="sp-sch-inst-name">' + escHtml(s.institute_name) + '</div>'
                        + '<div class="sp-sch-course-name">' + escHtml(s.course_name) + '</div>'
                        + (meta ? '<div class="sp-sch-meta">' + meta + '</div>' : '')
                        + '</div></div>'
                        + '<div class="sp-sch-award"><i class="fa fa-star"></i> ' + escHtml(s.scholarship_text) + '</div>'
                        + '<a href="' + escHtml(s.profile_url) + '" target="_blank" rel="noopener" class="sp-sch-apply-btn">'
                        + '<i class="fa fa-external-link"></i> View &amp; Apply</a>'
                        + '</div>';
                });
                if (schList) schList.innerHTML = html;
                if (schResults) schResults.style.display = 'block';
            } else {
                // Empty state — redirect based on plan
                var hasAgent = d.has_agent_plan === true;
                if (schFallback) {
                    schFallback.href = hasAgent
                        ? (typeof _sp_apply_url !== 'undefined' ? _sp_apply_url : '#')
                        : (typeof _sp_chat_url  !== 'undefined' ? _sp_chat_url  : '#');
                }
                if (schFallbackLabel) {
                    schFallbackLabel.textContent = hasAgent
                        ? 'Book a Meeting with Your Agent'
                        : 'Chat with Our Education Advisor';
                }
                var fallbackIcon = (schFallback && schFallback.querySelector) ? schFallback.querySelector('i') : null;
                if (fallbackIcon) {
                    fallbackIcon.className = hasAgent ? 'fa fa-calendar' : 'fa fa-comments';
                }
                if (schEmptyMsg) {
                    schEmptyMsg.textContent = hasAgent
                        ? 'No scholarships are listed by our partner universities yet. Your agent can help you find external scholarship options tailored to your profile.'
                        : 'No scholarships are listed by our partner universities yet. Chat with our education advisor to explore external scholarship opportunities.';
                }
                if (schEmpty) schEmpty.style.display = 'block';
            }
        })
        .catch(function () {
            if (schLoading) schLoading.style.display = 'none';
            if (schEmptyMsg) schEmptyMsg.textContent = 'Could not load scholarships. Please try again.';
            if (schEmpty) schEmpty.style.display = 'block';
        });
    }

    function closeSchModal() {
        if (!schModal) return;
        schModal.style.display = 'none';
        document.body.style.overflow = '';
    }

    function escHtml(s) {
        return String(s || '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    if (schOpenBtn) schOpenBtn.addEventListener('click', openSchModal);
    if (schCloseBtn) schCloseBtn.addEventListener('click', closeSchModal);
    if (schModal) {
        schModal.addEventListener('click', function (e) {
            if (e.target === schModal) closeSchModal();
        });
    }
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && schModal && schModal.style.display !== 'none') {
            closeSchModal();
        }
    });

});

/* ── Full-width dark→light bg transition for study_plans ── */
(function() {
  function applySpBgTransition() {
    var hero = document.querySelector('.sp-hero');
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
    applySpBgTransition();
    setTimeout(applySpBgTransition, 150);
  });
  window.addEventListener('resize', applySpBgTransition);
})();
