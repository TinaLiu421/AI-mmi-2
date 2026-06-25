(function () {
    'use strict';

    var form       = document.getElementById('ip-form');
    var submitBtn  = document.getElementById('ip-submit-btn');
    var errorBox   = document.getElementById('ip-error-box');
    var formSect   = document.getElementById('ip-form-section');
    var successSect = document.getElementById('ip-success-section');

    if (!form) return;

    form.addEventListener('submit', function (e) {
        e.preventDefault();

        errorBox.classList.remove('show');
        errorBox.innerHTML = '';
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fa fa-spinner fa-spin"></i>&nbsp; Sending...';

        var data = new FormData(form);

        fetch(window.location.href, {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') || {}).content || ''
            },
            body: data
        })
        .then(function (res) { return res.json(); })
        .then(function (json) {
            if (json && json.status === 200) {
                formSect.style.display  = 'none';
                successSect.style.display = 'block';
            } else {
                var msg = '';
                if (json && Array.isArray(json.errors) && json.errors.length) {
                    msg = json.errors.join('<br>');
                } else if (json && json.message) {
                    msg = json.message;
                } else {
                    msg = 'Something went wrong. Please try again.';
                }
                errorBox.innerHTML = '<i class="fa fa-exclamation-circle"></i>&nbsp;' + msg;
                errorBox.classList.add('show');
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i class="fa fa-paper-plane"></i>&nbsp; Send Partnership Enquiry';
            }
        })
        .catch(function () {
            errorBox.innerHTML = '<i class="fa fa-exclamation-circle"></i>&nbsp; Network error. Please try again.';
            errorBox.classList.add('show');
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<i class="fa fa-paper-plane"></i>&nbsp; Send Partnership Enquiry';
        });
    });
})();
