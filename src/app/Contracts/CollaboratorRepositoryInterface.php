<?php

namespace App\Contracts;

use App\Models\Collaborator;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface CollaboratorRepositoryInterface
{
    /**
     * Get all collaborators with pagination
     */
    public function getAllPaginated(int $perPage = 15): LengthAwarePaginator;

    /**
     * Get all collaborators without pagination
     */
    public function getAll(): Collection;

    /**
     * Find collaborator by ID
     */
    public function findById(int $id): ?Collaborator;

    /**
     * Find collaborator by email
     */
    public function findByEmail(string $email): ?Collaborator;

    /**
     * Find collaborator by CPF
     */
    public function findByCpf(string $cpf): ?Collaborator;

    /**
     * Create a new collaborator
     */
    public function create(array $data): Collaborator;

    /**
     * Update a collaborator
     */
    public function update(int $id, array $data): Collaborator;

    /**
     * Delete a collaborator
     */
    public function delete(int $id): bool;

    /**
     * Search collaborators by name, email, or CPF
     */
    public function search(string $query, int $perPage = 15): LengthAwarePaginator;

    /**
     * Filter collaborators with multiple criteria
     */
    public function filter(array $filters, int $perPage = 15): LengthAwarePaginator;

    /**
     * Get collaborators by city
     */
    public function getByCity(string $city, int $perPage = 15): LengthAwarePaginator;

    /**
     * Get collaborators by state
     */
    public function getByState(string $state, int $perPage = 15): LengthAwarePaginator;

    /**
     * Get collaborators by user ID
     */
    public function getByUserId(int $userId, int $perPage = 15): LengthAwarePaginator;

    /**
     * Get collaborator statistics
     */
    public function getStatistics(): array;

    /**
     * Count collaborators by state
     */
    public function countByState(): array;

    /**
     * Count collaborators by city
     */
    public function countByCity(): array;

    /**
     * Get recent collaborators (last 30 days)
     */
    public function getRecentCollaborators(int $days = 30): Collection;

    /**
     * Check if email exists (excluding specific ID)
     */
    public function emailExists(string $email, ?int $excludeId = null): bool;

    /**
     * Check if CPF exists (excluding specific ID)
     */
    public function cpfExists(string $cpf, ?int $excludeId = null): bool;
}