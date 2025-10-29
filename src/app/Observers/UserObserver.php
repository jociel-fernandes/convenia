<?php

namespace App\Observers;

use App\Mail\WelcomeUserMail;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class UserObserver
{
    /**
     * Handle the User "created" event.
     */
    public function created(User $user): void
    {
        // Log user creation
        Log::info('User created', [
            'user_id' => $user->id,
            'user_email' => $user->email,
            'user_name' => $user->name,
            'created_at' => now(),
        ]);

        // Send welcome email using native Laravel functionality
        try {
            Mail::to($user->email)->queue(new WelcomeUserMail($user));
            
            Log::info('Welcome email queued', [
                'user_id' => $user->id,
                'user_email' => $user->email,
                'queued_at' => now(),
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to queue welcome email', [
                'user_id' => $user->id,
                'user_email' => $user->email,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Handle the User "updated" event.
     */
    public function updated(User $user): void
    {
        //
    }

    /**
     * Handle the User "deleted" event.
     */
    public function deleted(User $user): void
    {
        //
    }

    /**
     * Handle the User "restored" event.
     */
    public function restored(User $user): void
    {
        //
    }

    /**
     * Handle the User "force deleted" event.
     */
    public function forceDeleted(User $user): void
    {
        //
    }
}
