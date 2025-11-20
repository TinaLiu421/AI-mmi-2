document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('course-application-form');
    if (!form) {
        return;
    }

    const statusBanner = document.getElementById('application-status');
    const saveBtn = document.getElementById('save-application');
    const submitBtn = document.getElementById('submit-application');
    const paymentWidget = document.getElementById('payment-widget');
    const paymentCard = document.getElementById('payment-card');
    const paymentConfig = paymentCard ? paymentCard.dataset : {};
    const docInputs = form.querySelectorAll('.document-field input[type="file"]');
    const dobInput = document.getElementById('date_of_birth');
    const englishTestSelect = document.getElementById('english-test-select');
    const englishTestScoresRow = document.getElementById('english-test-score-fields');

    let isSubmitting = false;
    let currentApplication = null;

    const docDefaultText = {
        passport_copy: 'No file uploaded yet.',
        education_certificate: 'No file uploaded yet.',
        english_test_result: 'No file uploaded yet.',
        financial_statement: 'No file uploaded yet.',
    };

    const requiredFieldLabels = {
        family_name: 'Family Name',
        given_name: 'Given Name',
        email_address: 'Email address',
        mobile_number: 'Mobile number',
        residential_address: 'Residential address',
        date_of_birth: 'Date of Birth',
        nationality: 'Nationality',
        highest_education: 'Highest education completed',
        target_institution: 'Target institution',
        target_program: 'Target program',
        start_year: 'Preferred start year',
    };

    const requiredDocuments = [
        { key: 'passport_copy', label: 'Copy of your passport' },
        { key: 'education_certificate', label: 'Copy of your education certificate' },
        { key: 'financial_statement', label: 'Copy of your or sponsor’s bank statement' },
    ];

    const englishTestLimits = {
        IELTS: { max: 9, step: 0.1 },
        'TOEFL iBT': { max: 120, step: 1 },
        PTE: { max: 90, step: 1 },
    };

    const formatDateForDisplay = (isoDate) => {
        if (!isoDate) {
            return '';
        }
        const match = /^(\d{4})-(\d{2})-(\d{2})$/.exec(isoDate);
        if (!match) {
            return isoDate;
        }
        return `${match[3]}/${match[2]}/${match[1]}`;
    };

    const parseDisplayDate = (value) => {
        if (!value) {
            return '';
        }
        const match = /^(\d{1,2})[\/\-](\d{1,2})[\/\-](\d{4})$/.exec(value.trim());
        if (!match) {
            return '';
        }
        const day = match[1].padStart(2, '0');
        const month = match[2].padStart(2, '0');
        const year = match[3];
        return `${year}-${month}-${day}`;
    };

    const showStatus = (type, message) => {
        if (!statusBanner) {
            return;
        }
        statusBanner.classList.remove('success', 'error', 'is-visible');
        if (!message) {
            return;
        }
        statusBanner.classList.add(type === 'success' ? 'success' : 'error', 'is-visible');
        statusBanner.innerHTML = message;
    };

    const toggleButtons = (state) => {
        [saveBtn, submitBtn].forEach((btn) => {
            if (btn) {
                btn.disabled = state;
            }
        });
    };

    const formatErrors = (errors, fallback) => {
        if (!errors) {
            return fallback;
        }
        const parts = [];
        Object.keys(errors).forEach((field) => {
            const msgs = [].concat(errors[field]);
            msgs.forEach((msg) => parts.push(msg));
        });
        return parts.length ? parts.join('<br>') : fallback;
    };

    const updateDocumentsUI = (documents = {}) => {
        document.querySelectorAll('.document-field').forEach((wrapper) => {
            const key = wrapper.getAttribute('data-doc-key');
            const statusNode = wrapper.querySelector('.doc-status');
            if (!statusNode) {
                return;
            }
            const info = documents[key];
            if (info) {
                const link = info.url ? `<a href="${info.url}" target="_blank" rel="noopener">Download</a>` : '';
                statusNode.innerHTML = `Uploaded: ${info.original_name || 'Document'} ${link}`;
            } else {
                statusNode.textContent = docDefaultText[key] || 'No file uploaded yet.';
            }
        });
    };

    const updateDocPreview = (input) => {
        const key = input.name;
        const status = document.getElementById(`${input.id}_status`);
        if (!status) {
            return;
        }
        if (input.files && input.files.length) {
            status.textContent = `Ready to upload: ${input.files[0].name}`;
        } else if (currentApplication && currentApplication.documents && currentApplication.documents[key]) {
            const info = currentApplication.documents[key];
            status.textContent = `Uploaded: ${info.original_name}`;
        } else {
            status.textContent = docDefaultText[key] || 'No file uploaded yet.';
        }
    };

    const setRadioValue = (name, value) => {
        const radios = form.querySelectorAll(`input[name="${name}"]`);
        radios.forEach((radio) => {
            radio.checked = radio.value === value;
        });
    };

    const updateEnglishTestConstraints = () => {
        if (!englishTestSelect || !englishTestScoresRow) {
            return;
        }
        const limit = englishTestLimits[englishTestSelect.value] || null;
        englishTestScoresRow.querySelectorAll('input').forEach((input) => {
            if (limit) {
                input.dataset.max = limit.max;
            } else {
                delete input.dataset.max;
            }
        });
    };

    const enforceScoreLimit = (input) => {
        if (!englishTestSelect) {
            return;
        }
        const limit = englishTestLimits[englishTestSelect.value];
        if (!limit || !limit.max) {
            return;
        }
        const value = parseFloat(input.value);
        if (Number.isNaN(value)) {
            return;
        }
        if (value > limit.max) {
            input.value = limit.max;
            showStatus('error', `Max score for ${englishTestSelect.value} is ${limit.max}.`);
        }
    };

    const isNumericScore = (value) => {
        if (value === undefined || value === null) {
            return false;
        }
        const cleaned = value.replace(/,/g, '.').trim();
        if (!cleaned) {
            return false;
        }
        return !Number.isNaN(parseFloat(cleaned));
    };

    const hydrateForm = (application) => {
        if (!application) {
            return;
        }
        currentApplication = application;
        const fields = [
            'family_name',
            'given_name',
            'email_address',
            'mobile_number',
            'residential_address',
            'date_of_birth',
            'nationality',
            'highest_education',
            'target_institution',
            'target_program',
            'start_year',
            'financial_notes',
        ];

        document.getElementById('application-id').value = application.id || '';

        fields.forEach((field) => {
            const input = form.querySelector(`[name="${field}"]`);
            if (input) {
                input.value = application[field] || '';
            }
        });

        // English tests
        const englishTest = application.english_test || {};
        if (englishTestSelect && englishTestScoresRow) {
            englishTestSelect.value = englishTest.selected || '';
            if (englishTestSelect.value) {
                englishTestScoresRow.style.display = 'grid';
                updateEnglishTestConstraints();
                const scoreFields = englishTest.scores || {};
                ['overall', 'listening', 'speaking', 'reading', 'writing'].forEach((field) => {
                    const input = form.querySelector(`[name="english_test[scores][${field}]"]`);
                    if (input) {
                        input.value = scoreFields[field] || '';
                    }
                });
            } else {
                englishTestScoresRow.style.display = 'none';
                englishTestScoresRow.querySelectorAll('input').forEach((input) => {
                    input.value = '';
                });
            }
        }

        if (dobInput) {
            dobInput.value = formatDateForDisplay(application.date_of_birth);
        }

        setRadioValue('has_english_test', application.has_english_test ? 'yes' : 'no');
        setRadioValue('has_financial_support', application.has_financial_support ? 'yes' : 'no');
        setRadioValue('wants_scholarship', application.wants_scholarship ? 'yes' : 'no');

        // Scholarships
        const selectedColleges = application.scholarship_colleges || [];
        form.querySelectorAll('input[name="scholarship_colleges[]"]').forEach((checkbox) => {
            checkbox.checked = selectedColleges.includes(checkbox.value);
        });

        syncDobInputMode();
        updateDocumentsUI(application.documents || {});
        renderPaymentWidget(application);
    };

    const renderPaymentWidget = (application) => {
        if (!paymentWidget) {
            return;
        }
        if (!application || application.status !== 'submitted') {
            paymentWidget.classList.add('is-locked');
            paymentWidget.innerHTML = `
                <div class="payment-placeholder">
                    <strong>Submit your application details first.</strong>
                    <p>The payment button appears once the form and documents are saved.</p>
                </div>
            `;
            return;
        }

        const pricingTableId = paymentConfig.pricingTableId;
        const stripeKey = paymentConfig.stripeKey;
        if (!pricingTableId || !stripeKey) {
            paymentWidget.innerHTML = '<p class="payment-placeholder">Stripe configuration missing.</p>';
            return;
        }

        paymentWidget.classList.remove('is-locked');
        paymentWidget.innerHTML = '';

        const element = document.createElement('stripe-pricing-table');
        element.setAttribute('pricing-table-id', pricingTableId);
        element.setAttribute('publishable-key', stripeKey);
        const memberRef = application.member_id || paymentConfig.memberId || '';
        const clientReference = memberRef ? `${memberRef}|APP-${application.id}` : `APP-${application.id}`;
        element.setAttribute('client-reference-id', clientReference);
        const email = application.email_address || paymentConfig.defaultEmail || '';
        if (email) {
            element.setAttribute('customer-email', email);
        }
        paymentWidget.appendChild(element);
    };

    const submitApplication = async (intent) => {
        if (isSubmitting) {
            return;
        }
        isSubmitting = true;
        toggleButtons(true);
        showStatus('success', 'Saving...');

        const formData = new FormData(form);
        if (dobInput) {
            const isoDob = parseDisplayDate(dobInput.value);
            if (isoDob) {
                formData.set('date_of_birth', isoDob);
            }
        }

        if (englishTestSelect) {
            const selectedTest = englishTestSelect.value;
            formData.set('english_test[selected]', selectedTest);
            if (selectedTest) {
                ['overall', 'listening', 'speaking', 'reading', 'writing'].forEach((field) => {
                    const input = form.querySelector(`[name="english_test[scores][${field}]"]`);
                    if (input) {
                        formData.set(`english_test[scores][${field}]`, input.value || '');
                    }
                });
            }
        }

        formData.set('intent', intent);

        try {
            const response = await fetch('/api/course-applications', {
                method: 'POST',
                body: formData,
                headers: {
                    Accept: 'application/json',
                },
                credentials: 'same-origin',
            });

            const data = await response.json();
            if (!response.ok) {
                const errMsg = formatErrors(data.errors, data.message || 'Unable to save application.');
                showStatus('error', errMsg);
                return;
            }

            hydrateForm(data.application);
            const message =
                intent === 'save'
                    ? 'Draft saved successfully.'
                    : 'Application submitted successfully! You can now complete the payment.';
            showStatus('success', message);
        } catch (error) {
            showStatus('error', 'Something went wrong. Please try again.');
        } finally {
            isSubmitting = false;
            toggleButtons(false);
        }
    };

    const fetchLatestApplication = async () => {
        try {
            const response = await fetch('/api/course-applications/latest', {
                headers: { Accept: 'application/json' },
                credentials: 'same-origin',
            });
            const data = await response.json();
            if (data.application) {
                hydrateForm(data.application);
                showStatus(
                    'success',
                    `Loaded your last ${data.application.status === 'submitted' ? 'submitted' : 'draft'} application.`
                );
            }
        } catch (error) {
            console.warn('Unable to load existing application', error);
        }
    };

    const hasDocumentUploaded = (key) => {
        const input = form.querySelector(`input[name="${key}"]`);
        const newFile = input && input.files && input.files.length > 0;
        const existing =
            currentApplication &&
            currentApplication.documents &&
            currentApplication.documents[key];
        return !!(newFile || existing);
    };

    const validateSubmission = () => {
        const missingFields = [];
        Object.keys(requiredFieldLabels).forEach((name) => {
            const input = form.querySelector(`[name="${name}"]`);
            if (!input) {
                return;
            }
            const value = input.value ? input.value.trim() : '';
            if (!value) {
                missingFields.push(requiredFieldLabels[name]);
            }
        });

        if (dobInput) {
            const dobValue = dobInput.value.trim();
            if (dobValue && !parseDisplayDate(dobValue)) {
                showStatus('error', 'Please enter Date of Birth in dd/mm/yyyy format.');
                return false;
            }
        }

        if (englishTestSelect && englishTestSelect.value) {
            const limit = englishTestLimits[englishTestSelect.value];
            const scoreFields = ['overall', 'listening', 'speaking', 'reading', 'writing'];
            for (const field of scoreFields) {
                const input = form.querySelector(`[name="english_test[scores][${field}]"]`);
                const val = input ? input.value.trim() : '';
                if (!val) {
                    showStatus('error', 'Please enter all English test scores.');
                    return false;
                }
                if (!isNumericScore(val)) {
                    showStatus('error', 'Please enter numeric values for English test scores.');
                    return false;
                }
                if (limit) {
                    const num = parseFloat(val);
                    if (num > limit.max) {
                        showStatus('error', `Max score for ${englishTestSelect.value} is ${limit.max}.`);
                        return false;
                    }
                }
            }
        }

        const missingDocs = [];
        requiredDocuments.forEach((doc) => {
            if (!hasDocumentUploaded(doc.key)) {
                missingDocs.push(doc.label);
            }
        });

        if (missingFields.length || missingDocs.length) {
            const messages = [];
            if (missingFields.length) {
                messages.push(`Please complete: ${missingFields.join(', ')}`);
            }
            if (missingDocs.length) {
                messages.push(`Upload required documents: ${missingDocs.join(', ')}`);
            }
            showStatus('error', messages.join('<br>'));
            return false;
        }
        return true;
    };

    const handleSaveDraft = () => {
        submitApplication('save');
    };

    const handleSubmit = () => {
        if (!validateSubmission()) {
            return;
        }
        submitApplication('submit');
    };

    if (englishTestSelect && englishTestScoresRow) {
        englishTestSelect.addEventListener('change', () => {
            if (englishTestSelect.value) {
                englishTestScoresRow.style.display = 'grid';
                updateEnglishTestConstraints();
            } else {
                englishTestScoresRow.style.display = 'none';
                englishTestScoresRow.querySelectorAll('input').forEach((input) => {
                    input.value = '';
                });
            }
        });
        englishTestScoresRow.querySelectorAll('input').forEach((input) => {
            input.addEventListener('input', () => enforceScoreLimit(input));
        });
    }

    if (saveBtn) {
        saveBtn.addEventListener('click', handleSaveDraft);
    }
    if (submitBtn) {
        submitBtn.addEventListener('click', handleSubmit);
    }

    docInputs.forEach((input) => {
        input.addEventListener('change', () => updateDocPreview(input));
    });

    fetchLatestApplication();
});
