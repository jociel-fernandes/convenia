<?php

namespace App\Services;

use App\Contracts\CollaboratorRepositoryInterface;
use App\Models\Collaborator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class CollaboratorService
{
    public function __construct(
        private CollaboratorRepositoryInterface $collaboratorRepository
    ) {}

    /**
     * Get paginated collaborators with optional filters
     */
    public function getPaginatedCollaborators(array $filters = []): LengthAwarePaginator
    {
        $perPage = $filters['per_page'] ?? 15;
        $page = request()->get('page', 1);
        
        // Cache key includes filters and page for accurate caching
        $cacheKey = 'collaborators.paginated.' . md5(serialize($filters)) . ".page.{$page}";

        return Cache::tags(['collaborators'])->remember($cacheKey, 900, function () use ($filters, $perPage) {
            return $this->collaboratorRepository->filter($filters, $perPage);
        });
    }

    /**
     * Create a new collaborator
     */
    public function createCollaborator(array $data): Collaborator
    {
        DB::beginTransaction();

        try {
            $collaborator = $this->collaboratorRepository->create($data);

            // Clear related caches
            Cache::tags(['collaborators'])->flush();

            DB::commit();

            return $collaborator;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Update a collaborator
     */
    public function updateCollaborator(int $id, array $data): Collaborator
    {
        DB::beginTransaction();

        try {
            $collaborator = $this->collaboratorRepository->update($id, $data);

            // Clear related caches
            Cache::tags(['collaborators'])->flush();

            DB::commit();

            return $collaborator;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Delete a collaborator
     */
    public function deleteCollaborator(int $id): bool
    {
        DB::beginTransaction();

        try {
            $result = $this->collaboratorRepository->delete($id);

            // Clear related caches
            Cache::tags(['collaborators'])->flush();

            DB::commit();

            return $result;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Get collaborator by ID
     */
    public function getCollaboratorById(int $id): ?Collaborator
    {
        return Cache::tags(['collaborators'])->remember("collaborator.{$id}", 3600, function () use ($id) {
            return $this->collaboratorRepository->findById($id);
        });
    }

    /**
     * Search collaborators
     */
    public function searchCollaborators(string $query, int $perPage = 15): LengthAwarePaginator
    {
        $page = request()->get('page', 1);
        $cacheKey = "collaborators.search." . md5($query) . ".page.{$page}";

        return Cache::tags(['collaborators'])->remember($cacheKey, 600, function () use ($query, $perPage) {
            return $this->collaboratorRepository->search($query, $perPage);
        });
    }

    /**
     * Get collaborators by city
     */
    public function getCollaboratorsByCity(string $city, int $perPage = 15): LengthAwarePaginator
    {
        $page = request()->get('page', 1);
        $cacheKey = "collaborators.city." . md5($city) . ".page.{$page}";

        return Cache::tags(['collaborators'])->remember($cacheKey, 1800, function () use ($city, $perPage) {
            return $this->collaboratorRepository->getByCity($city, $perPage);
        });
    }

    /**
     * Get collaborators by state
     */
    public function getCollaboratorsByState(string $state, int $perPage = 15): LengthAwarePaginator
    {
        $page = request()->get('page', 1);
        $cacheKey = "collaborators.state." . md5($state) . ".page.{$page}";

        return Cache::tags(['collaborators'])->remember($cacheKey, 1800, function () use ($state, $perPage) {
            return $this->collaboratorRepository->getByState($state, $perPage);
        });
    }

    /**
     * Get collaborators by user ID
     */
    public function getCollaboratorsByUserId(int $userId, int $perPage = 15): LengthAwarePaginator
    {
        $page = request()->get('page', 1);
        $cacheKey = "collaborators.user.{$userId}.page.{$page}";

        return Cache::tags(['collaborators'])->remember($cacheKey, 1800, function () use ($userId, $perPage) {
            return $this->collaboratorRepository->getByUserId($userId, $perPage);
        });
    }

    /**
     * Get collaborator statistics
     */
    public function getCollaboratorStatistics(): array
    {
        return Cache::tags(['collaborators'])->remember('collaborators.statistics', 3600, function () {
            return $this->collaboratorRepository->getStatistics();
        });
    }

    /**
     * Check if email is unique (excluding specific ID)
     */
    public function isEmailUnique(string $email, ?int $excludeId = null): bool
    {
        return !$this->collaboratorRepository->emailExists($email, $excludeId);
    }

    /**
     * Check if CPF is unique (excluding specific ID)
     */
    public function isCpfUnique(string $cpf, ?int $excludeId = null): bool
    {
        return !$this->collaboratorRepository->cpfExists($cpf, $excludeId);
    }

    /**
     * Get recent collaborators
     */
    public function getRecentCollaborators(int $days = 30): Collection
    {
        $cacheKey = "collaborators.recent.{$days}";

        return Cache::tags(['collaborators'])->remember($cacheKey, 1800, function () use ($days) {
            return $this->collaboratorRepository->getRecentCollaborators($days);
        });
    }

    /**
     * Validate CPF format and algorithm
     */
    public function validateCpf(string $cpf): bool
    {
        // Remove any non-numeric characters
        $cpf = preg_replace('/\D/', '', $cpf);

        // Check if it has 11 digits
        if (strlen($cpf) !== 11) {
            return false;
        }

        // Check for known invalid CPFs (all same digits)
        if (preg_match('/^(\d)\1{10}$/', $cpf)) {
            return false;
        }

        // Validate first digit
        $sum = 0;
        for ($i = 0; $i < 9; $i++) {
            $sum += (int) $cpf[$i] * (10 - $i);
        }
        $remainder = $sum % 11;
        $digit1 = ($remainder < 2) ? 0 : 11 - $remainder;

        if ((int) $cpf[9] !== $digit1) {
            return false;
        }

        // Validate second digit
        $sum = 0;
        for ($i = 0; $i < 10; $i++) {
            $sum += (int) $cpf[$i] * (11 - $i);
        }
        $remainder = $sum % 11;
        $digit2 = ($remainder < 2) ? 0 : 11 - $remainder;

        return (int) $cpf[10] === $digit2;
    }
}