/* study_college_match.js */
(function(){
    /* Apply button: pre-fill university from choice_1 */
    var applyBtn = document.getElementById('cm-apply-btn');
    if (applyBtn) {
        applyBtn.addEventListener('click', function() {
            var uniInput = document.querySelector('input[name="choice_1_university"]');
            var uniVal = uniInput ? uniInput.value.trim() : '';
            var base = _cm_baseUrl + '/apply';
            applyBtn.href = uniVal ? base + '?institution=' + encodeURIComponent(uniVal) : base;
        });
    }

    var saveBtn     = document.getElementById('cm-save-btn');
    var matchBtn    = document.getElementById('cm-match-btn');
    var actionMsg   = document.getElementById('cm-action-msg');
    var errMsg      = document.getElementById('cm-error-msg');
    var loadingDiv  = document.getElementById('cm-loading');
    var resultsWrap = document.getElementById('cm-results-wrap');
    var topBlock    = document.getElementById('cm-top-matches-block');
    var alsoBlock   = document.getElementById('cm-also-block');
    var alsoTitle   = document.getElementById('cm-also-title');
    var form        = document.getElementById('cm-prefs-form');

    function showMsg(el, txt, dur) {
        el.textContent = txt;
        el.style.display = 'inline';
        if (dur) { setTimeout(function(){ el.style.display = 'none'; }, dur); }
    }
    function hideMsg(el) { el.style.display = 'none'; }

    function escHtml(str) {
        return String(str || '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    }

    function getFormData() {
        var fd = new FormData(form);
        fd.set('_token', _cm_csrf);
        return fd;
    }

    /* Save Preferences */
    if (saveBtn) {
        saveBtn.addEventListener('click', function(){
            hideMsg(actionMsg); hideMsg(errMsg);
            saveBtn.disabled = true;
            saveBtn.innerHTML = '<span>&#128190;</span> Saving...';
            fetch(_cm_saveUrl, { method:'POST', headers:{'X-Requested-With':'XMLHttpRequest'}, body: getFormData() })
            .then(function(r){ return r.json(); })
            .then(function(res){
                saveBtn.disabled = false;
                saveBtn.innerHTML = '<span>&#128190;</span> Save Preferences';
                if (res.status === 200) { showMsg(actionMsg, '✔ Preferences saved!', 3000); }
                else { showMsg(errMsg, res.message || 'Save failed.', 4000); }
            })
            .catch(function(){
                saveBtn.disabled = false;
                saveBtn.innerHTML = '<span>&#128190;</span> Save Preferences';
                showMsg(errMsg, 'Network error. Please try again.', 4000);
            });
        });
    }

    /* Build top match table */
    function buildMatchedTable(items) {
        if (!items || !items.length) {
            return '<div class="cm-empty-state">&#128269; No matching universities found for your preferences. Try broadening your criteria.</div>';
        }
        var html = '<table class="cm-match-table"><thead><tr><th style="width:180px">University</th><th>Programs</th><th style="width:100px"></th></tr></thead><tbody>';
        items.forEach(function(inst){
            var logoHtml = inst.logo_url
                ? '<div class="cm-inst-logo-div" style="background-image:url(\'' + escHtml(inst.logo_url) + '\')"></div>'
                : '<div class="cm-inst-logo-div"></div>';
            var topProgs = Array.isArray(inst.top_programs) ? inst.top_programs : [];
            var progHtml;
            if (topProgs.length) {
                progHtml = '<table class="cm-prog-table"><thead><tr><th>Program</th><th>Admission</th><th>Fees</th></tr></thead><tbody>';
                topProgs.forEach(function(p){
                    var name = (p && typeof p === 'object') ? (p.name || '') : String(p || '');
                    var adm  = (p && typeof p === 'object') ? (p.admission || '&mdash;') : '&mdash;';
                    var fee  = (p && typeof p === 'object') ? (p.fees || '&mdash;') : '&mdash;';
                    progHtml += '<tr><td class="cm-prog-name">' + escHtml(name) + '</td><td class="cm-prog-adm">' + escHtml(adm) + '</td><td class="cm-prog-fee">' + escHtml(fee) + '</td></tr>';
                });
                progHtml += '</tbody></table>';
            } else {
                progHtml = '<div style="padding:12px;color:#94a3b8;font-size:13px;">&mdash;</div>';
            }
            var scoreHtml = '<span class="cm-score-badge">&#9733; ' + escHtml(String(inst.match_score)) + '/10</span>';
            var reasonHtml = inst.match_reason ? '<div class="cm-match-reason">' + escHtml(inst.match_reason) + '</div>' : '';
            html += '<tr>'
                + '<td class="cm-inst-cell">' + logoHtml + '<div class="cm-inst-name">' + escHtml(inst.name) + '</div>' + scoreHtml + reasonHtml + '</td>'
                + '<td class="cm-progs-cell">' + progHtml + '</td>'
                + '<td class="cm-view-cell"><a class="cm-view-btn" href="' + escHtml(_cm_baseUrl + '/apply?institution=' + encodeURIComponent(inst.name)) + '">Apply &rarr;</a></td>'
                + '</tr>';
        });
        return html + '</tbody></table>';
    }

    /* Build also-consider table */
    function buildAlsoTable(items) {
        if (!items || !items.length) return '';
        var html = '<table class="cm-match-table"><thead><tr><th style="width:180px">University</th><th>Programs</th><th>Why Recommended</th><th style="width:90px"></th></tr></thead><tbody>';
        items.forEach(function(item){
            var progs = Array.isArray(item.programs) ? item.programs : [];
            var progHtml = '<table class="cm-prog-table"><thead><tr><th>Program</th><th>Fees</th></tr></thead><tbody>';
            if (progs.length) {
                progs.forEach(function(p){
                    var name = (typeof p === 'object' && p !== null) ? (p.name || '') : String(p);
                    var fee  = (typeof p === 'object' && p !== null) ? (p.fees || '&mdash;') : '&mdash;';
                    progHtml += '<tr><td class="cm-prog-name-cell">' + escHtml(name) + '</td><td class="cm-prog-fee-cell">' + escHtml(fee) + '</td></tr>';
                });
            } else {
                progHtml += '<tr><td colspan="2" style="color:#94a3b8;padding:10px;">&mdash;</td></tr>';
            }
            progHtml += '</tbody></table>';
            var domain = '';
            try { if (item.website) domain = (new URL(item.website)).hostname.replace(/^www\./, ''); } catch(e) {}
            var logoHtml = domain
                ? '<img src="https://logo.clearbit.com/' + escHtml(domain) + '" onerror="this.src=\'https://www.google.com/s2/favicons?domain=' + escHtml(domain) + '&sz=64\';this.onerror=function(){this.style.display=\'none\';};" class="cm-also-logo" alt="">'
                : '';
            html += '<tr>'
                + '<td class="cm-inst-cell">' + logoHtml + '<div class="cm-inst-name">' + escHtml(item.name) + '</div><div style="font-size:11px;color:#94a3b8;">' + escHtml(item.country) + '</div></td>'
                + '<td class="cm-progs-cell" style="padding:0!important">' + progHtml + '</td>'
                + '<td style="font-size:12px;color:#64748b;padding:12px 14px;">' + escHtml(item.why_recommended) + '</td>'
                + '<td class="cm-view-cell"><a class="cm-ext-link" href="' + escHtml(_cm_baseUrl + '/apply?institution=' + encodeURIComponent(item.name)) + '">Apply</a></td>'
                + '</tr>';
        });
        return html + '</tbody></table>';
    }

    /* Find My Match */
    if (matchBtn) {
        matchBtn.addEventListener('click', function(){
            hideMsg(actionMsg); hideMsg(errMsg);
            resultsWrap.style.display = 'none';
            loadingDiv.style.display  = 'block';
            matchBtn.disabled         = true;
            matchBtn.innerHTML        = '<span>&#9889;</span> Finding matches...';

            fetch(_cm_saveUrl, { method:'POST', headers:{'X-Requested-With':'XMLHttpRequest'}, body: getFormData() })
            .then(function(r){ return r.json(); })
            .then(function(saveRes){
                if (saveRes.status !== 200) { throw new Error(saveRes.message || 'Could not save preferences.'); }
                return fetch(_cm_matchUrl, {
                    method: 'POST',
                    headers: { 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': _cm_csrf },
                    body: new URLSearchParams({'_token': _cm_csrf})
                });
            })
            .then(function(r){ return r.json(); })
            .then(function(res){
                loadingDiv.style.display = 'none';
                matchBtn.disabled = false;
                matchBtn.innerHTML = '<span>&#9889;</span> Find My Match';
                if (res.status !== 200) { showMsg(errMsg, res.message || 'Matching failed.', 5000); return; }
                topBlock.innerHTML  = buildMatchedTable(res.matched);
                alsoBlock.innerHTML = buildAlsoTable(res.also_consider);
                alsoTitle.style.display = (res.also_consider && res.also_consider.length) ? 'block' : 'none';
                resultsWrap.style.display = 'block';
                resultsWrap.scrollIntoView({behavior: 'smooth', block: 'start'});
            })
            .catch(function(err){
                loadingDiv.style.display = 'none';
                matchBtn.disabled = false;
                matchBtn.innerHTML = '<span>&#9889;</span> Find My Match';
                showMsg(errMsg, err.message || 'Network error. Please try again.', 5000);
            });
        });
    }
})();

/* ── Full-width dark→light bg transition for study_college_match ── */
(function() {
  function applyCmBgTransition() {
    var hero = document.querySelector('.cm-hero');
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
    applyCmBgTransition();
    setTimeout(applyCmBgTransition, 150);
  });
  window.addEventListener('resize', applyCmBgTransition);
})();
