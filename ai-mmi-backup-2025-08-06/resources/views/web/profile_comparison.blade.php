@extends('web.common')

@section('content')
<div class="comparison-container">
    <!-- Header -->
    <div class="page-header">
        <h1><i class="fa fa-passport"></i> <?php echo $_page_lang['profile_comparison.page_title']; ?></h1>
        <p><?php echo $_page_lang['profile_comparison.page_subtitle']; ?></p>
    </div>

    <!-- AI Analysis Section -->
    <div class="analysis-section">
        <button onclick="loadAIComparison()" id="ai-btn" class="ai-btn">
            <i class="fa fa-magic"></i> <?php echo $_page_lang['profile_comparison.analyze_button']; ?>
        </button>
        <p class="hint"><?php echo $_page_lang['profile_comparison.analyze_hint']; ?></p>

        <!-- Loading -->
        <div id="loading" class="loading">
            <div class="spinner"></div>
            <p><?php echo $_page_lang['profile_comparison.analyzing_message']; ?></p>
        </div>
    </div>

    <!-- Results -->
    <div id="results"></div>

    <!-- Initial Message -->
    <div id="initial-msg" class="initial-message">
        <i class="fa fa-arrow-up"></i>
        <h3><?php echo $_page_lang['profile_comparison.initial_message']; ?></h3>
    </div>
</div>

<style>
/* Container */
.comparison-container {
    max-width: 1400px;
    margin: 30px auto;
    padding: 20px;
}

/* Header */
.page-header {
    text-align: center;
    margin-bottom: 30px;
}

.page-header h1 {
    color: #012069;
    font-size: 32px;
    margin-bottom: 10px;
}

.page-header p {
    color: #666;
    font-size: 16px;
}

/* Analysis Section */
.analysis-section {
    background: white;
    padding: 25px;
    border-radius: 12px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    margin-bottom: 30px;
    text-align: center;
}

.ai-btn {
    padding: 15px 50px;
    background: linear-gradient(135deg, #012069 0%, #0052cc 100%);
    color: white;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    font-weight: 600;
    font-size: 18px;
    box-shadow: 0 4px 15px rgba(1, 32, 105, 0.3);
    transition: all 0.3s ease;
}

.ai-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(1, 32, 105, 0.4);
}

.hint {
    color: #666;
    margin-top: 10px;
    font-size: 14px;
}

/* Loading */
.loading {
    display: none;
    padding: 40px;
}

.spinner {
    display: inline-block;
    width: 50px;
    height: 50px;
    border: 5px solid #f3f3f3;
    border-top: 5px solid #012069;
    border-radius: 50%;
    animation: spin 1s linear infinite;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

.loading p {
    margin-top: 20px;
    color: #012069;
    font-weight: 600;
}

/* Initial Message */
.initial-message {
    background: white;
    padding: 60px;
    border-radius: 12px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    text-align: center;
}

.initial-message i {
    font-size: 48px;
    color: #ddd;
    margin-bottom: 20px;
}

.initial-message h3 {
    color: #999;
    margin: 0;
}

/* Visa Cards */
.visa-card {
    background: white;
    border-radius: 12px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    margin-bottom: 30px;
    overflow: hidden;
    transition: all 0.3s ease;
}

.visa-card:hover {
    box-shadow: 0 4px 20px rgba(0,0,0,0.15);
}

.visa-header {
    background: linear-gradient(135deg, #012069 0%, #0052cc 100%);
    color: white;
    padding: 20px;
}

.visa-header h2 {
    margin: 0;
    font-size: 24px;
    display: inline-block;
}

.visa-header p {
    margin: 10px 0 0 0;
    opacity: 0.9;
}

.visa-body {
    padding: 20px;
}

/* Badges */
.badge {
    display: inline-block;
    padding: 8px 20px;
    border-radius: 20px;
    font-weight: 600;
    font-size: 18px;
    margin-left: 15px;
}

.badge-high { background: #27ae60; color: white; }
.badge-medium { background: #f39c12; color: white; }
.badge-low { background: #e74c3c; color: white; }

.badge-warning {
    background: #f39c12;
    color: white;
    padding: 6px 15px;
    border-radius: 15px;
    font-size: 14px;
    margin-left: 10px;
}

/* Table */
table {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 20px;
}

thead tr {
    background: #f8f9fa;
    border-bottom: 2px solid #dee2e6;
}

th {
    padding: 12px;
    text-align: left;
    font-weight: 600;
    color: #012069;
}

th:last-child {
    text-align: center;
}

tbody tr {
    border-bottom: 1px solid #dee2e6;
}

tbody tr:hover {
    background: #f8f9fa;
}

tbody tr.missing {
    background: #fff8dc;
}

td {
    padding: 12px;
}

td:last-child {
    text-align: center;
}

.critical-badge {
    color: #e74c3c;
    font-size: 11px;
    font-weight: bold;
}

/* Status */
.status-met { color: #27ae60; font-weight: bold; }
.status-not-met { color: #e74c3c; font-weight: bold; }
.status-missing { color: #f39c12; font-weight: bold; }

.not-provided {
    color: #f39c12;
    font-weight: 600;
}

.warning-text {
    color: #f39c12;
}

/* Recommendation */
.recommendation {
    background: #f8f9fa;
    padding: 20px;
    border-radius: 8px;
    border-left: 4px solid #f39c12;
}

.recommendation.success { border-left-color: #27ae60; }
.recommendation.warning { border-left-color: #e74c3c; }

.recommendation h3 {
    margin: 0 0 10px 0;
    color: #012069;
}

.recommendation p {
    margin: 0 0 15px 0;
}

.recommendation ul {
    margin: 10px 0 0 0;
    padding-left: 20px;
}
</style>

<script>
function loadAIComparison() {
    document.getElementById('ai-btn').style.display = 'none';
    document.getElementById('loading').style.display = 'block';
    document.getElementById('initial-msg').style.display = 'none';

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
        } else {
            alert('Error: ' + (data.message || 'Failed to generate comparison'));
            document.getElementById('ai-btn').style.display = 'block';
        }
    })
    .catch(error => {
        console.error('Error:', error);
        document.getElementById('loading').style.display = 'none';
        document.getElementById('ai-btn').style.display = 'block';
        alert('Failed to load comparison. Please try again.');
    });
}

function displayResults(data) {
    const results = document.getElementById('results');
    results.style.display = 'block';
    results.innerHTML = '';

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
