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

        $planCode = $this->getMemberActivePlanCode($memberId);

        // VIP → always allowed
        if ($planCode === 'vip') {
            return null;
        }

        // DIY (premium) → always allowed (full chat, no meeting required)
        if ($planCode === 'premium') {
            return null;
        }

        if ($responseMode === 'redirect') {
            return null;
        }

        return response()->json([
            'ok' => false,
            'message' => 'Please schedule a meeting with Wealthskey Migration & Education first to unlock Talk to Agent chat.',
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

            // VIP → full agent chat access, no booking required
            if ($planCode === 'vip') {
                return $this->renderChatPage($targetId);
            }

            // DIY (premium) → always full chat access, no meeting booking needed
            if ($planCode === 'premium') {
                return $this->renderChatPage($targetId);
            }

            // AI+Agent (hybrid) → booking page; after agent confirms attendance → upgrade redirect
            if ($planCode === 'hybrid') {
                if ($this->hasMeetingAttended((int)$memberId, 'hybrid')) {
                    return $this->doRedirect($this->toURL('upgrade'));
                }
                return $this->buildBookingPage(
                    'https://calendly.com/admin-wealthskey/ai-agent-plan-users',
                    'hybrid',
                    (int)$memberId
                );
            }

            // Free / AI Smart → 1x 15-min meeting; once used → upgrade redirect
            if (in_array($planCode, ['free', 'all_ai'], true)) {
                if ($this->hasMeetingAttended((int)$memberId, 'free')) {
                    return $this->doRedirect($this->toURL('upgrade') . '?notice=meeting_used');
                }
                return $this->buildBookingPage(
                    'https://calendly.com/admin-wealthskey/free-users',
                    'free',
                    (int)$memberId
                );
            }

            // No active plan → same as free plan (also check if meeting already used)
            if ($this->hasMeetingAttended((int)$memberId, 'free')) {
                return $this->doRedirect($this->toURL('upgrade') . '?notice=meeting_used');
            }
            return $this->buildBookingPage(
                'https://calendly.com/admin-wealthskey/free-users',
                'free',
                (int)$memberId
            );
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
            // Only VIP or DIY (full access) may reach the direct chat page
            if ($planCode !== 'vip' && $planCode !== 'premium') {
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
        $postContext = null;

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

        // Load post context if post_id is provided
        $postId = request()->input('post_id');
        if (!empty($postId) && is_numeric($postId)) {
            $postData = DB::table('member_posts')
                ->where('id', (int)$postId)
                ->where('status', '>', 0)
                ->first(['id', 'title', 'content', 'sector']);
            
            if ($postData) {
                $postContext = [
                    'id' => $postData->id,
                    'title' => $postData->title,
                    'content' => $postData->content,
                    'sector' => $postData->sector,
                ];
            }
        }

        $memberPlanCode = '';
        if ($memberId && !$isAgent) {
            $memberPlanCode = $this->getMemberActivePlanCode((int)$memberId);
        }

        return $this->pageData([
            'is_agent'           => $isAgent,
            'agents'             => $agents,
            'threads'            => $threads,
            'active_target_type' => $activeTargetType,
            'active_target_id'   => $activeTargetId,
            'member_plan_code'   => $memberPlanCode,
            'post_context'       => $postContext,
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

        $this->ensureAgentChatBookingTableExists();

        $currentPlanCode = $this->getMemberActivePlanCode($memberId);

        // null means no active subscription found — treat as free plan (same 1-time booking gate)
        if ($currentPlanCode === null) {
            $currentPlanCode = 'free';
        }

        // Free / AI Smart: 1-time only. Check if already used.
        $isFreePlan = in_array($currentPlanCode, ['free', 'all_ai'], true);
        if ($isFreePlan && $this->hasMeetingAttended($memberId, 'free')) {
            return response()->json([
                'ok'            => false,
                'already_used'  => true,
                'redirect_upgrade' => true,
                'message'       => 'You have already used your one-time free consultation meeting.',
            ], 422);
        }

        // AI+Agent (hybrid): 1-time 2-hour meeting. Check if already used.
        if ($currentPlanCode === 'hybrid' && $this->hasMeetingAttended($memberId, 'hybrid')) {
            return response()->json([
                'ok'            => false,
                'already_used'  => true,
                'redirect_upgrade' => true,
                'message'       => 'You have already completed your AI+Agent 2-hour consultation meeting.',
            ], 422);
        }

        $alreadyUnlocked = $this->hasUnlockedAgentChat($memberId, $currentPlanCode);

        // For free/all_ai/hybrid: auto-confirm is always safe — no need to require prior schedule click.
        // The strict gate is only kept for other non-auto-confirm plans.
        $isAutoConfirmPlan = $isFreePlan || $currentPlanCode === 'hybrid';
        if (!$isAutoConfirmPlan && $source === 'manual_continue' && !$alreadyUnlocked && !session()->get($scheduleClickSessionKey, false)) {
            return response()->json([
                'ok' => false,
                'unlocked' => false,
                'require_schedule_click' => true,
                'message' => 'Please click "Schedule meeting with agent" first to record your meeting.',
            ], 422);
        }

        // For free/all_ai/hybrid: auto-confirm immediately (no agent manual confirmation needed)
        $autoConfirm = $isAutoConfirmPlan;

        $payload = [
            'member_id'            => $memberId,
            'status'               => $autoConfirm ? 'attended' : 'booked',
            'plan_code'            => $currentPlanCode,
            'agent_attended'       => $autoConfirm ? 1 : 0,
            'attended_at'          => $autoConfirm ? now() : null,
            'calendly_event_uri'   => trim((string)request()->input('event_uri', '')) ?: null,
            'calendly_invitee_uri' => trim((string)request()->input('invitee_uri', '')) ?: null,
            'booked_at'            => now(),
            'created_at'           => now(),
            'updated_at'           => now(),
        ];

        $existingId = DB::table('agent_chat_meeting_bookings')
            ->where('member_id', $memberId)
            ->value('id');

        if ($existingId) {
            DB::table('agent_chat_meeting_bookings')
                ->where('id', (int)$existingId)
                ->update([
                    'status'               => $payload['status'],
                    'plan_code'            => $payload['plan_code'],
                    'agent_attended'       => $payload['agent_attended'],
                    'attended_at'          => $payload['attended_at'],
                    'calendly_event_uri'   => $payload['calendly_event_uri'],
                    'calendly_invitee_uri' => $payload['calendly_invitee_uri'],
                    'booked_at'            => $payload['booked_at'],
                    'updated_at'           => $payload['updated_at'],
                ]);
        } else {
            DB::table('agent_chat_meeting_bookings')->insert($payload);
        }

        if ($source === 'schedule_click') {
            session()->put($scheduleClickSessionKey, true);

            if ($autoConfirm) {
                // Free/all_ai/hybrid: mark as used immediately, tell frontend to redirect to upgrade
                session()->forget($scheduleClickSessionKey);
                $confirmMsg = ($currentPlanCode === 'hybrid')
                    ? 'Your 2-hour AI+Agent consultation has been scheduled! This is a one-time benefit of your plan.'
                    : 'Your 15-minute consultation has been scheduled! This is a one-time benefit. Upgrade your plan to book more meetings.';
                return response()->json([
                    'ok'               => true,
                    'booked'           => true,
                    'meeting_used'     => true,
                    'redirect_upgrade' => true,
                    'message'          => $confirmMsg,
                ]);
            }

            return response()->json([
                'ok'               => true,
                'booked'           => true,
                'needs_attendance' => true,
                'schedule_clicked' => true,
                'message'          => 'Schedule click recorded. Your meeting is being logged.',
            ]);
        }

        // manual_continue
        session()->forget($scheduleClickSessionKey);

        if ($autoConfirm) {
            $confirmMsg = ($currentPlanCode === 'hybrid')
                ? 'Your 2-hour AI+Agent consultation has been scheduled! This is a one-time benefit of your plan.'
                : 'Your 15-minute consultation has been scheduled! This is a one-time benefit.';
            return response()->json([
                'ok'               => true,
                'booked'           => true,
                'meeting_used'     => true,
                'redirect_upgrade' => true,
                'message'          => $confirmMsg,
            ]);
        }

        return response()->json([
            'ok'               => true,
            'booked'           => true,
            'needs_attendance' => true,
            'message'          => 'Your meeting has been scheduled. Please wait for the agent to confirm your attendance.',
        ]);
    }

    public function calendlyWebhook()
    {
        $rawBody    = request()->getContent();
        $signingKey = '4940a185ff10cb9243e1be0d320fbd741efad2c4c95bce0f36ad3882220812a1';

        // Verify HMAC signature
        if ($signingKey !== '') {
            $sigHeader = request()->header('Calendly-Webhook-Signature', '');
            if (!$this->verifyCalendlySignature($sigHeader, $rawBody, $signingKey)) {
                \Illuminate\Support\Facades\Log::warning('calendly_webhook.invalid_signature', [
                    'header' => $sigHeader,
                ]);
                return response()->json(['ok' => false, 'message' => 'Invalid signature'], 401);
            }
        }

        $data  = json_decode($rawBody, true) ?: [];
        $event = $data['event'] ?? '';

        // Only process new bookings
        if ($event !== 'invitee.created') {
            return response()->json(['ok' => true, 'message' => 'event ignored']);
        }

        $payload    = $data['payload'] ?? [];
        $invitee    = $payload['invitee'] ?? [];
        $email      = strtolower(trim((string)($invitee['email'] ?? '')));
        $eventUri   = (string)($payload['event'] ?? '');
        $inviteeUri = (string)($invitee['uri'] ?? '');

        if ($email === '') {
            return response()->json(['ok' => false, 'message' => 'No invitee email'], 422);
        }

        // Find member by email
        $member = DB::table('member')
            ->where('email', $email)
            ->where('verified', 1)
            ->where('status', '>', 0)
            ->select(['id'])
            ->first();

        if (!$member) {
            \Illuminate\Support\Facades\Log::info('calendly_webhook.member_not_found', ['email' => $email]);
            // Return 200 so Calendly does not keep retrying
            return response()->json(['ok' => true, 'message' => 'member not found, ignored']);
        }

        $memberId    = (int)$member->id;
        $planCode    = $this->getMemberActivePlanCode($memberId);

        // premium doesn't need booking records
        if ($planCode === 'premium') {
            return response()->json(['ok' => true, 'message' => 'plan does not require booking']);
        }

        // free/all_ai/hybrid: 1-time auto-confirmed meeting
        // VIP: always auto-confirmed (unlimited meetings; freeflow access)
        // null (no active plan): treated as free (auto-confirm)
        $autoConfirm = in_array($planCode, ['free', 'all_ai', 'hybrid', 'vip'], true) || $planCode === null;

        $this->ensureAgentChatBookingTableExists();

        $bookingData = [
            'status'               => $autoConfirm ? 'attended' : 'booked',
            'plan_code'            => $planCode,
            'agent_attended'       => $autoConfirm ? 1 : 0,
            'attended_at'          => $autoConfirm ? now() : null,
            'calendly_event_uri'   => $eventUri ?: null,
            'calendly_invitee_uri' => $inviteeUri ?: null,
            'booked_at'            => now(),
            'updated_at'           => now(),
        ];

        $existingId = DB::table('agent_chat_meeting_bookings')
            ->where('member_id', $memberId)
            ->value('id');

        if ($existingId) {
            DB::table('agent_chat_meeting_bookings')
                ->where('id', (int)$existingId)
                ->update($bookingData);
        } else {
            DB::table('agent_chat_meeting_bookings')->insert(array_merge($bookingData, [
                'member_id'  => $memberId,
                'created_at' => now(),
            ]));
        }

        \Illuminate\Support\Facades\Log::info('calendly_webhook.booking_recorded', [
            'member_id'    => $memberId,
            'email'        => $email,
            'plan_code'    => $planCode,
            'auto_confirm' => $autoConfirm,
        ]);

        return response()->json(['ok' => true]);
    }

    private function verifyCalendlySignature(string $sigHeader, string $rawBody, string $signingKey): bool
    {
        // Header format: t=TIMESTAMP,v1=HMAC_SHA256_HEX
        if (!preg_match('/t=(\d+),v1=([a-f0-9]+)/i', $sigHeader, $m)) {
            return false;
        }
        $expected = hash_hmac('sha256', $m[1] . '.' . $rawBody, $signingKey);
        return hash_equals($expected, strtolower($m[2]));
    }

    public function bookingMarkAttended()
    {
        $authResponse = $this->requireMemberAuth('json');
        if ($authResponse !== null) {
            return $authResponse;
        }

        $agentMemberId = (int)($this->_current_member['id'] ?? 0);
        if (!$agentMemberId || !$this->canUseAgentHomeLayout($agentMemberId)) {
            return response()->json(['ok' => false, 'message' => 'Unauthorized'], 403);
        }

        $targetMemberId = (int)request()->input('member_id', 0);
        $attended       = filter_var(request()->input('attended', true), FILTER_VALIDATE_BOOLEAN);

        if ($targetMemberId <= 0) {
            return response()->json(['ok' => false, 'message' => 'Invalid member_id'], 422);
        }

        $this->ensureAgentChatBookingTableExists();

        $existingId = DB::table('agent_chat_meeting_bookings')
            ->where('member_id', $targetMemberId)
            ->value('id');

        if ($existingId) {
            DB::table('agent_chat_meeting_bookings')
                ->where('member_id', $targetMemberId)
                ->update([
                    'agent_attended' => $attended ? 1 : 0,
                    'attended_at'    => $attended ? now() : null,
                    'status'         => $attended ? 'attended' : 'booked',
                    'updated_at'     => now(),
                ]);
        } else {
            DB::table('agent_chat_meeting_bookings')->insert([
                'member_id'      => $targetMemberId,
                'status'         => $attended ? 'attended' : 'booked',
                'agent_attended' => $attended ? 1 : 0,
                'attended_at'    => $attended ? now() : null,
                'created_at'     => now(),
                'updated_at'     => now(),
            ]);
        }

        return response()->json(['ok' => true, 'member_id' => $targetMemberId, 'attended' => $attended]);
    }

    public function bookingDeleteReset()
    {
        $authResponse = $this->requireMemberAuth('json');
        if ($authResponse !== null) {
            return $authResponse;
        }

        $agentMemberId = (int)($this->_current_member['id'] ?? 0);
        if (!$agentMemberId || !$this->canUseAgentHomeLayout($agentMemberId)) {
            return response()->json(['ok' => false, 'message' => 'Unauthorized'], 403);
        }

        $targetMemberId = (int)request()->input('member_id', 0);
        if ($targetMemberId <= 0) {
            return response()->json(['ok' => false, 'message' => 'Invalid member_id'], 422);
        }

        $this->ensureAgentChatBookingTableExists();

        DB::table('agent_chat_meeting_bookings')
            ->where('member_id', $targetMemberId)
            ->delete();

        return response()->json(['ok' => true, 'member_id' => $targetMemberId, 'message' => 'Booking record deleted.']);
    }

    public function agentVerification()
    {
        $authResponse = $this->requireMemberAuth('redirect');
        if ($authResponse !== null) {
            return $authResponse;
        }

        $memberId = (int)($this->_current_member['id'] ?? 0);
        if (!$memberId || !$this->canUseAgentHomeLayout($memberId)) {
            return $this->doRedirect($this->toURL('home'));
        }

        $this->ensureAgentChatBookingTableExists();

        // All members with any active subscription
        $members = [];
        $seenIds = [];

        try {
            $subRows = DB::table('subscriptions as s')
                ->join('plans as p', 'p.id', '=', 's.plan_id')
                ->join('member as m', 'm.id', '=', 's.member_id')
                ->where('s.status', 'active')
                ->where(function ($q) {
                    $q->whereNull('s.ends_at')->orWhere('s.ends_at', '>', now());
                })
                ->where('m.status', '>', 0)
                ->select(['m.id as member_id', 'm.alias_name', 'm.full_name', 'm.email', 'p.name as plan_name', 'p.code as plan_code'])
                ->get();
        } catch (\Throwable $e) {
            $subRows = collect();
            \Illuminate\Support\Facades\Log::warning('Agent_verification.subs_query_failed', ['error' => $e->getMessage()]);
        }

        // Load verification state from booking table
        try {
            $bookingMap = DB::table('agent_chat_meeting_bookings')
                ->select(['member_id', 'agent_attended', 'attended_at', 'status', 'booked_at', 'plan_code'])
                ->get()
                ->keyBy('member_id');
        } catch (\Throwable $e) {
            $bookingMap = collect();
            \Illuminate\Support\Facades\Log::warning('Agent_verification.bookingmap_failed', ['error' => $e->getMessage()]);
        }

        foreach ($subRows as $row) {
            $mid = (int)$row->member_id;
            if (isset($seenIds[$mid])) {
                continue;
            }
            $seenIds[$mid] = true;

            $booking  = $bookingMap[$mid] ?? null;
            $planCode = (string)($row->plan_code ?? '');
            $name     = trim((string)($row->alias_name ?: $row->full_name ?: ('Member #' . $mid)));

            // What happens after agent verifies this member?
            $outcomeMap = [
                'vip'     => 'Full chat access (no booking required)',
                'premium' => 'Full chat access (no booking required)',
                'hybrid'  => '2-hour consultation — chat unlocked after agent confirms meeting',
                'all_ai'  => 'One-time 15-min consultation — auto-recorded when scheduled',
                'free'    => 'One-time 15-min consultation — auto-recorded when scheduled',
            ];
            $outcome = $outcomeMap[$planCode] ?? 'Redirected to upgrade page';

            $members[] = [
                'member_id'        => $mid,
                'name'             => $name,
                'email'            => (string)($row->email ?? ''),
                'plan_name'        => (string)($row->plan_name ?? ''),
                'plan_code'        => $planCode,
                'outcome'          => $outcome,
                'has_booked'       => !is_null($booking),
                'verified'         => $booking ? (bool)$booking->agent_attended : false,
                'verified_at'      => $booking ? (string)($booking->attended_at ?? '') : '',
                'booked_at'        => $booking ? (string)($booking->booked_at ?? '') : '',
                'booking_plan_code'=> $booking ? (string)($booking->plan_code ?? '') : '',
            ];
        }

        // Also include members from booking table not already in list (edge case: expired plan)
        foreach ($bookingMap as $mid => $booking) {
            $mid = (int)$mid;
            if (isset($seenIds[$mid])) {
                continue;
            }
            $seenIds[$mid] = true;

            $mem = DB::table('member')
                ->where('id', $mid)
                ->select(['alias_name', 'full_name', 'email'])
                ->first();

            if (!$mem) {
                continue;
            }

            $name = trim((string)($mem->alias_name ?: $mem->full_name ?: ('Member #' . $mid)));
            $members[] = [
                'member_id'   => $mid,
                'name'        => $name,
                'email'       => (string)($mem->email ?? ''),
                'plan_name'   => '—',
                'plan_code'   => '',
                'outcome'     => 'No active plan',
                'has_booked'  => true,
                'verified'    => (bool)$booking->agent_attended,
                'verified_at' => (string)($booking->attended_at ?? ''),
                'booked_at'   => (string)($booking->booked_at ?? ''),
            ];
        }

        // Sort by plan priority: vip > premium > hybrid > all_ai > free > other
        $planPriority = ['vip' => 1, 'premium' => 2, 'hybrid' => 3, 'all_ai' => 4, 'free' => 5];
        usort($members, function ($a, $b) use ($planPriority) {
            $pa = $planPriority[$a['plan_code']] ?? 99;
            $pb = $planPriority[$b['plan_code']] ?? 99;
            if ($pa !== $pb) return $pa <=> $pb;
            return strcmp($a['name'], $b['name']);
        });

        $this->pageCss('agent_verification');
        $this->pageScript('agent_verification');

        return $this->pageData([
            'members' => $members,
        ])->pageView('agent_verification');
    }

    public function agentDashboard()
    {
        $authResponse = $this->requireMemberAuth('redirect');
        if ($authResponse !== null) {
            return $authResponse;
        }

        $memberId = (int)($this->_current_member['id'] ?? 0);
        if (!$memberId || !$this->canUseAgentHomeLayout($memberId)) {
            return $this->doRedirect($this->toURL('home'));
        }

        $this->ensureAgentChatBookingTableExists();

        try {
            $bookingRows = DB::table('agent_chat_meeting_bookings as b')
                ->leftJoin('member as m', 'm.id', '=', 'b.member_id')
                ->select([
                    'b.id as booking_id',
                    'b.member_id',
                    'b.status',
                    'b.agent_attended',
                    'b.attended_at',
                    'b.booked_at',
                    'b.plan_code as saved_plan_code',
                    'm.alias_name',
                    'm.full_name',
                    'm.email',
                ])
                ->orderByDesc('b.created_at')
                ->get();
        } catch (\Throwable $e) {
            $bookingRows = collect();
            \Illuminate\Support\Facades\Log::warning('Agent_dashboard.bookingrows_failed', ['error' => $e->getMessage()]);
        }

        $memberIds = $bookingRows->pluck('member_id')->filter()->unique()->values()->toArray();

        $planNames = [];
        $planCodes = [];
        if (!empty($memberIds)) {
            try {
                $planRows = DB::table('subscriptions')
                    ->join('plans', 'plans.id', '=', 'subscriptions.plan_id')
                    ->whereIn('subscriptions.member_id', $memberIds)
                    ->where('subscriptions.status', 'active')
                    ->where(function ($q) {
                        $q->whereNull('subscriptions.ends_at')
                          ->orWhere('subscriptions.ends_at', '>', now());
                    })
                    ->orderByRaw("CASE app_plans.code WHEN 'vip' THEN 1 WHEN 'premium' THEN 2 WHEN 'hybrid' THEN 3 WHEN 'all_ai' THEN 4 WHEN 'free' THEN 5 ELSE 99 END")
                    ->select('subscriptions.member_id', 'plans.name', 'plans.code')
                    ->get();

                foreach ($planRows as $pr) {
                    $mid = (int)$pr->member_id;
                    if (!isset($planNames[$mid])) {
                        $planNames[$mid] = $pr->name;
                        $planCodes[$mid] = $pr->code;
                    }
                }
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning('Agent_dashboard.plan_lookup_failed', ['error' => $e->getMessage()]);
            }
        }

        $bookings = [];
        foreach ($bookingRows as $row) {
            $mid  = (int)($row->member_id ?? 0);
            $name = trim((string)($row->alias_name ?: $row->full_name ?: ('Member #' . $mid)));
            $bookings[] = [
                'booking_id'     => (int)$row->booking_id,
                'member_id'      => $mid,
                'name'           => $name,
                'email'          => (string)($row->email ?? ''),
                'plan_name'      => $planNames[$mid] ?? ucfirst((string)($row->saved_plan_code ?? 'Free')),
                'plan_code'      => $planCodes[$mid] ?? (string)($row->saved_plan_code ?? ''),
                'status'         => (string)($row->status ?? ''),
                'agent_attended' => (bool)$row->agent_attended,
                'attended_at'    => (string)($row->attended_at ?? ''),
                'booked_at'      => (string)($row->booked_at ?? ''),
            ];
        }

        $this->pageCss('agent_dashboard');
        $this->pageScript('agent_dashboard');

        return $this->pageData([
            'bookings' => $bookings,
        ])->pageView('agent_dashboard');
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
                    'message' => 'Please schedule a meeting with Wealthskey Migration & Education first to unlock Talk to Agent chat.',
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
            $content .= '<p style="margin:0 0 12px 0;">A member has sent a new chat message to Wealthskey Migration & Education.</p>';
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
        try {
            $isExplicitAgent = DB::table('member_agent')
                ->where('member_id', $memberId)
                ->where('status', '>', 0)
                ->exists();

            if ($isExplicitAgent) {
                return true;
            }
        } catch (\Throwable $e) {
            // member_agent table may not exist on this environment — fall through
        }

        try {
            return DB::table('member')
                ->where('id', $memberId)
                ->whereIn('type', [2, 3])
                ->where('status', '>', 0)
                ->exists();
        } catch (\Throwable $e) {
            return false;
        }
    }

    private function canUseAgentHomeLayout(int $memberId): bool
    {
        if ($memberId <= 0) {
            return false;
        }

        // Use the session-loaded member data first (already authenticated, avoids extra DB query).
        // Fall back to a fresh DB lookup if the session email is unavailable.
        $email = !empty($this->_current_member['email'])
            ? (string)$this->_current_member['email']
            : (string)(DB::table('member')->where('id', $memberId)->value('email') ?? '');

        $email = mb_strtolower(trim($email), 'UTF-8');
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
                    ->orderByRaw("CASE app_plans.code WHEN 'vip' THEN 1 WHEN 'premium' THEN 2 WHEN 'hybrid' THEN 3 WHEN 'all_ai' THEN 4 WHEN 'free' THEN 5 ELSE 99 END")
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

        $prefix = DB::getTablePrefix();
        $table  = $prefix . 'agent_chat_meeting_bookings';

        if (!Schema::hasTable('agent_chat_meeting_bookings')) {
            DB::statement("CREATE TABLE IF NOT EXISTS `{$table}` (
                `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                `member_id` BIGINT UNSIGNED NOT NULL,
                `status` VARCHAR(30) NOT NULL DEFAULT 'booked',
                `plan_code` VARCHAR(30) NULL DEFAULT NULL,
                `agent_attended` TINYINT(1) NOT NULL DEFAULT 0,
                `attended_at` TIMESTAMP NULL DEFAULT NULL,
                `calendly_event_uri` VARCHAR(500) NULL,
                `calendly_invitee_uri` VARCHAR(500) NULL,
                `booked_at` TIMESTAMP NULL DEFAULT NULL,
                `created_at` TIMESTAMP NULL DEFAULT NULL,
                `updated_at` TIMESTAMP NULL DEFAULT NULL,
                PRIMARY KEY (`id`),
                UNIQUE KEY `agent_chat_meeting_bookings_member_id_unique` (`member_id`),
                KEY `agent_chat_meeting_bookings_status_index` (`status`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        } else {
            // Add new columns to existing table if missing
            $existingColumns = DB::select("SHOW COLUMNS FROM `{$table}`");
            $colNames = array_map(function ($col) {
                // DB::select() returns stdClass objects on most setups, but some
                // hosting environments may return associative arrays — handle both.
                if (is_object($col)) {
                    return $col->Field ?? $col->field ?? '';
                }
                return $col['Field'] ?? $col['field'] ?? '';
            }, $existingColumns);
            if (!in_array('plan_code', $colNames, true)) {
                DB::statement("ALTER TABLE `{$table}` ADD COLUMN `plan_code` VARCHAR(30) NULL DEFAULT NULL AFTER `status`");
            }
            if (!in_array('agent_attended', $colNames, true)) {
                DB::statement("ALTER TABLE `{$table}` ADD COLUMN `agent_attended` TINYINT(1) NOT NULL DEFAULT 0 AFTER `plan_code`");
            }
            if (!in_array('attended_at', $colNames, true)) {
                DB::statement("ALTER TABLE `{$table}` ADD COLUMN `attended_at` TIMESTAMP NULL DEFAULT NULL AFTER `agent_attended`");
            }
            if (!in_array('calendly_event_uri', $colNames, true)) {
                DB::statement("ALTER TABLE `{$table}` ADD COLUMN `calendly_event_uri` VARCHAR(500) NULL DEFAULT NULL AFTER `attended_at`");
            }
            if (!in_array('calendly_invitee_uri', $colNames, true)) {
                DB::statement("ALTER TABLE `{$table}` ADD COLUMN `calendly_invitee_uri` VARCHAR(500) NULL DEFAULT NULL AFTER `calendly_event_uri`");
            }
            if (!in_array('booked_at', $colNames, true)) {
                DB::statement("ALTER TABLE `{$table}` ADD COLUMN `booked_at` TIMESTAMP NULL DEFAULT NULL AFTER `calendly_invitee_uri`");
            }
        }

        $checked = true;
    }

    /**
     * Check if member has an active (not-yet-attended) booking for their current plan context.
     * For hybrid: only counts hybrid bookings.
     * For free/all_ai: only counts free/all_ai bookings.
     */
    private function hasUnlockedAgentChat(int $memberId, string $planCode = ''): bool
    {
        if ($memberId <= 0) {
            return false;
        }

        $this->ensureAgentChatBookingTableExists();

        $q = DB::table('agent_chat_meeting_bookings')
            ->where('member_id', $memberId)
            ->where('status', 'booked');

        if ($planCode === 'hybrid') {
            $q->where('plan_code', 'hybrid');
        } elseif (in_array($planCode, ['free', 'all_ai'], true)) {
            $q->whereIn('plan_code', ['free', 'all_ai']);
        }

        return $q->exists();
    }

    /**
     * Check if member's meeting has been attended/used in the given plan context.
     * - 'hybrid': only true if agent confirmed a hybrid booking
     * - 'free' or 'all_ai': only true if free/all_ai auto-confirmed booking exists
     * - '' (default): any attended booking
     */
    private function hasMeetingAttended(int $memberId, string $forPlanCode = ''): bool
    {
        if ($memberId <= 0) {
            return false;
        }

        $this->ensureAgentChatBookingTableExists();

        $row = DB::table('agent_chat_meeting_bookings')
            ->where('member_id', $memberId)
            ->select(['agent_attended', 'plan_code'])
            ->first();

        if (!$row || !(bool)$row->agent_attended) {
            return false;
        }

        if ($forPlanCode === 'hybrid') {
            // Only count as attended if this booking was made under hybrid plan
            return ($row->plan_code ?? '') === 'hybrid';
        }

        if (in_array($forPlanCode, ['free', 'all_ai'], true)) {
            // Count as used if booking was made under free or all_ai plan
            return in_array($row->plan_code ?? '', ['free', 'all_ai']);
        }

        return true;
    }

    private function buildBookingPage(string $calendlyUrl, string $planMode, int $memberId)
    {
        $this->pageCss('agent_chat_booking_required');
        $this->pageScript('agent_chat_booking_required');

        // has_booked controls whether the schedule button is hidden on page load.
        // - hybrid: ALWAYS false — member can reschedule until agent confirms attendance.
        //   (If agent_attended=1, index() already redirected to /upgrade before reaching here.)
        // - free/all_ai: ALWAYS false — same logic: if quota used, index() redirects away.
        $hasBooked = false;

        $langPrefix  = '';
        $currentLang = trim((string)request()->segment(1));
        if ($currentLang !== '' && preg_match('/^[a-zA-Z_\-]+$/', $currentLang)) {
            $langPrefix = '/' . $currentLang;
        }

        return $this->pageData([
            'mode'           => $planMode,
            'calendly_url'   => $calendlyUrl,
            'has_booked'     => $hasBooked,
            'unlock_api_url' => $langPrefix . '/agent_chat/booking/confirm',
        ])->pageView('agent_chat_booking_required');
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
                ->orderByRaw("CASE app_plans.code WHEN 'vip' THEN 1 WHEN 'premium' THEN 2 WHEN 'hybrid' THEN 3 WHEN 'all_ai' THEN 4 WHEN 'free' THEN 5 ELSE 99 END")
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
