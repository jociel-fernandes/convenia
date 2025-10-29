<?php

namespace App\Contracts;

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface UserRepositoryInterface
{
    /**
     * Get all users with pagination
     */
    public function getAllPaginated(int $perPage = 15): LengthAwarePaginator;

    /**
     * Get all users without pagination
     */
    public function getAll(): Collection;

    /**
     * Find user by ID
     */
    public function findById(int $id): ?User;

    /**
     * Find user by email
     */
    public function findByEmail(string $email): ?User;

    /**
     * Create a new user
     */
    public function create(array $data): User;

    /**
     * Update a user
     */
    public function update(User $user, array $data): User;

    /**
     * Delete a user (soft delete)
     */
    public function delete(User $user): bool;

    /**
     * Restore a soft deleted user
     */
    public function restore(User $user): bool;

    /**
     * Get users with specific roles
     */
    public function getUsersByRole(string $role): Collection;

    /**
     * Get users with roles loaded
     */
    public function getAllWithRoles(): Collection;

    /**
     * Get paginated users with roles
     */
    public function getPaginatedWithRoles(int $perPage = 15): LengthAwarePaginator;

    /**
     * Search users by name or email
     */
    public function search(string $query, int $perPage = 15): LengthAwarePaginator;

    /**
     * Get users count by role
     */
    public function countByRole(string $role): int;

    /**
     * Get users with specific permission
     */
    public function getUsersByPermission(string $permission): Collection;

    /**
     * Get managers only
     */
    public function getManagers(): Collection;

    /**
     * Get collaborators only
     */
    public function getCollaborators(): Collection;
}
