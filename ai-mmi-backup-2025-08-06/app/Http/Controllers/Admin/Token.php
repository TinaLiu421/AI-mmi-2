<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\AdminController;
use App\Services\TokenService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class Token extends AdminController
{
    public function __construct($data)
    {
        parent::__construct($data);
        $this->pageIndex('token_area');
    }

    /**
     * GET admin/token/index
     * Paginated transaction log with optional member filter.
     */
    public function index($action = '', $id = 0)
    {
        $this->pageAction(function () {
            // No POST actions on this page
        });

        $search   = trim((string) ($this->_page_get_data['search'] ?? ''));
        $typeFilter = trim((string) ($this->_page_get_data['type'] ?? ''));
        $page     = max(1, (int) ($this->_page_get_data['page'] ?? 1));
        $perPage  = 50;

        $query = DB::table('token_transactions as t')
            ->join('member as m', 'm.id', '=', 't.member_id')
            ->select(
                't.id', 't.member_id', 't.type', 't.amount',
                't.balance_after', 't.reference_type', 't.reference_id',
                't.notes', 't.created_at',
                'm.email', 'm.full_name'
            )
            ->orderByDesc('t.id');

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('m.email', 'like', "%{$search}%")
                  ->orWhere('m.full_name', 'like', "%{$search}%")
                  ->orWhere('t.notes', 'like', "%{$search}%")
                  ->orWhere('t.member_id', '=', (int) $search);
            });
        }

        if ($typeFilter !== '') {
            $query->where('t.type', $typeFilter);
        }

        $total      = $query->count();
        $rows       = $query->offset(($page - 1) * $perPage)->limit($perPage)->get();
        $totalPages = max(1, (int) ceil($total / $perPage));

        // Earn types for filter dropdown
        $allTypes = [
            'earn_signup', 'earn_daily_login', 'earn_profile_complete',
            'earn_share_results', 'earn_referral_accepted', 'earn_admin_grant',
            'purchase',
            'spend_chat', 'spend_match', 'spend_agent_call',
            'spend_diy_visa', 'spend_full_assistance', 'spend_school_payment', 'spend_admin_deduct',
            'transfer_out', 'transfer_in',
        ];

        $data = [
            'rows'        => $rows,
            'total'       => $total,
            'page'        => $page,
            'total_pages' => $totalPages,
            'search'      => $search,
            'type_filter' => $typeFilter,
            'all_types'   => $allTypes,
        ];

        return $this->pageData($data)->pageView('token_index');
    }

    /**
     * GET admin/token/member_balance
     * All members with their token balance and active plan.
     */
    public function member_balance($action = '', $id = 0)
    {
        $search  = trim((string) ($this->_page_get_data['search'] ?? ''));
        $page    = max(1, (int) ($this->_page_get_data['page'] ?? 1));
        $perPage = 50;

        // Get the latest active token-plan subscription id per member (avoids dup rows)
        $latestSubRows = DB::select("
            SELECT MAX(s.id) AS id
            FROM app_subscriptions s
            INNER JOIN app_plans p ON p.id = s.plan_id
            WHERE s.status = 'active'
              AND (s.ends_at IS NULL OR s.ends_at > NOW())
              AND p.code IN ('premium', 'vip')
            GROUP BY s.member_id
        ");
        $latestSubIds = collect($latestSubRows)->pluck('id')->filter()->values();

        $query = DB::table('member as m')
            ->leftJoin('subscriptions as s', function ($join) use ($latestSubIds) {
                $join->on('s.member_id', '=', 'm.id');
                if ($latestSubIds->isNotEmpty()) {
                    $join->whereIn('s.id', $latestSubIds);
                } else {
                    $join->whereRaw('1=0');
                }
            })
            ->leftJoin('plans as p', 'p.id', '=', 's.plan_id')
            ->select(
                'm.id', 'm.email', 'm.full_name',
                'm.token_balance', 'm.created_at',
                'p.code as plan_code', 'p.name as plan_name',
                's.ends_at as plan_ends_at'
            )
            ->orderByDesc('m.token_balance');

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('m.email', 'like', "%{$search}%")
                  ->orWhere('m.full_name', 'like', "%{$search}%")
                  ->orWhere('m.id', '=', (int) $search);
            });
        }

        $total      = $query->count();
        $rows       = $query->offset(($page - 1) * $perPage)->limit($perPage)->get();
        $totalPages = max(1, (int) ceil($total / $perPage));

        $data = [
            'rows'        => $rows,
            'total'       => $total,
            'page'        => $page,
            'total_pages' => $totalPages,
            'search'      => $search,
        ];

        return $this->pageData($data)->pageView('token_member_balance');
    }

    /**
     * POST admin/token/grant
     * Grant tokens to a member (earn_admin_grant).
     */
    public function grant($action = '', $id = 0)
    {
        if (!$this->hasUserPrivilege()) {
            return response()->json(['ok' => false, 'message' => 'Unauthorized.'], 401);
        }

        $memberId = (int) $this->postParamValue('member_id', 0);
        $tokens   = (int) $this->postParamValue('tokens', 0);
        $notes    = trim((string) $this->postParamValue('notes', ''));

        if (!$memberId || $tokens <= 0) {
            return response()->json(['ok' => false, 'message' => 'Invalid member_id or token amount.'], 422);
        }

        $member = DB::table('member')->where('id', $memberId)->first();
        if (!$member) {
            return response()->json(['ok' => false, 'message' => 'Member not found.'], 404);
        }

        $svc = new TokenService();
        $ok  = $svc->earn(
            $memberId,
            $tokens,
            TokenService::EARN_ADMIN_GRANT,
            'member',
            $memberId,
            $notes ?: "Admin grant: {$tokens} tokens"
        );

        if ($ok) {
            Log::info("Admin token grant: +{$tokens} to member #{$memberId}", [
                'admin_action' => 'grant',
                'member_id'    => $memberId,
                'tokens'       => $tokens,
                'notes'        => $notes,
            ]);
            return response()->json(['ok' => true, 'new_balance' => $svc->getBalance($memberId)]);
        }

        return response()->json(['ok' => false, 'message' => 'Failed to grant tokens. Please try again.'], 500);
    }

    /**
     * POST admin/token/deduct
     * Deduct tokens from a member (spend_admin_deduct).
     */
    public function deduct($action = '', $id = 0)
    {
        if (!$this->hasUserPrivilege()) {
            return response()->json(['ok' => false, 'message' => 'Unauthorized.'], 401);
        }

        $memberId = (int) $this->postParamValue('member_id', 0);
        $tokens   = (int) $this->postParamValue('tokens', 0);
        $notes    = trim((string) $this->postParamValue('notes', ''));

        if (!$memberId || $tokens <= 0) {
            return response()->json(['ok' => false, 'message' => 'Invalid member_id or token amount.'], 422);
        }

        $member = DB::table('member')->where('id', $memberId)->first();
        if (!$member) {
            return response()->json(['ok' => false, 'message' => 'Member not found.'], 404);
        }

        $svc = new TokenService();
        $ok  = $svc->spend(
            $memberId,
            $tokens,
            TokenService::SPEND_ADMIN_DEDUCT,
            'member',
            $memberId,
            $notes ?: "Admin deduct: {$tokens} tokens"
        );

        if ($ok) {
            Log::info("Admin token deduct: -{$tokens} from member #{$memberId}", [
                'admin_action' => 'deduct',
                'member_id'    => $memberId,
                'tokens'       => $tokens,
                'notes'        => $notes,
            ]);
            return response()->json(['ok' => true, 'new_balance' => $svc->getBalance($memberId)]);
        }

        return response()->json(['ok' => false, 'message' => 'Failed to deduct tokens. Please try again.'], 500);
    }
}
