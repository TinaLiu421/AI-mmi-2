@extends('admin.common')
@section('content')
<?php
$rows       = $_page_data['rows']        ?? [];
$total      = (int)($_page_data['total']       ?? 0);
$page       = (int)($_page_data['page']        ?? 1);
$totalPages = (int)($_page_data['total_pages'] ?? 1);
$search     = $_page_data['search']      ?? '';

$baseUrl = url('admin/token/member_balance');
$pageUrl = function($pg) use ($search, $baseUrl) {
    $params = http_build_query(array_filter(['search' => $search, 'page' => $pg]));
    return $baseUrl . ($params ? '?' . $params : '');
};
?>
<style>
.tkb-wrap       { padding: 24px; }
.tkb-top        { display: flex; align-items: center; justify-content: space-between; margin-bottom: 18px; flex-wrap: wrap; gap: 10px; }
.tkb-top h2     { margin: 0; font-size: 22px; font-weight: 800; }
.tkb-filter-bar { display: flex; gap: 10px; flex-wrap: wrap; align-items: center; background: #f8f9fa;
    padding: 12px 16px; border-radius: 8px; border: 1px solid #dee2e6; margin-bottom: 18px; }
.tkb-filter-bar input { padding: 7px 12px; border: 1px solid #ced4da; border-radius: 6px; font-size: 13px; }
.tkb-filter-bar button { padding: 7px 18px; background: #4361ee; color: #fff; border: none; border-radius: 6px; font-size: 13px; font-weight: 600; cursor: pointer; }
.tkb-filter-bar button:hover { background: #3651d4; }
.tkb-filter-bar a.reset { font-size: 13px; color: #666; text-decoration: underline; }

.tkb-stats { display: flex; gap: 12px; flex-wrap: wrap; margin-bottom: 18px; }
.tkb-stat-card { background: #fff; border: 1px solid #dee2e6; border-radius: 8px; padding: 14px 18px; min-width: 120px; }
.tkb-stat-num  { font-size: 26px; font-weight: 900; color: #4361ee; }
.tkb-stat-lbl  { font-size: 12px; color: #666; margin-top: 2px; }

table.tkb-table { width: 100%; border-collapse: collapse; background: #fff; border: 1px solid #dee2e6;
    border-radius: 8px; overflow: hidden; font-size: 13px; }
.tkb-table th { background: #f1f3f9; font-weight: 700; padding: 11px 13px; text-align: left;
    color: #333; border-bottom: 2px solid #dee2e6; white-space: nowrap; }
.tkb-table td { padding: 10px 13px; border-bottom: 1px solid #f0f0f0; vertical-align: middle; color: #444; }
.tkb-table tbody tr:hover { background: #fafbff; }
.tkb-table tbody tr:last-child td { border-bottom: none; }

.tkb-pagination { display: flex; align-items: center; gap: 6px; margin-top: 18px; flex-wrap: wrap; }
.tkb-pagination a, .tkb-pagination span {
    padding: 5px 11px; border-radius: 6px; font-size: 13px; border: 1px solid #dee2e6;
    background: #fff; color: #333; text-decoration: none; }
.tkb-pagination a:hover { background: #f1f3f9; }
.tkb-pagination span.current { background: #4361ee; color: #fff; border-color: #4361ee; }

.plan-pill { display: inline-block; padding: 2px 10px; border-radius: 100px; font-size: 11px; font-weight: 800; }
.plan-premium { background: #e8f4fd; color: #084298; }
.plan-vip     { background: #fff3cd; color: #664d03; }
.plan-none    { background: #f5f5f5; color: #999; }

.balance-hi { color: #c97d00; font-weight: 900; }
.balance-lo { color: #888; }
</style>

<div class="tkb-wrap">
    <div class="tkb-top">
        <h2>Member Credit Balances</h2>
        <a href="{{ url('admin/token/index') }}" style="font-size:13px;color:#4361ee;text-decoration:none;font-weight:700;">
            &larr; Transaction Log
        </a>
    </div>

    <div class="tkb-stats">
        <div class="tkb-stat-card">
            <div class="tkb-stat-num">{{ number_format($total) }}</div>
            <div class="tkb-stat-lbl">Total Members</div>
        </div>
    </div>

    <form method="GET" action="{{ $baseUrl }}" class="tkb-filter-bar">
        <input type="text" name="search" value="{{ $search }}" placeholder="Search by name, email or ID…" style="min-width:240px;">
        <button type="submit">Search</button>
        @if($search)
        <a href="{{ $baseUrl }}" class="reset">Reset</a>
        @endif
    </form>

    <table class="tkb-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Member</th>
                <th>Credit Balance</th>
                <th>Active Plan</th>
                <th>Plan Expires</th>
                <th>Member Since</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
        @if(count((array)$rows) === 0)
            <tr><td colspan="7" style="text-align:center;color:#999;padding:30px;">No members found.</td></tr>
        @else
            @foreach($rows as $row)
            @php
                $balance = (int) $row->token_balance;
                $balanceClass = $balance > 0 ? 'balance-hi' : 'balance-lo';
                $planCode = $row->plan_code ?? null;
                $planClass = $planCode === 'vip' ? 'plan-vip' : ($planCode === 'premium' ? 'plan-premium' : 'plan-none');
                $planLabel = $planCode ? ucfirst($planCode) : 'None';
            @endphp
            <tr>
                <td>{{ $row->id }}</td>
                <td>
                    <div style="font-weight:700;">{{ $row->full_name ?: '—' }}</div>
                    <div style="font-size:12px;color:#666;">{{ $row->email }}</div>
                </td>
                <td><span class="{{ $balanceClass }}">{{ number_format($balance) }}</span></td>
                <td><span class="plan-pill {{ $planClass }}">{{ $planLabel }}</span></td>
                <td style="font-size:12px;color:#666;">
                    {{ $row->plan_ends_at ? \Carbon\Carbon::parse($row->plan_ends_at)->format('M j, Y') : '—' }}
                </td>
                <td style="font-size:12px;color:#666;">
                    {{ $row->created_at ? \Carbon\Carbon::parse($row->created_at)->format('M j, Y') : '—' }}
                </td>
                <td>
                    <a href="{{ url('admin/token/index?search=' . $row->id) }}"
                       style="font-size:12px;color:#4361ee;text-decoration:none;font-weight:700;">History</a>
                </td>
            </tr>
            @endforeach
        @endif
        </tbody>
    </table>

    @if($totalPages > 1)
    <div class="tkb-pagination">
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
@endsection
