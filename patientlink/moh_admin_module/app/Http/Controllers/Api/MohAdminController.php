<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use App\Models\Patient;
use App\Models\Doctor;
use App\Models\Facility;
use App\Models\ConsentRecord;
use Illuminate\Http\Request;

class MohAdminController extends Controller
{
    /**
     * System-wide analytics (FR-M03)
     */
    public function analytics()
    {
        return response()->json([
            'totals' => [
                'patients'   => Patient::count(),
                'doctors'    => Doctor::count(),
                'facilities' => Facility::count(),
                'users'      => User::count(),
            ],
            'consents' => [
                'pending'  => ConsentRecord::where('status', 'pending')->count(),
                'approved' => ConsentRecord::where('status', 'approved')->count(),
                'expired'  => ConsentRecord::where('status', 'expired')->count(),
                'rejected' => ConsentRecord::where('status', 'rejected')->count(),
            ],
            'users_by_role' => [
                'doctors'          => User::where('role', 'doctor')->count(),
                'patients'         => User::where('role', 'patient')->count(),
                'facility_admins'  => User::where('role', 'facility_admin')->count(),
                'moh_admins'       => User::where('role', 'moh_admin')->count(),
            ],
            'active_users' => User::where('is_active', true)->count(),
        ]);
    }

    /**
     * System-wide audit logs with filtering and pagination (FR-M02)
     */
    public function auditLogs(Request $request)
    {
        $query = AuditLog::with('actor:id,name,role')
            ->orderBy('timestamp', 'desc');

        if ($request->filled('action')) {
            $query->where('action', 'like', '%' . $request->action . '%');
        }

        if ($request->filled('outcome')) {
            $query->where('outcome', $request->outcome);
        }

        if ($request->filled('role')) {
            $query->whereHas('actor', fn($q) => $q->where('role', $request->role));
        }

        if ($request->filled('from')) {
            $query->where('timestamp', '>=', $request->from);
        }

        if ($request->filled('to')) {
            $query->where('timestamp', '<=', $request->to);
        }

        $logs = $query->paginate($request->get('per_page', 20));

        return response()->json($logs);
    }
}
