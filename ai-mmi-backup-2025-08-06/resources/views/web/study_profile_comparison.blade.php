@extends('web.common')

@section('content')
<div class="comparison-container">
    <div class="page-header">
        <h1><i class="fa fa-graduation-cap"></i> Study Abroad Eligibility Analysis</h1>
        <p>Comprehensive analysis of your study abroad eligibility based on your submitted information</p>
    </div>

    <div class="chat-notice" role="status" aria-live="polite">
        <div class="chat-notice-title">IMPORTANT: AI answers appear in the RIGHT CHAT PANEL</div>
        <div class="chat-notice-body">Look to the right panel now. On mobile, tap the Chat button to view answers.</div>
    </div>

    <div class="analysis-section">
        <button onclick="generateComparison()" id="generate-btn" class="generate-btn">
            <i class="fa fa-magic"></i> Generate Analysis
        </button>
        <p class="hint" id="hint-text">Click the button above to get AI-powered analysis of your study abroad eligibility</p>

        <div id="loading" class="loading" style="display: none;">
            <div class="spinner"></div>
            <p>Analyzing your profile...</p>
        </div>
    </div>

    <div id="results"></div>
    
    <!-- Additional Quick Actions -->
    <div class="quick-actions-section" id="quick-actions" style="display: none;">
        <h3><i class="fa fa-lightbulb-o"></i> Quick Actions</h3>
        <div id="chat-guidance" class="chat-guidance" role="status" aria-live="polite">
            Answers will appear in the right chat panel ->
        </div>
        <div class="quick-actions-grid">
            <div class="quick-action-card">
                <div class="quick-action-header">
                    <i class="fa fa-globe"></i>
                    <div>
                        <div class="quick-action-title">Country Comparison</div>
                        <div class="quick-action-subtitle">Where to go?</div>
                        <div class="quick-action-note">Answer appears in chat -></div>
                    </div>
                </div>
                <form class="quick-form" data-question="Which countries and institutions would you recommend as the strongest options for someone with my background?" onsubmit="submitQuickForm(event)">
                    <div class="form-row">
                        <input type="text" data-label="Nationality" placeholder="Nationality">
                        <input type="text" data-label="Current country" placeholder="Current country">
                    </div>
                    <div class="form-row">
                        <input type="text" data-label="Preferred countries" placeholder="Preferred countries (optional)">
                        <input type="text" data-label="Study level" placeholder="Study level (e.g. Bachelor, Master)">
                    </div>
                    <div class="form-row">
                        <input type="text" data-label="Field of study" placeholder="Field of study">
                        <input type="text" data-label="Budget range" placeholder="Budget range">
                    </div>
                    <textarea data-label="Academic background" placeholder="Academic background (school, GPA, major)"></textarea>
                    <button type="submit" class="quick-action-submit">Ask AI</button>
                </form>
            </div>

            <div class="quick-action-card">
                <div class="quick-action-header">
                    <i class="fa fa-book"></i>
                    <div>
                        <div class="quick-action-title">Program Finder</div>
                        <div class="quick-action-subtitle">What to study?</div>
                        <div class="quick-action-note">Answer appears in chat -></div>
                    </div>
                </div>
                <form class="quick-form" data-question="Which programs, fields of study, or courses would you recommend as the best match for my academic history, career aspirations, and long-term objectives?" onsubmit="submitQuickForm(event)">
                    <div class="form-row">
                        <input type="text" data-label="Academic background" placeholder="Academic background">
                        <input type="text" data-label="Career goal" placeholder="Career goal">
                    </div>
                    <div class="form-row">
                        <input type="text" data-label="Interests" placeholder="Interests">
                        <input type="text" data-label="Study level" placeholder="Study level">
                    </div>
                    <div class="form-row">
                        <input type="text" data-label="Preferred countries" placeholder="Preferred countries">
                        <input type="text" data-label="Budget range" placeholder="Budget range">
                    </div>
                    <textarea data-label="Long-term objectives" placeholder="Long-term objectives"></textarea>
                    <button type="submit" class="quick-action-submit">Ask AI</button>
                </form>
            </div>

            <div class="quick-action-card">
                <div class="quick-action-header">
                    <i class="fa fa-money"></i>
                    <div>
                        <div class="quick-action-title">Cost Estimates</div>
                        <div class="quick-action-subtitle">How much?</div>
                        <div class="quick-action-note">Answer appears in chat -></div>
                    </div>
                </div>
                <form class="quick-form" data-question="What are the estimated total costs (tuition, living expenses, visa/application fees, etc.)?" onsubmit="submitQuickForm(event)">
                    <div class="form-row">
                        <input type="text" data-label="Destination countries" placeholder="Destination countries">
                        <input type="text" data-label="Study level" placeholder="Study level">
                    </div>
                    <div class="form-row">
                        <input type="text" data-label="Program length" placeholder="Program length (e.g. 2 years)">
                        <input type="text" data-label="Accommodation preference" placeholder="Accommodation preference">
                    </div>
                    <div class="form-row">
                        <input type="text" data-label="Budget range" placeholder="Budget range">
                        <input type="text" data-label="Dependents" placeholder="Dependents (if any)">
                    </div>
                    <textarea data-label="Additional notes" placeholder="Additional notes"></textarea>
                    <button type="submit" class="quick-action-submit">Ask AI</button>
                </form>
            </div>

            <div class="quick-action-card">
                <div class="quick-action-header">
                    <i class="fa fa-list-ol"></i>
                    <div>
                        <div class="quick-action-title">Admission Plan</div>
                        <div class="quick-action-subtitle">How to get in?</div>
                        <div class="quick-action-note">Answer appears in chat -></div>
                    </div>
                </div>
                <form class="quick-form" data-question="Could you please outline the key admission and visa requirements, along with the step-by-step application process?" onsubmit="submitQuickForm(event)">
                    <div class="form-row">
                        <input type="text" data-label="Target country" placeholder="Target country">
                        <input type="text" data-label="Study level" placeholder="Study level">
                    </div>
                    <div class="form-row">
                        <input type="text" data-label="English test status" placeholder="English test status (IELTS/TOEFL etc.)">
                        <input type="text" data-label="GPA/Grades" placeholder="GPA/Grades">
                    </div>
                    <div class="form-row">
                        <input type="text" data-label="Intake" placeholder="Intake (e.g. Feb/Jul)">
                        <input type="text" data-label="Timeline" placeholder="Target application timeline">
                    </div>
                    <textarea data-label="Academic background" placeholder="Academic background"></textarea>
                    <button type="submit" class="quick-action-submit">Ask AI</button>
                </form>
            </div>

            <div class="quick-action-card">
                <div class="quick-action-header">
                    <i class="fa fa-graduation-cap"></i>
                    <div>
                        <div class="quick-action-title">Scholarship Search</div>
                        <div class="quick-action-subtitle">Scholarship?</div>
                        <div class="quick-action-note">Answer appears in chat -></div>
                    </div>
                </div>
                <form class="quick-form" data-question="What scholarships, bursaries, grants, or other forms of financial assistance are available for international students in my situation, and what are the eligibility requirements and application deadlines?" onsubmit="submitQuickForm(event)">
                    <div class="form-row">
                        <input type="text" data-label="Nationality" placeholder="Nationality">
                        <input type="text" data-label="Target country" placeholder="Target country">
                    </div>
                    <div class="form-row">
                        <input type="text" data-label="Study level" placeholder="Study level">
                        <input type="text" data-label="GPA/Grades" placeholder="GPA/Grades">
                    </div>
                    <div class="form-row">
                        <input type="text" data-label="Field of study" placeholder="Field of study">
                        <input type="text" data-label="Financial need" placeholder="Financial need (yes/no)">
                    </div>
                    <textarea data-label="Achievements" placeholder="Achievements / awards / extracurricular"></textarea>
                    <button type="submit" class="quick-action-submit">Ask AI</button>
                </form>
            </div>

            <div class="quick-action-card">
                <div class="quick-action-header">
                    <i class="fa fa-calendar"></i>
                    <div>
                        <div class="quick-action-title">Timeline & Actions</div>
                        <div class="quick-action-subtitle">When to start?</div>
                        <div class="quick-action-note">Answer appears in chat -></div>
                    </div>
                </div>
                <form class="quick-form" data-question="What are the available intake periods / commencement dates for the recommended programs, and what are the corresponding application submission deadlines? Please make a timeline for me." onsubmit="submitQuickForm(event)">
                    <div class="form-row">
                        <input type="text" data-label="Target country" placeholder="Target country">
                        <input type="text" data-label="Preferred intake" placeholder="Preferred intake">
                    </div>
                    <div class="form-row">
                        <input type="text" data-label="Study level" placeholder="Study level">
                        <input type="text" data-label="Program length" placeholder="Program length">
                    </div>
                    <div class="form-row">
                        <input type="text" data-label="English test date" placeholder="English test date (if any)">
                        <input type="text" data-label="Current stage" placeholder="Current stage (researching, applied, etc.)">
                    </div>
                    <textarea data-label="Notes" placeholder="Notes / constraints"></textarea>
                    <button type="submit" class="quick-action-submit">Ask AI</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function submitQuickForm(event) {
    event.preventDefault();
    const form = event.target;
    const baseQuestion = form.getAttribute('data-question') || '';
    const fields = form.querySelectorAll('[data-label]');
    const details = [];

    fields.forEach((field) => {
        const value = (field.value || '').trim();
        if (value) {
            details.push(`${field.getAttribute('data-label')}: ${value}`);
        }
    });

    const detailText = details.length ? `\n\nProfile details:\n- ${details.join('\n- ')}` : '';
    const fullPrompt = `${baseQuestion}${detailText}`;
    askChatbot(fullPrompt);
}

function showChatGuidance() {
    const guidance = document.getElementById('chat-guidance');
    if (guidance) {
        guidance.classList.add('show');
        setTimeout(() => guidance.classList.remove('show'), 5000);
    }

    const chatArea = document.querySelector('.chat-area');
    if (chatArea) {
        chatArea.classList.add('chat-attention');
        setTimeout(() => chatArea.classList.remove('chat-attention'), 5000);
    }
}

function askChatbot(message) {
    const $chatInput = $('#ask_question');
    if ($chatInput.length) {
        showChatGuidance();
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

console.log('Auto-generate variable:', <?php echo json_encode(isset($_page_data['auto_generate']) ? $_page_data['auto_generate'] : false); ?>);
</script>

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

.chat-notice {
    display: flex;
    flex-direction: column;
    gap: 8px;
    margin: 0 auto 2rem;
    max-width: 980px;
    padding: 16px 20px;
    border: 2px solid #0f766e;
    border-radius: 14px;
    background: linear-gradient(135deg, #ecfeff 0%, #f0fdf4 100%);
    color: #0f172a;
    text-align: center;
    box-shadow: 0 8px 20px rgba(15, 118, 110, 0.15);
    animation: chatNoticePulse 2.2s ease-in-out infinite;
}

.chat-notice-title {
    font-weight: 800;
    font-size: 1.05rem;
    letter-spacing: 0.02em;
    text-transform: uppercase;
    color: #0f766e;
}

.chat-notice-body {
    font-size: 0.98rem;
}

.chat-guidance {
    margin: 0 auto 1.5rem;
    max-width: 720px;
    padding: 10px 14px;
    border-radius: 999px;
    border: 2px solid #f59e0b;
    background: #fffbeb;
    color: #92400e;
    font-weight: 700;
    text-align: center;
    opacity: 0.65;
    transition: opacity 0.2s ease, transform 0.2s ease;
}

.chat-guidance.show {
    opacity: 1;
    transform: scale(1.02);
}

.chat-area.chat-attention {
    outline: 3px solid #f59e0b;
    outline-offset: -6px;
    box-shadow: 0 0 0 6px rgba(245, 158, 11, 0.25);
    animation: chatPanelPulse 1.4s ease-in-out infinite;
}

@keyframes chatNoticePulse {
    0% {
        box-shadow: 0 8px 20px rgba(15, 118, 110, 0.15);
        transform: translateY(0);
    }
    50% {
        box-shadow: 0 12px 28px rgba(15, 118, 110, 0.28);
        transform: translateY(-1px);
    }
    100% {
        box-shadow: 0 8px 20px rgba(15, 118, 110, 0.15);
        transform: translateY(0);
    }
}

@keyframes chatPanelPulse {
    0% {
        box-shadow: 0 0 0 6px rgba(245, 158, 11, 0.25);
    }
    50% {
        box-shadow: 0 0 0 10px rgba(245, 158, 11, 0.35);
    }
    100% {
        box-shadow: 0 0 0 6px rgba(245, 158, 11, 0.25);
    }
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

.quick-action-card {
    background: #ffffff;
    border: 1px solid #e5e7eb;
    border-radius: 12px;
    padding: 1.25rem;
    box-shadow: 0 4px 14px rgba(15, 23, 42, 0.08);
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.quick-action-header {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    color: #1a237e;
}

.quick-action-header i {
    font-size: 1.6rem;
}

.quick-action-title {
    font-weight: 700;
    font-size: 1rem;
}

.quick-action-subtitle {
    font-size: 0.85rem;
    color: #64748b;
}

.quick-action-note {
    margin-top: 4px;
    font-size: 0.72rem;
    font-weight: 700;
    color: #b45309;
    text-transform: uppercase;
    letter-spacing: 0.03em;
}

.quick-form {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.quick-form .form-row {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
    gap: 0.75rem;
}

.quick-form input,
.quick-form textarea {
    width: 100%;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    padding: 0.6rem 0.75rem;
    font-size: 0.9rem;
    font-family: inherit;
    background: #f8fafc;
}

.quick-form textarea {
    min-height: 90px;
    resize: vertical;
}

.quick-action-submit {
    align-self: flex-start;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: #fff;
    border: none;
    border-radius: 8px;
    padding: 0.6rem 1.5rem;
    font-weight: 600;
    cursor: pointer;
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.quick-action-submit:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 14px rgba(102, 126, 234, 0.25);
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

.country-card {
    background: #f8f9fa;
    padding: 1.5rem;
    border-radius: 8px;
    margin-bottom: 1.5rem;
    border-left: 4px solid #1a237e;
}

.country-card h3 {
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
    
    fetch('/<?php echo $_current_lang_code; ?>/study_profile_comparison/get_ai_comparison', {
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
    
    // Display country comparisons
    if (jsonData.country_comparisons) {
        jsonData.country_comparisons.forEach(country => {
            const badgeClass = country.overall_eligibility === 'High' ? 'badge-high' : 
                              country.overall_eligibility === 'Medium' ? 'badge-medium' : 'badge-low';
            
            html += `<div class="country-card">`;
            html += `<h3><i class="fa fa-globe"></i> ${country.country}</h3>`;
            html += `<span class="eligibility-badge ${badgeClass}">${country.overall_eligibility} Eligibility (${country.eligibility_score}/100)</span>`;
            
            // Display requirements
            if (country.requirements) {
                html += `<h4 style="margin-top: 1.5rem;">Requirements Assessment:</h4>`;
                country.requirements.forEach(req => {
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
            if (country.strengths && country.strengths.length) {
                html += `<h4 style="margin-top: 1.5rem; color: #4caf50;">Strengths:</h4><ul>`;
                country.strengths.forEach(s => html += `<li>${s}</li>`);
                html += `</ul>`;
            }
            
            if (country.challenges && country.challenges.length) {
                html += `<h4 style="color: #f44336;">Challenges:</h4><ul>`;
                country.challenges.forEach(c => html += `<li>${c}</li>`);
                html += `</ul>`;
            }
            
            if (country.recommendations && country.recommendations.length) {
                html += `<h4>Recommendations:</h4><ul>`;
                country.recommendations.forEach(r => html += `<li>${r}</li>`);
                html += `</ul>`;
            }
            
            html += `<p><strong>Estimated Timeline:</strong> ${country.estimated_timeline || 'N/A'}</p>`;
            html += `<p><strong>Estimated Cost:</strong> ${country.estimated_cost || 'N/A'}</p>`;
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
