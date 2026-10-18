<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Employee;
use App\Models\Store;
use App\Services\PayrollService;
use Illuminate\Http\Request;

class AttendanceController extends Controller
{
    public function __construct(
        protected PayrollService $payrollService
    ) {}

    public function index(Request $request)
    {
        $user = auth()->user();
        $date = $request->input('date', now()->format('Y-m-d'));

        if ($user->isSuperAdmin() || $user->isAccountant()) {
            $stores = Store::where('is_active', true)->orderBy('name')->get();
            $storeId = $request->input('store_id') ?: ($stores->first()?->id);
        } elseif ($user->isStoreManager()) {
            $stores = Store::where('is_active', true)
                ->where(function ($q) use ($user) {
                    $q->where('manager_id', $user->id);
                    if ($user->store_id) {
                        $q->orWhere('id', $user->store_id);
                    }
                })->orderBy('name')->get();
            $storeId = $request->input('store_id');
            if (!$storeId || !$stores->contains('id', (int) $storeId)) {
                $storeId = $stores->first()?->id ?: $user->store_id;
            }
        } else {
            $stores = $user->store_id ? Store::where('id', $user->store_id)->get() : collect();
            $storeId = $user->store_id;
        }

        $storeId = $storeId ? (int) $storeId : null;
        $store = $storeId ? Store::find($storeId) : null;

        $employees = Employee::where('employment_status', 'active')
            ->when($storeId, fn ($q) => $q->where('store_id', $storeId))
            ->with(['attendances' => fn ($q) => $q->where('date', $date)])
            ->get();

        $attendances = Attendance::with(['employee', 'recorder', 'store'])
            ->when($storeId, fn ($q) => $q->where('store_id', $storeId))
            ->when($date, fn ($q) => $q->where('date', $date))
            ->latest('id')
            ->paginate(25)
            ->withQueryString();

        return view('attendance.index', compact('employees', 'attendances', 'date', 'store', 'storeId', 'stores'));
    }

    public function store(Request $request)
    {
        $user = auth()->user();

        // Support single attendance submission
        if ($request->has('employee_id')) {
            $validated = $request->validate([
                'employee_id' => 'required|exists:employees,id',
                'store_id' => 'required|exists:stores,id',
                'date' => 'nullable|date',
                'attendance_date' => 'nullable|date',
                'status' => 'required|in:present,absent,late,half_day,leave',
                'check_in' => 'nullable|string',
                'in_time' => 'nullable|string',
                'check_out' => 'nullable|string',
                'out_time' => 'nullable|string',
                'notes' => 'nullable|string|max:255',
            ]);

            $store = Store::findOrFail($validated['store_id']);
            if (!$user->canAccessStore($store->id)) {
                abort(403, "You can only record attendance for your authorized store.");
            }

            $date = $validated['attendance_date'] ?? $validated['date'] ?? now()->toDateString();
            $employee = Employee::findOrFail($validated['employee_id']);

            $this->payrollService->recordAttendance(
                $employee,
                $store,
                $date,
                $validated['status'],
                $user,
                $validated['check_in'] ?? $validated['in_time'] ?? null,
                $validated['check_out'] ?? $validated['out_time'] ?? null,
                $validated['notes'] ?? null
            );

            return redirect()->route('attendance.index', ['date' => $date, 'store_id' => $store->id])
                ->with('success', "Attendance saved for {$employee->name}.");
        }

        // Support batch attendance submission
        $validated = $request->validate([
            'store_id' => 'required|exists:stores,id',
            'date' => 'required|date',
            'attendances' => 'required|array',
            'attendances.*.employee_id' => 'required|exists:employees,id',
            'attendances.*.status' => 'required|in:present,absent,late,half_day,leave',
            'attendances.*.in_time' => 'nullable|string',
            'attendances.*.out_time' => 'nullable|string',
            'attendances.*.notes' => 'nullable|string|max:255',
        ]);

        $store = Store::findOrFail($validated['store_id']);

        if (!$user->canAccessStore($store->id)) {
            abort(403, "You can only record attendance for your authorized store.");
        }

        foreach ($validated['attendances'] as $att) {
            $employee = Employee::findOrFail($att['employee_id']);
            $this->payrollService->recordAttendance(
                $employee,
                $store,
                $validated['date'],
                $att['status'],
                $user,
                $att['in_time'] ?? null,
                $att['out_time'] ?? null,
                $att['notes'] ?? null
            );
        }

        return redirect()->route('attendance.index', ['date' => $validated['date'], 'store_id' => $store->id])
            ->with('success', "Attendance saved for {$validated['date']}.");
    }

    public function correct(Request $request, Attendance $attendance)
    {
        $validated = $request->validate([
            'status' => 'required|in:present,absent,late,half_day,leave',
            'correction_reason' => 'required|string|max:255',
        ]);

        try {
            $this->payrollService->correctAttendance($attendance, $validated['status'], auth()->user(), $validated['correction_reason']);
            return back()->with('success', "Attendance corrected with audit log.");
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}
