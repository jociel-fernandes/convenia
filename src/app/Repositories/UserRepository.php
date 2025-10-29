<?php

namespace App\Repositories;

use App\Contracts\UserRepositoryInterface;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Hash;

class UserRepository implements UserRepositoryInterface
{
    /**
     * Get all users with pagination
     */
    public function getAllPaginated(int $perPage = 15): LengthAwarePaginator
    {
        return User::with('roles')->paginate($perPage);
    }

    /**
     * Get all users without pagination
     */
    public function getAll(): Collection
    {
        return User::with('roles')->get();
    }

    /**
     * Find user by ID
     */
    public function findById(int $id): ?User
    {
        return User::with('roles')->find($id);
    }

    /**
     * Find user by email
     */
    public function findByEmail(string $email): ?User
    {
        return User::with('roles')->where('email', $email)->first();
    }

    /**
     * Create a new user
     */
    public function create(array $data): User
    {
        // Prepare user data
        $userData = [
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
        ];

        // Create user
        $user = User::create($userData);

        // Assign roles if provided
        if (isset($data['roles']) && is_array($data['roles'])) {
            foreach ($data['roles'] as $role) {
                $user->assignRole($role);
            }
        }

        // Load roles relationship for response
        $user->load('roles');

        return $user;
    }

    /**
     * Update a user
     */
    public function update(User $user, array $data): User
    {
        // Prepare update data
        $updateData = [];

        if (isset($data['name'])) {
            $updateData['name'] = $data['name'];
        }

        if (isset($data['email'])) {
            $updateData['email'] = $data['email'];
        }

        if (isset($data['password'])) {
            $updateData['password'] = Hash::make($data['password']);
        }

        // Update user if there's data to update
        if (! empty($updateData)) {
            $user->update($updateData);
        }

        // Update roles if provided
        if (isset($data['roles']) && is_array($data['roles'])) {
            // Remove all current roles
            $user->syncRoles([]);
            
            // Assign new roles
            foreach ($data['roles'] as $role) {
                $user->assignRole($role);
            }
        }

        // Refresh and load roles
        $user->refresh();
        $user->load('roles');

        return $user;
    }

    /**
     * Delete a user (soft delete)
     */
    public function delete(User $user): bool
    {
        return $user->delete();
    }

    /**
     * Restore a soft deleted user
     */
    public function restore(User $user): bool
    {
        return $user->restore();
    }

    /**
     * Get users with specific roles
     */
    public function getUsersByRole(string $role): Collection
    {
        return User::role($role)->with('roles')->get();
    }

    /**
     * Get users with roles loaded
     */
    public function getAllWithRoles(): Collection
    {
        return User::with('roles')->get();
    }

    /**
     * Get paginated users with roles
     */
    public function getPaginatedWithRoles(int $perPage = 15): LengthAwarePaginator
    {
        return User::with('roles')->paginate($perPage);
    }

    /**
     * Search users by name or email
     */
    public function search(string $query, int $perPage = 15): LengthAwarePaginator
    {
        return User::with('roles')
            ->where(function ($q) use ($query) {
                $q->where('name', 'like', "%{$query}%")
                    ->orWhere('email', 'like', "%{$query}%");
            })
            ->paginate($perPage);
    }

    /**
     * Get users count by role
     */
    public function countByRole(string $role): int
    {
        return User::role($role)->count();
    }

    /**
     * Get users with specific permission
     */
    public function getUsersByPermission(string $permission): Collection
    {
        return User::permission($permission)->with('roles')->get();
    }

    /**
     * Get managers only
     */
    public function getManagers(): Collection
    {
        return $this->getUsersByRole('manager');
    }

    /**
     * Get collaborators only
     */
    public function getCollaborators(): Collection
    {
        return $this->getUsersByRole('collaborator');
    }
}
