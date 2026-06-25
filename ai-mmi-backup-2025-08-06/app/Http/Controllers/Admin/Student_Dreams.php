<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\AdminController;
use Illuminate\Support\Facades\DB;

class Student_Dreams extends AdminController
{
    public function __construct($data)
    {
        parent::__construct($data);
        $this->pageIndex('student_dreams');
    }

    // ─── Listing ──────────────────────────────────────────────────────────────
    public function index()
    {
        if (!$this->hasUserPrivilege()) {
            return $this->doRedirect($this->toURL('home'));
        }

        // Handle POST actions (remove / restore) via AJAX
        $this->pageAction(function () {
            $action   = strtolower($this->postParamValue('page_action', ''));
            $dreamId  = (int)$this->postParamValue('id', 0);

            if (!$dreamId) {
                $this->pageResult(['status' => 400, 'message' => 'Invalid dream ID.']);
                return;
            }

            if (!DB::getSchemaBuilder()->hasTable('app_student_dreams')) {
                $this->pageResult(['status' => 503, 'message' => 'Dreams table not found.']);
                return;
            }

            if ($action === 'remove') {
                // Soft-delete: set status=0 and deleted_at
                $affected = DB::table('app_student_dreams')
                    ->where('id', $dreamId)
                    ->whereNull('deleted_at')   // guard: only delete once
                    ->update([
                        'status'     => 0,
                        'deleted_at' => now(),
                    ]);

                if ($affected) {
                    $this->pageResult(['status' => 200, 'message' => 'Dream removed successfully.'], true);
                } else {
                    $this->pageResult(['status' => 404, 'message' => 'Dream not found or already removed.']);
                }
                return;
            }

            if ($action === 'restore') {
                $affected = DB::table('app_student_dreams')
                    ->where('id', $dreamId)
                    ->whereNotNull('deleted_at')
                    ->update([
                        'status'     => 1,
                        'deleted_at' => null,
                    ]);

                if ($affected) {
                    $this->pageResult(['status' => 200, 'message' => 'Dream restored successfully.'], true);
                } else {
                    $this->pageResult(['status' => 404, 'message' => 'Dream not found or already active.']);
                }
                return;
            }

            $this->pageResult(['status' => 400, 'message' => 'Unknown action.']);
        });

        // ─── Build listing ────────────────────────────────────────────────────
        if (!DB::getSchemaBuilder()->hasTable('app_student_dreams')) {
            return $this->pageData([
                'rows'        => [],
                'total'       => 0,
                'page'        => 1,
                'total_pages' => 1,
                'search'      => '',
                'status_filter' => '',
                'dreams_missing' => true,
            ])->pageView('student_dreams');
        }

        $search       = trim((string)$this->getParamValue('search', ''));
        $statusFilter = $this->getParamValue('status_filter', 'active'); // active | removed | all
        $page         = max(1, (int)$this->getParamValue('page', 1));
        $perPage      = 20;

        $q = DB::table('app_student_dreams as d')
            ->join('member as m', 'm.id', '=', 'd.member_id')
            ->select([
                'd.id',
                'd.member_id',
                'd.title',
                'd.description',
                'd.status',
                'd.created_at',
                'd.deleted_at',
                'm.alias_name',
                'm.email',
                'm.avatar',
            ]);

        // Status filter
        if ($statusFilter === 'active') {
            $q->where('d.status', 1)->whereNull('d.deleted_at');
        } elseif ($statusFilter === 'removed') {
            $q->where(function ($sub) {
                $sub->where('d.status', 0)->orWhereNotNull('d.deleted_at');
            });
        }
        // 'all' → no filter

        // Search across member name, email, dream title
        if ($search !== '') {
            $like = '%' . $search . '%';
            $q->where(function ($sub) use ($like) {
                $sub->where('d.title', 'like', $like)
                    ->orWhere('m.alias_name', 'like', $like)
                    ->orWhere('m.email', 'like', $like);
            });
        }

        $total      = $q->count();
        $totalPages = max(1, (int)ceil($total / $perPage));
        $page       = min($page, $totalPages);

        $rows = $q->orderBy('d.id', 'desc')
            ->offset(($page - 1) * $perPage)
            ->limit($perPage)
            ->get()
            ->map(fn($r) => (array)$r)
            ->toArray();

        $this->pageNavigator('Student Dreams');

        return $this->pageData([
            'rows'          => $rows,
            'total'         => $total,
            'page'          => $page,
            'total_pages'   => $totalPages,
            'search'        => $search,
            'status_filter' => $statusFilter,
            'dreams_missing'=> false,
        ])->pageView('student_dreams');
    }
}
