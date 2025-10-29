<?php

namespace Tests\Feature;

use App\Mail\WelcomeUserMail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class WelcomeEmailTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create roles for API guard (default guard)
        Role::create(['name' => 'manager', 'guard_name' => 'api']);
        Role::create(['name' => 'collaborator', 'guard_name' => 'api']);
    }

    public function test_welcome_email_is_sent_when_user_is_created(): void
    {
        Mail::fake();

        // Create a new user - UserObserver will automatically trigger
        $userData = [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123',
            'roles' => ['collaborator']
        ];

        $user = User::create([
            'name' => $userData['name'],
            'email' => $userData['email'],
            'password' => bcrypt($userData['password']),
        ]);

        $user->assignRole('collaborator');

        // Assert that a welcome email was queued (UserObserver handles this automatically)
        Mail::assertQueued(WelcomeUserMail::class, function ($mail) use ($user) {
            return $mail->hasTo($user->email);
        });
    }

    public function test_welcome_email_contains_correct_user_data(): void
    {
        Mail::fake();

        $user = User::create([
            'name' => 'João Silva',
            'email' => 'joao@example.com',
            'password' => bcrypt('password123'),
        ]);

        $user->assignRole('manager');

        // Create and queue the welcome email
        $mail = new WelcomeUserMail($user);

        // Queue the email (will be queued due to ShouldQueue)
        Mail::to($user->email)->queue($mail);

        // Assert that the email was queued with the correct data
        Mail::assertQueued(WelcomeUserMail::class, function ($mail) use ($user) {
            $mailContent = $mail->content();
            
            return $mail->hasTo($user->email) && 
                   isset($mailContent->with['user']) &&
                   $mailContent->with['user']->id === $user->id &&
                   isset($mailContent->with['loginUrl']);
        });
    }

    public function test_welcome_email_handles_different_roles(): void
    {
        Mail::fake();

        // Test with manager role
        $manager = User::create([
            'name' => 'Manager User',
            'email' => 'manager@example.com',
            'password' => bcrypt('password123'),
        ]);
        $manager->assignRole('manager');

        // Test with collaborator role
        $collaborator = User::create([
            'name' => 'Collaborator User',
            'email' => 'collaborator@example.com',
            'password' => bcrypt('password123'),
        ]);
        $collaborator->assignRole('collaborator');

        // Assert both emails were queued automatically by UserObserver
        Mail::assertQueued(WelcomeUserMail::class, 2);
    }
}