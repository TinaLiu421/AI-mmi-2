@extends('web.common')

@section('content')
<div class="comparison-container">
    <div class="page-header">
        <h1><i class="fa fa-passport"></i> Visa Profile Comparison</h1>
        <p>Check your eligibility against visa requirements</p>
    </div>

    <div class="selection-section">
        <div class="select-group">
            <label for="country-select">Select Country:</label>
            <select id="country-select" onchange="loadVisaTypes()">
                <option value="">-- Choose a country --</option>
            </select>
        </div>

        <div class="select-group">
            <label for="visa-select">Select Visa Type:</label>
            <select id="visa-select">
                <option value="">-- Choose a visa type --</option>
            </select>
        </div>

        <button onclick="loadProfileAndRequirements()" class="load-btn">
            <i class="fa fa-search"></i> Compare Profile
        </button>
    </div>

    <div id="loading" class="loading" style="display: none;">
        <div class="spinner"></div>
        <p>Loading profile and requirements...</p>
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

.selection-section {
    background: var(--white);
    padding: var(--space-6);
    border-radius: var(--space-3);
    box-shadow: var(--shadow-md);
    margin-bottom: var(--space-8);
    display: flex;
    gap: var(--space-4);
    flex-wrap: wrap;
    align-items: flex-end;
}

.select-group {
    flex: 1;
    min-width: 200px;
}

.select-group label {
    display: block;
    margin-bottom: var(--space-2);
    font-weight: 600;
    color: var(--primary-blue-dark);
}

.select-group select {
    width: 100%;
    padding: var(--space-2) var(--space-3);
    border: 2px solid var(--neutral-300);
    border-radius: var(--space-2);
    font-size: 1rem;
    cursor: pointer;
    transition: border-color 0.3s;
}

.select-group select:focus {
    outline: none;
    border-color: var(--primary-blue-dark);
}

.load-btn {
    padding: var(--space-2) var(--space-6);
    background: var(--primary-blue-dark);
    color: white;
    border: none;
    border-radius: var(--space-2);
    font-size: 1rem;
    font-weight: 600;
    cursor: pointer;
    transition: background 0.3s;
}

.load-btn:hover {
    background: var(--primary-blue);
}

.load-btn i {
    margin-right: var(--space-2);
}

.loading {
    text-align: center;
    padding: var(--space-8);
}

.spinner {
    display: inline-block;
    width: 40px;
    height: 40px;
    border: 4px solid var(--neutral-200);
    border-top-color: var(--primary-blue-dark);
    border-radius: 50%;
    animation: spin 0.8s linear infinite;
    margin-bottom: var(--space-4);
}

@keyframes spin {
    to { transform: rotate(360deg); }
}

/* Comparison card */
.comparison-card {
    background: var(--white);
    border-radius: var(--space-3);
    box-shadow: var(--shadow-md);
    overflow: hidden;
}

.card-header {
    background: var(--primary-blue-dark);
    color: white;
    padding: var(--space-4);
    display: flex;
    align-items: center;
    gap: var(--space-3);
}

.card-header h2 {
    margin: 0;
    font-size: 1.3rem;
}

.card-header i {
    font-size: 1.5rem;
}

.card-body {
    padding: var(--space-4);
}

/* Comparison table */
.comparison-table {
    width: 100%;
    border-collapse: collapse;
}

.comparison-table thead {
    background: var(--neutral-100);
    border-bottom: 2px solid var(--neutral-300);
}

.comparison-table th {
    padding: var(--space-3);
    text-align: left;
    font-weight: 600;
    color: var(--primary-blue-dark);
    font-size: 0.95rem;
}

.comparison-table td {
    padding: var(--space-3);
    border-bottom: 1px solid var(--neutral-200);
}

.comparison-table tbody tr:hover {
    background: var(--neutral-50);
}

.requirement-col {
    font-weight: 600;
    color: var(--primary-blue-dark);
    width: 35%;
}

.description-col {
    color: var(--neutral-700);
    font-size: 0.9rem;
    width: 35%;
}

.status-col {
    text-align: center;
    width: 15%;
    font-weight: 600;
}

.profile-col {
    color: var(--neutral-700);
    font-size: 0.9rem;
    width: 15%;
}

.status-badge {
    display: inline-block;
    padding: var(--space-1) var(--space-3);
    border-radius: var(--space-2);
    font-size: 0.85rem;
    font-weight: 600;
    text-transform: uppercase;
}

.status-met {
    background: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
}

.status-not-met {
    background: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
}

.status-missing {
    background: #fff3cd;
    color: #856404;
    border: 1px solid #ffeeba;
}

.status-icon {
    margin-right: var(--space-1);
}

.no-data {
    text-align: center;
    color: var(--neutral-500);
    padding: var(--space-6);
}

.error-message {
    background: var(--error-light, #fee);
    border: 1px solid var(--error-dark, #c33);
    color: var(--error-dark);
    padding: var(--space-4);
    border-radius: var(--space-2);
    margin: var(--space-4) 0;
}

@media (max-width: 768px) {
    .comparison-grid {
        grid-template-columns: 1fr;
    }

    .selection-section {
        flex-direction: column;
    }

    .select-group {
        width: 100%;
    }
}
</style>

<script>
let visaSelectionOptions = {};
let userProfile = {};

document.addEventListener('DOMContentLoaded', function() {
    // Load user profile first, then load countries and options
    extractUserProfile().then(() => {
        loadCountries();
    });
});

function loadCountries() {
    fetch(_page_base_url + '/profile_comparison/get_visa_options', {
        method: 'GET',
        headers: { 'Content-Type': 'application/json' }
    })
    .then(res => res.json())
    .then(data => {
        if (data.status === 200 && data.visa_options) {
            visaSelectionOptions = data.visa_options;
            populateCountries();

            // Auto-trigger comparison if profile has both country and visa type
            if (userProfile.interested_country && userProfile.interested_visa_type) {
                setTimeout(() => {
                    loadProfileAndRequirements();
                }, 500); // Small delay to ensure UI is fully rendered
            }
        }
    })
    .catch(error => console.error('Error loading countries:', error));
}

function populateCountries() {
    const countrySelect = document.getElementById('country-select');
    const countries = visaSelectionOptions.countries || [];

    countries.forEach(country => {
        const option = document.createElement('option');
        option.value = country;
        option.textContent = country;
        countrySelect.appendChild(option);
    });

    // Auto-select user's interested country if available
    if (userProfile.interested_country) {
        countrySelect.value = userProfile.interested_country;
        // Trigger visa type loading for this country
        loadVisaTypes();
    }
}

function loadVisaTypes() {
    const countrySelect = document.getElementById('country-select');
    const visaSelect = document.getElementById('visa-select');
    const selectedCountry = countrySelect.value;

    visaSelect.innerHTML = '<option value="">-- Choose a visa type --</option>';

    if (!selectedCountry) return;

    const visaTypes = visaSelectionOptions.visa_types_by_country[selectedCountry] || {};

    Object.keys(visaTypes).forEach(visaName => {
        const option = document.createElement('option');
        option.value = visaName;
        option.textContent = visaName;
        visaSelect.appendChild(option);
    });

    // Auto-select user's interested visa type if available and matches current country
    if (userProfile.interested_visa_type && Object.keys(visaTypes).includes(userProfile.interested_visa_type)) {
        visaSelect.value = userProfile.interested_visa_type;
    }
}

function extractUserProfile() {
    // Extract user information from chat logs or stored data
    // Returns a promise so we can chain operations
    return fetch(_page_base_url + '/profile_comparison/get_user_profile', {
        method: 'GET',
        headers: { 'Content-Type': 'application/json' }
    })
    .then(res => res.json())
    .then(data => {
        if (data.status === 200 && data.profile) {
            userProfile = data.profile;
            console.log('Loaded user profile:', userProfile);
        }
    })
    .catch(error => {
        console.warn('Could not load user profile:', error);
        userProfile = {};
    });
}

function loadProfileAndRequirements() {
    const country = document.getElementById('country-select').value;
    const visaType = document.getElementById('visa-select').value;
    const loadBtn = document.querySelector('.load-btn');

    if (!country || !visaType) {
        alert('Please select both country and visa type');
        return;
    }

    // Disable button while loading
    loadBtn.disabled = true;
    loadBtn.style.opacity = '0.6';
    loadBtn.style.cursor = 'not-allowed';

    document.getElementById('loading').style.display = 'block';
    document.getElementById('results').innerHTML = '';

    fetch(_page_base_url + '/profile_comparison/get_ai_comparison?country=' + encodeURIComponent(country) + '&visa_type=' + encodeURIComponent(visaType), {
        method: 'GET',
        headers: { 'Content-Type': 'application/json' }
    })
    .then(res => res.json())
    .then(data => {
        document.getElementById('loading').style.display = 'none';

        // Re-enable button after loading
        loadBtn.disabled = false;
        loadBtn.style.opacity = '1';
        loadBtn.style.cursor = 'pointer';

        if (data.status === 200 && data.found) {
            displayComparison(data, country, visaType);
        } else {
            document.getElementById('results').innerHTML = `
                <div class="error-message">
                    <strong>No Data Found</strong><br>
                    ${data.message || 'No requirements found for this visa type.'}
                </div>`;
        }
    })
    .catch(error => {
        document.getElementById('loading').style.display = 'none';

        // Re-enable button on error
        loadBtn.disabled = false;
        loadBtn.style.opacity = '1';
        loadBtn.style.cursor = 'pointer';

        console.error('Error:', error);
        document.getElementById('results').innerHTML = `
            <div class="error-message">
                <strong>Error</strong><br>
                Failed to load requirements. Please try again.
            </div>`;
    });
}

function displayComparison(data, country, visaType) {
    const results = document.getElementById('results');
    const requirements = data.requirements || [];

    let comparisonHtml = buildComparisonTable(requirements);

    results.innerHTML = `
        <div class="comparison-card">
            <div class="card-header">
                <i class="fa fa-balance-scale"></i>
                <h2>Eligibility Check: ${country} - ${visaType}</h2>
            </div>
            <div class="card-body">
                ${comparisonHtml}
            </div>
        </div>
    `;
}

function buildComparisonTable(requirements) {
    const profile = userProfile || {};

    if (requirements.length === 0) {
        return `<div class="no-data">No requirements found</div>`;
    }

    let html = `
        <table class="comparison-table">
            <thead>
                <tr>
                    <th class="requirement-col">Requirement</th>
                    <th class="description-col">Description</th>
                    <th class="profile-col">Your Profile</th>
                    <th class="status-col">Status</th>
                </tr>
            </thead>
            <tbody>`;

    requirements.forEach(req => {
        const status = evaluateRequirementMatch(req, profile);
        const statusBadge = getStatusBadge(status);

        html += `
            <tr>
                <td class="requirement-col">${req.name || req.requirement || '-'}</td>
                <td class="description-col">${req.description || '-'}</td>
                <td class="profile-col">${status.profileValue}</td>
                <td class="status-col">${statusBadge}</td>
            </tr>`;
    });

    html += `</tbody></table>`;
    return html;
}

function evaluateRequirementMatch(requirement, profile) {
    const reqName = (requirement.requirement || '').toLowerCase();
    const description = (requirement.description || '').toLowerCase();
    const fullText = reqName + ' ' + description;

    // Check for degree/education requirements
    if (reqName.includes('degree') || reqName.includes('qualification') || reqName.includes('education')) {
        if (profile.education) {
            return {
                status: 'met',
                profileValue: profile.education
            };
        }
        return {
            status: 'missing',
            profileValue: 'Not provided'
        };
    }

    // Check for English/language requirements
    if (reqName.includes('english') || reqName.includes('language') || reqName.includes('ielts') || reqName.includes('toefl') || reqName.includes('pte')) {
        if (profile.ielts_score) {
            return {
                status: 'met',
                profileValue: profile.ielts_score
            };
        } else if (profile.english_level) {
            return {
                status: 'met',
                profileValue: profile.english_level
            };
        }
        return {
            status: 'missing',
            profileValue: 'Not provided'
        };
    }

    // Check for work experience (must have "experience" or specific year requirement)
    if (reqName.includes('experience') || (reqName.includes('years') && !reqName.includes('age'))) {
        if (profile.experience) {
            return {
                status: 'met',
                profileValue: profile.experience
            };
        }
        return {
            status: 'missing',
            profileValue: 'Not provided'
        };
    }

    // Check for occupation/skills ONLY if we have the user's occupation
    if ((reqName.includes('occupation') || reqName.includes('skills') || reqName.includes('skill')) && profile.occupation) {
        return {
            status: 'met',
            profileValue: profile.occupation
        };
    }

    // For other occupation-related requirements without user data
    if (reqName.includes('occupation') || reqName.includes('skills') || reqName.includes('skill') || (reqName.includes('have') && (reqName.includes('qualification') || reqName.includes('skilled')))) {
        return {
            status: 'missing',
            profileValue: 'Not provided'
        };
    }

    // Check for age requirements (must explicitly mention age limit/maximum/minimum)
    if ((reqName.includes('age') || fullText.includes('maximum age') || fullText.includes('minimum age') || fullText.includes('under') || fullText.includes('above')) && !reqName.includes('check')) {
        // Only mark as "met" if we can actually verify the age meets the requirement
        // Without specific age limits in the requirement, mark as missing
        if (profile.age) {
            // Extract age limit from description if available
            const ageMatch = description.match(/(\d+)\s*(?:years?|yrs?)/);
            if (ageMatch) {
                const maxAge = parseInt(ageMatch[1]);
                const userAge = parseInt(profile.age);
                if (userAge <= maxAge) {
                    return {
                        status: 'met',
                        profileValue: profile.age + ' years'
                    };
                } else {
                    return {
                        status: 'not-met',
                        profileValue: profile.age + ' years (exceeds limit)'
                    };
                }
            }
            // If no specific limit found, mark as missing info
            return {
                status: 'missing',
                profileValue: profile.age + ' years (age limit unclear)'
            };
        }
        return {
            status: 'missing',
            profileValue: 'Not provided'
        };
    }

    // Check for financial/income requirements
    if (fullText.includes('financial') || fullText.includes('income') || fullText.includes('sponsor') || fullText.includes('funds')) {
        return {
            status: 'missing',
            profileValue: 'Need manual check'
        };
    }

    // Check for character/health requirements
    if (fullText.includes('character') || fullText.includes('health') || fullText.includes('medical')) {
        return {
            status: 'missing',
            profileValue: 'Need manual check'
        };
    }

    // Default: information not available
    return {
        status: 'missing',
        profileValue: 'Not provided'
    };
}

function getStatusBadge(result) {
    const statusClass = `status-${result.status}`;
    const icon = result.status === 'met' ? '✓' : result.status === 'missing' ? '?' : '✗';
    const text = result.status === 'met' ? 'Met' : result.status === 'missing' ? 'Missing' : 'Not Met';

    return `<span class="status-badge ${statusClass}"><i class="status-icon">${icon}</i>${text}</span>`;
}
</script>
@endsection
