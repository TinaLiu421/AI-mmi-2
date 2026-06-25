@extends('admin.common')
@section('content')
<?php
$rows       = $_page_data['rows']        ?? [];
$total      = (int)($_page_data['total']       ?? 0);
$page       = (int)($_page_data['page']        ?? 1);
$totalPages = (int)($_page_data['total_pages'] ?? 1);
$search     = $_page_data['search']      ?? '';
$typeFilter = $_page_data['type_filter'] ?? '';
$allTypes   = $_page_data['all_types']   ?? [];

$baseUrl = url('admin/token/index');
$pageUrl = function($pg) use ($search, $typeFilter, $baseUrl) {
    $params = http_build_query(array_filter(['search' => $search, 'type' => $typeFilter, 'page' => $pg]));
    return $baseUrl . ($params ? '?' . $params : '');
};

$amountLabel = function($row) {
    $val = (int)$row->amount;
    if ($val > 0) return '<span style="color:#198754;font-weight:700;">+' . number_format($val) . '</span>';
    return '<span style="color:#dc3545;font-weight:700;">' . number_format($val) . '</span>';
};
?>
<style>
.tk-wrap       { padding: 24px; }
.tk-top        { display: flex; align-items: center; justify-content: space-between; margin-bottom: 18px; flex-wrap: wrap; gap: 10px; }
.tk-top h2     { margin: 0; font-size: 22px; font-weight: 800; }
.tk-filter-bar { display: flex; gap: 10px; flex-wrap: wrap; align-items: center; background: #f8f9fa;
    padding: 12px 16px; border-radius: 8px; border: 1px solid #dee2e6; margin-bottom: 18px; }
.tk-filter-bar input, .tk-filter-bar select { padding: 7px 12px; border: 1px solid #ced4da;
    border-radius: 6px; font-size: 13px; }
.tk-filter-bar button { padding: 7px 18px; background: #4361ee; color: #fff; border: none;
    border-radius: 6px; font-size: 13px; font-weight: 600; cursor: pointer; }
.tk-filter-bar button:hover { background: #3651d4; }
.tk-filter-bar a.reset { font-size: 13px; color: #666; text-decoration: underline; }

.tk-stats { display: flex; gap: 12px; flex-wrap: wrap; margin-bottom: 18px; }
.tk-stat-card { background: #fff; border: 1px solid #dee2e6; border-radius: 8px; padding: 14px 18px; min-width: 120px; }
.tk-stat-num  { font-size: 26px; font-weight: 900; color: #4361ee; }
.tk-stat-lbl  { font-size: 12px; color: #666; margin-top: 2px; }

table.tk-table { width: 100%; border-collapse: collapse; background: #fff; border: 1px solid #dee2e6;
    border-radius: 8px; overflow: hidden; font-size: 13px; }
.tk-table th { background: #f1f3f9; font-weight: 700; padding: 11px 13px; text-align: left;
    color: #333; border-bottom: 2px solid #dee2e6; white-space: nowrap; }
.tk-table td { padding: 10px 13px; border-bottom: 1px solid #f0f0f0; vertical-align: middle; color: #444; }
.tk-table tbody tr:hover { background: #fafbff; }
.tk-table tbody tr:last-child td { border-bottom: none; }

.tk-pagination { display: flex; align-items: center; gap: 6px; margin-top: 18px; flex-wrap: wrap; }
.tk-pagination a, .tk-pagination span {
    padding: 5px 11px; border-radius: 6px; font-size: 13px; border: 1px solid #dee2e6;
    background: #fff; color: #333; text-decoration: none; }
.tk-pagination a:hover { background: #f1f3f9; }
.tk-pagination span.current { background: #4361ee; color: #fff; border-color: #4361ee; }

.tk-type-pill { display: inline-block; padding: 2px 9px; border-radius: 100px; font-size: 11px; font-weight: 700; white-space: nowrap; }
.tk-earn  { background: #e6f9ee; color: #155724; }
.tk-spend { background: #fdecea; color: #6b0000; }
.tk-purchase  { background: #e8f4fd; color: #084298; }
.tk-transfer  { background: #fff3cd; color: #664d03; }

/* Grant / Deduct modal */
.tk-action-bar { display: flex; gap: 10px; margin-bottom: 18px; flex-wrap: wrap; }
.tk-btn { padding: 8px 20px; border-radius: 8px; font-size: 13px; font-weight: 700; border: none; cursor: pointer; }
.tk-btn.grant  { background: #198754; color: #fff; }
.tk-btn.grant:hover  { background: #146c43; }
.tk-btn.deduct { background: #dc3545; color: #fff; }
.tk-btn.deduct:hover { background: #b02a37; }

.tk-modal-bg  { display: none; position: fixed; inset: 0; background: rgba(0,0,0,.4); z-index: 1000; align-items: center; justify-content: center; }
.tk-modal-bg.open { display: flex; }
.tk-modal     { background: #fff; border-radius: 14px; padding: 28px 28px 24px; width: 380px; max-width: 96vw; }
.tk-modal h3  { margin: 0 0 18px; font-size: 18px; font-weight: 800; }
.tk-modal label { display: block; font-size: 13px; font-weight: 700; margin-bottom: 5px; color: #333; }
.tk-modal input, .tk-modal textarea {
    width: 100%; box-sizing: border-box; padding: 9px 12px; border: 1px solid #ced4da;
    border-radius: 7px; font-size: 14px; margin-bottom: 14px; font-family: inherit; }
.tk-modal textarea { resize: vertical; min-height: 60px; }
.tk-modal-footer { display: flex; gap: 10px; justify-content: flex-end; }
.tk-modal-footer button { padding: 8px 22px; border-radius: 8px; font-size: 14px; font-weight: 700; border: none; cursor: pointer; }
.tk-modal-footer .confirm { background: #4361ee; color: #fff; }
.tk-modal-footer .confirm:hover { background: #3651d4; }
.tk-modal-footer .cancel  { background: #e9ecef; color: #333; }
.tk-modal-result { margin-top: 10px; font-size: 13px; font-weight: 700; }
.tk-modal-result.ok  { color: #198754; }
.tk-modal-result.err { color: #dc3545; }
</style>

<div class="tk-wrap">
    <div class="tk-top">
        <h2>Credit Transactions</h2>
        <a href="{{ url('admin/token/member_balance') }}" style="font-size:13px;color:#4361ee;text-decoration:none;font-weight:700;">
            &rarr; Member Balances
        </a>
    </div>

    <div class="tk-action-bar">
        <button class="tk-btn grant"  onclick="openModal('grant')">&#43; Grant Credits</button>
        <button class="tk-btn deduct" onclick="openModal('deduct')">&minus; Deduct Credits</button>
    </div>

    <div class="tk-stats">
        <div class="tk-stat-card">
            <div class="tk-stat-num">{{ number_format($total) }}</div>
            <div class="tk-stat-lbl">Total Transactions</div>
        </div>
    </div>

    <form method="GET" action="{{ $baseUrl }}" class="tk-filter-bar">
        <input type="text" name="search" value="{{ $search }}" placeholder="Search member / notes…" style="min-width:200px;">
        <select name="type">
            <option value="">All types</option>
            @foreach($allTypes as $t)
            <option value="{{ $t }}" {{ $typeFilter === $t ? 'selected' : '' }}>{{ $t }}</option>
            @endforeach
        </select>
        <button type="submit">Filter</button>
        @if($search || $typeFilter)
        <a href="{{ $baseUrl }}" class="reset">Reset</a>
        @endif
    </form>

    <table class="tk-table">
        <thead>
            <tr>
                <th>#</th>
                <th>Member</th>
                <th>Type</th>
                <th>Amount</th>
                <th>Balance After</th>
                <th>Reference</th>
                <th>Notes</th>
                <th>Date</th>
            </tr>
        </thead>
        <tbody>
        @if(count((array)$rows) === 0)
            <tr><td colspan="8" style="text-align:center;color:#999;padding:30px;">No transactions found.</td></tr>
        @else
            @foreach($rows as $row)
            @php
                $typeClass = 'tk-earn';
                if (strncmp($row->type, 'spend', 5) === 0)    $typeClass = 'tk-spend';
                elseif ($row->type === 'purchase')             $typeClass = 'tk-purchase';
                elseif (strncmp($row->type, 'transfer', 8) === 0) $typeClass = 'tk-transfer';
            @endphp
            <tr>
                <td>{{ $row->id }}</td>
                <td>
                    <div style="font-weight:700;font-size:13px;">#{{ $row->member_id }}</div>
                    <div style="font-size:12px;color:#666;">{{ $row->email }}</div>
                </td>
                <td><span class="tk-type-pill {{ $typeClass }}">{{ $row->type }}</span></td>
                <td>{!! $amountLabel($row) !!}</td>
                <td style="font-weight:700;">{{ number_format($row->balance_after) }}</td>
                <td style="font-size:12px;color:#666;">{{ $row->reference_type ?? '' }}{{ $row->reference_id ? ' #'.$row->reference_id : '' }}</td>
                <td style="font-size:12px;color:#555;max-width:200px;word-break:break-word;">{{ $row->notes ?? '' }}</td>
                <td style="font-size:12px;white-space:nowrap;">{{ $row->created_at ? \Carbon\Carbon::parse($row->created_at)->format('M j, Y H:i') : '' }}</td>
            </tr>
            @endforeach
        @endif
        </tbody>
    </table>

    @if($totalPages > 1)
    <div class="tk-pagination">
        @if($page > 1)<a href="{{ $pageUrl($page - 1) }}">&laquo; Prev</a>@endif
        @for($i = max(1, $page - 3); $i <= min($totalPages, $page + 3); $i++)
            @if($i === $page)
                <span class="current">{{ $i }}</span>
            @else
                <a href="{{ $pageUrl($i) }}">{{ $i }}</a>
            @endif
        @endfor
        @if($page < $totalPages)<a href="{{ $pageUrl($page + 1) }}">Next &raquo;</a>@endif
    </div>
    @endif
</div>

{{-- Grant/Deduct Modal --}}
<div class="tk-modal-bg" id="tk-modal-bg">
    <div class="tk-modal">
        <h3 id="tk-modal-title">Grant Tokens</h3>
        <label>Member ID</label>
        <input type="number" id="tk-member-id" placeholder="e.g. 23" min="1">
        <label id="tk-token-label">Credits to grant</label>
        <input type="number" id="tk-tokens" placeholder="e.g. 100" min="1">
        <label>Notes (optional)</label>
        <textarea id="tk-notes" placeholder="Reason or context…"></textarea>
        <div class="tk-modal-footer">
            <button class="cancel" onclick="closeModal()">Cancel</button>
            <button class="confirm" id="tk-modal-submit" onclick="submitModal()">Confirm</button>
        </div>
        <div class="tk-modal-result" id="tk-modal-result"></div>
    </div>
</div>

<script>
var tkModalMode = 'grant';

function openModal(mode) {
    tkModalMode = mode;
    document.getElementById('tk-modal-title').textContent = mode === 'grant' ? 'Grant Credits' : 'Deduct Credits';
    document.getElementById('tk-token-label').textContent = mode === 'grant' ? 'Credits to grant' : 'Credits to deduct';
    document.getElementById('tk-modal-submit').textContent = mode === 'grant' ? 'Grant' : 'Deduct';
    document.getElementById('tk-modal-submit').style.background = mode === 'grant' ? '#198754' : '#dc3545';
    document.getElementById('tk-modal-result').textContent = '';
    document.getElementById('tk-modal-result').className = 'tk-modal-result';
    document.getElementById('tk-modal-bg').classList.add('open');
}

function closeModal() {
    document.getElementById('tk-modal-bg').classList.remove('open');
}

function submitModal() {
    var memberId = document.getElementById('tk-member-id').value.trim();
    var tokens   = document.getElementById('tk-tokens').value.trim();
    var notes    = document.getElementById('tk-notes').value.trim();
    var result   = document.getElementById('tk-modal-result');
    result.textContent = '';

        if (!memberId || !tokens || parseInt(tokens) <= 0) {
        result.textContent = 'Please enter a valid member ID and credit amount.';
        result.className = 'tk-modal-result err';
        return;
    }

    var url = tkModalMode === 'grant'
        ? '{{ url("admin/token/grant") }}'
        : '{{ url("admin/token/deduct") }}';

    var formData = new FormData();
    formData.append('member_id', memberId);
    formData.append('tokens', tokens);
    formData.append('notes', notes);
    formData.append('_token', document.querySelector('meta[name="csrf-token"]') ? document.querySelector('meta[name="csrf-token"]').content : '');

    fetch(url, { method: 'POST', body: formData })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (data.ok) {
                result.textContent = 'Success! New balance: ' + data.new_balance + ' credits.';
                result.className = 'tk-modal-result ok';
                setTimeout(function() { closeModal(); location.reload(); }, 1500);
            } else {
                result.textContent = data.message || 'Error occurred.';
                result.className = 'tk-modal-result err';
            }
        })
        .catch(function() {
            result.textContent = 'Network error. Please try again.';
            result.className = 'tk-modal-result err';
        });
}

document.getElementById('tk-modal-bg').addEventListener('click', function(e) {
    if (e.target === this) closeModal();
});
</script>
@endsection
