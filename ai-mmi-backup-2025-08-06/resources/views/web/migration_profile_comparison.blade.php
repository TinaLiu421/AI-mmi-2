@extends('web.common')

@section('content')
<div class="comparison-container">
    <div class="page-header">
        <h1><i class="fa fa-plane"></i> Migration Eligibility Analysis</h1>
        <p>Comprehensive analysis of your migration eligibility based on your submitted information</p>
    </div>

    <div class="analysis-section">
        <button onclick="generateComparison()" id="generate-btn" class="generate-btn">
            <i class="fa fa-magic"></i> Generate Analysis
        </button>
        <p class="hint" id="hint-text">Click the button above to get AI-powered analysis of your migration eligibility</p>

        <div id="loading" class="loading" style="display: none;">
            <div class="spinner"></div>
            <p>Analyzing your profile...</p>
        </div>
    </div>

    <div id="results"></div>
    
    <!-- Additional Quick Actions -->
    <div class="quick-actions-section" id="quick-actions" style="display: none;">
        <h3><i class="fa fa-lightbulb-o"></i> Quick Actions</h3>
        <div class="quick-actions-grid">
            <button class="quick-action-btn" onclick="askChatbot('Which migration pathways would you recommend as the best match for my work experience, qualifications, and long-term settlement goals?')">
                <i class="fa fa-road"></i>
                <span>Pathway Finder</span>
            </button>
            <button class="quick-action-btn" onclick="askChatbot('Which countries and institutions would you recommend as the strongest options for someone with my background?')">
                <i class="fa fa-globe"></i>
                <span>Country Comparison</span>
            </button>
            <button class="quick-action-btn" onclick="askChatbot('What are the estimated total costs (visa fees, medical exams, skills assessment, documentation, relocation expenses, etc.)?')">
                <i class="fa fa-money"></i>
                <span>Cost Estimates</span>
            </button>
            <button class="quick-action-btn" onclick="askChatbot('Could you please outline the key eligibility requirements and the step-by-step application process for each recommended pathway?')">
                <i class="fa fa-list-ol"></i>
                <span>Application Plan</span>
            </button>
            <button class="quick-action-btn" onclick="askChatbot('What skills assessment, language tests, or other certifications do I need to complete for my recommended pathways?')">
                <i class="fa fa-certificate"></i>
                <span>Requirements</span>
            </button>
            <button class="quick-action-btn" onclick="askChatbot('What is the expected timeline from application to decision, and what are the key deadlines I need to be aware of? Please make a timeline for me.')">
                <i class="fa fa-calendar"></i>
                <span>Timeline & Actions</span>
            </button>
        </div>
    </div>
</div>

<script>
function askChatbot(message) {
    const $chatInput = $('#ask_question');
    if ($chatInput.length) {
        // Set the message
        $chatInput.val(message);
        
        // Show mobile chat if on mobile
        if (window.innerWidth <= 768) {
            const mobileChat = document.querySelector('.mobile-chatbox');
            if (mobileChat) {
                mobileChat.classList.add('active');
            }
        }
        
        // Focus and scroll to chat
        $chatInput.focus();
        $chatInput[0].scrollIntoView({ behavior: 'smooth', block: 'center' });
        
        // Submit the form to send the message
        setTimeout(function() {
            $('#ask-form').submit();
        }, 300);
    }
}

@if(isset($_page_data['auto_generate']) && $_page_data['auto_generate'])
<script>
// Immediately invoke auto-generate when page loads
window.addEventListener('load', function() {
    console.log('Auto-generate triggered - recent assessment detected');
    
    // Small delay to ensure all CSS is fully loaded and applied
    setTimeout(function() {
        var btn = document.getElementById('generate-btn');
        var hint = document.getElementById('hint-text');
        if(btn) btn.style.display = 'none';
        if(hint) hint.style.display = 'none';
        
        // Trigger the analysis
        generateComparison();
    }, 250);
});
</script>
@endif

<style>
.comparison-container {
    max-width: 1400px;
    margin: 2rem auto;
    padding: 2rem;
}

.page-header {
    text-align: center;
    margin-bottom: 3rem;
}

.page-header h1 {
    color: #1a237e;
    font-size: 2rem;
    margin-bottom: 1rem;
}

.page-header p {
    color: #666;
}

.analysis-section {
    background: white;
    padding: 2rem;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    margin-bottom: 3rem;
    text-align: center;
}

.generate-btn {
    padding: 12px 32px;
    background: #1a237e;
    color: white;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    font-weight: 600;
    font-size: 1rem;
    transition: all 0.3s ease;
}

.generate-btn:hover {
    background: #0d47a1;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(26, 35, 126, 0.3);
}

.generate-btn:disabled {
    opacity: 0.6;
    cursor: not-allowed;
    transform: none;
}

.hint {
    color: #666;
    margin-top: 1rem;
    font-size: 0.875rem;
}

.quick-actions-section {
    background: white;
    padding: 2rem;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    margin-bottom: 2rem;
}

.quick-actions-section h3 {
    color: #1a237e;
    margin-bottom: 1.5rem;
    text-align: center;
}

.quick-actions-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1rem;
}

.quick-action-btn {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border: none;
    padding: 1.2rem;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.3s ease;
    text-align: center;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.5rem;
    font-weight: 500;
}

.quick-action-btn:hover {
    transform: translateY(-4px);
    box-shadow: 0 8px 16px rgba(102, 126, 234, 0.3);
}

.quick-action-btn i {
    font-size: 1.8rem;
}

.quick-action-btn span {
    font-size: 0.95rem;
}

@media (max-width: 768px) {
    .quick-actions-grid {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .quick-action-btn {
        padding: 1rem;
    }
    
    .quick-action-btn i {
        font-size: 1.5rem;
    }
    
    .quick-action-btn span {
        font-size: 0.85rem;
    }
}

.loading {
    margin-top: 2rem;
}

.spinner {
    border: 3px solid #f3f3f3;
    border-top: 3px solid #1a237e;
    border-radius: 50%;
    width: 40px;
    height: 40px;
    animation: spin 1s linear infinite;
    margin: 0 auto 1rem;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

.results-content {
    background: white;
    padding: 2rem;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.visa-card {
    background: #f8f9fa;
    padding: 1.5rem;
    border-radius: 8px;
    margin-bottom: 1.5rem;
    border-left: 4px solid #1a237e;
}

.visa-card h3 {
    color: #1a237e;
    margin-bottom: 1rem;
}

.eligibility-badge {
    display: inline-block;
    padding: 0.5rem 1rem;
    border-radius: 20px;
    font-weight: 600;
    margin-bottom: 1rem;
}

.badge-high {
    background: #4caf50;
    color: white;
}

.badge-medium {
    background: #ff9800;
    color: white;
}

.badge-low {
    background: #f44336;
    color: white;
}

.points-section {
    background: white;
    padding: 1rem;
    border-radius: 6px;
    margin: 1rem 0;
}

.points-item {
    display: flex;
    justify-content: space-between;
    padding: 0.5rem;
    border-bottom: 1px solid #e0e0e0;
}

.points-item:last-child {
    border-bottom: none;
}

.requirement-item {
    padding: 0.75rem;
    margin: 0.5rem 0;
    background: white;
    border-radius: 6px;
    border-left: 3px solid #e0e0e0;
}

.requirement-item.met {
    border-left-color: #4caf50;
}

.requirement-item.not-met {
    border-left-color: #f44336;
}

.requirement-item.partial {
    border-left-color: #ff9800;
}
</style>

<script>
function generateComparison() {
    const btn = document.getElementById('generate-btn');
    const loading = document.getElementById('loading');
    const results = document.getElementById('results');
    
    btn.disabled = true;
    loading.style.display = 'block';
    results.innerHTML = '';
    
    fetch('/<?php echo $_current_lang_code; ?>/migration_profile_comparison/get_ai_comparison', {
        method: 'GET',
        headers: {
            'Content-Type': 'application/json'
        }
    })
    .then(response => {
        console.log('Response status:', response.status);
        return response.json();
    })
    .then(data => {
        console.log('Response data:', data);
        loading.style.display = 'none';
        btn.disabled = false;
        
        if (data.status === 200) {
            displayResults(data.comparison);
        } else {
            results.innerHTML = `<div class="alert alert-danger">${data.message || 'Unknown error occurred'}</div>`;
        }
    })
    .catch(error => {
        loading.style.display = 'none';
        btn.disabled = false;
        results.innerHTML = '<div class="alert alert-danger">An error occurred. Please try again. Check console for details.</div>';
        console.error('Error:', error);
        console.error('Error:', error);
    });
}

function displayResults(comparison) {
    const results = document.getElementById('results');
    
    // Try to parse JSON from the response
    let jsonData;
    try {
        // First, try to extract JSON from markdown code blocks
        const jsonMatch = comparison.match(/```json\n([\s\S]*?)\n```/);
        if (jsonMatch) {
            jsonData = JSON.parse(jsonMatch[1]);
        } else {
            // Try to parse the response directly as JSON
            jsonData = JSON.parse(comparison);
        }
    } catch (e) {
        // If parsing fails, display as plain text
        console.error('JSON parse error:', e);
        results.innerHTML = `<div class="results-content"><pre>${comparison}</pre></div>`;
        return;
    }
    
    let html = '<div class="results-content">';
    
    // Display visa options
    if (jsonData.visa_options) {
        jsonData.visa_options.forEach(visa => {
            const badgeClass = visa.overall_eligibility === 'High' ? 'badge-high' : 
                              visa.overall_eligibility === 'Medium' ? 'badge-medium' : 'badge-low';
            
            html += `<div class="visa-card">`;
            html += `<h3><i class="fa fa-flag"></i> ${visa.country} - ${visa.visa_type}</h3>`;
            html += `<span class="eligibility-badge ${badgeClass}">${visa.overall_eligibility} Eligibility (${visa.eligibility_score}/100)</span>`;
            
            // Display points breakdown if available
            if (visa.points_breakdown) {
                html += `<div class="points-section">`;
                html += `<h4>Points Breakdown:</h4>`;
                Object.entries(visa.points_breakdown).forEach(([key, value]) => {
                    html += `<div class="points-item">`;
                    html += `<span>${key.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase())}: ${value.points}/${value.max}</span>`;
                    if (value.notes) html += `<small>${value.notes}</small>`;
                    html += `</div>`;
                });
                html += `<div class="points-item" style="font-weight: bold; font-size: 1.1rem;">`;
                html += `<span>Total Points: ${visa.total_points}</span>`;
                html += `<span>Required: ${visa.minimum_points_required}</span>`;
                html += `</div>`;
                html += `</div>`;
            }
            
            // Display requirements
            if (visa.requirements) {
                html += `<h4 style="margin-top: 1.5rem;">Requirements Assessment:</h4>`;
                visa.requirements.forEach(req => {
                    const statusClass = req.status.includes('✓') ? 'met' : 
                                       req.status.includes('✗') ? 'not-met' : 'partial';
                    html += `<div class="requirement-item ${statusClass}">`;
                    html += `<strong>${req.requirement_name}:</strong> `;
                    html += `Required: ${req.required_value}, Your Value: ${req.user_value} `;
                    html += `<span>${req.status}</span>`;
                    if (req.notes) html += `<br><small>${req.notes}</small>`;
                    html += `</div>`;
                });
            }
            
            // Display strengths and challenges
            if (visa.strengths && visa.strengths.length) {
                html += `<h4 style="margin-top: 1.5rem; color: #4caf50;">Strengths:</h4><ul>`;
                visa.strengths.forEach(s => html += `<li>${s}</li>`);
                html += `</ul>`;
            }
            
            if (visa.challenges && visa.challenges.length) {
                html += `<h4 style="color: #f44336;">Challenges:</h4><ul>`;
                visa.challenges.forEach(c => html += `<li>${c}</li>`);
                html += `</ul>`;
            }
            
            if (visa.recommendations && visa.recommendations.length) {
                html += `<h4>Recommendations:</h4><ul>`;
                visa.recommendations.forEach(r => html += `<li>${r}</li>`);
                html += `</ul>`;
            }
            
            html += `<p><strong>Processing Time:</strong> ${visa.estimated_processing_time || 'N/A'}</p>`;
            html += `<p><strong>Estimated Cost:</strong> ${visa.estimated_cost || 'N/A'}</p>`;
            html += `</div>`;
        });
    }
    
    // Overall recommendation
    if (jsonData.overall_recommendation) {
        html += `<div style="background: #e3f2fd; padding: 1.5rem; border-radius: 8px; margin-top: 2rem;">`;
        html += `<h3><i class="fa fa-lightbulb-o"></i> Overall Recommendation</h3>`;
        html += `<p>${jsonData.overall_recommendation}</p>`;
        html += `</div>`;
    }
    
    // Priority actions
    if (jsonData.priority_actions && jsonData.priority_actions.length) {
        html += `<div style="background: #fff3e0; padding: 1.5rem; border-radius: 8px; margin-top: 1rem;">`;
        html += `<h3><i class="fa fa-exclamation-triangle"></i> Priority Actions</h3><ul>`;
        jsonData.priority_actions.forEach(action => html += `<li>${action}</li>`);
        html += `</ul></div>`;
    }
    
    // Next steps
    if (jsonData.next_steps && jsonData.next_steps.length) {
        html += `<div style="background: #f1f8e9; padding: 1.5rem; border-radius: 8px; margin-top: 1rem;">`;
        html += `<h3><i class="fa fa-check-square-o"></i> Next Steps</h3><ol>`;
        jsonData.next_steps.forEach(step => html += `<li>${step}</li>`);
        html += `</ol></div>`;
    }
    
    html += '</div>';
    results.innerHTML = html;
    
    // Show quick actions after results are displayed
    const quickActions = document.getElementById('quick-actions');
    if (quickActions) {
        quickActions.style.display = 'block';
    }
}

@if(isset($auto_generate) && $auto_generate)
// Auto-generate flag detected - trigger immediately when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    console.log('Auto-generate: DOM ready, triggering analysis');
    generateComparison();
});
@endif
</script>
@endsection
