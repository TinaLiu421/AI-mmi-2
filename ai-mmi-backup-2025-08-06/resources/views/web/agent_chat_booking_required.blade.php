@extends('web.common')

@section('title', 'Talk to Agent - Meeting Required')

@section('content')
    @php
        $agentChatContinueUrl = '/' . trim((string)($_current_lang_code ?? ''), '/') . '/agent_chat/chat';
        $agentChatContinueUrl = preg_replace('#/+#', '/', $agentChatContinueUrl);
    @endphp
    <section class="agent-booking-page">
        <div class="agent-booking-card">
            <div class="agent-booking-title">Talk to Agent</div>
            <div class="agent-booking-desc">
                Please schedule an online meeting with <strong>Wealthskey Migration</strong> first.
                After your Calendly booking is successfully completed, Talk to Agent chat will be unlocked automatically.
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
                <a
                    class="agent-booking-btn secondary"
                    href="{{ $agentChatContinueUrl }}"
                >
                    I already booked, continue to chat
                </a>
            </div>

            <div class="agent-booking-status" id="agent-booking-status">
                Waiting for booking confirmation...
            </div>
        </div>
    </section>
    <script>
        window.agentBookingConfig = {
            calendlyUrl: @json($calendly_url ?? 'https://calendly.com/admin-wealthskey/30min'),
            unlockApiUrl: @json($unlock_api_url ?? '/agent_chat/booking/confirm'),
            continueUrl: @json($agentChatContinueUrl)
        };
    </script>
@endsection
