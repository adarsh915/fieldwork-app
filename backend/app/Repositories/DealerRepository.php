<?php

namespace App\Repositories;

use App\Models\Dealer;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * DealerRepository handles all database queries for dealers
 */
class DealerRepository
{
    /**
     * Get all dealers with relationships and pagination
     *
     * @param  array  $filters
     * @param  int  $perPage
     * @return LengthAwarePaginator
     */
    public function getAllWithFilters(array $filters = [], int $perPage = 25): LengthAwarePaginator
    {
        $query = Dealer::query()
            ->with(['assignedTo:id,name,email'])
            ->select('id', 'dealer_code', 'name', 'company_name', 'mobile', 'city', 'state', 
                     'outstanding_balance', 'balance_type', 'status', 'assigned_to', 'dealer_type');

        // Apply filters
        if (!empty($filters['assigned_to'])) {
            $query->where('assigned_to', $filters['assigned_to']);
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['city'])) {
            $query->where('city', 'like', '%' . $filters['city'] . '%');
        }

        if (!empty($filters['dealer_type'])) {
            $query->where('dealer_type', $filters['dealer_type']);
        }

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%')
                  ->orWhere('dealer_code', 'like', '%' . $search . '%')
                  ->orWhere('mobile', 'like', '%' . $search . '%')
                  ->orWhere('city', 'like', '%' . $search . '%');
            });
        }

        return $query->orderBy('id', 'desc')->paginate($perPage);
    }

    /**
     * Get dealers assigned to a specific user
     *
     * @param  int  $userId
     * @param  int  $perPage
     * @return LengthAwarePaginator
     */
    public function getAssignedDealers(int $userId, int $perPage = 25): LengthAwarePaginator
    {
        return Dealer::query()
            ->with(['assignedTo:id,name,email'])
            ->where('assigned_to', $userId)
            ->select('id', 'dealer_code', 'name', 'company_name', 'mobile', 'city', 'state',
                     'outstanding_balance', 'balance_type', 'status', 'assigned_to', 'dealer_type')
            ->orderBy('id', 'desc')
            ->paginate($perPage);
    }

    /**
     * Find dealer by ID with relationships
     *
     * @param  int  $id
     * @return Dealer
     */
    public function findWithRelations(int $id): Dealer
    {
        return Dealer::with(['assignedTo:id,name,email', 'creator:id,name', 'updater:id,name'])
            ->findOrFail($id);
    }

    /**
     * Create a new dealer
     *
     * @param  array  $data
     * @return Dealer
     */
    public function create(array $data): Dealer
    {
        return Dealer::create($data);
    }

    /**
     * Update dealer
     *
     * @param  Dealer  $dealer
     * @param  array  $data
     * @return Dealer
     */
    public function update(Dealer $dealer, array $data): Dealer
    {
        $dealer->update($data);
        return $dealer->fresh(['assignedTo']);
    }

    /**
     * Delete dealer (soft delete)
     *
     * @param  Dealer  $dealer
     * @return bool
     */
    public function delete(Dealer $dealer): bool
    {
        return $dealer->delete();
    }

    /**
     * Check if dealer code is unique
     *
     * @param  string  $code
     * @param  int|null  $excludeId
     * @return bool
     */
    public function isDealerCodeUnique(string $code, ?int $excludeId = null): bool
    {
        $query = Dealer::where('dealer_code', $code);
        
        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }
        
        return !$query->exists();
    }

    /**
     * Generate next dealer code
     *
     * @return string
     */
    public function generateDealerCode(): string
    {
        $lastDealer = Dealer::withTrashed()
            ->orderBy('id', 'desc')
            ->first();

        $nextNumber = $lastDealer ? ($lastDealer->id + 1) : 1;
        
        return 'DLR-' . str_pad($nextNumber, 5, '0', STR_PAD_LEFT);
    }

    /**
     * Get all active dealers for dropdown
     *
     * @return Collection
     */
    public function getActiveForDropdown(): Collection
    {
        return Dealer::active()
            ->select('id', 'dealer_code', 'name', 'city')
            ->orderBy('name')
            ->get();
    }

    /**
     * Count dealers by assigned user
     *
     * @param  int  $userId
     * @return int
     */
    public function countByAssignedUser(int $userId): int
    {
        return Dealer::where('assigned_to', $userId)->count();
    }
}
