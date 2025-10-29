<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Passport\HasApiTokens; // ✅ Passport em vez de Sanctum
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, HasRoles, Notifiable, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Check if user is a manager
     */
    public function isManager(): bool
    {
        return $this->hasRole('manager');
    }

    /**
     * Check if user is a collaborator
     */
    public function isCollaborator(): bool
    {
        return $this->hasRole('collaborator');
    }

    /**
     * Check if user can manage users
     */
    public function canManageUsers(): bool
    {
        return $this->can('manage users');
    }

    /**
     * Check if user can manage collaborators
     */
    public function canManageCollaborators(): bool
    {
        return $this->can('manage collaborators');
    }

    /**
     * Get the collaborators for the user.
     */
    public function collaborators()
    {
        return $this->hasMany(Collaborator::class);
    }
}
