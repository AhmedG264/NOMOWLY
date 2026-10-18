<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AuditAction;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AuditLogController extends Controller
{
    /**
     * Display a listing of audit and monitoring logs with multi-dimensional filtering.
     */
    public function index(Request $request): View
    {
        $query = AuditLog::with('user')->latest('created_at');

        // 1. Text Search (User Name, Email, User ID, Entity ID, IP)
        if ($request->filled('q')) {
            $search = trim((string) $request->input('q'));
            $query->where(function ($q) use ($search) {
                $q->where('entity_id', 'like', "%{$search}%")
                  ->orWhere('ip_address', 'like', "%{$search}%")
                  ->orWhere('reason', 'like', "%{$search}%")
                  ->orWhereHas('user', function ($uq) use ($search) {
                      $uq->where('email', 'like', "%{$search}%")
                         ->orWhere('id', 'like', "%{$search}%");
                  });
            });
        }

        // 2. Filter by Audit Action
        if ($request->filled('action')) {
            $query->where('action', $request->input('action'));
        }

        // 3. Filter by Status / Result
        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        // 4. Filter by Entity Type
        if ($request->filled('entity_type')) {
            $query->where('entity_type', $request->input('entity_type'));
        }

        // 5. Filter by Entity ID
        if ($request->filled('entity_id')) {
            $query->where('entity_id', trim((string) $request->input('entity_id')));
        }

        // 6. Filter by IP Address
        if ($request->filled('ip_address')) {
            $query->where('ip_address', trim((string) $request->input('ip_address')));
        }

        // 7. Filter by Date Range
        if ($request->filled('date_from')) {
            try {
                $from = Carbon::parse($request->input('date_from'))->startOfDay();
                $query->where('created_at', '>=', $from);
            } catch (\Exception) {}
        }

        if ($request->filled('date_to')) {
            try {
                $to = Carbon::parse($request->input('date_to'))->endOfDay();
                $query->where('created_at', '<=', $to);
            } catch (\Exception) {}
        }

        $logs = $query->paginate(25)->withQueryString();

        // High-level monitoring KPI metrics
        $now = now();
        $stats = [
            'total_logs' => AuditLog::count(),
            'last_24h' => AuditLog::where('created_at', '>=', $now->copy()->subDay())->count(),
            'failed_or_rejected' => AuditLog::whereIn('status', ['FAILED', 'REJECTED', 'CANCELLED'])->count(),
            'security_alerts' => AuditLog::where('action', AuditAction::UNAUTHORIZED_ACCESS_ATTEMPT)
                ->orWhere('status', 'UNAUTHORIZED')
                ->count(),
        ];

        // Unique entity types for filter dropdown
        $availableEntityTypes = AuditLog::whereNotNull('entity_type')
            ->distinct()
            ->pluck('entity_type')
            ->filter()
            ->values();

        // Available actions for filter dropdown grouped by category
        $actions = AuditAction::cases();

        return view('admin.audit-logs.index', compact('logs', 'stats', 'availableEntityTypes', 'actions'));
    }

    /**
     * Display full details of an individual audit event.
     */
    public function show(AuditLog $auditLog): View
    {
        $auditLog->load('user');

        return view('admin.audit-logs.show', compact('auditLog'));
    }
}
