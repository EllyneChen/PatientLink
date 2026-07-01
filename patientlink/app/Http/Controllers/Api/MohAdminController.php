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
    public function analytics()
    {
        $totalPatients   = Patient::count();
        $totalDoctors    = Doctor::count();
        $totalFacilities = Facility::count();
        $totalUsers      = User::count();
        $activeUsers     = User::where('is_active', true)->count();

        $consentStats = [
            'pending'  => ConsentRecord::where('status', 'pending')->count(),
            'approved' => ConsentRecord::where('status', 'approved')->count(),
            'rejected' => ConsentRecord::where('status', 'rejected')->count(),
            'expired'  => ConsentRecord::where('status', 'expired')->count(),
        ];

        $recentActivity = AuditLog::with('actor:id,name,role')
            ->orderBy('timestamp', 'desc')
            ->limit(5)
            ->get()
            ->map(fn($l) => [
                'actor'     => $l->actor ? $l->actor->name : 'System',
                'role'      => $l->actor ? $l->actor->role : '—',
                'action'    => $l->action,
                'outcome'   => $l->outcome,
                'timestamp' => $l->timestamp,
            ]);

        return response()->json([
            'totals' => [
                'patients'   => $totalPatients,
                'doctors'    => $totalDoctors,
                'facilities' => $totalFacilities,
                'users'      => $totalUsers,
            ],
            'consents' => [
                'pending'  => $consentStats['pending'],
                'approved' => $consentStats['approved'],
                'rejected' => $consentStats['rejected'],
                'expired'  => $consentStats['expired'],
            ],
            'active_users'    => $activeUsers,
            'recent_activity' => $recentActivity,
        ]);
    }

    public function generateReportPdf()
{
    $totalPatients   = Patient::count();
    $totalDoctors    = Doctor::count();
    $totalFacilities = Facility::count();
    $activeUsers     = User::where('is_active', true)->count();

    $consentStats = [
        'pending'  => ConsentRecord::where('status', 'pending')->count(),
        'approved' => ConsentRecord::where('status', 'approved')->count(),
        'rejected' => ConsentRecord::where('status', 'rejected')->count(),
        'expired'  => ConsentRecord::where('status', 'expired')->count(),
    ];

    $recentActivity = AuditLog::with('actor:id,name,role')
        ->orderBy('timestamp', 'desc')
        ->limit(15)
        ->get()
        ->map(fn($l) => [
            'actor'     => $l->actor ? $l->actor->name : 'System',
            'role'      => $l->actor ? $l->actor->role : '—',
            'action'    => $l->action,
            'outcome'   => $l->outcome,
            'timestamp' => $l->timestamp,
        ]);

    $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('reports.moh-report', [
        'totals' => [
            'patients'   => $totalPatients,
            'doctors'    => $totalDoctors,
            'facilities' => $totalFacilities,
        ],
        'consents'       => $consentStats,
        'activeUsers'    => $activeUsers,
        'recentActivity' => $recentActivity,
        'generatedAt'    => now()->format('d M Y, H:i'),
    ]);

    return $pdf->download('patientlink-moh-report-' . now()->format('Y-m-d') . '.pdf');
}

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

        $logs = $query->paginate(15);

        return response()->json($logs->through(fn($l) => [
            'log_id'      => $l->id,
            'actor'       => $l->actor ? [
                'name'  => $l->actor->name,
                'email' => $l->actor->email,
                'role'  => $l->actor->role,
            ] : null,
            'action'      => $l->action,
            'outcome'     => $l->outcome,
            'entity_type' => $l->entity_type ?? null,
            'timestamp'   => $l->timestamp,
        ]));
    }
}