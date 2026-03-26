@extends('web.common')

@section('title', 'Agent — Members')

@section('content')
    @php
        $members = $_page_data['members'] ?? [];

        $planBadgeMap = [
            'vip'     => ['label' => 'VIP Agent Plan', 'class' => 'badge-vip'],
            'premium' => ['label' => 'DIY Plan',        'class' => 'badge-premium'],
            'hybrid'  => ['label' => 'AI+Agent Plan',   'class' => 'badge-hybrid'],
            'all_ai'  => ['label' => 'AI Smart Plan',   'class' => 'badge-allai'],
            'free'    => ['label' => 'Free',             'class' => 'badge-free'],
        ];
    @endphp

    <section class="agent-verif-page">
        <div class="agent-verif-header">
            <div>
                <h1 class="agent-verif-title">Members</h1>
                <p class="agent-verif-subtitle">All subscribed members.</p>
            </div>
            <div class="agent-verif-stats">
                <div class="stat-pill stat-total">{{ count($members) }} Members</div>
            </div>
        </div>

        @if(empty($members))
            <div class="agent-verif-empty">No subscribed members found yet.</div>
        @else
            <div class="agent-verif-filter-row">
                <input
                    type="text"
                    id="member-search"
                    class="agent-verif-search"
                    placeholder="Search by name or email…"
                    autocomplete="off"
                />
                <select id="plan-filter" class="agent-verif-select">
                    <option value="">All plans</option>
                    <option value="vip">VIP Agent Plan</option>
                    <option value="premium">DIY Plan</option>
                    <option value="hybrid">AI+Agent Plan</option>
                    <option value="all_ai">AI Smart Plan</option>
                    <option value="free">Free</option>
                </select>
            </div>

            <div class="agent-verif-table-wrap">
                <table class="agent-verif-table" id="verif-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Plan</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($members as $i => $m)
                            @php
                                $planCode  = $m['plan_code'] ?? '';
                                $planBadge = $planBadgeMap[$planCode] ?? ['label' => $m['plan_name'] ?? '—', 'class' => 'badge-free'];
                            @endphp
                            <tr
                                class="verif-row"
                                data-plan="{{ $planCode }}"
                                data-search="{{ strtolower($m['name'] . ' ' . $m['email']) }}"
                            >
                                <td class="cell-num">{{ $i + 1 }}</td>
                                <td class="cell-name">{{ $m['name'] }}</td>
                                <td class="cell-email">{{ $m['email'] }}</td>
                                <td>
                                    <span class="plan-badge {{ $planBadge['class'] }}">{{ $planBadge['label'] }}</span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                <div class="no-results-row" id="no-results" style="display:none;">No members match your filters.</div>
            </div>
        @endif
    </section>

    <script>
        (function () {
            var searchEl = document.getElementById('member-search');
            var planEl   = document.getElementById('plan-filter');
            var rows     = document.querySelectorAll('#verif-table tbody .verif-row');
            var noRes    = document.getElementById('no-results');

            function filter() {
                var q    = searchEl ? searchEl.value.toLowerCase() : '';
                var plan = planEl   ? planEl.value : '';
                var visible = 0;
                rows.forEach(function (r) {
                    var match = (!q || r.dataset.search.includes(q))
                             && (!plan || r.dataset.plan === plan);
                    r.style.display = match ? '' : 'none';
                    if (match) visible++;
                });
                if (noRes) noRes.style.display = visible === 0 ? '' : 'none';
            }

            if (searchEl) searchEl.addEventListener('input', filter);
            if (planEl)   planEl.addEventListener('change', filter);
        })();
    </script>
@endsection
