<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Dealer\StoreDealerRequest;
use App\Http\Requests\Admin\Dealer\UpdateDealerRequest;
use App\Services\DealerService;
use App\Models\User;
use App\Models\Dealer;
use Illuminate\Http\Request;

/**
 * DealerController handles dealer management
 * 
 * THIN CONTROLLER - All business logic in DealerService
 * All database queries in DealerRepository
 */
class DealerController extends Controller
{
    protected DealerService $dealerService;

    public function __construct(DealerService $dealerService)
    {
        $this->dealerService = $dealerService;
    }

    /**
     * Display list of dealers
     * Super Admin sees all with filter, others see only assigned
     */
    public function index(Request $request)
    {
        // Authorization check
        $this->authorize('viewAny', Dealer::class);

        // Get filters from request
        $filters = [
            'assigned_to' => $request->input('assigned_to'),
            'status' => $request->input('status'),
            'city' => $request->input('city'),
            'dealer_type' => $request->input('dealer_type'),
            'search' => $request->input('search'),
        ];

        // Get dealers through service
        $dealers = $this->dealerService->getDealersForUser(auth()->user(), $filters, 25);

        // Get users for filter dropdown (only for Super Admin)
        $users = auth()->user()->hasRole('Super Admin') 
            ? User::select('id', 'name')->orderBy('name')->get()
            : collect();

        return view('admin.dealers.index', compact('dealers', 'users', 'filters'));
    }

    /**
     * Show create dealer form
     */
    public function create()
    {
        // Authorization check
        $this->authorize('create', Dealer::class);

        // Generate dealer code
        $dealerCode = $this->dealerService->generateDealerCode();

        // Get users for assignment dropdown (only for Super Admin)
        $users = auth()->user()->hasRole('Super Admin')
            ? User::select('id', 'name', 'email')->orderBy('name')->get()
            : collect();

        return view('admin.dealers.create', compact('dealerCode', 'users'));
    }

    /**
     * Store new dealer
     */
    public function store(StoreDealerRequest $request)
    {
        // Authorization handled by FormRequest
        // Validation handled by FormRequest

        $dealer = $this->dealerService->createDealer(
            $request->validated(),
            auth()->id()
        );

        return redirect()->route('admin.dealers.index')
            ->with('success', 'Dealer "' . $dealer->name . '" created successfully!');
    }

    /**
     * Show edit dealer form
     */
    public function edit($id)
    {
        // Get dealer
        $dealer = $this->dealerService->getDealerById($id);

        // Authorization check
        $this->authorize('update', $dealer);

        // Get users for assignment dropdown (only for Super Admin)
        $users = auth()->user()->hasRole('Super Admin')
            ? User::select('id', 'name', 'email')->orderBy('name')->get()
            : collect();

        return view('admin.dealers.edit', compact('dealer', 'users'));
    }

    /**
     * Update dealer
     */
    public function update(UpdateDealerRequest $request, $id)
    {
        // Get dealer for authorization
        $dealer = $this->dealerService->getDealerById($id);

        // Authorization check
        $this->authorize('update', $dealer);

        // Update dealer
        $dealer = $this->dealerService->updateDealer(
            $id,
            $request->validated(),
            auth()->id()
        );

        return redirect()->route('admin.dealers.index')
            ->with('success', 'Dealer "' . $dealer->name . '" updated successfully!');
    }

    /**
     * Delete dealer
     */
    public function destroy($id)
    {
        // Get dealer
        $dealer = $this->dealerService->getDealerById($id);

        // Authorization check
        $this->authorize('delete', $dealer);

        // Delete dealer
        $this->dealerService->deleteDealer($id);

        return redirect()->route('admin.dealers.index')
            ->with('success', 'Dealer deleted successfully!');
    }

    /**
     * Toggle dealer status
     */
    public function toggleStatus($id)
    {
        // Get dealer
        $dealer = $this->dealerService->getDealerById($id);

        // Authorization check
        $this->authorize('update', $dealer);

        // Toggle status
        $dealer = $this->dealerService->toggleStatus($id, auth()->id());

        return back()->with('success', 'Dealer status updated to ' . ucfirst($dealer->status));
    }

    /**
     * Show dealer details
     */
    public function show($id)
    {
        // Get dealer
        $dealer = $this->dealerService->getDealerById($id);

        // Authorization check
        $this->authorize('view', $dealer);

        return view('admin.dealers.show', compact('dealer'));
    }
}
