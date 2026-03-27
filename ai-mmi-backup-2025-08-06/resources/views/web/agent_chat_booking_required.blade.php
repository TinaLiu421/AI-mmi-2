@extends('web.common')

@section('title', 'Talk to Agent - Book Your Meeting')

@section('content')
    @php
        $pageMode    = $_page_data['mode'] ?? 'free';
        $hasBooked   = !empty($_page_data['has_booked']);
        $calendlyUrl = $_page_data['calendly_url'] ?? 'https://calendly.com/admin-wealthskey/free-users';
        $unlockApiUrl = $_page_data['unlock_api_url'] ?? '/agent_chat/booking/confirm';
        $agentChatContinueUrl = '/' . trim((string)($_current_lang_code ?? ''), '/') . '/agent_chat';
        $agentChatContinueUrl = preg_replace('#/+#', '/', $agentChatContinueUrl);

        $meetingLabel = ($pageMode === 'hybrid') ? '2-hour' : '15-minute';

        $descriptionMap = [
            'free'   => 'Book a complimentary <strong>15-minute consultation</strong> with a qualified migration agent from Wealthskey Migration & Education.',,
            'hybrid' => 'Your <strong>AI + Agent Plan</strong> includes a one-time <strong>2-hour consultation</strong> with a qualified migration agent. After the meeting, the agent will confirm your attendance to complete your consultation.',
        ];
        $description = $descriptionMap[$pageMode] ?? $descriptionMap['free'];

        $bookedMessageMap = [
            'free'   => 'Your 15-minute consultation has been scheduled! This is your one-time free meeting benefit. After scheduling, your quota is used and you may wish to upgrade your plan for continued agent access.',
            'hybrid' => 'Your 2-hour consultation has been scheduled! The Wealthskey agent will confirm your attendance after the meeting. Your AI service continues in the meantime.',
        ];
        $bookedMessage = $bookedMessageMap[$pageMode] ?? $bookedMessageMap['free'];

        $showBookedButton = !$hasBooked;
    @endphp
    <section class="agent-booking-page">
        <div class="agent-booking-card">
            <div class="agent-booking-title">Book Your Agent Meeting</div>
            <div class="agent-booking-desc">{!! $description !!}</div>

            <div class="agent-booking-details">
                <div class="agent-booking-logo-row">
                    <img
                        src="/upload/member_avatar/d148b40e4988fd1cbe690bfc0613dcaf.png"
                        alt="Wealthskey Migration & Education Logo"
                        class="agent-booking-logo"
                    />
                    <div class="agent-name">Wealthskey Migration & Education</div>
                </div>
                <div class="agent-meta">
                    <div>Website : <a href="https://wealthskey.com" target="_blank" rel="noopener noreferrer">https://wealthskey.com</a></div>
                    @if($pageMode === 'hybrid')
                    <div>Location : Australia, Hong Kong</div>
                    @endif
                    <div>Whatsapp Number : +852 97016686</div>
                    <div>Phone Number : +61 413892060</div>
                    @if($pageMode === 'hybrid')
                    <div>Registration number : 2418441</div>
                    @endif
                </div>
            </div>

            @if($hasBooked)
                <div class="agent-booking-status agent-booking-status--booked">
                    <span class="agent-booking-check">✓</span>
                    {!! $bookedMessage !!}
                </div>
            @else
                <div class="agent-booking-actions">
                    <a
                        class="agent-booking-btn primary"
                        id="open-calendly-booking"
                        href="{{ $calendlyUrl }}"
                        target="_blank"
                        rel="noopener noreferrer"
                    >
                        Schedule {{ $meetingLabel }} meeting with agent
                    </a>
                    <button
                        class="agent-booking-btn secondary"
                        id="already-booked-btn"
                        type="button"
                    >
                        I have already booked my meeting
                    </button>
                </div>
                <div class="agent-booking-status" id="agent-booking-status"></div>
            @endif
        </div>
    </section>
    <script>
        window.agentBookingConfig = {
            mode: @json($pageMode),
            calendlyUrl: @json($calendlyUrl),
            unlockApiUrl: @json($unlockApiUrl),
            continueUrl: @json($agentChatContinueUrl),
            hasBooked: @json($hasBooked)
        };
    </script>
@endsection
