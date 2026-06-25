<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <title>Activate Your Education Agent Account — AI-MMI</title>
    <link href="/asset/image/logo-mmi.png" rel="icon" type="image/x-icon">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Heebo:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Heebo', sans-serif;
            background: #f0f4f8;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 32px 16px;
        }
        .card {
            background: #fff;
            border-radius: 14px;
            box-shadow: 0 4px 24px rgba(0,0,0,0.10);
            padding: 40px 40px 36px;
            width: 100%;
            max-width: 480px;
        }
        .logo-wrap {
            text-align: center;
            margin-bottom: 28px;
        }
        .logo-wrap img {
            height: 42px;
        }
        h1 {
            font-size: 1.5rem;
            font-weight: 700;
            color: #0b2d6f;
            text-align: center;
            margin-bottom: 10px;
            line-height: 1.3;
        }
        .subtitle {
            font-size: 0.92rem;
            color: #666;
            text-align: center;
            margin-bottom: 28px;
            line-height: 1.55;
        }
        .divider {
            height: 3px;
            width: 48px;
            background: linear-gradient(90deg, #0b2d6f, #e53935);
            border-radius: 2px;
            margin: 0 auto 28px;
        }
        .field {
            margin-bottom: 18px;
        }
        .field label {
            display: block;
            font-size: 0.85rem;
            font-weight: 600;
            color: #333;
            margin-bottom: 6px;
        }
        .field label .hint {
            font-weight: 400;
            color: #aaa;
            font-size: 0.82em;
        }
        .field input {
            width: 100%;
            padding: 11px 14px;
            border: 1.5px solid #d0d8e8;
            border-radius: 8px;
            font-size: 0.95rem;
            font-family: inherit;
            outline: none;
            transition: border-color 0.2s;
            background: #fafbfd;
        }
        .field input:focus {
            border-color: #1a73e8;
            background: #fff;
        }
        .msg {
            display: none;
            padding: 12px 16px;
            border-radius: 8px;
            font-size: 0.92rem;
            margin-bottom: 18px;
            line-height: 1.5;
        }
        .msg.error { background: #fff1f2; border: 1px solid #fca5a5; color: #dc2626; }
        .msg.success { background: #f0fdf4; border: 1px solid #86efac; color: #16a34a; }
        .btn-activate {
            width: 100%;
            padding: 13px;
            background: #0b2d6f;
            color: #fff;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 700;
            font-family: inherit;
            cursor: pointer;
            transition: background 0.2s;
            margin-top: 8px;
        }
        .btn-activate:hover { background: #1a4ba8; }
        .btn-activate:disabled { background: #9ab0d4; cursor: not-allowed; }
        .login-link {
            text-align: center;
            margin-top: 18px;
            font-size: 0.88rem;
            color: #888;
        }
        .login-link a { color: #1a73e8; text-decoration: none; font-weight: 600; }
        .login-link a:hover { text-decoration: underline; }
        @media (max-width: 520px) {
            .card { padding: 28px 20px 24px; }
        }
    </style>
</head>
<body>
<?php
$_error   = $_page_data['error'] ?? null;
$_success = $_page_data['success'] ?? false;
$_token   = $_page_data['token'] ?? '';
?>
<div class="card">
    <div class="logo-wrap">
        <img src="/asset/image/logo.png" alt="AI-MMI">
    </div>
    <h1>Activate Your Education Agent Account</h1>
    <p class="subtitle">You have been granted access to the AI-MMI Education Agent portal. Set your email and password below to activate your account.</p>
    <div class="divider"></div>

    @if($_error)
        <div class="msg error" style="display:block;">
            {{ $_error }}
            @if(str_contains($_error ?? '', 'already been claimed') || str_contains($_error ?? '', 'log in'))
                <br><br><a href="{{ url('/en/account_login') }}">Go to Login →</a>
            @endif
        </div>
    @elseif($_success)
        <div class="msg success" style="display:block;">
            Your account has been activated! <a href="{{ url('/en/account_login') }}">Click here to log in →</a>
        </div>
    @else
        <div id="claim-form-wrap">
            <div class="msg" id="claim-msg"></div>

            <div class="field">
                <label for="claim-email">Your Email Address <span style="color:red;">*</span></label>
                <input type="email" id="claim-email" placeholder="you@yourinstitution.edu" autocomplete="email">
            </div>

            <div class="field">
                <label for="claim-pass">Password <span style="color:red;">*</span> <span class="hint">(min. 8 characters)</span></label>
                <input type="password" id="claim-pass" placeholder="Choose a strong password" autocomplete="new-password">
            </div>

            <div class="field">
                <label for="claim-pass2">Confirm Password <span style="color:red;">*</span></label>
                <input type="password" id="claim-pass2" placeholder="Repeat password" autocomplete="new-password">
            </div>

            <button type="button" id="btn-claim" class="btn-activate" onclick="submitClaim()">Activate Account</button>
        </div>
        <div class="login-link">Already activated? <a href="{{ url('/en/account_login') }}">Log in here</a></div>
    @endif
</div>

<script>
var _claimToken = '<?php echo addslashes($_token); ?>';
var _csrf = '{{ csrf_token() }}';

function submitClaim() {
    var email  = document.getElementById('claim-email').value.trim();
    var pass   = document.getElementById('claim-pass').value;
    var pass2  = document.getElementById('claim-pass2').value;
    var btn    = document.getElementById('btn-claim');

    if (!email || !pass || !pass2) {
        showClaimMsg('Please fill in all required fields.', false);
        return;
    }

    btn.disabled = true;
    btn.textContent = 'Activating...';

    fetch('/claim_edu_account/' + _claimToken, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': _csrf },
        body: JSON.stringify({ email: email, password: pass, password_confirm: pass2 })
    })
    .then(r => r.json())
    .then(function(d) {
        if (d.status === 200) {
            document.getElementById('claim-form-wrap').innerHTML =
                '<div class="msg success" style="display:block;">Account activated! <a href="' + (d.redirect || '/en/account_login') + '">Click here to log in →</a></div>';
        } else {
            showClaimMsg(d.message || 'An error occurred. Please try again.', false);
            btn.disabled = false;
            btn.textContent = 'Activate Account';
        }
    })
    .catch(function() {
        showClaimMsg('Network error. Please check your connection and try again.', false);
        btn.disabled = false;
        btn.textContent = 'Activate Account';
    });
}

function showClaimMsg(msg, ok) {
    var el = document.getElementById('claim-msg');
    el.textContent = msg;
    el.style.display = 'block';
    el.className = 'msg ' + (ok ? 'success' : 'error');
}
</script>
</body>
</html>
