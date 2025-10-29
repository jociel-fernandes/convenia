<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class UserPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasRole('manager') ||
               $user->hasPermissionTo('show users');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, User $model): bool
    {
        // Users can always view their own profile
        if ($user->id === $model->id) {
            return true;
        }

        // Managers can view any user
        if ($user->hasRole('manager')) {
            return true;
        }

        // Check specific permission
        return $user->hasPermissionTo('show users');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->hasRole('manager') ||
               $user->hasPermissionTo('create users');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, User $model): bool
    {
        // Users can always update their own profile (with restrictions)
        if ($user->id === $model->id) {
            return true;
        }

        // Managers can update any user
        if ($user->hasRole('manager')) {
            return true;
        }

        // Check specific permission
        return $user->hasPermissionTo('update users');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, User $model): bool
    {
        // Users cannot delete themselves
        if ($user->id === $model->id) {
            return false;
        }

        // Managers can delete collaborators but not other managers
        if ($user->hasRole('manager')) {
            return ! $model->hasRole('manager');
        }

        // Check specific permission
        return $user->hasPermissionTo('delete users') &&
               ! $model->hasRole('manager');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, User $model): bool
    {
        return $user->hasRole('manager') ||
               $user->hasPermissionTo('manage users');
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, User $model): bool
    {
        // Only managers with manage users permission can force delete
        return $user->hasRole('manager') &&
               $user->hasPermissionTo('manage users');
    }

    /**
     * Determine whether the user can manage roles.
     */
    public function manageRoles(User $user, User $model): bool
    {
        // Users cannot change their own role
        if ($user->id === $model->id) {
            return false;
        }

        // Only managers can manage roles
        return $user->hasRole('manager') ||
               $user->hasPermissionTo('manage users');
    }

    /**
     * Determine whether the user can assign specific role.
     */
    public function assignRole(User $user, User $model, string $roleName): bool
    {
        // Check if user can manage roles first
        if (! $this->manageRoles($user, $model)) {
            return false;
        }

        // Managers can assign any role if they have manage users permission
        return $user->hasPermissionTo('manage users');
    }

    /**
     * Determine whether the user can view sensitive information.
     */
    public function viewSensitiveInfo(User $user, User $model): bool
    {
        // Users can view their own sensitive info
        if ($user->id === $model->id) {
            return true;
        }

        // Managers can view sensitive info of collaborators
        return $user->hasRole('manager') ||
               $user->hasPermissionTo('manage users');
    }

    /**
     * Determine whether the user can export user data.
     */
    public function export(User $user): bool
    {
        return $user->hasRole('manager') ||
               $user->hasPermissionTo('export csv');
    }

    /**
     * Determine whether the user can import user data.
     */
    public function import(User $user): bool
    {
        return $user->hasRole('manager') ||
               $user->hasPermissionTo('import csv');
    }
}
