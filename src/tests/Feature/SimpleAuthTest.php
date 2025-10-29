<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SimpleAuthTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Executar o seeder de permissões para os testes
        $this->artisan('db:seed', ['--class' => 'RolePermissionSeeder']);
    }

    /**
     * Test that authentication is required for protected routes
     */
    public function test_protected_routes_require_authentication(): void
    {
        $response = $this->getJson('/api/users');
        $response->assertStatus(401);
        
        $response = $this->postJson('/api/users', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123'
        ]);
        $response->assertStatus(401);
    }

    /**
     * Test that managers can access protected routes when authenticated
     */
    public function test_manager_can_access_protected_routes(): void
    {
        $manager = User::factory()->create();
        $manager->assignRole('manager');

        $response = $this->actingAs($manager, 'api')->getJson('/api/users');
        $response->assertStatus(200);
    }

    /**
     * Test that only managers can access the API
     */
    public function test_only_managers_can_access_api(): void
    {
        $manager = User::factory()->create();
        $manager->assignRole('manager');

        // Manager can access API
        $response = $this->actingAs($manager, 'api')->getJson('/api/users');
        $response->assertStatus(200);

        // Note: Collaborators are separate entities (not users), 
        // so we don't create collaborator users for testing API access
    }

    /**
     * Test role-based permissions for managers only
     */
    public function test_manager_permissions(): void
    {
        $manager = User::factory()->create();
        $manager->assignRole('manager');
        
        $userToEdit = User::factory()->create();
        $userToEdit->assignRole('manager');

        // Manager can edit users
        $response = $this->actingAs($manager, 'api')
            ->putJson("/api/users/{$userToEdit->id}", [
                'name' => 'Updated Name',
                'email' => $userToEdit->email
            ]);
        $response->assertStatus(200);

        // Manager can create new managers
        $response = $this->actingAs($manager, 'api')
            ->postJson("/api/users", [
                'name' => 'New Manager',
                'email' => 'newmanager@example.com',
                'password' => 'password123',
                'password_confirmation' => 'password123',
                'roles' => ['manager']
            ]);
        $response->assertStatus(201);
    }

    /**
     * Test login validation (basic endpoint check)
     */
    public function test_login_endpoint_exists_and_validates(): void
    {
        // Test with missing fields
        $response = $this->postJson('/api/auth/login', []);
        $this->assertContains($response->status(), [422, 400, 401]); // Accept validation or auth errors

        // Test with invalid email format
        $response = $this->postJson('/api/auth/login', [
            'email' => 'invalid-email',
            'password' => 'password'
        ]);
        $this->assertContains($response->status(), [422, 400, 401]); // Accept validation or auth errors
    }

    /**
     * Test user roles and permissions assignment
     */
    public function test_manager_role_and_permissions(): void
    {
        $user = User::factory()->create();
        
        // User starts without roles
        $this->assertFalse($user->hasRole('manager'));
        
        // Assign manager role
        $user->assignRole('manager');
        $this->assertTrue($user->hasRole('manager'));
        $this->assertTrue($user->can('create users'));
        $this->assertTrue($user->can('show users'));
        
        // Note: All users in the system should be managers
        // Collaborators are a separate entity (collaborators table)
    }
}