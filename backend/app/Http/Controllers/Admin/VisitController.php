<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Visit;
use App\Services\DataScope;
use Illuminate\Http\Request;

class VisitController extends Controller
{
    public function index(Request $request)
    {
        $scopedUserIds = DataScope::visibleUserIds('visits');

        $query = Visit::with(['party', 'user.roles'])
            ->orderBy('created_at', 'desc');

        if ($scopedUserIds !== null) {
            $query->whereIn('user_id', $scopedUserIds);
        }

        if ($request->filled('employee_id')) {
            if ($scopedUserIds === null || in_array($request->employee_id, $scopedUserIds)) {
                $query->where('user_id', $request->employee_id);
            } else {
                $query->whereRaw('1=0');
            }
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('check_in_time', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('check_in_time', '<=', $request->date_to);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('notes', 'like', "%{$search}%")
                  ->orWhere('purpose', 'like', "%{$search}%")
                  ->orWhereHas('party', fn($pq) => $pq->where('name', 'like', "%{$search}%")->orWhere('code', 'like', "%{$search}%"))
                  ->orWhereHas('user', fn($uq) => $uq->where('name', 'like', "%{$search}%"));
            });
        }

        $visits = $query->paginate(20)->withQueryString();

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'records' => $visits->map(function ($visit) {
                    return [
                        'id'             => $visit->id,
                        'party_name'     => $visit->party?->name ?? '—',
                        'party_code'     => $visit->party?->party_code ?? $visit->party?->code ?? '',
                        'user_name'      => $visit->user ? $visit->user->name_with_role : 'Unknown',
                        'check_in_time'  => $visit->check_in_time ? $visit->check_in_time->format('d M Y, h:i A') : 'Pending',
                        'check_out_time' => $visit->check_out_time ? $visit->check_out_time->format('d M Y, h:i A') : '—',
                        'status'         => $visit->status,
                        'purpose'        => $visit->purpose ?? '',
                        'notes'          => $visit->notes ?? '',
                        'check_in_photo' => $visit->check_in_photo_url,
                    ];
                }),
                'pagination' => [
                    'current_page' => $visits->currentPage(),
                    'last_page'    => $visits->lastPage(),
                    'per_page'     => $visits->perPage(),
                    'total'        => $visits->total(),
                    'from'         => $visits->firstItem() ?? 0,
                    'to'           => $visits->lastItem() ?? 0,
                ],
            ]);
        }

        $employeesQuery = User::with('roles')->where('is_active', true);
        if ($scopedUserIds !== null) {
            $employeesQuery->whereIn('id', $scopedUserIds);
        } else {
            $employeesQuery->whereHas('roles', fn($q) => $q->whereIn('name', ['Field Worker', 'field_worker', 'Employee']));
        }
        $employees = $employeesQuery->orderBy('name')->get();

        return view('admin.visits.index', compact('visits', 'employees'));
    }
}
