<?php

namespace App\Services;

use App\Models\Dealer;
use App\Repositories\DealerRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

/**
 * DealerService handles all business logic for dealer management
 */
class DealerService
{
    protected DealerRepository $dealerRepository;

    public function __construct(DealerRepository $dealerRepository)
    {
        $this->dealerRepository = $dealerRepository;
    }

    /**
     * Get dealers based on user role and filters
     * Super Admin sees all, others see only assigned
     *
     * @param  \App\Models\User  $user
     * @param  array  $filters
     * @param  int  $perPage
     * @return LengthAwarePaginator
     */
    public function getDealersForUser($user, array $filters = [], int $perPage = 25): LengthAwarePaginator
    {
        // Super Admin can see all dealers and filter by assigned user
        if ($user->hasRole('Super Admin')) {
            return $this->dealerRepository->getAllWithFilters($filters, $perPage);
        }

        // Other users only see dealers assigned to them
        return $this->dealerRepository->getAssignedDealers($user->id, $perPage);
    }

    /**
     * Get dealer by ID
     *
     * @param  int  $id
     * @return Dealer
     */
    public function getDealerById(int $id): Dealer
    {
        return $this->dealerRepository->findWithRelations($id);
    }

    /**
     * Create a new dealer
     *
     * @param  array  $data
     * @param  int  $createdBy
     * @return Dealer
     */
    public function createDealer(array $data, int $createdBy): Dealer
    {
        return DB::transaction(function () use ($data, $createdBy) {
            // Generate dealer code if not provided
            if (empty($data['dealer_code'])) {
                $data['dealer_code'] = $this->dealerRepository->generateDealerCode();
            }

            // Set audit fields
            $data['created_by'] = $createdBy;
            $data['updated_by'] = $createdBy;

            // Create dealer
            return $this->dealerRepository->create($data);
        });
    }

    /**
     * Update dealer
     *
     * @param  int  $id
     * @param  array  $data
     * @param  int  $updatedBy
     * @return Dealer
     */
    public function updateDealer(int $id, array $data, int $updatedBy): Dealer
    {
        return DB::transaction(function () use ($id, $data, $updatedBy) {
            $dealer = $this->dealerRepository->findWithRelations($id);

            // Set audit field
            $data['updated_by'] = $updatedBy;

            // Update dealer
            return $this->dealerRepository->update($dealer, $data);
        });
    }

    /**
     * Delete dealer
     *
     * @param  int  $id
     * @return bool
     */
    public function deleteDealer(int $id): bool
    {
        $dealer = $this->dealerRepository->findWithRelations($id);
        return $this->dealerRepository->delete($dealer);
    }

    /**
     * Assign dealer to user (salesman/manager)
     *
     * @param  int  $dealerId
     * @param  int|null  $userId
     * @param  int  $updatedBy
     * @return Dealer
     */
    public function assignDealerToUser(int $dealerId, ?int $userId, int $updatedBy): Dealer
    {
        return DB::transaction(function () use ($dealerId, $userId, $updatedBy) {
            $dealer = $this->dealerRepository->findWithRelations($dealerId);

            return $this->dealerRepository->update($dealer, [
                'assigned_to' => $userId,
                'updated_by' => $updatedBy,
            ]);
        });
    }

    /**
     * Toggle dealer status
     *
     * @param  int  $id
     * @param  int  $updatedBy
     * @return Dealer
     */
    public function toggleStatus(int $id, int $updatedBy): Dealer
    {
        return DB::transaction(function () use ($id, $updatedBy) {
            $dealer = $this->dealerRepository->findWithRelations($id);

            $newStatus = $dealer->status === Dealer::STATUS_ACTIVE 
                ? Dealer::STATUS_INACTIVE 
                : Dealer::STATUS_ACTIVE;

            return $this->dealerRepository->update($dealer, [
                'status' => $newStatus,
                'updated_by' => $updatedBy,
            ]);
        });
    }

    /**
     * Check if dealer code is available
     *
     * @param  string  $code
     * @param  int|null  $excludeId
     * @return bool
     */
    public function isDealerCodeAvailable(string $code, ?int $excludeId = null): bool
    {
        return $this->dealerRepository->isDealerCodeUnique($code, $excludeId);
    }

    /**
     * Generate next available dealer code
     *
     * @return string
     */
    public function generateDealerCode(): string
    {
        return $this->dealerRepository->generateDealerCode();
    }

    /**
     * Get active dealers for dropdown
     *
     * @return Collection
     */
    public function getActiveDealersForDropdown(): Collection
    {
        return $this->dealerRepository->getActiveForDropdown();
    }

    /**
     * Get dealer count by assigned user
     *
     * @param  int  $userId
     * @return int
     */
    public function getDealerCountByUser(int $userId): int
    {
        return $this->dealerRepository->countByAssignedUser($userId);
    }

    /**
     * Check if user can access dealer
     * Super Admin can access all, others only assigned
     *
     * @param  \App\Models\User  $user
     * @param  Dealer  $dealer
     * @return bool
     */
    public function canUserAccessDealer($user, Dealer $dealer): bool
    {
        // Super Admin can access all dealers
        if ($user->hasRole('Super Admin')) {
            return true;
        }

        // Other users can only access dealers assigned to them
        return $dealer->assigned_to === $user->id;
    }
}
