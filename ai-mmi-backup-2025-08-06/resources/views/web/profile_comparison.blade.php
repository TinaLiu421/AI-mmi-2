@extends('web.common')

@section('content')
<div class="comparison-container">
    <div class="page-header">
        <h1><i class="fa fa-passport"></i> <?php echo $_page_lang['profile_comparison.page_title']; ?></h1>
        <p><?php echo $_page_lang['profile_comparison.page_subtitle']; ?></p>
    </div>

    <div class="analysis-section">
        <button onclick="recalculateComparison()" id="recalculate-btn" class="recalculate-btn" style="display: none;">
            <i class="fa fa-magic" id="recalculate-icon"></i> Recalculate
        </button>
        <p class="hint"><?php echo $_page_lang['profile_comparison.analyze_hint']; ?></p>

        <div id="loading" class="loading" style="display: none;">
            <div class="spinner"></div>
            <p><?php echo $_page_lang['profile_comparison.analyzing_message']; ?></p>
        </div>

        <div id="no-history-notification" class="toast-notification" style="display: none;">
            <div class="toast-content">
                <i class="fa fa-info-circle"></i>
                <span>Please chat with AI first to share your information before generating comparison</span>
                <button class="toast-close" onclick="document.getElementById('no-history-notification').style.display='none';">×</button>
            </div>
        </div>
    </div>

    <div id="results"></div>
</div>

<style>
.comparison-container {
    max-width: 1400px;
    margin: var(--space-8) auto;
    padding: var(--space-5);
}

.page-header {
    text-align: center;
    margin-bottom: var(--space-8);
}

.page-header h1 {
    color: var(--primary-blue-dark);
    font-size: 2rem;
    margin-bottom: var(--space-2);
}

.page-header p {
    color: var(--neutral-500);
}

.analysis-section {
    background: var(--white);
    padding: var(--space-6);
    border-radius: var(--space-3);
    box-shadow: var(--shadow-md);
    margin-bottom: var(--space-8);
    text-align: center;
}

.recalculate-btn {
    padding: var(--space-2) var(--space-6);
    background: var(--white);
    color: var(--primary-blue-dark);
    border: 2px solid var(--primary-blue-dark);
    border-radius: var(--space-2);
    cursor: pointer;
    font-weight: 500;
    font-size: 0.875rem;
    transition: all 0.3s ease;
    margin-left: var(--space-2);
}

.recalculate-btn:hover {
    background: var(--primary-blue-dark);
    color: var(--white);
    transform: translateY(-1px);
}

.recalculate-btn:disabled {
    opacity: 0.6;
    cursor: not-allowed;
    transform: none;
}

.recalculate-btn.spinning #recalculate-icon {
    animation: spin 1s linear infinite;
}

.hint {
    color: var(--neutral-500);
    margin-top: var(--space-2);
    font-size: 0.875rem;
}

.loading {
    display: none;
    padding: var(--space-10);
}

.spinner {
    display: inline-block;
    width: 50px;
    height: 50px;
    border: 5px solid var(--neutral-100);
    border-top: 5px solid var(--primary-blue-dark);
    border-radius: 50%;
    animation: spin 1s linear infinite;
}

@keyframes spin {
    to { transform: rotate(360deg); }
}

.loading p {
    margin-top: var(--space-5);
    color: var(--primary-blue-dark);
    font-weight: 600;
}

.toast-notification {
    background: #fff3cd;
    border: 1px solid #ffc107;
    border-radius: var(--space-2);
    padding: var(--space-4);
    margin: var(--space-4) 0;
    animation: slideDown 0.3s ease;
}

.toast-content {
    display: flex;
    align-items: center;
    gap: var(--space-3);
    color: #856404;
}

.toast-content i {
    font-size: 1.25rem;
    flex-shrink: 0;
}

.toast-content span {
    flex: 1;
    font-weight: 500;
}

.toast-close {
    background: none;
    border: none;
    color: #856404;
    font-size: 1.5rem;
    cursor: pointer;
    padding: 0;
    flex-shrink: 0;
    transition: opacity 0.2s;
}

.toast-close:hover {
    opacity: 0.7;
}

@keyframes slideDown {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.visa-card {
    background: var(--white);
    border-radius: var(--space-3);
    box-shadow: var(--shadow-md);
    margin-bottom: var(--space-8);
    overflow: hidden;
    transition: all 0.3s ease;
}

.visa-card:hover {
    box-shadow: var(--shadow-lg);
}

.visa-header {
    background: var(--primary-blue-dark);
    color: var(--white);
    padding: var(--space-5);
}

.visa-header h2 {
    margin: 0;
    font-size: 1.5rem;
    display: inline-block;
}

.visa-header p {
    margin: var(--space-2) 0 0 0;
    opacity: 0.9;
}

.visa-body {
    padding: var(--space-5);
}

.badge {
    display: inline-block;
    padding: var(--space-2) var(--space-5);
    border-radius: var(--space-5);
    font-weight: 600;
    font-size: 1.125rem;
    margin-left: var(--space-4);
}

.badge-high { background: var(--accent-green); color: var(--white); }
.badge-medium { background: #f39c12; color: var(--white); }
.badge-low { background: var(--accent-red); color: var(--white); }

.badge-warning {
    background: #f39c12;
    color: var(--white);
    padding: var(--space-2) var(--space-4);
    border-radius: var(--space-4);
    font-size: 0.875rem;
    margin-left: var(--space-2);
}

table {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: var(--space-5);
}

thead tr {
    background: var(--neutral-50);
    border-bottom: 2px solid var(--neutral-200);
}

th, td {
    padding: var(--space-3);
    text-align: left;
}

th {
    font-weight: 600;
    color: var(--primary-blue-dark);
}

th:last-child, td:last-child {
    text-align: center;
}

tbody tr {
    border-bottom: 1px solid var(--neutral-200);
}

tbody tr:hover {
    background: var(--neutral-50);
}

tbody tr.missing {
    background: #fff8dc;
}

.critical-badge {
    color: var(--accent-red);
    font-size: 0.688rem;
    font-weight: bold;
}

.status-met { color: var(--accent-green); font-weight: bold; }
.status-not-met { color: var(--accent-red); font-weight: bold; }
.status-missing, .not-provided, .warning-text { color: #f39c12; font-weight: bold; }

.recommendation {
    background: var(--neutral-50);
    padding: var(--space-5);
    border-radius: var(--space-2);
    border-left: 4px solid #f39c12;
}

.recommendation.success { border-left-color: var(--accent-green); }
.recommendation.warning { border-left-color: var(--accent-red); }

.recommendation h3 {
    margin: 0 0 var(--space-2) 0;
    color: var(--primary-blue-dark);
}

.recommendation p {
    margin: 0 0 var(--space-4) 0;
}

.recommendation ul {
    margin: var(--space-2) 0 0 0;
    padding-left: var(--space-5);
}

@media (max-width: 700px) {
    .comparison-container {
        padding: var(--space-2);
        margin: var(--space-4) auto;
    }

    .page-header h1 { font-size: 1.5rem; }
    .page-header p { font-size: 0.875rem; }

    .analysis-section { padding: var(--space-4); }

    .ai-btn, .recalculate-btn {
        width: 100%;
        margin: var(--space-1) 0;
    }

    .ai-btn { padding: var(--space-3) var(--space-8); }
    .recalculate-btn { padding: var(--space-2) var(--space-5); }

    .visa-card { margin-bottom: var(--space-5); }
    .visa-header { padding: var(--space-4); }
    .visa-header h2 {
        font-size: 1.125rem;
        display: block;
        margin-bottom: var(--space-2);
    }
    .visa-body { padding: var(--space-2); }

    .badge {
        display: block;
        margin: var(--space-2) 0 0 0;
        padding: var(--space-2) var(--space-4);
        font-size: 0.875rem;
        text-align: center;
    }

    .badge-warning {
        display: inline-block;
        font-size: 0.75rem;
    }

    table { display: block; overflow-x: auto; -webkit-overflow-scrolling: touch; }
    thead { display: none; }
    tbody { display: block; }

    tbody tr {
        display: block;
        margin-bottom: var(--space-4);
        border: 1px solid var(--neutral-200);
        border-radius: var(--space-2);
        padding: var(--space-3);
        background: var(--white);
    }

    tbody tr.missing { background: #fff8dc; }
    tbody tr:hover { background: var(--neutral-50); }
    tbody tr.missing:hover { background: #fff3cd; }

    td {
        display: block;
        text-align: left !important;
        padding: var(--space-2) 0;
        border: none;
    }

    td:nth-child(1)::before { content: "Requirement: "; font-weight: bold; color: var(--primary-blue-dark); }
    td:nth-child(2)::before { content: "Visa Requirement: "; font-weight: bold; color: var(--primary-blue-dark); }
    td:nth-child(3)::before { content: "Your Profile: "; font-weight: bold; color: var(--primary-blue-dark); }
    td:nth-child(4)::before { content: "Status: "; font-weight: bold; color: var(--primary-blue-dark); }

    .critical-badge { display: block; margin-top: var(--space-1); }
    .status-met, .status-not-met, .status-missing { font-size: 1rem; }

    .recommendation { padding: var(--space-4); font-size: 0.875rem; }
    .recommendation h3 { font-size: 1rem; }
    .recommendation p, .recommendation ul { font-size: 0.813rem; }
}

@media (max-width: 480px) {
    .page-header h1 { font-size: 1.25rem; }
    .visa-header h2 { font-size: 1rem; }
    .badge { font-size: 0.813rem; padding: var(--space-1) var(--space-3); }
    td { font-size: 0.813rem; }
    .recommendation { padding: var(--space-3); }
}
</style>

<script>
function loadAIComparison() {
    document.getElementById('loading').style.display = 'block';
    document.getElementById('no-history-notification').style.display = 'none';

    fetch(_page_base_url + '/profile_comparison/get_ai_comparison', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ _token: _token })
    })
    .then(res => res.json())
    .then(data => {
        document.getElementById('loading').style.display = 'none';
        if (data.status === 200 && data.comparison) {
            displayResults(data.comparison);
        } else if (data.status === 400 && data.message.includes('No chat history')) {
            document.getElementById('no-history-notification').style.display = 'block';
        } else {
            document.getElementById('no-history-notification').style.display = 'block';
        }
    })
    .catch(error => {
        console.error('Error:', error);
        document.getElementById('loading').style.display = 'none';
        document.getElementById('no-history-notification').style.display = 'block';
    });
}

function recalculateComparison() {
    const btn = document.getElementById('recalculate-btn');
    const icon = document.getElementById('recalculate-icon');

    btn.disabled = true;
    btn.classList.add('spinning');

    document.getElementById('loading').style.display = 'block';
    document.getElementById('results').style.display = 'none';

    fetch(_page_base_url + '/profile_comparison/get_ai_comparison', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ _token: _token })
    })
    .then(res => res.json())
    .then(data => {
        document.getElementById('loading').style.display = 'none';
        btn.disabled = false;
        btn.classList.remove('spinning');

        if (data.status === 200 && data.comparison) {
            displayResults(data.comparison);
        } else {
            alert('Error: ' + (data.message || 'Failed to generate comparison'));
            document.getElementById('results').style.display = 'block';
        }
    })
    .catch(error => {
        console.error('Error:', error);
        document.getElementById('loading').style.display = 'none';
        document.getElementById('results').style.display = 'block';
        btn.disabled = false;
        btn.classList.remove('spinning');
        alert('Failed to recalculate comparison. Please try again.');
    });
}

document.addEventListener('DOMContentLoaded', function() {
    loadAIComparison();
});

function displayResults(data) {
    const results = document.getElementById('results');
    results.style.display = 'block';
    results.innerHTML = '';

    document.getElementById('recalculate-btn').style.display = 'inline-block';

    const visas = data.visa_options || [];
    if (visas.length === 0) {
        results.innerHTML = '<p style="text-align: center; padding: 40px; color: #999;">No visa options found.</p>';
        return;
    }

    visas.forEach(visa => {
        const matchClass = visa.match_score >= 70 ? 'badge-high' : (visa.match_score >= 50 ? 'badge-medium' : 'badge-low');
        const recLevel = visa.recommendation?.level || 'info';

        let html = `
            <div class="visa-card">
                <div class="visa-header">
                    <h2><i class="fa fa-passport"></i> ${visa.country} - ${visa.visa_name}</h2>
                    <span class="badge ${matchClass}">${visa.match_score}% Match</span>
                    <p>${visa.description || ''}</p>
                </div>
                <div class="visa-body">
                    <table>
                        <thead>
                            <tr>
                                <th>Requirement</th>
                                <th>Visa Requirement</th>
                                <th>Your Profile</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>`;

        (visa.requirements || []).forEach(req => {
            const icon = req.status === 'met' ? '✅' : (req.status === 'not_met' ? '❌' : '⚠️');
            const statusClass = 'status-' + (req.status || 'missing');
            const isMissing = req.status === 'missing';

            html += `
                <tr>
                    <td>
                        <strong>${req.requirement}</strong>
                        ${req.is_critical ? '<span class="critical-badge">CRITICAL</span>' : ''}
                        ${req.details ? '<br><small style="color: #666;">' + req.details + '</small>' : ''}
                    </td>
                    <td>${req.visa_requirement || '-'}</td>
                    <td>
                        ${req.user_value || '<span class="not-provided">❓ Not provided</span>'}
                        ${isMissing ? '<br><small class="warning-text">⚠️ Treated as not met in score calculation</small>' : ''}
                    </td>
                    <td><span class="${statusClass}">${icon}</span></td>
                </tr>`;
        });

        html += `
                        </tbody>
                    </table>
                    <div class="recommendation ${recLevel}">
                        <h3><i class="fa fa-${visa.recommendation?.icon || 'info-circle'}"></i> Recommendation</h3>
                        <p>${visa.recommendation?.message || ''}</p>
                        ${visa.recommendation?.next_steps && visa.recommendation.next_steps.length > 0 ? `
                            <strong>Next Steps:</strong>
                            <ul>${visa.recommendation.next_steps.map(s => '<li>' + s + '</li>').join('')}</ul>
                        ` : ''}
                    </div>
                </div>
            </div>`;

        results.innerHTML += html;
    });
}
</script>
@endsection
