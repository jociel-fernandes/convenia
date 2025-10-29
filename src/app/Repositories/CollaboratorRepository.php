<?php

namespace App\Repositories;

use App\Contracts\CollaboratorRepositoryInterface;
use App\Models\Collaborator;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class CollaboratorRepository implements CollaboratorRepositoryInterface
{
    /**
     * Get all collaborators with pagination
     */
    public function getAllPaginated(int $perPage = 15): LengthAwarePaginator
    {
        return Collaborator::with('user:id,name,email')
            ->orderBy('name')
            ->paginate($perPage);
    }

    /**
     * Get all collaborators without pagination
     */
    public function getAll(): Collection
    {
        return Collaborator::with('user:id,name,email')
            ->orderBy('name')
            ->get();
    }

    /**
     * Find collaborator by ID
     */
    public function findById(int $id): ?Collaborator
    {
        return Collaborator::with('user:id,name,email')->find($id);
    }

    /**
     * Find collaborator by email
     */
    public function findByEmail(string $email): ?Collaborator
    {
        return Collaborator::where('email', $email)->first();
    }

    /**
     * Find collaborator by CPF
     */
    public function findByCpf(string $cpf): ?Collaborator
    {
        return Collaborator::where('cpf', $cpf)->first();
    }

    /**
     * Create a new collaborator
     */
    public function create(array $data): Collaborator
    {
        $collaborator = Collaborator::create($data);
        return $collaborator->load('user:id,name,email');
    }

    /**
     * Update a collaborator
     */
    public function update(int $id, array $data): Collaborator
    {
        $collaborator = Collaborator::findOrFail($id);
        $collaborator->update($data);
        return $collaborator->load('user:id,name,email');
    }

    /**
     * Delete a collaborator
     */
    public function delete(int $id): bool
    {
        $collaborator = Collaborator::findOrFail($id);
        return $collaborator->delete();
    }

    /**
     * Search collaborators by name, email, or CPF
     */
    public function search(string $query, int $perPage = 15): LengthAwarePaginator
    {
        return Collaborator::with('user:id,name,email')
            ->where(function ($q) use ($query) {
                $q->where('name', 'LIKE', "%{$query}%")
                  ->orWhere('email', 'LIKE', "%{$query}%")
                  ->orWhere('cpf', 'LIKE', "%{$query}%");
            })
            ->orderBy('name')
            ->paginate($perPage);
    }

    /**
     * Filter collaborators with multiple criteria
     */
    public function filter(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        $query = Collaborator::with('user:id,name,email');

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                  ->orWhere('email', 'LIKE', "%{$search}%")
                  ->orWhere('cpf', 'LIKE', "%{$search}%");
            });
        }

        if (!empty($filters['city'])) {
            $query->where('city', $filters['city']);
        }

        if (!empty($filters['state'])) {
            $query->where('state', $filters['state']);
        }

        if (!empty($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        return $query->orderBy('name')->paginate($perPage);
    }

    /**
     * Get collaborators by city
     */
    public function getByCity(string $city, int $perPage = 15): LengthAwarePaginator
    {
        return Collaborator::with('user:id,name,email')
            ->where('city', $city)
            ->orderBy('name')
            ->paginate($perPage);
    }

    /**
     * Get collaborators by state
     */
    public function getByState(string $state, int $perPage = 15): LengthAwarePaginator
    {
        return Collaborator::with('user:id,name,email')
            ->where('state', $state)
            ->orderBy('name')
            ->paginate($perPage);
    }

    /**
     * Get collaborators by user ID
     */
    public function getByUserId(int $userId, int $perPage = 15): LengthAwarePaginator
    {
        return Collaborator::with('user:id,name,email')
            ->where('user_id', $userId)
            ->orderBy('name')
            ->paginate($perPage);
    }

    /**
     * Get collaborator statistics
     */
    public function getStatistics(): array
    {
        return [
            'total_collaborators' => Collaborator::count(),
            'by_state' => $this->countByState(),
            'by_city' => $this->countByCity(),
            'recent_collaborators' => $this->getRecentCollaborators()->count(),
        ];
    }

    /**
     * Count collaborators by state
     */
    public function countByState(): array
    {
        return Collaborator::selectRaw('state, COUNT(*) as count')
            ->groupBy('state')
            ->pluck('count', 'state')
            ->toArray();
    }

    /**
     * Count collaborators by city
     */
    public function countByCity(): array
    {
        return Collaborator::selectRaw('city, COUNT(*) as count')
            ->groupBy('city')
            ->pluck('count', 'city')
            ->toArray();
    }

    /**
     * Get recent collaborators (last 30 days)
     */
    public function getRecentCollaborators(int $days = 30): Collection
    {
        return Collaborator::where('created_at', '>=', now()->subDays($days))
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Check if email exists (excluding specific ID)
     */
    public function emailExists(string $email, ?int $excludeId = null): bool
    {
        $query = Collaborator::where('email', $email);
        
        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }
        
        return $query->exists();
    }

    /**
     * Check if CPF exists (excluding specific ID)
     */
    public function cpfExists(string $cpf, ?int $excludeId = null): bool
    {
        $query = Collaborator::where('cpf', $cpf);
        
        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }
        
        return $query->exists();
    }
}