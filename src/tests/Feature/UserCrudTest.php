<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class UserCrudTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        
        // As permissões já existem no seeder, não precisamos criar novamente
        // Apenas criar roles se não existirem
        $managerRole = Role::firstOrCreate(['name' => 'manager', 'guard_name' => 'api']);
        $collaboratorRole = Role::firstOrCreate(['name' => 'collaborator', 'guard_name' => 'api']);
    }

    /**
     * Test user creation by authenticated manager
     */
    public function test_manager_can_create_user(): void
    {
        // Create and authenticate a manager
        $manager = User::factory()->create();
        $manager->assignRole('manager');
        
        $userData = [
            'name' => 'João Silva',
            'email' => 'joao.silva@test.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'roles' => ['manager'] // Apenas managers são usuários do sistema
        ];

        $response = $this->actingAs($manager, 'api')
            ->postJson('/api/users', $userData);

        $response->assertStatus(201)
            ->assertJson([
                'data' => [
                    'name' => 'João Silva',
                    'email' => 'joao.silva@test.com',
                    'roles' => ['manager']
                ]
            ])
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'name',
                    'email',
                    'roles',
                    'created_at',
                    'updated_at'
                ],
                'message'
            ]);

        $this->assertDatabaseHas('users', [
            'name' => 'João Silva',
            'email' => 'joao.silva@test.com'
        ]);
    }

    /**
     * Test user creation requires authentication
     */
    public function test_user_creation_requires_authentication(): void
    {
        $userData = [
            'name' => 'João Silva',
            'email' => 'joao.silva@test.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'roles' => ['manager'] // Apenas managers são usuários
        ];

        $response = $this->postJson('/api/users', $userData);

        $response->assertStatus(401)
            ->assertJsonStructure([
                'message',
                'data',
                'code'
            ]);
    }

    /**
     * Test user creation validation
     */
    public function test_user_creation_validation(): void
    {
        $manager = User::factory()->create();
        $manager->assignRole('manager');

        // Test missing fields
        $response = $this->actingAs($manager, 'api')
            ->postJson('/api/users', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'email', 'password', 'roles']);

        // Test invalid email
        $response = $this->actingAs($manager, 'api')
            ->postJson('/api/users', [
                'name' => 'Test User',
                'email' => 'invalid-email',
                'password' => 'password123',
                'password_confirmation' => 'password123',
                'roles' => ['manager']
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);

        // Test password confirmation mismatch
        $response = $this->actingAs($manager, 'api')
            ->postJson('/api/users', [
                'name' => 'Test User',
                'email' => 'test@example.com',
                'password' => 'password123',
                'password_confirmation' => 'different',
                'roles' => ['manager']
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    }

    /**
     * Test listing users
     */
    public function test_manager_can_list_users(): void
    {
        $manager = User::factory()->create();
        $manager->assignRole('manager');

        // Create some test users (all managers)
        $user1 = User::factory()->create(['name' => 'Manager One']);
        $user1->assignRole('manager');
        
        $user2 = User::factory()->create(['name' => 'Manager Two']);
        $user2->assignRole('manager');

        $response = $this->actingAs($manager, 'api')
            ->getJson('/api/users');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'email',
                        'roles',
                        'created_at',
                        'updated_at'
                    ]
                ]
            ]);
    }

    /**
     * Test showing specific user
     */
    public function test_manager_can_view_user(): void
    {
        $manager = User::factory()->create();
        $manager->assignRole('manager');

        $user = User::factory()->create(['name' => 'Test Manager']);
        $user->assignRole('manager');

        $response = $this->actingAs($manager, 'api')
            ->getJson("/api/users/{$user->id}");

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'id' => $user->id,
                    'name' => 'Test Manager',
                    'email' => $user->email,
                    'roles' => ['manager']
                ]
            ]);
    }

    /**
     * Test updating user
     */
    public function test_manager_can_update_user(): void
    {
        $manager = User::factory()->create();
        $manager->assignRole('manager');

        $user = User::factory()->create(['name' => 'Original Manager']);
        $user->assignRole('manager');

        $updateData = [
            'name' => 'Updated Manager',
            'email' => $user->email,
            'roles' => ['manager']
        ];

        $response = $this->actingAs($manager, 'api')
            ->putJson("/api/users/{$user->id}", $updateData);

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'name' => 'Updated Manager',
                    'roles' => ['manager']
                ]
            ])
            ->assertJsonStructure([
                'data',
                'message'
            ]);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Updated Manager'
        ]);
    }

    /**
     * Test deleting user
     */
    public function test_manager_can_delete_user(): void
    {
        $manager = User::factory()->create();
        $manager->assignRole('manager');

        // Create a user without roles to be deleted
        $user = User::factory()->create();
        // Don't assign any role - this simulates a user that could be deleted

        $response = $this->actingAs($manager, 'api')
            ->deleteJson("/api/users/{$user->id}");

        $response->assertStatus(204); // Laravel typically returns 204 for successful deletes

        $this->assertSoftDeleted('users', [
            'id' => $user->id
        ]);
    }

    /**
     * Test user search functionality
     */
    public function test_manager_can_search_users(): void
    {
        $manager = User::factory()->create();
        $manager->assignRole('manager');

        $user1 = User::factory()->create(['name' => 'João Silva']);
        $user1->assignRole('manager');
        
        $user2 = User::factory()->create(['name' => 'Maria Santos']);
        $user2->assignRole('manager');

        $response = $this->actingAs($manager, 'api')
            ->getJson('/api/users/search/João?search=João');

        $response->assertStatus(200);
        
        // A busca retorna uma estrutura de resource collection
        $responseData = $response->json();
        $this->assertArrayHasKey('data', $responseData);
        
        // Verificar que João está nos resultados e Maria não está
        $users = $responseData['data'];
        $names = collect($users)->pluck('name')->toArray();
        
        $this->assertContains('João Silva', $names, 'João Silva deveria estar nos resultados');
        $this->assertNotContains('Maria Santos', $names, 'Maria Santos não deveria estar nos resultados');
    }

    /**
     * Test user statistics
     */
    public function test_manager_can_view_statistics(): void
    {
        $manager = User::factory()->create();
        $manager->assignRole('manager');

        // Create test users (all managers, since collaborators are separate entities)
        $user1 = User::factory()->create();
        $user1->assignRole('manager');
        
        $user2 = User::factory()->create();
        $user2->assignRole('manager');

        $response = $this->actingAs($manager, 'api')
            ->getJson('/api/users/statistics/overview');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'total_users',
                    'managers',
                    'collaborators',
                    'active_users'
                ],
                'message'
            ]);
            
        // Verificar se os valores fazem sentido
        $data = $response->json('data');
        $this->assertGreaterThanOrEqual(3, $data['total_users']); // pelo menos os 3 usuários criados
        $this->assertGreaterThanOrEqual(3, $data['managers']); // todos são managers
        // collaborators serão 0 ou contarão a tabela collaborators separada
    }

    /**
     * Test that unauthenticated users cannot access user endpoints
     */
    public function test_unauthenticated_users_cannot_access_user_endpoints(): void
    {
        // Teste de listagem
        $response = $this->getJson('/api/users');
        $response->assertStatus(401);

        // Teste de criação
        $userData = [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'roles' => ['manager']
        ];

        $response = $this->postJson('/api/users', $userData);
        $response->assertStatus(401);
    }

    /**
     * Test duplicate email validation
     */
    public function test_duplicate_email_validation(): void
    {
        $manager = User::factory()->create();
        $manager->assignRole('manager');

        $existingUser = User::factory()->create(['email' => 'existing@test.com']);

        $userData = [
            'name' => 'New User',
            'email' => 'existing@test.com', // Duplicate email
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'roles' => ['manager']
        ];

        $response = $this->actingAs($manager, 'api')
            ->postJson('/api/users', $userData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }
}