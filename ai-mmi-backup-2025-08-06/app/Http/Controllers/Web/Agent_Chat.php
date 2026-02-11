<?php
namespace App\Http\Controllers\Web;

use App\Http\Controllers\WebController;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class Agent_Chat extends WebController
{
    public function index($targetId = null)
    {
        $this->pageCss('agent_chat');
        $this->pageScript('agent_chat');

        $actor = $this->resolveActor();
        $member = $this->_current_member ?: null;
        $memberId = $member['id'] ?? null;
        $isAgent = $memberId ? ($this->isAgentMember($memberId) || app()->environment('local')) : false;

        // Debug logging
        \Illuminate\Support\Facades\Log::info('Agent_chat.index', [
            'memberId' => $memberId,
            'isAgent' => $isAgent,
            'member_email' => $member['email'] ?? 'none',
            'member_type' => $member['type'] ?? 'none'
        ]);

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
        $member = $this->_current_member ?: null;
        $memberId = $member['id'] ?? null;
        if (!$memberId || (!$this->isAgentMember($memberId) && !app()->environment('local'))) {
            return response()->json(['ok' => false, 'message' => 'Unauthorized'], 401);
        }

        $threads = $this->getAgentThreads($memberId);
        return response()->json(['ok' => true, 'threads' => $threads]);
    }

    public function messages($targetType = null, $targetId = null)
    {
        $actor = $this->resolveActor();
        if (empty($targetType) || empty($targetId)) {
            return response()->json(['ok' => false, 'message' => 'Invalid target'], 422);
        }

        $targetType = strtolower((string)$targetType);
        $targetId = is_numeric($targetId) ? (int)$targetId : (string)$targetId;

        $query = DB::table('agent_chat_messages');
        if ($actor['type'] === 'member') {
            $memberId = (int)$actor['id'];
            if ($targetType === 'member') {
                $query->where(function ($q) use ($memberId, $targetId) {
                    $q->where('sender_member_id', $memberId)
                        ->where('receiver_member_id', $targetId);
                })->orWhere(function ($q) use ($memberId, $targetId) {
                    $q->where('sender_member_id', $targetId)
                        ->where('receiver_member_id', $memberId);
                });
            } elseif ($targetType === 'guest') {
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

        $messages = $rows->map(function ($row) use ($actor) {
            $isMine = ($actor['type'] === 'member' && (int)$row->sender_member_id === (int)$actor['id'])
                || ($actor['type'] === 'guest' && (string)$row->sender_guest_id === (string)$actor['id']);

            return [
                'id' => (int)$row->id,
                'message' => $row->message,
                'created_at' => $row->created_at,
                'is_mine' => $isMine,
            ];
        })->toArray();

        return response()->json(['ok' => true, 'messages' => $messages]);
    }

    public function send()
    {
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

    private function handleSend(): void
    {
        $actor = $this->resolveActor();
        $targetType = strtolower((string)$this->postParamValue('target_type', ''));
        $targetId = $this->postParamValue('target_id', '');
        $message = trim((string)$this->postParamValue('message', ''));

        \Illuminate\Support\Facades\Log::info('Agent_chat.handleSend', [
            'targetType' => $targetType,
            'targetId' => $targetId,
            'message_length' => strlen($message),
            'actor_type' => $actor['type'] ?? 'unknown'
        ]);

        if ($message === '' || $targetType === '' || $targetId === '') {
            \Illuminate\Support\Facades\Log::warning('Agent_chat.send_failed', [
                'reason' => 'invalid_payload',
                'targetType' => $targetType,
                'targetId' => $targetId,
                'has_message' => !empty($message)
            ]);
            $this->pageResult(['status' => 422, 'message' => 'Invalid payload'], true);
            return;
        }

        $targetId = is_numeric($targetId) ? (int)$targetId : (string)$targetId;

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
            if ($targetType === 'member') {
                $payload['receiver_member_id'] = (int)$targetId;
            } elseif ($targetType === 'guest') {
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
            DB::table('agent_chat_messages')->insert($payload);
            \Illuminate\Support\Facades\Log::info('Agent_chat.send_success', [
                'targetType' => $targetType,
                'targetId' => $targetId
            ]);
            $this->pageResult(['status' => 200, 'message' => 'ok'], true);
        } catch (\Exception $e) {
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

        $guestId = (string)$this->getMyCookie('guest_id');
        if ($guestId === '') {
            $guestId = 'guest_' . Str::random(20);
            $this->setMyCookie('guest_id', $guestId);
        }

        return ['type' => 'guest', 'id' => $guestId];
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

    private function getAgentThreads(int $agentId): array
    {
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
            if (!isset($threads[$key])) {
                $threads[$key] = [
                    'target_type' => $targetType,
                    'target_id' => $targetId,
                    'label' => $this->resolveThreadLabel($targetType, $targetId),
                    'last_message' => $row->message,
                    'last_at' => $row->created_at,
                ];
            }
        }

        return array_values($threads);
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
}
