@extends('web.common')

@section('title', 'Agent Dashboard - Meeting Attendance')

@section('content')
    @php
        $bookings = $_page_data['bookings'] ?? [];

        $planBadgeMap = [
            'vip'     => ['label' => 'VIP Agent Plan',  'class' => 'badge-vip'],
            'premium' => ['label' => 'DIY Plan',         'class' => 'badge-premium'],
            'hybrid'  => ['label' => 'AI+Agent Plan',    'class' => 'badge-hybrid'],
            'all_ai'  => ['label' => 'AI Smart Plan',    'class' => 'badge-allai'],
            'free'    => ['label' => 'Free',              'class' => 'badge-free'],
        ];
    @endphp

    <section class="agent-dashboard-page">
        <div class="agent-dashboard-header">
            <h1 class="agent-dashboard-title">Meeting Attendance Dashboard</h1>
            <p class="agent-dashboard-subtitle">Members who have booked a meeting with you. Tick the checkbox once you have confirmed attendance.</p>
        </div>

        @if(empty($bookings))
            <div class="agent-dashboard-empty">
                No meeting bookings yet.
            </div>
        @else
            <div class="agent-dashboard-table-wrap">
                <table class="agent-dashboard-table" id="attendance-table">
                    <thead>
                        <tr>
                            <th>Member</th>
                            <th>Email</th>
                            <th>Plan</th>
                            <th>Booked At</th>
                            <th>Attended</th>
                            <th>Attended At</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($bookings as $b)
                            @php
                                $planCode  = $b['plan_code'] ?? '';
                                $planBadge = $planBadgeMap[$planCode] ?? ['label' => $b['plan_name'] ?? 'Unknown', 'class' => 'badge-free'];
                                $attended  = !empty($b['agent_attended']);
                            @endphp
                            <tr class="{{ $attended ? 'row-attended' : '' }}" data-member-id="{{ $b['member_id'] }}">
                                <td class="cell-name">{{ $b['name'] }}</td>
                                <td class="cell-email">{{ $b['email'] }}</td>
                                <td><span class="plan-badge {{ $planBadge['class'] }}">{{ $planBadge['label'] }}</span></td>
                                <td class="cell-date">{{ $b['booked_at'] ? \Carbon\Carbon::parse($b['booked_at'])->format('d M Y H:i') : '—' }}</td>
                                <td class="cell-attended">
                                    <label class="attendance-toggle" title="{{ $attended ? 'Mark as not attended' : 'Mark as attended' }}">
                                        <input
                                            type="checkbox"
                                            class="attendance-checkbox"
                                            data-member-id="{{ $b['member_id'] }}"
                                            {{ $attended ? 'checked' : '' }}
                                        />
                                        <span class="attendance-toggle-label">{{ $attended ? 'Yes' : 'No' }}</span>
                                    </label>
                                </td>
                                <td class="cell-attended-at">
                                    @if($attended && $b['attended_at'])
                                        {{ \Carbon\Carbon::parse($b['attended_at'])->format('d M Y H:i') }}
                                    @else
                                        <span class="cell-pending">—</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>

    <div class="agent-dashboard-toast" id="dashboard-toast"></div>

    <script>
        window.agentDashboardConfig = {
            attendanceUrl: '/agent_chat/booking/attendance',
            csrfToken: document.querySelector('meta[name="csrf-token"]')
                ? document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                : ''
        };
    </script>
@endsection
