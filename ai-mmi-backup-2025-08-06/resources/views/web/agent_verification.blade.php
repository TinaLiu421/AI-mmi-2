@extends('web.common')

@section('title', 'Agent — Member Verification')

@section('content')
    @php
        $members = $_page_data['members'] ?? [];

        $planBadgeMap = [
            'vip'     => ['label' => 'VIP Agent Plan',  'class' => 'badge-vip'],
            'premium' => ['label' => 'DIY Plan',         'class' => 'badge-premium'],
            'hybrid'  => ['label' => 'AI+Agent Plan',    'class' => 'badge-hybrid'],
            'all_ai'  => ['label' => 'AI Smart Plan',    'class' => 'badge-allai'],
            'free'    => ['label' => 'Free',              'class' => 'badge-free'],
        ];

        $outcomeIconMap = [
            'premium' => ['icon' => '💬', 'class' => 'outcome-chat'],
            'vip'     => ['icon' => '⭐', 'class' => 'outcome-vip'],
        ];

        $totalMembers   = count($members);
        $verifiedCount  = count(array_filter($members, function($m) { return !empty($m['verified']); }));
        $pendingCount   = $totalMembers - $verifiedCount;
    @endphp

    <section class="agent-verif-page">
        <div class="agent-verif-header">
            <div>
                <h1 class="agent-verif-title">Member Verification Checklist</h1>
                <p class="agent-verif-subtitle">
                    Review all subscribed members, tick verified once you have completed their meeting or consultation.
                    Verification outcome differs by plan.
                </p>
            </div>
            <div class="agent-verif-stats">
                <div class="stat-pill stat-total">{{ $totalMembers }} Members</div>
                <div class="stat-pill stat-done">{{ $verifiedCount }} Verified</div>
                @if($pendingCount > 0)
                <div class="stat-pill stat-pending">{{ $pendingCount }} Pending</div>
                @endif
            </div>
        </div>

        <div class="agent-verif-legend">
            <span class="legend-item"><span class="legend-dot dot-premium"></span> DIY Plan — verification <strong>unlocks agent chat</strong></span>
            <span class="legend-item"><span class="legend-dot dot-hybrid"></span> AI+Agent / Free / AI Smart — verification <strong>redirects to upgrade</strong></span>
            <span class="legend-item"><span class="legend-dot dot-vip"></span> VIP — always has direct chat access</span>
        </div>

        @if(empty($members))
            <div class="agent-verif-empty">
                No subscribed members found yet.
            </div>
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
                <select id="status-filter" class="agent-verif-select">
                    <option value="">All statuses</option>
                    <option value="verified">Verified</option>
                    <option value="pending">Pending</option>
                </select>
            </div>

            <div class="agent-verif-table-wrap">
                <table class="agent-verif-table" id="verif-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Member</th>
                            <th>Email</th>
                            <th>Plan</th>
                            <th>Meeting Booked</th>
                            <th>Verification Outcome</th>
                            <th>Verified</th>
                            <th>Verified At</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($members as $i => $m)
                            @php
                                $planCode  = $m['plan_code'] ?? '';
                                $planBadge = $planBadgeMap[$planCode] ?? ['label' => $m['plan_name'] ?? '—', 'class' => 'badge-free'];
                                $verified  = !empty($m['verified']);
                                $hasBooked = !empty($m['has_booked']);
                                $isChatPlan = in_array($planCode, ['premium', 'vip']);
                            @endphp
                            <tr
                                class="verif-row {{ $verified ? 'row-verified' : '' }}"
                                data-member-id="{{ $m['member_id'] }}"
                                data-plan="{{ $planCode }}"
                                data-status="{{ $verified ? 'verified' : 'pending' }}"
                                data-search="{{ strtolower($m['name'] . ' ' . $m['email']) }}"
                                data-has-booked="{{ $hasBooked ? '1' : '0' }}"
                                data-booking-plan="{{ $m['booking_plan_code'] ?? '' }}"
                            >
                                <td class="cell-num">{{ $i + 1 }}</td>
                                <td class="cell-name">{{ $m['name'] }}</td>
                                <td class="cell-email">{{ $m['email'] }}</td>
                                <td>
                                    <span class="plan-badge {{ $planBadge['class'] }}">{{ $planBadge['label'] }}</span>
                                </td>
                                <td class="cell-booked">
                                    @if($hasBooked)
                                        <span class="booked-yes" title="{{ $m['booked_at'] ? \Carbon\Carbon::parse($m['booked_at'])->format('d M Y H:i') : '' }}">✓ Booked</span>
                                        @if(in_array($planCode, ['hybrid','free','all_ai']))
                                            <br><button class="btn-delete-booking" data-member-id="{{ $m['member_id'] }}" title="Delete this booking record (resets their quota)">Reset</button>
                                        @endif
                                    @else
                                        <span class="booked-no">Not yet</span>
                                    @endif
                                </td>
                                <td class="cell-outcome {{ $isChatPlan ? 'outcome-unlock' : 'outcome-upgrade' }}">
                                    @if($planCode === 'vip')
                                        <span title="VIP always has direct access">⭐ Full chat (no booking)</span>
                                    @elseif($planCode === 'premium')
                                        <span>💬 Full chat (no booking)</span>
                                    @elseif($planCode === 'hybrid')
                                        <span>📅 2-hr consult — unlocks after agent confirms</span>
                                    @elseif($planCode === 'all_ai')
                                        <span>⏱ 15-min auto-recorded</span>
                                    @else
                                        <span>⏱ 15-min auto-recorded</span>
                                    @endif
                                </td>
                                <td class="cell-verif">
                                    <label class="verif-toggle {{ $planCode === 'vip' ? 'toggle-vip' : '' }}" title="{{ $verified ? 'Mark as not verified' : 'Mark as verified' }}">
                                        <input
                                            type="checkbox"
                                            class="verif-checkbox"
                                            data-member-id="{{ $m['member_id'] }}"
                                            {{ $verified ? 'checked' : '' }}
                                            {{ $planCode === 'vip' ? 'disabled title=VIP always has access' : '' }}
                                        />
                                        <span class="verif-toggle-label">{{ $verified ? 'Verified' : 'Pending' }}</span>
                                    </label>
                                </td>
                                <td class="cell-verif-at">
                                    @if($verified && $m['verified_at'])
                                        {{ \Carbon\Carbon::parse($m['verified_at'])->format('d M Y H:i') }}
                                    @else
                                        <span class="cell-pending">—</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                <div class="no-results-row" id="no-results" style="display:none;">No members match your filters.</div>
            </div>
        @endif
    </section>

    <div class="agent-verif-toast" id="verif-toast"></div>

    <script>
        window.agentVerifConfig = {
            attendanceUrl: '/agent_chat/booking/attendance',
            deleteUrl: '/agent_chat/booking/delete',
            csrfToken: (typeof _token !== 'undefined' && _token) ? _token
                : (document.querySelector('meta[name="csrf-token"]')
                    ? document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    : '')
        };
    </script>
@endsection
