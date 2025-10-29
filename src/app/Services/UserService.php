<?php

namespace App\Services;

use App\Contracts\UserRepositoryInterface;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

class UserService
{
    public function __construct(
        private UserRepositoryInterface $userRepository
    ) {}

    /**
     * Get paginated users with optional search and role filter
     */
    public function getPaginatedUsers(array $filters = []): LengthAwarePaginator
    {
        $perPage = $filters['per_page'] ?? 15;
        $page = request()->get('page', 1);
        
        // Cache key includes filters and page for accurate caching
        $cacheKey = 'users.paginated.' . md5(serialize($filters)) . ".page.{$page}";

        return Cache::tags(['users'])->remember($cacheKey, 900, function () use ($filters, $perPage) {
            if (!empty($filters['search'])) {
                return $this->userRepository->search($filters['search'], $perPage);
            }

            return $this->userRepository->getPaginatedWithRoles($perPage);
        });
    }

    /**
     * Get all users matching criteria with native caching
     */
    public function getAllUsers(array $filters = []): Collection
    {
        $cacheKey = 'users.all.' . md5(serialize($filters));
        
        return Cache::tags(['users'])->remember($cacheKey, 1800, function () use ($filters) {
            if (!empty($filters['role'])) {
                return $this->getUsersByRole($filters['role']);
            }

            return $this->userRepository->getAllWithRoles();
        });
    }

    /**
     * Find user by ID with native caching
     */
    public function findUser(int $id): ?User
    {
        return Cache::tags(['users'])->remember("users.find.{$id}", 1800, function () use ($id) {
            return $this->userRepository->findById($id);
        });
    }

    /**
     * Create a new user with business logic and cache invalidation
     */
    public function createUser(array $data): User
    {
        return DB::transaction(function () use ($data) {
            // Create user - Observer will handle welcome email automatically
            $user = $this->userRepository->create($data);

            // Clear all user-related cache using tags
            Cache::tags(['users'])->flush();

            return $user;
        });
    }

    /**
     * Update user with business logic and cache invalidation
     */
    public function updateUser(int $id, array $data): User
    {
        return DB::transaction(function () use ($id, $data) {
            $user = $this->userRepository->findById($id);

            if (!$user) {
                throw new \Illuminate\Database\Eloquent\ModelNotFoundException(_('api.errors.user_not_found'));
            }

            // Update user - Observer will handle logging automatically
            $updatedUser = $this->userRepository->update($user, $data);

            // Clear all user-related cache using tags
            Cache::tags(['users'])->flush();

            return $updatedUser;
        });
    }

    /**
     * Soft delete user with cache invalidation
     */
    public function deleteUser(int $id): bool
    {
        return DB::transaction(function () use ($id) {
            $user = $this->userRepository->findById($id);

            if (!$user) {
                throw new \Illuminate\Database\Eloquent\ModelNotFoundException(_('api.errors.user_not_found'));
            }

            $deleted = $this->userRepository->delete($user);

            if ($deleted) {
                // Clear all user-related cache using tags
                Cache::tags(['users'])->flush();
                
                // Observer will handle logging automatically
            }

            return $deleted;
        });
    }

    /**
     * Search users by term with caching
     */
    public function searchUsers(string $search, array $filters = []): LengthAwarePaginator
    {
        $perPage = $filters['per_page'] ?? 15;
        $page = request()->get('page', 1);
        $cacheKey = "users.search." . md5($search . serialize($filters)) . ".page.{$page}";

        return Cache::tags(['users'])->remember($cacheKey, 600, function () use ($search, $perPage) {
            return $this->userRepository->search($search, $perPage);
        });
    }

    /**
     * Get users by role with caching
     */
    public function getUsersByRole(string $roleName): Collection
    {
        return Cache::tags(['users'])->remember("users.role.{$roleName}", 1800, function () use ($roleName) {
            return $this->userRepository->getUsersByRole($roleName);
        });
    }

    /**
     * Get managers (users with manager role) with caching
     */
    public function getManagers(): Collection
    {
        return $this->getUsersByRole('manager');
    }

    /**
     * Get collaborators (users with collaborator role) with caching
     */
    public function getCollaborators(): Collection
    {
        return $this->getUsersByRole('collaborator');
    }

    /**
     * Check if user can manage other users (cached)
     */
    public function canManageUsers(User $user): bool
    {
        return Cache::tags(['users'])->remember("users.can_manage.{$user->id}", 3600, function () use ($user) {
            return $user->hasRole('manager') || $user->hasPermissionTo('manage_users');
        });
    }

    /**
     * Bulk assign role to users with cache invalidation
     */
    public function bulkAssignRole(array $userIds, string $roleName): int
    {
        return DB::transaction(function () use ($userIds, $roleName) {
            $role = Role::findByName($roleName);
            $count = 0;

            foreach ($userIds as $userId) {
                $user = $this->userRepository->findById($userId);
                if ($user) {
                    $user->assignRole($role);
                    $count++;
                }
            }

            // Clear all user-related cache after bulk operation
            Cache::tags(['users'])->flush();

            return $count;
        });
    }

    /**
     * Get user statistics with caching
     */
    public function getUserStatistics(): array
    {
        return Cache::tags(['users'])->remember('users.statistics', 3600, function () {
            return [
                'total_users' => User::count(),
                'managers' => User::role('manager')->count(),
                'collaborators' => User::role('collaborator')->count(),
                'active_users' => User::whereNull('deleted_at')->count(),
            ];
        });
    }
}