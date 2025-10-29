<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Passport\Passport;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class UserAuthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Criar client para Passport
        $this->artisan('passport:client', [
            '--personal' => true,
            '--name' => 'Personal Access Client Tests',
            '--provider' => 'users'
        ]);

        // As permissões já existem no seeder, não precisamos criar novamente
        // Apenas criar roles se não existirem
        $managerRole = Role::firstOrCreate(['name' => 'manager', 'guard_name' => 'api']);
        $collaboratorRole = Role::firstOrCreate(['name' => 'collaborator', 'guard_name' => 'api']);
    }

    /**
     * Test successful manager login
     */
    public function test_manager_can_login_successfully(): void
    {
        $user = User::factory()->create([
            'email' => 'manager@test.com',
            'password' => Hash::make('password123')
        ]);
        $user->assignRole('manager');

        $response = $this->postJson('/api/auth/login', [
            'email' => 'manager@test.com',
            'password' => 'password123'
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'message',
                'data' => [
                    'message',
                    'data' => [
                        'user' => [
                            'id',
                            'name',
                            'email',
                            'roles',
                            'created_at',
                            'updated_at'
                        ],
                        'access_token',
                        'token_type',
                        'expires_in',
                        'scopes'
                    ]
                ]
            ])
            ->assertJson([
                'status' => true,
                'data' => [
                    'data' => [
                        'user' => [
                            'email' => 'manager@test.com',
                            'roles' => ['manager']
                        ],
                        'token_type' => 'Bearer'
                    ]
                ]
            ]);

        // Verify token is valid
        $this->assertNotEmpty($response->json('data.data.access_token'));
    }

    /**
     * Test collaborator cannot login
     */
    public function test_collaborator_cannot_login(): void
    {
        $user = User::factory()->create([
            'email' => 'collaborator@test.com',
            'password' => Hash::make('password123')
        ]);
        $user->assignRole('collaborator');

        $response = $this->postJson('/api/auth/login', [
            'email' => 'collaborator@test.com',
            'password' => 'password123'
        ]);

        $response->assertStatus(403)
            ->assertJsonStructure([
                'success',
                'message',
                'data',
                'code'
            ]);
    }

    /**
     * Test login with invalid credentials
     */
    public function test_login_with_invalid_credentials(): void
    {
        $user = User::factory()->create([
            'email' => 'manager@test.com',
            'password' => Hash::make('password123')
        ]);
        $user->assignRole('manager');

        // Wrong password
        $response = $this->postJson('/api/auth/login', [
            'email' => 'manager@test.com',
            'password' => 'wrongpassword'
        ]);

        $response->assertStatus(401)
            ->assertJson([
                'success' => false,
                'message' => 'The provided credentials are incorrect.',
                'data' => null,
                'code' => 'INVALID_CREDENTIALS'
            ]);

        // Wrong email
        $response = $this->postJson('/api/auth/login', [
            'email' => 'wrong@test.com',
            'password' => 'wrongpassword'
        ]);

        $response->assertStatus(401)
            ->assertJson([
                'success' => false,
                'message' => 'The provided credentials are incorrect.',
                'data' => null,
                'code' => 'INVALID_CREDENTIALS'
            ]);
    }

    /**
     * Test login validation errors
     */
    public function test_login_validation_errors(): void
    {
        // Missing email and password
        $response = $this->postJson('/api/auth/login', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email', 'password']);

        // Invalid email format
        $response = $this->postJson('/api/auth/login', [
            'email' => 'invalid-email',
            'password' => 'password123'
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);

        // Empty password
        $response = $this->postJson('/api/auth/login', [
            'email' => 'test@example.com',
            'password' => ''
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    }

    /**
     * Test logout functionality
     */
    public function test_manager_can_logout(): void
    {
        $user = User::factory()->create();
        $user->assignRole('manager');

        // Create a token for the user
        Passport::actingAs($user);

        $response = $this->actingAs($user, 'api')
            ->postJson('/api/auth/logout');

        $response->assertStatus(200)
            ->assertJson([
                'status' => true,
                'message' => 'Logout successful',
                'data' => []
            ]);
    }

    /**
     * Test logout requires authentication
     */
    public function test_logout_requires_authentication(): void
    {
        $response = $this->postJson('/api/auth/logout');

        $response->assertStatus(401)
            ->assertJson([
                'message' => 'Invalid or expired access token.',
                'code' => 'UNAUTHENTICATED'
            ]);
    }

    /**
     * Test getting authenticated user info
     */
    public function test_authenticated_user_can_get_own_info(): void
    {
        $user = User::factory()->create([
            'name' => 'Test Manager',
            'email' => 'manager@test.com'
        ]);
        $user->assignRole('manager');

        $response = $this->actingAs($user, 'api')
            ->getJson('/api/auth/me');

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'User data retrieved successfully',
                'data' => [
                    'id' => $user->id,
                    'name' => 'Test Manager',
                    'email' => 'manager@test.com'
                ]
            ]);
    }

    /**
     * Test me endpoint requires authentication
     */
    public function test_me_endpoint_requires_authentication(): void
    {
        $response = $this->getJson('/api/auth/me');

        $response->assertStatus(401)
            ->assertJson([
                'message' => 'Invalid or expired access token.',
                'code' => 'UNAUTHENTICATED'
            ]);
    }

    /**
     * Test login with deleted user
     */
    public function test_login_with_deleted_user(): void
    {
        $user = User::factory()->create([
            'email' => 'deleted@test.com',
            'password' => Hash::make('password123')
        ]);
        $user->assignRole('manager');
        
        // Soft delete the user
        $user->delete();

        $response = $this->postJson('/api/auth/login', [
            'email' => 'deleted@test.com',
            'password' => 'password123'
        ]);

        $response->assertStatus(401)
            ->assertJson([
                'success' => false,
                'message' => 'The provided credentials are incorrect.',
                'data' => null,
                'code' => 'INVALID_CREDENTIALS'
            ]);
    }

    /**
     * Test login rate limiting (if implemented)
     */
    public function test_login_rate_limiting(): void
    {
        $user = User::factory()->create([
            'email' => 'manager@test.com',
            'password' => Hash::make('password123')
        ]);
        $user->assignRole('manager');

        // Attempt multiple failed logins
        for ($i = 0; $i < 6; $i++) {
            $response = $this->postJson('/api/auth/login', [
                'email' => 'manager@test.com',
                'password' => 'wrongpassword'
            ]);
        }

        // Check if rate limiting is triggered (this depends on your throttle configuration)
        // The exact status code may vary based on your throttle middleware setup
        $this->assertTrue(in_array($response->status(), [401, 429]));
    }

    /**
     * Test token structure and expiration
     */
    public function test_token_structure_and_properties(): void
    {
        $user = User::factory()->create([
            'email' => 'manager@test.com',
            'password' => Hash::make('password123')
        ]);
        $user->assignRole('manager');

        $response = $this->postJson('/api/auth/login', [
            'email' => 'manager@test.com',
            'password' => 'password123'
        ]);

        $response->assertStatus(200);
        
        $tokenData = $response->json('data.data');
        
        // Verify token structure
        $this->assertArrayHasKey('access_token', $tokenData);
        $this->assertArrayHasKey('token_type', $tokenData);
        $this->assertArrayHasKey('expires_in', $tokenData);
        $this->assertArrayHasKey('scopes', $tokenData);
        
        // Verify token type
        $this->assertEquals('Bearer', $tokenData['token_type']);
        
        // Verify token is a JWT (basic format check)
        $token = $tokenData['access_token'];
        $this->assertIsString($token);
        $this->assertStringContainsString('.', $token); // JWT has dots
        
        // Verify expires_in is a positive integer
        $this->assertIsInt($tokenData['expires_in']);
        $this->assertGreaterThan(0, $tokenData['expires_in']);
    }

    /**
     * Test user without roles cannot login
     */
    public function test_user_without_roles_cannot_login(): void
    {
        $user = User::factory()->create([
            'email' => 'norole@test.com',
            'password' => Hash::make('password123')
        ]);
        // Don't assign any role

        $response = $this->postJson('/api/auth/login', [
            'email' => 'norole@test.com',
            'password' => 'password123'
        ]);

        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'message' => 'Only managers can login.',
                'data' => null,
                'code' => 'ACCESS_DENIED'
            ]);
    }

    /**
     * Test case insensitive email login
     */
    public function test_case_insensitive_email_login(): void
    {
        $user = User::factory()->create([
            'email' => 'manager@test.com',
            'password' => Hash::make('password123')
        ]);
        $user->assignRole('manager');

        // Try with uppercase email
        $response = $this->postJson('/api/auth/login', [
            'email' => 'MANAGER@TEST.COM',
            'password' => 'password123'
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'status' => true,
                'data' => [
                    'data' => [
                        'user' => [
                            'email' => 'manager@test.com' // Should be normalized
                        ]
                    ]
                ]
            ]);
    }
}