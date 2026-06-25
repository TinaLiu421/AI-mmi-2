@extends('web.common')

@push('css')
<link href="/asset/css/web/wallet.css?v=<?php echo date('Ymd'); ?>" rel="stylesheet">
<style>
/* Wallet: dark layout (2/3 content + 1/3 chatbot) */
main.page-body .info-area {
    background-color: #020c1c !important;
    background-image: none !important;
    min-height: 100vh !important;
}
main.page-body .info-area::before { display: none !important; }
</style>
@endpush

@section('content')
<?php
    $balance       = $_page_data['balance'] ?? 0;
    $transactions  = $_page_data['transactions'] ?? collect();
    $packages      = $_page_data['packages'] ?? collect();
    $referralCode  = $_page_data['referral_code'] ?? '';
    $referralUrl   = $_page_data['referral_url'] ?? '';
    $paymentStatus = $_page_data['payment_status'] ?? null;

    // Human-readable transaction type labels
    $typeLabels = [
        'earn_signup'            => '✦ Sign-up bonus',
        'earn_daily_login'       => '✦ Daily login',
        'earn_profile_complete'  => '✦ Profile completed',
        'earn_share_results'     => '✦ Shared results',
        'earn_referral_accepted' => '✦ Referral accepted',
        'earn_admin_grant'       => '✦ Admin grant',
        'purchase'               => '✦ Credit purchase',
        'spend_chat'             => '↓ AI chat usage',
        'spend_match'            => '↓ Match usage',
        'spend_agent_call'       => '↓ Agent call',
        'spend_diy_visa'         => '↓ DIY visa review',
        'spend_full_assistance'  => '↓ Full assistance',
        'spend_school_payment'   => '↓ School payment',
        'spend_admin_deduct'     => '↓ Admin deduction',
        'transfer_out'           => '→ Sent to member',
        'transfer_in'            => '← Received from member',
    ];

    $usdPerToken = 0.10;
?>
<div class="wallet-wrap">

    <?php /* ── Flash messages ── */ ?>
    <?php if ($paymentStatus === 'success'): ?>
    <div class="w-flash ok">&#10003; Your credit purchase was successful! Your balance has been updated.</div>
    <?php elseif ($paymentStatus === 'cancelled'): ?>
    <div class="w-flash err">&#10005; Payment was cancelled. Your balance was not charged.</div>
    <?php endif; ?>

    <?php /* ── Hero balance ── */ ?>
    <div class="w-hero">
        <div class="w-balance-group">
            <div class="w-balance-label">Your Credit Balance</div>
            <div class="w-balance-amount" id="wallet-balance"><?php echo number_format($balance); ?></div>
            <div class="w-balance-usd">≈ USD <?php echo number_format($balance * $usdPerToken, 2); ?></div>
        </div>
        <div class="w-hero-actions">
            <a href="javascript:void(0)" onclick="document.getElementById('buy-tokens').scrollIntoView({behavior:'smooth'})" class="w-btn w-btn-primary">Buy Credits</a>
            <a href="javascript:void(0)" onclick="document.getElementById('transfer').scrollIntoView({behavior:'smooth'})" class="w-btn w-btn-outline">Transfer</a>
        </div>
    </div>

    <?php /* ── How to earn ── */ ?>
    <div class="w-section">
        <h2 class="w-section-title">How to Earn</h2>
        <div class="w-earn-grid">
            <div class="w-earn-item">
                <div class="w-earn-amount">+20</div>
                <div class="w-earn-label">Sign up</div>
            </div>
            <div class="w-earn-item">
                <div class="w-earn-amount">+1</div>
                <div class="w-earn-label">Daily login</div>
            </div>
            <div class="w-earn-item">
                <div class="w-earn-amount">+3</div>
                <div class="w-earn-label">Complete profile</div>
            </div>
            <div class="w-earn-item">
                <div class="w-earn-amount">+2</div>
                <div class="w-earn-label">Share results</div>
            </div>
            <div class="w-earn-item">
                <div class="w-earn-amount">+5</div>
                <div class="w-earn-label">Invite a friend</div>
            </div>
        </div>
    </div>

    <?php /* ── Buy tokens ── */ ?>
    <div class="w-section" id="buy-tokens">
        <h2 class="w-section-title">Buy Credits</h2>
        <div class="w-packages" id="w-package-grid">
            <?php foreach ($packages as $i => $pkg): ?>
            <?php $priceUsd = (float)$pkg->price_usd_cents / 100; $perCredit = ($pkg->tokens > 0) ? ($priceUsd / $pkg->tokens) : 0; ?>
            <div class="w-pkg-card" data-pkg-id="<?php echo (int)$pkg->id; ?>" onclick="selectPackage(this)">
                <?php if ($i === 2): /* Best value badge for 1000-credit pack */ ?>
                <div class="w-pkg-badge">Best Value</div>
                <?php endif; ?>
                <div class="w-pkg-tokens"><?php echo number_format($pkg->tokens); ?></div>
                <div class="w-pkg-label">Credits</div>
                <div class="w-pkg-price">$<?php echo number_format($priceUsd, 0); ?></div>
                <div class="w-pkg-per">$<?php echo number_format($perCredit, 3); ?> / credit</div>
            </div>
            <?php endforeach; ?>
        </div>
        <div style="margin-top:20px; text-align:center;">
            <button class="w-btn w-btn-primary" id="w-buy-btn" onclick="initPurchase()" disabled style="min-width:200px;">
                Select a Package
            </button>
            <p style="font-size:13px;color:#94a3b8;margin-top:10px;">Secure payment via Stripe. 1 AI-mmi Credit = $0.10 USD.</p>
        </div>
    </div>

    <?php /* ── Transfer tokens ── */ ?>
    <div class="w-section" id="transfer">
        <h2 class="w-section-title">Transfer Credits</h2>
        <div class="w-transfer-form">
            <p style="color:#94a3b8;font-size:14px;margin:0;">Send credits to a friend, family member, or institution by their email address.</p>
            <div class="w-form-row">
                <div class="w-field" style="flex:2;">
                    <label>Recipient Email</label>
                    <input type="email" id="transfer-email" placeholder="friend@example.com">
                </div>
                <div class="w-field" style="flex:1;">
                    <label>Amount (credits)</label>
                    <input type="number" id="transfer-amount" placeholder="e.g. 10" min="1">
                </div>
            </div>
            <div>
                <button class="w-btn w-btn-primary" onclick="doTransfer()" style="min-width:160px;">Send Credits</button>
            </div>
            <div class="w-transfer-msg" id="transfer-msg"></div>
        </div>
    </div>

    <?php /* ── Referral ── */ ?>
    <?php if (!empty($referralUrl)): ?>
    <div class="w-section">
        <h2 class="w-section-title">Invite Friends (+5 credits each)</h2>
        <div class="w-referral-box">
            <p style="color:#94a3b8;font-size:14px;margin:0 0 6px;">Share your unique referral link. When a friend signs up, you earn 5 credits!</p>
            <div class="w-ref-url-row">
                <input class="w-ref-url-input" id="ref-url-input" type="text" value="<?php echo htmlspecialchars($referralUrl, ENT_QUOTES, 'UTF-8'); ?>" readonly>
                <button class="w-ref-copy-btn" onclick="copyReferral()">Copy Link</button>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <?php /* ── Transaction history ── */ ?>
    <div class="w-section">
        <h2 class="w-section-title">Recent Transactions</h2>
        <?php if ($transactions->isEmpty()): ?>
        <p style="color:#94a3b8;">No transactions yet. Earn your first credits by completing your profile!</p>
        <?php else: ?>
        <div class="w-tx-list">
            <?php foreach ($transactions as $tx): ?>
            <?php $positive = $tx->amount > 0; ?>
            <div class="w-tx-item">
                <div class="w-tx-left">
                    <div class="w-tx-type"><?php echo htmlspecialchars($typeLabels[$tx->type] ?? ucfirst(str_replace('_', ' ', $tx->type)), ENT_QUOTES, 'UTF-8'); ?></div>
                    <?php if (!empty($tx->notes)): ?>
                    <div class="w-tx-date"><?php echo htmlspecialchars($tx->notes, ENT_QUOTES, 'UTF-8'); ?></div>
                    <?php endif; ?>
                    <div class="w-tx-date"><?php echo date('M j, Y  H:i', strtotime($tx->created_at)); ?></div>
                </div>
                <div class="w-tx-amount <?php echo $positive ? 'positive' : 'negative'; ?>">
                    <?php echo $positive ? '+' : ''; ?><?php echo number_format($tx->amount); ?>
                    <small style="font-size:12px;font-weight:400;color:#64748b;"> bal: <?php echo number_format($tx->balance_after); ?></small>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>

</div>

<script>
// ── Package selection ──
var _selectedPkgId = null;

function selectPackage(el) {
    document.querySelectorAll('.w-pkg-card').forEach(function(c) { c.classList.remove('selected'); });
    el.classList.add('selected');
    _selectedPkgId = parseInt(el.dataset.pkgId, 10);
    var btn = document.getElementById('w-buy-btn');
    btn.disabled = false;
    btn.textContent = 'Proceed to Payment';
}

// ── Purchase ──
function initPurchase() {
    if (!_selectedPkgId) return;
    var btn = document.getElementById('w-buy-btn');
    btn.disabled = true;
    btn.textContent = 'Redirecting...';

    fetch('/wallet/buy', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'X-CSRF-TOKEN': _token,
        },
        body: 'package_id=' + _selectedPkgId,
    })
    .then(function(r) { return r.json(); })
    .then(function(data) {
        if (data.status === 200 && data.url) {
            window.location.href = data.url;
        } else {
            alert(data.message || 'Something went wrong. Please try again.');
            btn.disabled = false;
            btn.textContent = 'Proceed to Payment';
        }
    })
    .catch(function() {
        alert('Network error. Please try again.');
        btn.disabled = false;
        btn.textContent = 'Proceed to Payment';
    });
}

// ── Transfer ──
function doTransfer() {
    var email  = document.getElementById('transfer-email').value.trim();
    var amount = parseInt(document.getElementById('transfer-amount').value, 10);
    var msgEl  = document.getElementById('transfer-msg');
    msgEl.className = 'w-transfer-msg';
    msgEl.textContent = '';

    if (!email || !amount || amount < 1) {
        msgEl.className = 'w-transfer-msg err';
        msgEl.textContent = 'Please enter a valid email and credit amount.';
        return;
    }

    fetch('/wallet/transfer', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'X-CSRF-TOKEN': _token,
        },
        body: 'to_email=' + encodeURIComponent(email) + '&amount=' + amount,
    })
    .then(function(r) { return r.json(); })
    .then(function(data) {
        if (data.status === 200) {
            msgEl.className = 'w-transfer-msg ok';
            msgEl.textContent = data.message;
            document.getElementById('transfer-email').value = '';
            document.getElementById('transfer-amount').value = '';
            // Refresh balance display
            refreshBalance();
        } else {
            msgEl.className = 'w-transfer-msg err';
            msgEl.textContent = data.message || 'Transfer failed.';
        }
    })
    .catch(function() {
        msgEl.className = 'w-transfer-msg err';
        msgEl.textContent = 'Network error. Please try again.';
    });
}

// ── Copy referral link ──
function copyReferral() {
    var inp = document.getElementById('ref-url-input');
    inp.select();
    document.execCommand('copy');
    // Brief feedback
    var btn = event.target;
    btn.textContent = 'Copied!';
    setTimeout(function() { btn.textContent = 'Copy Link'; }, 2000);
}

// ── Refresh balance ──
function refreshBalance() {
    fetch('/wallet/balance')
    .then(function(r) { return r.json(); })
    .then(function(data) {
        if (typeof data.balance !== 'undefined') {
            var el = document.getElementById('wallet-balance');
            if (el) el.textContent = data.balance.toLocaleString();
            var navEl = document.getElementById('nav-token-count');
            if (navEl) navEl.textContent = data.balance.toLocaleString();
        }
    });
}
</script>
@endsection
