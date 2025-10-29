<?php

namespace Tests\Feature;

use App\Mail\WelcomeUserMail;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class EmailQueueTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create roles for API guard (default guard)
        Role::firstOrCreate(['name' => 'manager', 'guard_name' => 'api']);
        Role::firstOrCreate(['name' => 'collaborator', 'guard_name' => 'api']);
    }

    public function test_welcome_email_is_queued_when_user_is_created(): void
    {
        Queue::fake();
        Mail::fake();

        $user = User::create([
            'name' => 'Queue Test User',
            'email' => 'queue.test@example.com',
            'password' => bcrypt('password123'),
        ]);

        $user->assignRole('collaborator');

        // Assert that the welcome email was queued (UserObserver handles this automatically)
        Mail::assertQueued(WelcomeUserMail::class, function ($mail) use ($user) {
            return $mail->hasTo($user->email) && 
                   $mail->queue === 'emails';
        });
    }

    public function test_welcome_email_has_correct_queue_configuration(): void
    {
        $user = User::create([
            'name' => 'Config Test User',
            'email' => 'config.test@example.com',
            'password' => bcrypt('password123'),
        ]);

        $user->assignRole('manager');

        $mail = new WelcomeUserMail($user);

        // Assert queue configuration
        $this->assertEquals('emails', $mail->queue);
        $this->assertEquals(3, $mail->tries);
        $this->assertEquals(120, $mail->timeout);
    }

    public function test_welcome_email_processes_correctly_from_queue(): void
    {
        Mail::fake();

        $user = User::create([
            'name' => 'Process Test User',
            'email' => 'process.test@example.com',
            'password' => bcrypt('password123'),
        ]);

        $user->assignRole('collaborator');

        // Create the mailable
        $mail = new WelcomeUserMail($user);

        // Queue the email
        Mail::to($user->email)->queue($mail);

        // Assert that the email was queued
        Mail::assertQueued(WelcomeUserMail::class, function ($queuedMail) use ($user) {
            $content = $queuedMail->content();
            
            return $queuedMail->hasTo($user->email) &&
                   isset($content->with['user']) &&
                   $content->with['user']->id === $user->id &&
                   isset($content->with['loginUrl']);
        });
    }
}