<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\AdminController;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Admin Student Interests — notification centre for institution ↔ student interest signals.
 *
 * Routes (auto via RouteMapping):
 *   GET  /admin/student_interests            → index()          — list all notifications
 *   POST /admin/student_interests/update     → update()         — mark contacted / add notes
 */
class Student_Interests extends AdminController
{
    public function __construct(array $data = [])
    {
        parent::__construct($data);
    }

    // -------------------------------------------------------
    // GET /admin/student_interests — notification list
    // -------------------------------------------------------
    public function index()
    {
        $statusFilter = request()->query('status', '');

        $query = DB::table('admin_student_notifications as asn')
            ->join('member as inst', 'inst.id', '=', 'asn.institution_member_id')
            ->join('member as stu', 'stu.id', '=', 'asn.student_member_id')
            ->leftJoin('student_academic_profiles as sap', 'sap.member_id', '=', 'asn.student_member_id')
            ->leftJoin('member_details as md_inst', 'md_inst.member_id', '=', 'asn.institution_member_id')
            ->select(
                'asn.id', 'asn.status', 'asn.is_read', 'asn.admin_notes', 'asn.created_at',
                // Institution info (no email exposed in view)
                'inst.alias_name as inst_name', 'inst.full_name as inst_full_name', 'inst.avatar as inst_avatar',
                'md_inst.company_name as inst_company',
                // Student info (no email exposed in view)
                'stu.alias_name as stu_name', 'stu.first_name as stu_first', 'stu.last_name as stu_last', 'stu.avatar as stu_avatar',
                'sap.headline as stu_headline', 'sap.nationality as stu_nationality',
                'sap.target_degree as stu_degree', 'sap.target_fields as stu_fields',
                'sap.language_scores as stu_lang_scores',
                'asn.student_member_id', 'asn.institution_member_id'
            );

        if (!empty($statusFilter)) {
            $query->where('asn.status', $statusFilter);
        }

        $notifications = $query->orderBy('asn.is_read')->orderByDesc('asn.created_at')->paginate(20);

        // Unread count
        $unreadCount = DB::table('admin_student_notifications')->where('is_read', 0)->count();

        return $this->pageData([
            'notifications' => $notifications,
            'unread_count'  => $unreadCount,
            'status_filter' => $statusFilter,
        ])->pageView('student_interests');
    }

    // -------------------------------------------------------
    // POST /admin/student_interests/update — mark status / add note
    // -------------------------------------------------------
    public function update()
    {
        if (!request()->isMethod('POST')) {
            return response()->json(['status' => 405]);
        }

        $notifId = (int)request()->input('id', 0);
        $action  = (string)request()->input('action', '');
        $note    = mb_substr(strip_tags((string)request()->input('note', '')), 0, 2000);

        if (!$notifId) {
            return response()->json(['status' => 400, 'message' => 'Invalid ID']);
        }

        $notif = DB::table('admin_student_notifications')->where('id', $notifId)->first();
        if (!$notif) {
            return response()->json(['status' => 404, 'message' => 'Not found']);
        }

        try {
            $updateData = ['is_read' => 1, 'updated_at' => now()];

            if ($action === 'contacted') {
                $updateData['status'] = 'contacted';
                // Also update institution_interests status
                DB::table('institution_interests')
                    ->where('institution_member_id', $notif->institution_member_id)
                    ->where('student_member_id', $notif->student_member_id)
                    ->update(['status' => 2, 'updated_at' => now()]);
            } elseif ($action === 'close') {
                $updateData['status'] = 'closed';
            } elseif ($action === 'reopen') {
                $updateData['status'] = 'pending';
            }

            if ($note !== '') {
                $updateData['admin_notes'] = $note;
            }

            DB::table('admin_student_notifications')->where('id', $notifId)->update($updateData);

            return response()->json(['status' => 200, 'message' => 'Updated']);

        } catch (\Exception $e) {
            Log::error('Admin Student_Interests::update error: ' . $e->getMessage());
            return response()->json(['status' => 500, 'message' => 'Update failed']);
        }
    }
}
