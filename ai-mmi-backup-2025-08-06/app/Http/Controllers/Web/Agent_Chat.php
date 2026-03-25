<?php
namespace App\Http\Controllers\Web;

use App\Http\Controllers\WebController;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class Agent_Chat extends WebController
{
    private function requireBookingUnlockIfNeeded(?int $memberId, bool $isAgent, string $responseMode = 'json')
    {
        if ($isAgent || !$memberId) {
            return null;
        }

        if ($this->hasUnlockedAgentChat($memberId)) {
            return null;
        }

        if ($responseMode === 'redirect') {
            return null;
        }

        return response()->json([
            'ok' => false,
            'message' => 'Please schedule a meeting with Wealthskey Migration first to unlock Talk to Agent chat.',
            'booking_required' => true,
            'redirect' => $this->toURL('agent_chat'),
        ], 403);
    }

    private function requireMemberAuth(string $responseMode = 'json')
    {
        if (!empty($this->_current_member['id'])) {
            return null;
        }

        if ($responseMode === 'redirect') {
            return $this->doRedirect($this->toURL('account_login'));
        }

        return response()->json(['ok' => false, 'message' => 'Please register or log in before using AI chat.'], 401);
    }

    public function index($targetId = null)
    {
        $authResponse = $this->requireMemberAuth('redirect');
        if ($authResponse !== null) {
            return $authResponse;
        }

        $actor = $this->resolveActor();
        $member = $this->_current_member ?: null;
        $memberId = $member['id'] ?? null;
        $isAgent = $memberId ? $this->isAgentMember($memberId) : false;

        // Debug logging
        \Illuminate\Support\Facades\Log::info('Agent_chat.index', [
            'memberId' => $memberId,
            'isAgent' => $isAgent,
            'member_email' => $member['email'] ?? 'none',
            'member_type' => $member['type'] ?? 'none'
        ]);

        if (!$isAgent && $memberId) {
            $planCode = $this->getMemberActivePlanCode((int)$memberId);

            // DIY Plan (premium) or VIP Agent Plan → full agent chat access
            if (in_array($planCode, ['premium', 'vip'], true)) {
                return $this->renderChatPage($targetId);
            }

            // AI + Agent Plan (hybrid) → Calendly booking page only (no chat)
            if ($planCode === 'hybrid') {
                $this->pageCss('agent_chat_booking_required');
                $this->pageScript('agent_chat_booking_required');
                $calendlyUrl = (string)env('AGENT_CHAT_CALENDLY_URL', 'https://calendly.com/admin-wealthskey/30min');
                return $this->pageData([
                    'mode'         => 'calendly_only',
                    'calendly_url' => $calendlyUrl,
                ])->pageView('agent_chat_booking_required');
            }

            // Free / AI Smart Plan / no plan → upgrade page
            return $this->doRedirect($this->toURL('upgrade'));
        }

        return $this->renderChatPage($targetId);
    }

    public function chatPage($targetId = null)
    {
        $authResponse = $this->requireMemberAuth('redirect');
        if ($authResponse !== null) {
            return $authResponse;
        }

        $member = $this->_current_member ?: null;
        $memberId = $member['id'] ?? null;
        $isAgent = $memberId ? $this->isAgentMember($memberId) : false;

        if (!$isAgent && $memberId) {
            $planCode = $this->getMemberActivePlanCode((int)$memberId);
            if (!in_array($planCode, ['premium', 'vip'], true)) {
                return $this->doRedirect($this->toURL('agent_chat'));
            }
        }

        return $this->renderChatPage($targetId);
    }

    private function renderChatPage($targetId = null)
    {
        $member = $this->_current_member ?: null;
        $memberId = $member['id'] ?? null;
        $isAgent = $memberId ? $this->isAgentMember($memberId) : false;

        if ($memberId) {
            $this->touchAgentPresence((int)$memberId);
        }

        $this->pageCss('agent_chat');
        $this->pageScript('agent_chat');

        $agents = [];
        $threads = [];
        $activeTargetType = null;
        $activeTargetId = null;

        if ($isAgent) {
            $threads = $this->getAgentThreads($memberId);
            \Illuminate\Support\Facades\Log::info('Agent_chat.threads_loaded', [
                'thread_count' => count($threads),
                'threads' => $threads
            ]);
            if (!empty($targetId)) {
                $activeTargetType = ($targetId === 'guest') ? 'guest' : 'member';
                $activeTargetId = is_numeric($targetId) ? (int)$targetId : null;
            }
            if (empty($activeTargetId) && !empty($threads)) {
                $activeTargetType = $threads[0]['target_type'] ?? 'member';
                $activeTargetId = $threads[0]['target_id'] ?? null;
            }
        } else {
            $agents = $this->getAgents();
            if (!empty($targetId) && is_numeric($targetId)) {
                $activeTargetType = 'member';
                $activeTargetId = (int)$targetId;
            }
            if (empty($activeTargetId) && !empty($agents)) {
                $activeTargetType = 'member';
                $activeTargetId = (int)$agents[0]['id'];
            }
        }

        return $this->pageData([
            'is_agent' => $isAgent,
            'agents' => $agents,
            'threads' => $threads,
            'active_target_type' => $activeTargetType,
            'active_target_id' => $activeTargetId,
        ])->pageView('agent_chat');
    }

    public function threads()
    {
        $authResponse = $this->requireMemberAuth('json');
        if ($authResponse !== null) {
            return $authResponse;
        }

        $member = $this->_current_member ?: null;
        $memberId = $member['id'] ?? null;
        if (!$memberId || !$this->isAgentMember($memberId)) {
            return response()->json(['ok' => false, 'message' => 'Unauthorized'], 401);
        }

        $this->touchAgentPresence((int)$memberId);

        $threads = $this->getAgentThreads($memberId);
        return response()->json(['ok' => true, 'threads' => $threads]);
    }

    public function notifications()
    {
        $authResponse = $this->requireMemberAuth('json');
        if ($authResponse !== null) {
            return $authResponse;
        }

        $member = $this->_current_member ?: null;
        $memberId = (int)($member['id'] ?? 0);
        if ($memberId <= 0) {
            return response()->json(['ok' => false, 'message' => 'Unauthorized'], 401);
        }

        $isAgent = $this->isAgentMember($memberId);
        $canUseAgentHomeLayout = $isAgent && $this->canUseAgentHomeLayout($memberId);
        $threads = $canUseAgentHomeLayout ? $this->getAgentThreads($memberId) : $this->getMemberThreads($memberId);

        $unreadThreads = array_values(array_filter($threads, function ($thread) {
            return (int)($thread['unread_count'] ?? 0) > 0;
        }));

        $totalUnread = array_reduce($unreadThreads, function ($carry, $thread) {
            return $carry + (int)($thread['unread_count'] ?? 0);
        }, 0);

        $chatUrl = $this->toURL('agent_chat/chat');
        $paidCustomers = $canUseAgentHomeLayout ? $this->getPaidCustomersForAgentHome() : [];

        return response()->json([
            'ok' => true,
            'is_agent' => $canUseAgentHomeLayout,
            'total_unread' => $totalUnread,
            'paid_customers' => $paidCustomers,
            'threads' => array_map(function ($thread) use ($chatUrl) {
                $thread['chat_url'] = $chatUrl;
                return $thread;
            }, array_slice($unreadThreads, 0, 8)),
        ]);
    }

    public function availability($agentId)
    {
        $authResponse = $this->requireMemberAuth('json');
        if ($authResponse !== null) {
            return $authResponse;
        }

        $agentId = is_numeric($agentId) ? (int)$agentId : 0;
        if ($agentId <= 0) {
            return response()->json(['ok' => false, 'message' => 'Invalid agent'], 422);
        }

        $memberExists = DB::table('member')
            ->where('id', $agentId)
            ->where('status', '>', 0)
            ->exists();

        if (!$memberExists) {
            return response()->json(['ok' => false, 'message' => 'Invalid agent'], 404);
        }

        $presence = $this->getAgentPresence($agentId);

        return response()->json([
            'ok' => true,
            'agent_id' => $agentId,
            'online' => !empty($presence['online']),
            'last_seen_at' => $presence['last_seen_at'] ?? null,
            'seconds_ago' => $presence['seconds_ago'] ?? null,
        ]);
    }

    public function messages($targetType = null, $targetId = null)
    {
        $authResponse = $this->requireMemberAuth('json');
        if ($authResponse !== null) {
            return $authResponse;
        }

        $actor = $this->resolveActor();
        if ($actor['type'] === 'member') {
            $memberId = (int)($actor['id'] ?? 0);
            $isAgent = $this->isAgentMember($memberId);
            $bookingLockResponse = $this->requireBookingUnlockIfNeeded($memberId, $isAgent, 'json');
            if ($bookingLockResponse !== null) {
                return $bookingLockResponse;
            }
        }

        if (empty($targetType) || empty($targetId)) {
            return response()->json(['ok' => false, 'message' => 'Invalid target'], 422);
        }

        if (($actor['type'] ?? '') === 'member') {
            $actorMemberId = (int)($actor['id'] ?? 0);
            if ($actorMemberId > 0) {
                $this->touchAgentPresence($actorMemberId);
            }
        }

        $targetType = strtolower((string)$targetType);
        $targetId = is_numeric($targetId) ? (int)$targetId : (string)$targetId;

        $query = DB::table('agent_chat_messages');
        if ($actor['type'] === 'member') {
            $memberId = (int)$actor['id'];
            $actorIsAgent = $this->isAgentMember($memberId);
            if ($targetType === 'member') {
                if (!$actorIsAgent && !$this->isAllowedPublicAgentId((int)$targetId)) {
                    return response()->json(['ok' => false, 'message' => 'Invalid target'], 403);
                }
                $query->where(function ($q) use ($memberId, $targetId) {
                    $q->where('sender_member_id', $memberId)
                        ->where('receiver_member_id', $targetId);
                })->orWhere(function ($q) use ($memberId, $targetId) {
                    $q->where('sender_member_id', $targetId)
                        ->where('receiver_member_id', $memberId);
                });
            } elseif ($targetType === 'guest') {
                if (!$actorIsAgent) {
                    return response()->json(['ok' => false, 'message' => 'Invalid target'], 403);
                }
                $query->where(function ($q) use ($memberId, $targetId) {
                    $q->where('sender_member_id', $memberId)
                        ->where('receiver_guest_id', $targetId);
                })->orWhere(function ($q) use ($memberId, $targetId) {
                    $q->where('sender_guest_id', $targetId)
                        ->where('receiver_member_id', $memberId);
                });
            } else {
                return response()->json(['ok' => false, 'message' => 'Invalid target'], 422);
            }
        } else {
            $guestId = (string)$actor['id'];
            if ($targetType !== 'member') {
                return response()->json(['ok' => false, 'message' => 'Invalid target'], 422);
            }
            $query->where(function ($q) use ($guestId, $targetId) {
                $q->where('sender_guest_id', $guestId)
                    ->where('receiver_member_id', $targetId);
            })->orWhere(function ($q) use ($guestId, $targetId) {
                $q->where('sender_member_id', $targetId)
                    ->where('receiver_guest_id', $guestId);
            });
        }

        $rows = $query->orderBy('created_at', 'asc')->limit(200)->get();
        $this->ensureAttachmentTableExists();

        $messageIds = $rows->map(function ($row) {
            return (int)$row->id;
        })->values()->toArray();

        $attachmentMap = [];
        $langPrefix = '';
        $currentLang = trim((string)request()->segment(1));
        if ($currentLang !== '' && preg_match('/^[a-zA-Z_\-]+$/', $currentLang)) {
            $langPrefix = '/' . $currentLang;
        }

        if (!empty($messageIds)) {
            $attachments = DB::table('agent_chat_attachments')
                ->whereIn('message_id', $messageIds)
                ->orderBy('id', 'asc')
                ->get();

            foreach ($attachments as $attachment) {
                $messageId = (int)$attachment->message_id;
                if (!isset($attachmentMap[$messageId])) {
                    $attachmentMap[$messageId] = [];
                }

                $attachmentMap[$messageId][] = [
                    'id' => (int)$attachment->id,
                    'file_name' => (string)$attachment->original_name,
                    'file_size' => (int)$attachment->file_size,
                    'download_url' => $langPrefix . '/agent_chat/attachment/' . (int)$attachment->id,
                ];
            }
        }

        $messages = $rows->map(function ($row) use ($actor, $attachmentMap) {
            $isMine = ($actor['type'] === 'member' && (int)$row->sender_member_id === (int)$actor['id'])
                || ($actor['type'] === 'guest' && (string)$row->sender_guest_id === (string)$actor['id']);

            $messageId = (int)$row->id;
            $attachments = $attachmentMap[$messageId] ?? [];
            $messageText = trim((string)$row->message);
            if ($messageText === '' && !empty($attachments)) {
                $messageText = 'Attachment';
            }

            return [
                'id' => $messageId,
                'message' => $messageText,
                'created_at' => $row->created_at,
                'is_mine' => $isMine,
                'attachments' => $attachments,
            ];
        })->toArray();

        if ($actor['type'] === 'member') {
            $actorMemberId = (int)($actor['id'] ?? 0);
            $lastIncomingMessageId = 0;
            foreach ($rows as $row) {
                $isIncoming = ((int)$row->receiver_member_id === $actorMemberId)
                    && ((int)$row->sender_member_id !== $actorMemberId);
                if ($isIncoming) {
                    $lastIncomingMessageId = max($lastIncomingMessageId, (int)$row->id);
                }
            }

            if ($actorMemberId > 0 && $lastIncomingMessageId > 0) {
                $this->markAgentThreadAsRead($actorMemberId, $targetType, $targetId, $lastIncomingMessageId);
            }
        }

        return response()->json(['ok' => true, 'messages' => $messages]);
    }

    public function send()
    {
        $authResponse = $this->requireMemberAuth('json');
        if ($authResponse !== null) {
            return $authResponse;
        }

        if (app()->environment('local')) {
            $this->handleSend();
            return;
        }

        $this->pageAction(function () {
            $this->handleSend();
        });

        if (request()->isMethod('post')) {
            return;
        }

        return abort(405);
    }

    public function bookingConfirm()
    {
        $authResponse = $this->requireMemberAuth('json');
        if ($authResponse !== null) {
            return $authResponse;
        }

        $memberId = (int)($this->_current_member['id'] ?? 0);
        if ($memberId <= 0) {
            return response()->json(['ok' => false, 'message' => 'Unauthorized'], 401);
        }

        if ($this->isAgentMember($memberId)) {
            return response()->json(['ok' => true, 'unlocked' => true]);
        }

        $source = strtolower(trim((string)request()->input('source', '')));
        if ($source === '') {
            $source = 'manual_continue';
        }

        $scheduleClickSessionKey = 'agent_chat_schedule_clicked_' . $memberId;

        $alreadyUnlocked = $this->hasUnlockedAgentChat($memberId);

        if ($source === 'manual_continue' && !$alreadyUnlocked && !session()->get($scheduleClickSessionKey, false)) {
            return response()->json([
                'ok' => false,
                'unlocked' => false,
                'require_schedule_click' => true,
                'message' => 'Please click "Schedule meeting with agent" first before continuing to chat.',
            ], 422);
        }

        $this->ensureAgentChatBookingTableExists();

        $payload = [
            'member_id' => $memberId,
            'status' => 'booked',
            'calendly_event_uri' => trim((string)request()->input('event_uri', '')) ?: null,
            'calendly_invitee_uri' => trim((string)request()->input('invitee_uri', '')) ?: null,
            'booked_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ];

        $existingId = DB::table('agent_chat_meeting_bookings')
            ->where('member_id', $memberId)
            ->value('id');

        if ($existingId) {
            DB::table('agent_chat_meeting_bookings')
                ->where('id', (int)$existingId)
                ->update([
                    'status' => 'booked',
                    'calendly_event_uri' => $payload['calendly_event_uri'],
                    'calendly_invitee_uri' => $payload['calendly_invitee_uri'],
                    'booked_at' => $payload['booked_at'],
                    'updated_at' => $payload['updated_at'],
                ]);
        } else {
            DB::table('agent_chat_meeting_bookings')->insert($payload);
        }

        session()->forget($scheduleClickSessionKey);

        if ($source === 'schedule_click') {
            return response()->json([
                'ok' => true,
                'unlocked' => true,
                'schedule_clicked' => true,
                'message' => 'Schedule click recorded. You can continue to chat now and on your next visit.',
            ]);
        }

        return response()->json(['ok' => true, 'unlocked' => true]);
    }

    public function downloadAttachment($attachmentId, $maybeAttachmentId = null)
    {
        $authResponse = $this->requireMemberAuth('json');
        if ($authResponse !== null) {
            return $authResponse;
        }

        if ($maybeAttachmentId !== null) {
            $attachmentId = $maybeAttachmentId;
        }

        $attachmentId = is_numeric($attachmentId) ? (int)$attachmentId : 0;
        if ($attachmentId <= 0) {
            return response()->json(['ok' => false, 'message' => 'Invalid attachment'], 422);
        }

        $this->ensureAttachmentTableExists();

        $row = DB::table('agent_chat_attachments as a')
            ->join('agent_chat_messages as m', 'm.id', '=', 'a.message_id')
            ->where('a.id', $attachmentId)
            ->select([
                'a.id',
                'a.original_name',
                'a.mime_type',
                'a.relative_path',
                'm.sender_member_id',
                'm.sender_guest_id',
                'm.receiver_member_id',
                'm.receiver_guest_id',
            ])
            ->first();

        if (!$row) {
            return response()->json(['ok' => false, 'message' => 'Attachment not found'], 404);
        }

        $actor = $this->resolveActor();
        if (!$this->canActorAccessMessage($actor, $row)) {
            return response()->json(['ok' => false, 'message' => 'Unauthorized'], 403);
        }

        $relativePath = trim((string)$row->relative_path, '/');
        $absolutePath = public_path($relativePath);
        $root = realpath(public_path('upload/agent_chat'));
        $realFile = file_exists($absolutePath) ? realpath($absolutePath) : false;

        if (!$root || !$realFile || strpos($realFile, $root) !== 0 || !file_exists($realFile)) {
            return response()->json(['ok' => false, 'message' => 'File not found'], 404);
        }

        return response()->download(
            $realFile,
            (string)$row->original_name,
            ['Content-Type' => (string)($row->mime_type ?: 'application/octet-stream')]
        );
    }

    private function handleSend(): void
    {
        $actor = $this->resolveActor();
        if ($actor['type'] === 'member') {
            $memberId = (int)($actor['id'] ?? 0);
            $isAgent = $this->isAgentMember($memberId);
            $bookingLockResponse = $this->requireBookingUnlockIfNeeded($memberId, $isAgent, 'json');
            if ($bookingLockResponse !== null) {
                $this->pageResult([
                    'status' => 403,
                    'message' => 'Please schedule a meeting with Wealthskey Migration first to unlock Talk to Agent chat.',
                    'booking_required' => true,
                    'redirect' => $this->toURL('agent_chat'),
                ], true);
                return;
            }
        }

        $targetType = strtolower((string)$this->postParamValue('target_type', ''));
        $targetId = $this->postParamValue('target_id', '');
        $message = trim((string)$this->postParamValue('message', ''));
        $attachment = request()->file('attachment');

        \Illuminate\Support\Facades\Log::info('Agent_chat.handleSend', [
            'targetType' => $targetType,
            'targetId' => $targetId,
            'message_length' => strlen($message),
            'actor_type' => $actor['type'] ?? 'unknown',
            'has_attachment' => $attachment ? true : false,
        ]);

        if (($targetType === '' || $targetId === '') && $actor['type'] === 'member') {
            $senderMemberId = (int)($actor['id'] ?? 0);
            if ($senderMemberId > 0 && !$this->isAgentMember($senderMemberId)) {
                $availableAgents = $this->getAgents();
                if (!empty($availableAgents[0]['id'])) {
                    $targetType = 'member';
                    $targetId = (string)((int)$availableAgents[0]['id']);
                }
            }
        }

        if (($message === '' && !$attachment) || $targetType === '' || $targetId === '') {
            \Illuminate\Support\Facades\Log::warning('Agent_chat.send_failed', [
                'reason' => 'invalid_payload',
                'targetType' => $targetType,
                'targetId' => $targetId,
                'has_message' => !empty($message),
                'has_attachment' => $attachment ? true : false,
            ]);
            $this->pageResult(['status' => 422, 'message' => 'Invalid payload'], true);
            return;
        }

        if ($attachment instanceof UploadedFile) {
            $attachmentError = $this->validateAttachment($attachment);
            if ($attachmentError !== null) {
                $this->pageResult(['status' => 422, 'message' => $attachmentError], true);
                return;
            }
        }

        $targetId = is_numeric($targetId) ? (int)$targetId : (string)$targetId;

        if ($message === '' && $attachment instanceof UploadedFile) {
            $message = '📎 ' . $attachment->getClientOriginalName();
        }

        $payload = [
            'sender_member_id' => null,
            'sender_guest_id' => null,
            'receiver_member_id' => null,
            'receiver_guest_id' => null,
            'message' => $message,
            'created_at' => now(),
            'updated_at' => now(),
        ];

        if ($actor['type'] === 'member') {
            $payload['sender_member_id'] = (int)$actor['id'];
            $actorIsAgent = $this->isAgentMember((int)$actor['id']);
            if ($targetType === 'member') {
                if (!$actorIsAgent && !$this->isAllowedPublicAgentId((int)$targetId)) {
                    $this->pageResult(['status' => 403, 'message' => 'Invalid target'], true);
                    return;
                }
                $payload['receiver_member_id'] = (int)$targetId;
            } elseif ($targetType === 'guest') {
                if (!$actorIsAgent) {
                    $this->pageResult(['status' => 403, 'message' => 'Invalid target'], true);
                    return;
                }
                $payload['receiver_guest_id'] = (string)$targetId;
            } else {
                $this->pageResult(['status' => 422, 'message' => 'Invalid target'], true);
                return;
            }
        } else {
            if ($targetType !== 'member') {
                $this->pageResult(['status' => 422, 'message' => 'Invalid target'], true);
                return;
            }
            $payload['sender_guest_id'] = (string)$actor['id'];
            $payload['receiver_member_id'] = (int)$targetId;
        }

        try {
            DB::beginTransaction();
            $messageId = (int)DB::table('agent_chat_messages')->insertGetId($payload);

            if ($attachment instanceof UploadedFile) {
                $this->ensureAttachmentTableExists();
                $stored = $this->storeAttachmentFile($attachment);
                DB::table('agent_chat_attachments')->insert([
                    'message_id' => $messageId,
                    'stored_name' => $stored['stored_name'],
                    'original_name' => $stored['original_name'],
                    'mime_type' => $stored['mime_type'],
                    'file_size' => $stored['file_size'],
                    'relative_path' => $stored['relative_path'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            DB::commit();

            $this->notifyAgentOfNewMemberMessage($payload, $actor, $message, $attachment instanceof UploadedFile);

            \Illuminate\Support\Facades\Log::info('Agent_chat.send_success', [
                'targetType' => $targetType,
                'targetId' => $targetId,
                'has_attachment' => $attachment ? true : false,
            ]);
            $this->pageResult(['status' => 200, 'message' => 'ok'], true);
        } catch (\Exception $e) {
            DB::rollBack();
            \Illuminate\Support\Facades\Log::error('Agent_chat.send_error', [
                'error' => $e->getMessage(),
                'targetType' => $targetType,
                'targetId' => $targetId
            ]);
            $this->pageResult(['status' => 500, 'message' => 'Database error: ' . $e->getMessage()], true);
        }
    }

    private function resolveActor(): array
    {
        $member = $this->_current_member ?: null;
        if (!empty($member['id'])) {
            return ['type' => 'member', 'id' => (int)$member['id']];
        }

        return ['type' => 'guest', 'id' => ''];
    }

    private function notifyAgentOfNewMemberMessage(array $payload, array $actor, string $message, bool $hasAttachment = false): void
    {
        try {
            if (($actor['type'] ?? '') !== 'member') {
                return;
            }

            $senderMemberId = (int)($payload['sender_member_id'] ?? 0);
            $receiverMemberId = (int)($payload['receiver_member_id'] ?? 0);

            if ($senderMemberId <= 0 || $receiverMemberId <= 0) {
                return;
            }

            if ($this->isAgentMember($senderMemberId)) {
                return;
            }

            if (!$this->isAgentMember($receiverMemberId) || !$this->isAllowedPublicAgentId($receiverMemberId)) {
                return;
            }

            $recipientEmail = $this->resolveAgentNotificationEmail($receiverMemberId);
            if ($recipientEmail === null) {
                \Illuminate\Support\Facades\Log::warning('Agent_chat.notification_skipped_no_recipient', [
                    'receiver_member_id' => $receiverMemberId,
                    'sender_member_id' => $senderMemberId,
                ]);
                return;
            }

            $sender = DB::table('member')
                ->where('id', $senderMemberId)
                ->select(['id', 'full_name', 'alias_name', 'email'])
                ->first();

            $senderName = trim((string)($sender->alias_name ?? $sender->full_name ?? ('Member #' . $senderMemberId)));
            if ($senderName === '') {
                $senderName = 'Member #' . $senderMemberId;
            }

            $senderEmail = trim((string)($sender->email ?? ''));
            $messagePreview = trim($message) !== '' ? trim($message) : 'Attachment only message';
            $messagePreview = mb_substr($messagePreview, 0, 1000, 'UTF-8');
            $chatUrl = rtrim((string)request()->getSchemeAndHttpHost(), '/') . '/en/agent_chat/chat';

            $subject = '[AI-mmi] New Talk to Agent message from ' . $senderName;
            $content = '';
            $content .= '<h2 style="margin:0 0 16px 0;color:#002065;">New Talk to Agent message</h2>';
            $content .= '<p style="margin:0 0 12px 0;">A member has sent a new chat message to Wealthskey Migration.</p>';
            $content .= '<table cellpadding="8" cellspacing="0" border="0" style="border-collapse:collapse;width:100%;max-width:720px;margin:0 0 16px 0;">';
            $content .= '<tr><td style="width:180px;font-weight:bold;border:1px solid #d9e2f3;">Sender name</td><td style="border:1px solid #d9e2f3;">' . htmlspecialchars($senderName, ENT_QUOTES, 'UTF-8') . '</td></tr>';
            $content .= '<tr><td style="font-weight:bold;border:1px solid #d9e2f3;">Sender email</td><td style="border:1px solid #d9e2f3;">' . htmlspecialchars($senderEmail !== '' ? $senderEmail : '-', ENT_QUOTES, 'UTF-8') . '</td></tr>';
            $content .= '<tr><td style="font-weight:bold;border:1px solid #d9e2f3;">Attachment</td><td style="border:1px solid #d9e2f3;">' . ($hasAttachment ? 'Yes' : 'No') . '</td></tr>';
            $content .= '<tr><td style="font-weight:bold;border:1px solid #d9e2f3;">Received at</td><td style="border:1px solid #d9e2f3;">' . now()->format('Y-m-d H:i:s') . '</td></tr>';
            $content .= '</table>';
            $content .= '<div style="margin:0 0 16px 0;">';
            $content .= '<div style="font-weight:bold;margin:0 0 8px 0;">Message preview</div>';
            $content .= '<div style="padding:12px;border:1px solid #d9e2f3;border-radius:8px;background:#f8fbff;white-space:pre-wrap;">' . nl2br(htmlspecialchars($messagePreview, ENT_QUOTES, 'UTF-8')) . '</div>';
            $content .= '</div>';
            $content .= '<div style="margin-top:18px;">';
            $content .= '<a href="' . htmlspecialchars($chatUrl, ENT_QUOTES, 'UTF-8') . '" style="display:inline-block;padding:12px 18px;background:#0f766e;color:#ffffff;text-decoration:none;border-radius:8px;font-weight:bold;">Open Talk to Agent</a>';
            $content .= '</div>';

            $delivery = $this->sendAgentNotificationEmail($recipientEmail, $subject, $content);
            $sent = (bool)($delivery['sent'] ?? false);
            $channel = (string)($delivery['channel'] ?? 'unknown');
            $error = (string)($delivery['error'] ?? '');

            if ($sent) {
                \Illuminate\Support\Facades\Log::info('Agent_chat.notification_sent', [
                    'recipient' => $recipientEmail,
                    'receiver_member_id' => $receiverMemberId,
                    'sender_member_id' => $senderMemberId,
                    'subject' => $subject,
                    'channel' => $channel,
                ]);
                return;
            }

            \Illuminate\Support\Facades\Log::warning('Agent_chat.notification_failed', [
                'recipient' => $recipientEmail,
                'receiver_member_id' => $receiverMemberId,
                'sender_member_id' => $senderMemberId,
                'subject' => $subject,
                'channel' => $channel,
                'error' => $error,
            ]);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Agent_chat.notification_exception', [
                'error' => $e->getMessage(),
                'sender_member_id' => (int)($payload['sender_member_id'] ?? 0),
                'receiver_member_id' => (int)($payload['receiver_member_id'] ?? 0),
            ]);
        }
    }

    private function resolveAgentNotificationEmail(int $receiverMemberId): ?string
    {
        $overrideEmail = trim((string)env('AGENT_CHAT_NOTIFY_EMAIL', app()->environment('local') ? 'poonkenith@gmail.com' : ''));
        if ($overrideEmail !== '' && filter_var($overrideEmail, FILTER_VALIDATE_EMAIL)) {
            return $overrideEmail;
        }

        $member = DB::table('member')
            ->where('id', $receiverMemberId)
            ->select(['email'])
            ->first();

        $email = trim((string)($member->email ?? ''));
        return filter_var($email, FILTER_VALIDATE_EMAIL) ? $email : null;
    }

    private function sendAgentNotificationEmail(string $recipientEmail, string $subject, string $content): array
    {
        if (getenv('SENDGRID_API_KEY')) {
            $sent = (bool)$this->sendEmail($recipientEmail, $subject, $content);
            return [
                'sent' => $sent,
                'channel' => 'sendgrid',
                'error' => $sent ? '' : 'sendgrid_send_failed',
            ];
        }

        try {
            $fromAddress = (string)(config('mail.from.address') ?: 'no-reply@wealthskey.com');
            $fromName = (string)(config('mail.from.name') ?: 'AI-mmi');

            \Illuminate\Support\Facades\Mail::send([], [], function ($mail) use ($recipientEmail, $subject, $content, $fromAddress, $fromName) {
                $mail->to($recipientEmail)
                    ->from($fromAddress, $fromName)
                    ->subject($subject)
                    ->setBody($content, 'text/html');
            });

            $failures = \Illuminate\Support\Facades\Mail::failures();
            if (!empty($failures)) {
                return [
                    'sent' => false,
                    'channel' => 'smtp',
                    'error' => 'mail_failures:' . implode(',', $failures),
                ];
            }

            return [
                'sent' => true,
                'channel' => 'smtp',
                'error' => '',
            ];
        } catch (\Throwable $e) {
            return [
                'sent' => false,
                'channel' => 'smtp',
                'error' => $e->getMessage(),
            ];
        }
    }

    private function isAgentMember(int $memberId): bool
    {
        $isExplicitAgent = DB::table('member_agent')
            ->where('member_id', $memberId)
            ->where('status', '>', 0)
            ->exists();

        if ($isExplicitAgent) {
            return true;
        }

        return DB::table('member')
            ->where('id', $memberId)
            ->whereIn('type', [2, 3])
            ->where('status', '>', 0)
            ->exists();
    }

    private function canUseAgentHomeLayout(int $memberId): bool
    {
        if ($memberId <= 0) {
            return false;
        }

        $email = DB::table('member')
            ->where('id', $memberId)
            ->value('email');

        $email = mb_strtolower(trim((string)$email), 'UTF-8');
        return in_array($email, ['admin@wealthskey.com', 'info@ai-mmi.com'], true);
    }

    private function getAgents(): array
    {
        $rows = DB::table('member as m')
            ->leftJoin('member_agent as a', 'm.id', '=', 'a.member_id')
            ->leftJoin('member_details as d', 'm.id', '=', 'd.member_id')
            ->where('m.status', '>', 0)
            ->where(function ($q) {
                $q->where('a.status', '>', 0)
                  ->orWhereIn('m.type', [2, 3]);
            })
            ->select([
                'm.id',
                'm.alias_name',
                'm.full_name',
                'm.telephone_code',
                'm.telephone_num',
                'd.company_name',
                'd.company_website',
                'd.company_address',
                'a.registration_num',
                'a.registration_country',
            ])
            ->orderBy('d.company_name', 'asc')
            ->orderBy('m.alias_name', 'asc')
            ->get();

        return $rows->map(function ($row) {
            // Clean up phone number
            $phone = trim((string)$row->telephone_num);
            if (!empty($phone) && !empty($row->telephone_code)) {
                $phone = trim($row->telephone_code) . ' ' . $phone;
            }
            // Get best name available
            $name = trim((string)($row->company_name ?: ($row->alias_name ?: $row->full_name)));

            return [
                'id' => (int)$row->id,
                'name' => $name,
                'website' => trim((string)$row->company_website) ?: null,
                'address' => trim((string)$row->company_address) ?: null,
                'phone' => $phone ?: null,
                'registration_num' => trim((string)$row->registration_num) ?: null,
                'registration_country' => trim((string)$row->registration_country) ?: null,
            ];
        })->filter(function ($agent) {
            $companyName = strtolower((string)($agent['name'] ?? ''));
            $website = strtolower((string)($agent['website'] ?? ''));
            $isWealthskey = (strpos($companyName, 'wealthskey migration') !== false)
                || (strpos($website, 'wealthskey.com') !== false);
            if (!$isWealthskey) {
                return false;
            }

            // Filter out agents without a valid name
            if (empty($agent['name'])) {
                return false;
            }
            
            // Filter out agents with no contact information at all
            $hasContactInfo = !empty($agent['website']) || 
                            !empty($agent['address']) || 
                            !empty($agent['phone']) || 
                            !empty($agent['registration_num']);
            
            return $hasContactInfo;
        })->values()->toArray();
    }

    private function isAllowedPublicAgentId(int $memberId): bool
    {
        if ($memberId <= 0) {
            return false;
        }

        return DB::table('member as m')
            ->leftJoin('member_details as d', 'm.id', '=', 'd.member_id')
            ->where('m.id', $memberId)
            ->where('m.status', '>', 0)
            ->where(function ($q) {
                                $q->where('d.company_name', 'like', '%Wealthskey Migration%')
                                    ->orWhere('d.company_website', 'like', '%wealthskey.com%');
            })
            ->exists();
    }

    private function getAgentThreads(int $agentId): array
    {
        $readMarkers = $this->getAgentThreadReadMarkers($agentId);

        $rows = DB::table('agent_chat_messages')
            ->where('sender_member_id', $agentId)
            ->orWhere('receiver_member_id', $agentId)
            ->orderByDesc('created_at')
            ->limit(200)
            ->get();

        $threads = [];
        foreach ($rows as $row) {
            $targetType = null;
            $targetId = null;
            if ((int)$row->sender_member_id === $agentId) {
                if (!empty($row->receiver_member_id)) {
                    $targetType = 'member';
                    $targetId = (int)$row->receiver_member_id;
                } elseif (!empty($row->receiver_guest_id)) {
                    $targetType = 'guest';
                    $targetId = (string)$row->receiver_guest_id;
                }
            } else {
                if (!empty($row->sender_member_id)) {
                    $targetType = 'member';
                    $targetId = (int)$row->sender_member_id;
                } elseif (!empty($row->sender_guest_id)) {
                    $targetType = 'guest';
                    $targetId = (string)$row->sender_guest_id;
                }
            }

            if (!$targetType || $targetId === null) {
                continue;
            }

            $key = $targetType . ':' . $targetId;
            $lastReadMessageId = (int)($readMarkers[$key] ?? 0);
            if (!isset($threads[$key])) {
                $threads[$key] = [
                    'target_type' => $targetType,
                    'target_id' => $targetId,
                    'label' => $this->resolveThreadLabel($targetType, $targetId),
                    'last_message' => $row->message,
                    'last_at' => $row->created_at,
                    'unread_count' => 0,
                ];
            }

            $isIncomingForAgent = ((int)$row->receiver_member_id === $agentId) && ((int)$row->sender_member_id !== $agentId);
            if ($isIncomingForAgent && (int)$row->id > $lastReadMessageId) {
                $threads[$key]['unread_count']++;
            }
        }

        $threadList = array_values($threads);
        usort($threadList, function ($a, $b) {
            return strtotime((string)($b['last_at'] ?? '')) <=> strtotime((string)($a['last_at'] ?? ''));
        });

        // Bulk-load plan names for member threads (shown in agent sidebar)
        $memberIds = array_values(array_unique(
            array_map(fn($t) => (int)$t['target_id'],
                array_filter($threadList, fn($t) => $t['target_type'] === 'member')
            )
        ));
        $planNames = [];
        if (!empty($memberIds)) {
            try {
                $planQuery = DB::table('subscriptions')
                    ->join('plans', 'plans.id', '=', 'subscriptions.plan_id')
                    ->whereIn('subscriptions.member_id', $memberIds)
                    ->where('subscriptions.status', 'active')
                    ->where(function ($q) {
                        $q->whereNull('subscriptions.ends_at')
                          ->orWhere('subscriptions.ends_at', '>', now());
                    });

                if (Schema::hasColumn('plans', 'is_active')) {
                    $planQuery->where('plans.is_active', 1);
                }

                $planRows = $planQuery
                    ->orderByRaw("CASE plans.code WHEN 'vip' THEN 1 WHEN 'premium' THEN 2 WHEN 'hybrid' THEN 3 WHEN 'all_ai' THEN 4 WHEN 'free' THEN 5 ELSE 99 END")
                    ->select('subscriptions.member_id', 'plans.name')
                    ->get();

                foreach ($planRows as $pr) {
                    $mid = (int)$pr->member_id;
                    if (!isset($planNames[$mid])) {
                        $planNames[$mid] = $pr->name;
                    }
                }
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning('Agent_chat.plan_names_lookup_failed', [
                    'error' => $e->getMessage(),
                ]);
            }
        }
        foreach ($threadList as &$thread) {
            $thread['plan_name'] = ($thread['target_type'] === 'member')
                ? ($planNames[(int)$thread['target_id']] ?? null)
                : null;
        }
        unset($thread);

        return $threadList;
    }

    private function getPaidCustomersForAgentHome(): array
    {
        try {
            $rows = DB::table('subscriptions')
                ->join('plans', 'plans.id', '=', 'subscriptions.plan_id')
                ->join('member', 'member.id', '=', 'subscriptions.member_id')
                ->where('subscriptions.status', 'active')
                ->where('plans.code', '!=', 'free')
                ->where(function ($q) {
                    $q->whereNull('subscriptions.ends_at')
                      ->orWhere('subscriptions.ends_at', '>', now());
                })
                ->orderByDesc('subscriptions.updated_at')
                ->limit(50)
                ->select([
                    'subscriptions.member_id',
                    'plans.name as plan_name',
                    'plans.code as plan_code',
                    'member.alias_name',
                    'member.full_name',
                    'member.email',
                ])
                ->get();

            $result = [];
            $seenMember = [];
            foreach ($rows as $row) {
                $memberId = (int)($row->member_id ?? 0);
                if ($memberId <= 0 || isset($seenMember[$memberId])) {
                    continue;
                }
                $seenMember[$memberId] = true;

                $name = trim((string)($row->alias_name ?: $row->full_name ?: ('Member #' . $memberId)));
                $result[] = [
                    'member_id' => $memberId,
                    'name' => $name,
                    'email' => (string)($row->email ?? ''),
                    'plan_name' => (string)($row->plan_name ?? ''),
                    'plan_code' => (string)($row->plan_code ?? ''),
                ];
            }

            return array_slice($result, 0, 20);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Agent_chat.paid_customers_lookup_failed', [
                'error' => $e->getMessage(),
            ]);
            return [];
        }
    }

    private function getMemberThreads(int $memberId): array
    {
        $readMarkers = $this->getAgentThreadReadMarkers($memberId);

        $rows = DB::table('agent_chat_messages')
            ->where(function ($q) use ($memberId) {
                $q->where('sender_member_id', $memberId)
                    ->orWhere('receiver_member_id', $memberId);
            })
            ->whereNotNull('sender_member_id')
            ->whereNotNull('receiver_member_id')
            ->orderByDesc('created_at')
            ->limit(300)
            ->get();

        $threads = [];
        foreach ($rows as $row) {
            $targetType = 'member';
            $targetId = null;

            if ((int)$row->sender_member_id === $memberId && !empty($row->receiver_member_id)) {
                $targetId = (int)$row->receiver_member_id;
            } elseif ((int)$row->receiver_member_id === $memberId && !empty($row->sender_member_id)) {
                $targetId = (int)$row->sender_member_id;
            }

            if (empty($targetId) || $targetId === $memberId) {
                continue;
            }

            $key = $targetType . ':' . $targetId;
            $lastReadMessageId = (int)($readMarkers[$key] ?? 0);

            if (!isset($threads[$key])) {
                $threads[$key] = [
                    'target_type' => $targetType,
                    'target_id' => $targetId,
                    'label' => $this->resolveThreadLabel($targetType, $targetId),
                    'last_message' => $row->message,
                    'last_at' => $row->created_at,
                    'unread_count' => 0,
                ];
            }

            $isIncomingForMember = ((int)$row->receiver_member_id === $memberId)
                && ((int)$row->sender_member_id !== $memberId);

            if ($isIncomingForMember && (int)$row->id > $lastReadMessageId) {
                $threads[$key]['unread_count']++;
            }
        }

        $threadList = array_values($threads);
        usort($threadList, function ($a, $b) {
            return strtotime((string)($b['last_at'] ?? '')) <=> strtotime((string)($a['last_at'] ?? ''));
        });

        return $threadList;
    }

    private function markAgentThreadAsRead(int $agentId, string $targetType, $targetId, int $messageId): void
    {
        if ($agentId <= 0 || $messageId <= 0) {
            return;
        }

        $threadKey = $this->threadKey($targetType, $targetId);
        $readMarkers = $this->getAgentThreadReadMarkers($agentId);
        $current = (int)($readMarkers[$threadKey] ?? 0);
        if ($messageId > $current) {
            $readMarkers[$threadKey] = $messageId;
            session()->put($this->threadReadSessionKey($agentId), $readMarkers);
            session()->save();
        }
    }

    private function getAgentThreadReadMarkers(int $agentId): array
    {
        if ($agentId <= 0) {
            return [];
        }

        $readMarkers = session()->get($this->threadReadSessionKey($agentId), []);
        return is_array($readMarkers) ? $readMarkers : [];
    }

    private function threadReadSessionKey(int $agentId): string
    {
        return 'agent_chat_thread_read_markers_' . $agentId;
    }

    private function threadKey(string $targetType, $targetId): string
    {
        return strtolower(trim($targetType)) . ':' . (is_numeric($targetId) ? (int)$targetId : (string)$targetId);
    }

    private function touchAgentPresence(int $agentId): void
    {
        if ($agentId <= 0) {
            return;
        }

        Cache::put($this->agentPresenceCacheKey($agentId), time(), now()->addMinutes(15));
    }

    private function getAgentPresence(int $agentId): array
    {
        $lastSeenTs = (int)Cache::get($this->agentPresenceCacheKey($agentId), 0);
        if ($lastSeenTs <= 0) {
            return [
                'online' => false,
                'last_seen_at' => null,
                'seconds_ago' => null,
            ];
        }

        $secondsAgo = max(0, time() - $lastSeenTs);
        $onlineWindow = (int)env('AGENT_CHAT_ONLINE_WINDOW_SECONDS', 45);

        return [
            'online' => ($secondsAgo <= $onlineWindow),
            'last_seen_at' => date('Y-m-d H:i:s', $lastSeenTs),
            'seconds_ago' => $secondsAgo,
        ];
    }

    private function agentPresenceCacheKey(int $agentId): string
    {
        return 'agent_chat_presence_' . $agentId;
    }

    private function resolveThreadLabel(string $targetType, $targetId): string
    {
        if ($targetType === 'member') {
            $member = DB::table('member')->where('id', (int)$targetId)->first();
            if ($member) {
                return $member->alias_name ?: ($member->full_name ?: ('Member #' . $targetId));
            }
            return 'Member #' . $targetId;
        }

        return 'Guest ' . substr((string)$targetId, -6);
    }

    private function ensureAttachmentTableExists(): void
    {
        static $checked = false;
        if ($checked) {
            return;
        }

        if (!Schema::hasTable('agent_chat_attachments')) {
            $table = DB::getTablePrefix() . 'agent_chat_attachments';
            DB::statement("CREATE TABLE IF NOT EXISTS `{$table}` (
                `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                `message_id` BIGINT UNSIGNED NOT NULL,
                `stored_name` VARCHAR(255) NOT NULL,
                `original_name` VARCHAR(255) NOT NULL,
                `mime_type` VARCHAR(255) NULL,
                `file_size` BIGINT UNSIGNED NOT NULL DEFAULT 0,
                `relative_path` VARCHAR(500) NOT NULL,
                `created_at` TIMESTAMP NULL DEFAULT NULL,
                `updated_at` TIMESTAMP NULL DEFAULT NULL,
                PRIMARY KEY (`id`),
                KEY `agent_chat_attachments_message_id_index` (`message_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        }

        $checked = true;
    }

    private function validateAttachment(UploadedFile $attachment): ?string
    {
        if (!$attachment->isValid()) {
            return 'File upload failed. Please try again.';
        }

        $maxBytes = (int)env('AGENT_CHAT_MAX_FILE_BYTES', 10485760);
        if ($attachment->getSize() > $maxBytes) {
            return 'File is too large. Maximum size is 10MB.';
        }

        $allowedExtensions = [
            'pdf', 'doc', 'docx', 'txt', 'rtf', 'csv',
            'xls', 'xlsx', 'jpg', 'jpeg', 'png', 'zip'
        ];

        $ext = strtolower((string)$attachment->getClientOriginalExtension());
        if ($ext === '' || !in_array($ext, $allowedExtensions, true)) {
            return 'Unsupported file type. Please upload a document, spreadsheet, image, text, or zip file.';
        }

        return null;
    }

    private function storeAttachmentFile(UploadedFile $attachment): array
    {
        $uploadDir = public_path('upload/agent_chat');
        if (!is_dir($uploadDir)) {
            @mkdir($uploadDir, 0775, true);
        }

        $mimeType = (string)$attachment->getClientMimeType();
        $fileSize = (int)$attachment->getSize();

        $originalName = preg_replace('/[^A-Za-z0-9._\- ]/', '_', (string)$attachment->getClientOriginalName());
        $originalName = trim((string)$originalName);
        if ($originalName === '') {
            $originalName = 'attachment';
        }

        $ext = strtolower((string)$attachment->getClientOriginalExtension());
        $storedName = date('YmdHis') . '_' . Str::random(12) . ($ext !== '' ? ('.' . $ext) : '');
        $attachment->move($uploadDir, $storedName);

        return [
            'stored_name' => $storedName,
            'original_name' => $originalName,
            'mime_type' => $mimeType,
            'file_size' => $fileSize,
            'relative_path' => 'upload/agent_chat/' . $storedName,
        ];
    }

    private function canActorAccessMessage(array $actor, $messageRow): bool
    {
        if ($actor['type'] === 'member') {
            $memberId = (int)$actor['id'];
            return ((int)$messageRow->sender_member_id === $memberId)
                || ((int)$messageRow->receiver_member_id === $memberId);
        }

        $guestId = (string)$actor['id'];
        return ((string)$messageRow->sender_guest_id === $guestId)
            || ((string)$messageRow->receiver_guest_id === $guestId);
    }

    private function ensureAgentChatBookingTableExists(): void
    {
        static $checked = false;
        if ($checked) {
            return;
        }

        if (!Schema::hasTable('agent_chat_meeting_bookings')) {
            $table = DB::getTablePrefix() . 'agent_chat_meeting_bookings';
            DB::statement("CREATE TABLE IF NOT EXISTS `{$table}` (
                `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                `member_id` BIGINT UNSIGNED NOT NULL,
                `status` VARCHAR(30) NOT NULL DEFAULT 'booked',
                `calendly_event_uri` VARCHAR(500) NULL,
                `calendly_invitee_uri` VARCHAR(500) NULL,
                `booked_at` TIMESTAMP NULL DEFAULT NULL,
                `created_at` TIMESTAMP NULL DEFAULT NULL,
                `updated_at` TIMESTAMP NULL DEFAULT NULL,
                PRIMARY KEY (`id`),
                UNIQUE KEY `agent_chat_meeting_bookings_member_id_unique` (`member_id`),
                KEY `agent_chat_meeting_bookings_status_index` (`status`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        }

        $checked = true;
    }

    private function hasUnlockedAgentChat(int $memberId): bool
    {
        if ($memberId <= 0) {
            return false;
        }

        $this->ensureAgentChatBookingTableExists();

        return DB::table('agent_chat_meeting_bookings')
            ->where('member_id', $memberId)
            ->where('status', 'booked')
            ->exists();
    }

    /**
     * Return the highest-priority active plan code for a member.
     * Priority: vip > premium > hybrid > all_ai > free
     */
    private function getMemberActivePlanCode(int $memberId): ?string
    {
        if ($memberId <= 0) {
            return null;
        }

        try {
            $planQuery = DB::table('subscriptions')
                ->join('plans', 'plans.id', '=', 'subscriptions.plan_id')
                ->where('subscriptions.member_id', $memberId)
                ->where('subscriptions.status', 'active')
                ->where(function ($q) {
                    $q->whereNull('subscriptions.ends_at')
                      ->orWhere('subscriptions.ends_at', '>', now());
                });

            if (Schema::hasColumn('plans', 'is_active')) {
                $planQuery->where('plans.is_active', 1);
            }

            $plan = $planQuery
                ->orderByRaw("CASE plans.code WHEN 'vip' THEN 1 WHEN 'premium' THEN 2 WHEN 'hybrid' THEN 3 WHEN 'all_ai' THEN 4 WHEN 'free' THEN 5 ELSE 99 END")
                ->select('plans.code')
                ->first();

            if ($plan && !empty($plan->code)) {
                return $plan->code;
            }

            // Fallback for envs with inconsistent legacy data: take latest active subscription plan.
            $fallbackPlan = DB::table('subscriptions')
                ->join('plans', 'plans.id', '=', 'subscriptions.plan_id')
                ->where('subscriptions.member_id', $memberId)
                ->where('subscriptions.status', 'active')
                ->orderByDesc('subscriptions.id')
                ->select('plans.code')
                ->first();

            return ($fallbackPlan && !empty($fallbackPlan->code)) ? $fallbackPlan->code : null;
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Agent_chat.member_plan_lookup_failed', [
                'member_id' => $memberId,
                'error' => $e->getMessage(),
            ]);

            // Last-resort fallback query without ends_at/is_active constraints.
            try {
                $fallbackPlan = DB::table('subscriptions')
                    ->join('plans', 'plans.id', '=', 'subscriptions.plan_id')
                    ->where('subscriptions.member_id', $memberId)
                    ->where('subscriptions.status', 'active')
                    ->orderByDesc('subscriptions.id')
                    ->select('plans.code')
                    ->first();

                return ($fallbackPlan && !empty($fallbackPlan->code)) ? $fallbackPlan->code : null;
            } catch (\Throwable $fallbackError) {
                \Illuminate\Support\Facades\Log::warning('Agent_chat.member_plan_lookup_fallback_failed', [
                    'member_id' => $memberId,
                    'error' => $fallbackError->getMessage(),
                ]);
                return null;
            }
        }
    }
}
