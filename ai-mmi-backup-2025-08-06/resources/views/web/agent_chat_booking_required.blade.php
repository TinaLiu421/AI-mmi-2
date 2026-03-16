@extends('web.common')

@section('title', 'Talk to Agent - Book Your Meeting')

@section('content')
    @php
        $pageMode = $_page_data['mode'] ?? 'calendly_only';
        $agentChatContinueUrl = '/' . trim((string)($_current_lang_code ?? ''), '/') . '/agent_chat/chat';
        $agentChatContinueUrl = preg_replace('#/+#', '/', $agentChatContinueUrl);
    @endphp
    <section class="agent-booking-page">
        <div class="agent-booking-card">
            <div class="agent-booking-title">Book Your Agent Meeting</div>
            <div class="agent-booking-desc">
                @if($pageMode === 'calendly_only')
                    Your <strong>AI + Agent Plan</strong> includes a one-time consultation with a qualified migration agent.<br>
                    Click below to schedule your meeting via Calendly.
                @else
                    Please schedule an online meeting with <strong>Wealthskey Migration</strong> to unlock agent chat.
                @endif
            </div>

            <div class="agent-booking-details">
                <div class="agent-booking-logo-row">
                    <img
                        src="/upload/member_avatar/d148b40e4988fd1cbe690bfc0613dcaf.png"
                        alt="Wealthskey Migration Logo"
                        class="agent-booking-logo"
                    />
                    <div class="agent-name">Wealthskey Migration</div>
                </div>
                <div class="agent-meta">
                    <div>Website : <a href="https://wealthskey.com" target="_blank" rel="noopener noreferrer">https://wealthskey.com</a></div>
                    <div>Location : Brisbane, Australia</div>
                    <div>Whatsapp Number +852 54867893</div>
                    <div>Mobile Number : +61 413892060</div>
                    <div>Registration number : 2418441</div>
                </div>
            </div>

            <div class="agent-booking-actions">
                <a
                    class="agent-booking-btn primary"
                    id="open-calendly-booking"
                    href="{{ $calendly_url ?? 'https://calendly.com/admin-wealthskey/30min' }}"
                    target="_blank"
                    rel="noopener noreferrer"
                >
                    Schedule meeting with agent
                </a>
                @if($pageMode !== 'calendly_only')
                <a
                    class="agent-booking-btn secondary"
                    href="{{ $agentChatContinueUrl }}"
                >
                    I already booked, continue to chat
                </a>
                @endif
            </div>

            @if($pageMode !== 'calendly_only')
            <div class="agent-booking-status" id="agent-booking-status">
                Waiting for booking confirmation...
            </div>
            @endif
        </div>
    </section>
    <script>
        window.agentBookingConfig = {
            mode: @json($pageMode),
            calendlyUrl: @json($calendly_url ?? 'https://calendly.com/admin-wealthskey/30min'),
            unlockApiUrl: @json($unlock_api_url ?? '/agent_chat/booking/confirm'),
            continueUrl: @json($agentChatContinueUrl)
        };
    </script>
@endsection
