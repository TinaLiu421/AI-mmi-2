@extends('web.common')

@section('title', 'Talk to Agent')

@push('css')
<link rel="stylesheet" href="/asset/css/web/agent_chat.css?v={{ date('Ymd') }}">
@endpush

@section('content')
<div class="agent-chat-container">
    <div class="agent-chat-sidebar">
        <div class="agent-chat-sidebar-header">
            {{ $_page_data['is_agent'] ? 'Agent Inbox' : 'Choose an Agent' }}
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
                @foreach($_page_data['agents'] as $agent)
                    <div class="agent-chat-list-item" data-target-type="member" data-target-id="{{ $agent['id'] }}">
                        <div class="agent-name">{{ $agent['name'] }}</div>
                        <div class="agent-meta">
                            @if(!empty($agent['website']))
                                <div>{{ $agent['website'] }}</div>
                            @endif
                            @if(!empty($agent['address']))
                                <div>{{ $agent['address'] }}</div>
                            @endif
                            @if(!empty($agent['phone']))
                                <div>{{ $agent['phone'] }}</div>
                            @endif
                            @if(!empty($agent['registration_num']))
                                <div>Registered Migration Agent Number: {{ $agent['registration_num'] }}</div>
                            @endif
                        </div>
                    </div>
                @endforeach
                @if(empty($_page_data['agents']))
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
        <div id="agent-chat-messages" class="agent-chat-messages"></div>
        <form id="agent-chat-form" class="agent-chat-form">
            <input id="agent-chat-input" type="text" placeholder="Type your message..." autocomplete="off">
            <button type="submit">Send</button>
        </form>
    </div>
</div>

<script>
window.agentChatConfig = {
    isAgent: {{ $_page_data['is_agent'] ? 'true' : 'false' }},
    activeTargetType: {!! json_encode($_page_data['active_target_type']) !!},
    activeTargetId: {!! json_encode($_page_data['active_target_id']) !!}
};
</script>
@endsection

@push('js')
<script src="/asset/js/web/agent_chat.js?v={{ date('Ymd') }}"></script>
@endpush
