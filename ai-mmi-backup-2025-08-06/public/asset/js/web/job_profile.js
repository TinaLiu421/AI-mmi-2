(function () {
    'use strict';
    var cfg = window.jpProfConfig || {};
    var base = cfg.baseUrl || '';
    var profile = cfg.profile || {};
    var currentSection = '';

    function toast(msg) {
        var el = document.getElementById('jp-prof-toast');
        if (!el) return;
        el.textContent = msg;
        el.className = 'sp-toast sp-toast-show';
        setTimeout(function () { el.className = 'sp-toast'; }, 3000);
    }

    function save(section, data) {
        return fetch(base + '/job_profile/save', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': cfg.csrf,
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({ section: section, data: data, _token: cfg.csrf })
        }).then(function (r) { return r.json(); });
    }

    window.jpCloseModal = function () {
        document.getElementById('jp-modal-bg').style.display = 'none';
    };

    function esc(s) { return String(s || '').replace(/"/g, '&quot;'); }

    window.jpOpenModal = function (section) {
        currentSection = section;
        var body = document.getElementById('jp-modal-body');
        var title = document.getElementById('jp-modal-title');
        var html = '';

        if (section === 'hero') {
            title.textContent = 'Headline & location';
            html = '<label>Headline<input id="m-headline" value="' + esc(profile.headline) + '"></label>'
                + '<label>Country<input id="m-country" value="' + esc(profile.current_country) + '"></label>'
                + '<label>City<input id="m-city" value="' + esc(profile.current_city) + '"></label>'
                + '<label><input type="checkbox" id="m-open"' + (profile.open_to_work ? ' checked' : '') + '> Open to work</label>';
        } else if (section === 'bio') {
            title.textContent = 'About';
            html = '<textarea id="m-bio" rows="6">' + esc(profile.bio) + '</textarea>';
        } else if (section === 'skills') {
            title.textContent = 'Skills (comma-separated)';
            html = '<textarea id="m-skills" rows="4">' + esc((profile.skills || []).join(', ')) + '</textarea>';
        } else if (section === 'preferences') {
            title.textContent = 'Job preferences';
            html = '<label>Target roles (comma-separated)<input id="m-roles" value="' + esc((profile.target_roles || []).join(', ')) + '"></label>'
                + '<label>Target locations<input id="m-locs" value="' + esc((profile.target_locations || []).join(', ')) + '"></label>'
                + '<label>Employment type<select id="m-emp"><option value="">Any</option>'
                + ['full_time','part_time','internship','contract','remote'].map(function (t) {
                    return '<option value="' + t + '"' + (profile.employment_preference === t ? ' selected' : '') + '>' + t.replace('_',' ') + '</option>';
                }).join('') + '</select></label>';
        } else if (section === 'work') {
            title.textContent = 'Work experience (one per line: Title | Company | From | To)';
            var lines = (profile.work_experience || []).map(function (w) {
                return [w.title, w.company, w.from, w.to].join(' | ');
            }).join('\n');
            html = '<textarea id="m-work" rows="8" placeholder="Engineer | Acme Co | 2022 | Present">' + esc(lines) + '</textarea>';
        } else if (section === 'education') {
            title.textContent = 'Education (one per line: Degree | Field | Institution)';
            var elines = (profile.education_history || []).map(function (e) {
                return [e.degree, e.field, e.institution].join(' | ');
            }).join('\n');
            html = '<textarea id="m-edu" rows="6">' + esc(elines) + '</textarea>';
        }

        body.innerHTML = html;
        document.getElementById('jp-modal-bg').style.display = 'flex';
    };

    document.getElementById('jp-modal-save').addEventListener('click', function () {
        var data = {};
        if (currentSection === 'hero') {
            data = {
                headline: document.getElementById('m-headline').value,
                current_country: document.getElementById('m-country').value,
                current_city: document.getElementById('m-city').value,
                open_to_work: document.getElementById('m-open').checked ? 'yes' : ''
            };
        } else if (currentSection === 'bio') {
            data = { bio: document.getElementById('m-bio').value };
        } else if (currentSection === 'skills') {
            data = { skills: document.getElementById('m-skills').value.split(',').map(function (s) { return s.trim(); }).filter(Boolean) };
        } else if (currentSection === 'preferences') {
            data = {
                target_roles: document.getElementById('m-roles').value.split(',').map(function (s) { return s.trim(); }).filter(Boolean),
                target_locations: document.getElementById('m-locs').value.split(',').map(function (s) { return s.trim(); }).filter(Boolean),
                employment_preference: document.getElementById('m-emp').value
            };
        } else if (currentSection === 'work') {
            data.entries = document.getElementById('m-work').value.split('\n').filter(Boolean).map(function (line) {
                var p = line.split('|').map(function (x) { return x.trim(); });
                return { title: p[0]||'', company: p[1]||'', from: p[2]||'', to: p[3]||'', current: (p[3]||'').toLowerCase() === 'present' };
            });
        } else if (currentSection === 'education') {
            data.entries = document.getElementById('m-edu').value.split('\n').filter(Boolean).map(function (line) {
                var p = line.split('|').map(function (x) { return x.trim(); });
                return { degree: p[0]||'', field: p[1]||'', institution: p[2]||'' };
            });
        }

        save(currentSection, data).then(function (res) {
            if (res.status === 200) {
                jpCloseModal();
                toast('Saved!');
                setTimeout(function () { location.reload(); }, 600);
            } else {
                toast(res.message || 'Save failed');
            }
        });
    });

    var resumeInput = document.getElementById('jp-resume-input');
    if (resumeInput) {
        resumeInput.addEventListener('change', function () {
            if (!resumeInput.files || !resumeInput.files[0]) return;
            var fd = new FormData();
            fd.append('resume', resumeInput.files[0]);
            fd.append('_token', cfg.csrf);
            fetch(base + '/job_profile/upload_resume', {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': cfg.csrf, 'X-Requested-With': 'XMLHttpRequest' },
                body: fd
            }).then(function (r) { return r.json(); }).then(function (res) {
                toast(res.message || 'Done');
                if (res.status === 200) setTimeout(function () { location.reload(); }, 600);
            });
        });
    }
})();
