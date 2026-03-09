@extends('web.common')

@section('title', 'Talk to Agent')

@push('css')
<link rel="stylesheet" href="/asset/css/web/agent_chat.css?v={{ @filemtime(public_path('asset/css/web/agent_chat.css')) ?: date('YmdHis') }}">
@endpush

@section('content')
@php
    $selectedAgent = null;
@endphp
<div class="agent-chat-container">
    <div class="agent-chat-sidebar">
        <div class="agent-chat-sidebar-header">
            {{ $_page_data['is_agent'] ? 'Agent Inbox (' . count($_page_data['threads']) . ' conversations)' : 'Choose an Agent (' . count($_page_data['agents']) . ' agents)' }}
        </div>
        <div class="agent-chat-list">
            @if($_page_data['is_agent'])
                @foreach($_page_data['threads'] as $thread)
                    <div class="agent-chat-list-item" data-target-type="{{ $thread['target_type'] }}" data-target-id="{{ $thread['target_id'] }}">
                        <div class="agent-name">{{ $thread['label'] }}</div>
                        <div class="agent-meta">{{ $thread['last_message'] }}</div>
                    </div>
                @endforeach
                @if(empty($_page_data['threads']))
                    <div class="agent-chat-empty">No conversations yet.</div>
                @endif
            @else
                @php
                    foreach(($_page_data['agents'] ?? []) as $candidate) {
                        $candidateName = strtolower(trim((string)($candidate['name'] ?? '')));
                        $candidateWebsite = strtolower(trim((string)($candidate['website'] ?? '')));
                        $candidateReg = trim((string)($candidate['registration_num'] ?? ''));

                        $isTargetAgent = (strpos($candidateName, 'wealthskey migration') !== false)
                            || (strpos($candidateWebsite, 'wealthskey.com') !== false)
                            || ($candidateReg === '2418441');

                        if ($isTargetAgent) {
                            $selectedAgent = $candidate;
                            break;
                        }
                    }

                    if ($selectedAgent === null && !empty($_page_data['agents'])) {
                        $selectedAgent = $_page_data['agents'][0];
                    }
                @endphp

                @if(!empty($selectedAgent))
                    <div class="agent-chat-list-item" data-target-type="member" data-target-id="{{ $selectedAgent['id'] }}">
                        <div class="agent-name">Wealthskey Migration</div>
                        <div class="agent-meta">
                            <div>https://wealthskey.com</div>
                            <div>Brisbane, Australia</div>
                            <div>852 54867893</div>
                            <div>Reg #: 2418441</div>
                            <a
                                class="agent-chat-schedule-link"
                                href="https://calendly.com/admin-wealthskey/30min"
                                target="_blank"
                                rel="noopener noreferrer"
                            >
                                Schedule online meeting with agent
                            </a>
                        </div>
                    </div>

                    <div class="agent-chat-promo-section">
                        <div class="agent-chat-promo-item">
                            <div class="agent-name">Rosehill College</div>
                            <div class="agent-meta">
                                <div>https://rosehillcollege.edu.au</div>
                                <div>+61 0272280008</div>
                            </div>
                        </div>

                        <div class="agent-chat-promo-item">
                            <div class="agent-name">Shamrock Migration Services</div>
                            <div class="agent-meta">
                                <div>www.shamrockmigrationservices.com</div>
                                <div>47 watson road beeliar wa 6164</div>
                                <div>0415422254</div>
                            </div>
                        </div>

                        <div class="agent-chat-promo-item">
                            <div class="agent-name">SW Consulting (Asia) Co Ltd</div>
                            <div class="agent-meta">
                                <div>AustralianVisas-Thailand.com</div>
                                <div>5/4 Moo 4, Soi Pasak 8 Cherngtalay, Thalang, phuket, Thailand 83110</div>
                                <div>+66 95 085 3355</div>
                            </div>
                        </div>
                    </div>
                @else
                    <div class="agent-chat-empty">No agents available.</div>
                @endif
            @endif
        </div>
    </div>
    <div class="agent-chat-panel">
        <div class="agent-chat-header">
            <div class="agent-chat-title">{{ $_page_data['is_agent'] ? 'Conversation' : 'Contact an Agent' }}</div>
            <div class="agent-chat-subtitle">Messages stay in this portal — no external apps required.</div>
        </div>
        <div id="agent-chat-messages" class="agent-chat-messages">
            <div class="agent-chat-hint">Select a conversation on the left to start chatting.</div>
        </div>
        <form id="agent-chat-form" class="agent-chat-form" method="post" action="/{{ $_current_lang_code }}/agent_chat/send" enctype="multipart/form-data">
            @csrf
            <label id="agent-chat-file-btn" class="agent-chat-file-btn" for="agent-chat-file">Attach File</label>
            <input id="agent-chat-file" type="file" name="attachment" class="agent-chat-file-input" />
            <div id="agent-chat-file-name" class="agent-chat-file-name"></div>
            <input id="agent-chat-input" name="message" type="text" placeholder="Type your message here..." autocomplete="off">
            <input id="agent-chat-target-type" name="target_type" type="hidden" value="">
            <input id="agent-chat-target-id" name="target_id" type="hidden" value="">
            <button type="submit">Send Message</button>
        </form>
    </div>
</div>

<script>
window.agentChatConfig = {
    isAgent: {{ $_page_data['is_agent'] ? 'true' : 'false' }},
    activeTargetType: {!! json_encode((!$_page_data['is_agent'] && !empty($selectedAgent['id'])) ? 'member' : $_page_data['active_target_type']) !!},
    activeTargetId: {!! json_encode((!$_page_data['is_agent'] && !empty($selectedAgent['id'])) ? (int)$selectedAgent['id'] : $_page_data['active_target_id']) !!},
    langCode: '{{ $_current_lang_code }}'
};
</script>
@endsection

@push('js')
<script src="/asset/js/web/agent_chat.js?v={{ @filemtime(public_path('asset/js/web/agent_chat.js')) ?: date('YmdHis') }}"></script>
@endpush
