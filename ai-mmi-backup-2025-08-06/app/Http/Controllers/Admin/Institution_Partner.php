<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\AdminController;
use Illuminate\Support\Facades\DB;

class Institution_Partner extends AdminController
{
    public function __construct($data)
    {
        parent::__construct($data);
        $this->pageIndex('institution_partner');
    }

    public function index()
    {
        $page    = max(1, (int)$this->getParamValue('page', 1));
        $perPage = 20;
        $search  = $this->toPlainText($this->getParamValue('search', ''));
        $status  = $this->toPlainText($this->getParamValue('status_filter', ''));

        $query = DB::table('institution_partner_inquiries');

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('institution_name', 'like', '%' . $search . '%')
                  ->orWhere('contact_person',  'like', '%' . $search . '%')
                  ->orWhere('email',           'like', '%' . $search . '%')
                  ->orWhere('country',         'like', '%' . $search . '%');
            });
        }

        if (!empty($status)) {
            $query->where('status', $status);
        }

        $total      = (clone $query)->count();
        $totalPages = max(1, ceil($total / $perPage));
        $page       = min($page, $totalPages);
        $rows       = (clone $query)->orderBy('created_at', 'desc')
                                    ->offset(($page - 1) * $perPage)
                                    ->limit($perPage)
                                    ->get();

        return $this->pageData([
            'rows'         => $rows,
            'total'        => $total,
            'page'         => $page,
            'total_pages'  => $totalPages,
            'search'       => $search,
            'status_filter'=> $status,
        ])->pageView();
    }

    public function details($id = 0)
    {
        $row = DB::table('institution_partner_inquiries')->where('id', (int)$id)->first();

        if (empty($row)) {
            $this->doRedirect(url('admin/institution_partner'));
            return;
        }

        // Mark as read if it was new
        if ($row->status === 'new') {
            DB::table('institution_partner_inquiries')
              ->where('id', (int)$id)
              ->update(['status' => 'read', 'updated_at' => now()]);
            $row->status = 'read';
        }

        // Handle status update POST
        $this->pageAction(function() use ($id) {
            $newStatus = $this->toPlainText($this->postParamValue('status'));
            $allowed   = ['new', 'read', 'contacted', 'closed'];
            if (in_array($newStatus, $allowed, true)) {
                DB::table('institution_partner_inquiries')
                  ->where('id', (int)$id)
                  ->update(['status' => $newStatus, 'updated_at' => now()]);
            }
            $this->doRedirect(url('admin/institution_partner/details/' . $id));
        });

        return $this->pageData(['row' => $row])->pageView();
    }
}
