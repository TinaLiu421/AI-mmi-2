(function () {
    'use strict';

    var cfg = window.jpConfig || {};
    var base = cfg.baseUrl || '';
    var pendingJobId = null;
    var pendingJobTitle = '';

    function $(sel, ctx) { return (ctx || document).querySelector(sel); }
    function $all(sel, ctx) { return Array.prototype.slice.call((ctx || document).querySelectorAll(sel)); }

    function toast(msg, isError) {
        var el = document.getElementById('jp-toast');
        if (!el) return;
        el.textContent = msg;
        el.className = 'jp-toast show' + (isError ? ' jp-toast-error' : '');
        setTimeout(function () { el.className = 'jp-toast'; }, 3600);
    }

    function post(url, body) {
        body = body || {};
        body._token = cfg.csrf;
        return fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': cfg.csrf,
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            },
            credentials: 'same-origin',
            body: JSON.stringify(body)
        }).then(function (r) {
            return r.json().then(function (data) {
                if (!r.ok && !data.message) {
                    data.message = 'Request failed (' + r.status + ')';
                    data.status = data.status || r.status;
                }
                return data;
            });
        });
    }

    function openModal(id) {
        var m = document.getElementById(id);
        if (!m) return;
        if (m.parentNode !== document.body) {
            document.body.appendChild(m);
        }
        m.style.display = 'flex';
        document.documentElement.style.overflow = 'hidden';
        document.body.style.overflow = 'hidden';
        var scrollEl = m.querySelector('.jp-modal-scroll');
        if (scrollEl) scrollEl.scrollTop = 0;
    }

    function closeModals() {
        $all('.jp-modal-bg').forEach(function (m) { m.style.display = 'none'; });
        document.documentElement.style.overflow = '';
        document.body.style.overflow = '';
        pendingJobId = null;
        pendingJobTitle = '';
    }

    function parseJobCard(card) {
        try {
            return JSON.parse(card.getAttribute('data-job-json') || '{}');
        } catch (e) {
            return {};
        }
    }

    function escHtml(s) {
        var d = document.createElement('div');
        d.textContent = s || '';
        return d.innerHTML;
    }

    function renderDetail(job, card) {
        var panel = document.getElementById('jp-detail-panel');
        var inner = document.getElementById('jp-detail-inner');
        if (!panel || !inner) return;

        var logoHtml = '';
        if (job.company_logo) {
            logoHtml = '<div class="jp-detail-logo jp-detail-logo-img"><img src="' + escHtml(job.company_logo) + '" alt=""></div>';
        } else {
            logoHtml = '<div class="jp-detail-logo jp-detail-logo-empty" aria-hidden="true"></div>';
        }
        var salary = '';
        if (job.salary_min || job.salary_max) {
            salary = (job.salary_currency || 'USD') + ' ' + (job.salary_min ? Number(job.salary_min).toLocaleString() : '');
            if (job.salary_max) salary += ' – ' + Number(job.salary_max).toLocaleString();
        }

        var applyHtml = '';
        if (job.applied) {
            applyHtml = '<span class="jp-applied"><i class="fa fa-check-circle"></i> Applied</span>';
        } else if (cfg.isGuest) {
            applyHtml = '<a href="' + escHtml(base + '/account_login?redirect=' + encodeURIComponent(base + '/job_applications')) + '" class="jp-btn-primary">Sign in to apply</a>';
        } else if (!cfg.canApply) {
            applyHtml = '<span class="jp-muted-action">Individual accounts only</span>';
        } else if (job.has_external && job.application_url) {
            applyHtml = '<a href="' + escHtml(job.application_url) + '" target="_blank" rel="noopener" class="jp-btn-primary">Apply on company site <i class="fa fa-external-link"></i></a>';
        } else {
            applyHtml = '<button type="button" class="jp-btn-primary jp-apply-btn" data-job-id="' + job.id + '" data-job-title="' + escHtml(job.title) + '">Easy Apply</button>';
        }

        inner.innerHTML = ''
            + '<button type="button" class="jp-detail-close" id="jp-detail-close" aria-label="Close">&times;</button>'
            + logoHtml
            + '<h2 class="jp-detail-title">' + escHtml(job.title) + '</h2>'
            + '<p class="jp-detail-company">' + escHtml(job.company) + (job.location ? ' · ' + escHtml(job.location) : '') + '</p>'
            + '<div class="jp-detail-meta">'
            + (job.employment_type ? '<span class="jp-meta-pill">' + escHtml(job.employment_type) + '</span>' : '')
            + (job.visa_sponsorship ? '<span class="jp-tag jp-tag-green">Visa sponsorship</span>' : '')
            + (job.posted ? '<span class="jp-time">' + escHtml(job.posted) + '</span>' : '')
            + '</div>'
            + (salary ? '<div class="jp-detail-section"><h4>Salary</h4><p>' + escHtml(salary) + '</p></div>' : '')
            + (job.description ? '<div class="jp-detail-section"><h4>About the job</h4><p>' + escHtml(job.description) + '</p></div>' : '')
            + (job.requirements ? '<div class="jp-detail-section"><h4>Requirements</h4><p>' + escHtml(job.requirements) + '</p></div>' : '')
            + '<div class="jp-detail-actions">' + applyHtml + '</div>';

        panel.classList.add('is-open');
        if (window.innerWidth <= 1024) {
            panel.classList.add('jp-detail-mobile-open');
        }

        $all('.jp-job-card').forEach(function (c) { c.classList.remove('is-selected'); });
        if (card) card.classList.add('is-selected');

        var closeBtn = document.getElementById('jp-detail-close');
        if (closeBtn) {
            closeBtn.addEventListener('click', closeDetail);
        }
        var applyBtn = inner.querySelector('.jp-apply-btn');
        if (applyBtn) {
            applyBtn.addEventListener('click', function () {
                openApplyModal(parseInt(applyBtn.getAttribute('data-job-id'), 10), applyBtn.getAttribute('data-job-title'));
            });
        }
    }

    function closeDetail() {
        var panel = document.getElementById('jp-detail-panel');
        if (panel) {
            panel.classList.remove('is-open', 'jp-detail-mobile-open');
        }
        $all('.jp-job-card').forEach(function (c) { c.classList.remove('is-selected'); });
    }

    function openApplyModal(jobId, title) {
        pendingJobId = jobId;
        pendingJobTitle = title || '';
        var titleEl = document.getElementById('jp-apply-job-title');
        if (titleEl) titleEl.textContent = pendingJobTitle;
        var cover = document.getElementById('jp-cover-letter');
        if (cover) cover.value = '';
        openModal('jp-apply-modal');
    }

    // Modal close handlers
    $all('[data-close-modal]').forEach(function (btn) {
        btn.addEventListener('click', closeModals);
    });
    $all('.jp-modal-bg').forEach(function (bg) {
        bg.addEventListener('click', function (e) {
            if (e.target === bg) closeModals();
        });
    });

    // Job card select / view detail
    $all('.jp-job-select, .jp-view-detail').forEach(function (btn) {
        btn.addEventListener('click', function (e) {
            e.preventDefault();
            e.stopPropagation();
            var card = btn.closest('.jp-job-card');
            if (!card) return;
            renderDetail(parseJobCard(card), card);
        });
    });

    // Easy apply buttons in list
    $all('.jp-apply-btn').forEach(function (btn) {
        btn.addEventListener('click', function (e) {
            e.stopPropagation();
            openApplyModal(
                parseInt(btn.getAttribute('data-job-id'), 10),
                btn.getAttribute('data-job-title') || ''
            );
        });
    });

    // Submit application
    var confirmApply = document.getElementById('jp-confirm-apply');
    if (confirmApply) {
        confirmApply.addEventListener('click', function () {
            if (!pendingJobId) return;
            confirmApply.disabled = true;
            confirmApply.textContent = 'Submitting…';

            post(base + '/job_applications/apply', {
                job_id: pendingJobId,
                cover_letter: (document.getElementById('jp-cover-letter') || {}).value || ''
            }).then(function (res) {
                confirmApply.disabled = false;
                confirmApply.textContent = 'Submit application';
                closeModals();
                if (res.status === 200) {
                    toast(res.message || 'Application submitted!');
                    setTimeout(function () { location.reload(); }, 900);
                } else {
                    toast(res.message || 'Could not apply.', true);
                    if (res.external_url) {
                        window.open(res.external_url, '_blank');
                    }
                }
            }).catch(function () {
                confirmApply.disabled = false;
                confirmApply.textContent = 'Submit application';
                toast('Network error. Please try again.', true);
            });
        });
    }

    function setLogoPreview(url, relativePath) {
        var wrap = document.getElementById('jp-logo-preview-wrap');
        var img = document.getElementById('jp-logo-preview');
        var hidden = document.getElementById('jp-company-logo');
        if (!wrap || !img || !hidden) return;
        if (url && relativePath) {
            img.src = url;
            hidden.value = relativePath;
            wrap.hidden = false;
        } else {
            img.removeAttribute('src');
            hidden.value = '';
            wrap.hidden = true;
        }
    }

    function postMultipart(url, formData) {
        formData.append('_token', cfg.csrf);
        return fetch(url, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': cfg.csrf,
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            },
            credentials: 'same-origin',
            body: formData
        }).then(function (r) {
            return r.json().then(function (data) {
                if (!r.ok && !data.message) {
                    data.message = 'Request failed (' + r.status + ')';
                    data.status = data.status || r.status;
                }
                return data;
            });
        });
    }

    // Admin: logo fetch / upload
    var fetchLogoBtn = document.getElementById('jp-fetch-logo');
    if (fetchLogoBtn) {
        fetchLogoBtn.addEventListener('click', function () {
            var company = (document.getElementById('jp-company-name') || {}).value || '';
            if (!String(company).trim()) {
                toast('Enter a company name first.', true);
                return;
            }
            var website = (document.getElementById('jp-company-website') || {}).value || '';
            fetchLogoBtn.disabled = true;
            fetchLogoBtn.textContent = 'Fetching…';
            post(base + '/job_applications/fetch_company_logo', {
                company_name: company.trim(),
                company_website: website.trim()
            }).then(function (res) {
                fetchLogoBtn.disabled = false;
                fetchLogoBtn.textContent = 'Auto-fetch logo';
                if (res.status === 200 && res.logo_url && res.company_logo) {
                    setLogoPreview(res.logo_url, res.company_logo);
                    toast(res.message || 'Logo found.');
                    if (res.warning) {
                        setTimeout(function () { toast(res.warning, true); }, 1200);
                    }
                } else {
                    toast(res.message || 'No logo found.', true);
                }
            }).catch(function () {
                fetchLogoBtn.disabled = false;
                fetchLogoBtn.textContent = 'Auto-fetch logo';
                toast('Network error.', true);
            });
        });
    }

    var uploadLogoBtn = document.getElementById('jp-upload-logo-btn');
    var logoFileInput = document.getElementById('jp-logo-file');
    if (uploadLogoBtn && logoFileInput) {
        uploadLogoBtn.addEventListener('click', function () { logoFileInput.click(); });
        logoFileInput.addEventListener('change', function () {
            if (!logoFileInput.files || !logoFileInput.files[0]) return;
            var fd = new FormData();
            fd.append('logo_file', logoFileInput.files[0]);
            uploadLogoBtn.disabled = true;
            uploadLogoBtn.textContent = 'Uploading…';
            postMultipart(base + '/job_applications/upload_company_logo', fd).then(function (res) {
                uploadLogoBtn.disabled = false;
                uploadLogoBtn.textContent = 'Upload logo';
                logoFileInput.value = '';
                if (res.status === 200 && res.logo_url && res.company_logo) {
                    setLogoPreview(res.logo_url, res.company_logo);
                    toast(res.message || 'Logo uploaded.');
                } else {
                    toast(res.message || 'Upload failed.', true);
                }
            }).catch(function () {
                uploadLogoBtn.disabled = false;
                uploadLogoBtn.textContent = 'Upload logo';
                logoFileInput.value = '';
                toast('Network error.', true);
            });
        });
    }

    var removeLogoBtn = document.getElementById('jp-logo-remove');
    if (removeLogoBtn) {
        removeLogoBtn.addEventListener('click', function () { setLogoPreview('', ''); });
    }

    // Admin: post job
    var openPost = document.getElementById('jp-open-post-modal');
    if (openPost) {
        openPost.addEventListener('click', function () {
            setLogoPreview('', '');
            var postFormEl = document.getElementById('jp-post-form');
            if (postFormEl) postFormEl.reset();
            var autoCb = document.getElementById('jp-auto-fetch-logo');
            if (autoCb) autoCb.checked = true;
            var prefill = cfg.companyPrefill || {};
            var companyInput = document.getElementById('jp-company-name');
            var websiteInput = document.getElementById('jp-company-website');
            if (companyInput && prefill.name && !companyInput.value) {
                companyInput.value = prefill.name;
            }
            if (websiteInput && prefill.website && !websiteInput.value) {
                websiteInput.value = prefill.website;
            }
            openModal('jp-post-modal');
        });
    }

    var postForm = document.getElementById('jp-post-form');
    if (postForm) {
        postForm.addEventListener('submit', function (e) {
            e.preventDefault();
            var submitBtn = document.getElementById('jp-post-submit');
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.textContent = 'Publishing…';
            }

            var fd = new FormData(postForm);
            var body = {};
            fd.forEach(function (v, k) { body[k] = v; });
            var visaCb = postForm.querySelector('[name=visa_sponsorship]');
            body.visa_sponsorship = visaCb && visaCb.checked ? 1 : 0;
            var autoFetchCb = postForm.querySelector('[name=auto_fetch_logo]');
            body.auto_fetch_logo = autoFetchCb && autoFetchCb.checked ? 1 : 0;
            body.company_logo = (document.getElementById('jp-company-logo') || {}).value || '';

            if (!body.title || !String(body.title).trim()) {
                toast('Job title is required.', true);
                if (submitBtn) { submitBtn.disabled = false; submitBtn.textContent = 'Publish job'; }
                return;
            }
            if (!body.company_name || !String(body.company_name).trim()) {
                toast('Company name is required.', true);
                if (submitBtn) { submitBtn.disabled = false; submitBtn.textContent = 'Publish job'; }
                return;
            }

            post(base + '/job_applications/post_job', body).then(function (res) {
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.textContent = 'Publish job';
                }
                if (res.status === 200) {
                    closeModals();
                    toast(res.message || 'Job posted!');
                    setTimeout(function () { location.reload(); }, 800);
                } else {
                    toast(res.message || 'Failed to post job.', true);
                }
            }).catch(function () {
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.textContent = 'Publish job';
                }
                toast('Network error.', true);
            });
        });
    }

    // Admin: delete job
    $all('.jp-delete-job').forEach(function (btn) {
        btn.addEventListener('click', function (e) {
            e.stopPropagation();
            if (!confirm('Remove this job posting?')) return;
            var jobId = parseInt(btn.getAttribute('data-job-id'), 10);
            btn.disabled = true;
            post(base + '/job_applications/delete_job', { job_id: jobId }).then(function (res) {
                if (res.status === 200) {
                    var card = btn.closest('.jp-job-card');
                    if (card) card.remove();
                    closeDetail();
                    toast('Job removed.');
                } else {
                    btn.disabled = false;
                    toast(res.message || 'Could not remove.', true);
                }
            }).catch(function () {
                btn.disabled = false;
                toast('Network error.', true);
            });
        });
    });

    // Mobile detail backdrop close
    var detailPanel = document.getElementById('jp-detail-panel');
    if (detailPanel) {
        detailPanel.addEventListener('click', function (e) {
            if (e.target === detailPanel) closeDetail();
        });
    }

    // Tracker smooth scroll
    $all('a[href="#jp-tracker"]').forEach(function (link) {
        link.addEventListener('click', function (e) {
            var target = document.getElementById('jp-tracker');
            if (target) {
                e.preventDefault();
                target.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        });
    });
})();
