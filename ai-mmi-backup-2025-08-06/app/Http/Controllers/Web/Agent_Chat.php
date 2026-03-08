<?php
namespace App\Http\Controllers\Web;

use App\Http\Controllers\WebController;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class Agent_Chat extends WebController
{
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

        $this->pageCss('agent_chat');
        $this->pageScript('agent_chat');

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

        $threads = $this->getAgentThreads($memberId);
        return response()->json(['ok' => true, 'threads' => $threads]);
    }

    public function messages($targetType = null, $targetId = null)
    {
        $authResponse = $this->requireMemberAuth('json');
        if ($authResponse !== null) {
            return $authResponse;
        }

        $actor = $this->resolveActor();
        if (empty($targetType) || empty($targetId)) {
            return response()->json(['ok' => false, 'message' => 'Invalid target'], 422);
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

    public function downloadAttachment($attachmentId)
    {
        $authResponse = $this->requireMemberAuth('json');
        if ($authResponse !== null) {
            return $authResponse;
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
}
