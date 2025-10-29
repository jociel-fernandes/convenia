<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Collaborator;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CollaboratorCrudTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Executar o seeder de permissões para os testes
        $this->artisan('db:seed', ['--class' => 'RolePermissionSeeder']);
    }

    /**
     * Test collaborator creation by authenticated manager
     */
    public function test_manager_can_create_collaborator(): void
    {
        // Create and authenticate a manager
        $manager = User::factory()->create();
        $manager->assignRole('manager');
        
        $collaboratorData = [
            'name' => 'Maria Santos',
            'email' => 'maria.santos@test.com',
            'cpf' => '11144477735', // CPF válido para testes
            'city' => 'São Paulo',
            'state' => 'SP',
            'user_id' => $manager->id
        ];

        $response = $this->actingAs($manager, 'api')
            ->postJson('/api/collaborators', $collaboratorData);

        $response->assertStatus(201)
            ->assertJson([
                'data' => [
                    'name' => 'Maria Santos',
                    'email' => 'maria.santos@test.com',
                    'city' => 'São Paulo',
                    'state' => 'SP'
                ],
                'message' => 'Colaborador criado com sucesso'
            ]);

        $this->assertDatabaseHas('collaborators', [
            'name' => 'Maria Santos',
            'email' => 'maria.santos@test.com',
            'cpf' => '11144477735',
            'city' => 'São Paulo',
            'state' => 'SP',
            'user_id' => $manager->id
        ]);
    }

    /**
     * Test that collaborator creation requires authentication
     */
    public function test_collaborator_creation_requires_authentication(): void
    {
        $manager = User::factory()->create();
        $manager->assignRole('manager');
        
        $collaboratorData = [
            'name' => 'Test Collaborator',
            'email' => 'test@example.com',
            'cpf' => '11144477735', // CPF válido para testes
            'city' => 'São Paulo',
            'state' => 'SP',
            'user_id' => $manager->id
        ];

        $response = $this->postJson('/api/collaborators', $collaboratorData);
        $response->assertStatus(401);
    }

    /**
     * Test collaborator creation validation
     */
    public function test_collaborator_creation_validation(): void
    {
        $manager = User::factory()->create();
        $manager->assignRole('manager');

        // Test with invalid data
        $response = $this->actingAs($manager, 'api')
            ->postJson('/api/collaborators', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'email', 'cpf', 'city', 'state', 'user_id']);

        // Test with invalid CPF
        $response = $this->actingAs($manager, 'api')
            ->postJson('/api/collaborators', [
                'name' => 'Test',
                'email' => 'invalid-email',
                'cpf' => '123', // Invalid CPF
                'city' => 'City',
                'state' => 'ST',
                'user_id' => 999999 // Non-existent user
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email', 'cpf', 'user_id']);
    }

    /**
     * Test CPF validation with invalid CPF
     */
    public function test_cpf_validation(): void
    {
        $manager = User::factory()->create();
        $manager->assignRole('manager');

        // Test with invalid CPF (all same digits)
        $response = $this->actingAs($manager, 'api')
            ->postJson('/api/collaborators', [
                'name' => 'Test Collaborator',
                'email' => 'test@example.com',
                'cpf' => '11111111111', // Invalid CPF (all same digits)
                'city' => 'São Paulo',
                'state' => 'SP',
                'user_id' => $manager->id
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['cpf']);

        // Test with valid CPF (using a known valid CPF for testing)
        $response = $this->actingAs($manager, 'api')
            ->postJson('/api/collaborators', [
                'name' => 'Test Collaborator',
                'email' => 'test@example.com',
                'cpf' => '11144477735', // Valid CPF for testing
                'city' => 'São Paulo',
                'state' => 'SP',
                'user_id' => $manager->id
            ]);

        $response->assertStatus(201);
    }

    /**
     * Test manager can list collaborators
     */
    public function test_manager_can_list_collaborators(): void
    {
        $manager = User::factory()->create();
        $manager->assignRole('manager');

        // Create test collaborators
        $collaborator1 = Collaborator::factory()->create(['user_id' => $manager->id]);
        $collaborator2 = Collaborator::factory()->create(['user_id' => $manager->id]);

        $response = $this->actingAs($manager, 'api')
            ->getJson('/api/collaborators');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'email',
                        'cpf',
                        'city',
                        'state',
                        'user'
                    ]
                ],
                'meta',
                'links',
                'message'
            ]);
    }

    /**
     * Test manager can view specific collaborator
     */
    public function test_manager_can_view_collaborator(): void
    {
        $manager = User::factory()->create();
        $manager->assignRole('manager');

        $collaborator = Collaborator::factory()->create([
            'name' => 'Test Collaborator',
            'user_id' => $manager->id
        ]);

        $response = $this->actingAs($manager, 'api')
            ->getJson("/api/collaborators/{$collaborator->id}");

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'id' => $collaborator->id,
                    'name' => 'Test Collaborator',
                    'email' => $collaborator->email
                ]
            ]);
    }

    /**
     * Test updating collaborator
     */
    public function test_manager_can_update_collaborator(): void
    {
        $manager = User::factory()->create();
        $manager->assignRole('manager');

        $collaborator = Collaborator::factory()->create(['user_id' => $manager->id]);

        $updateData = [
            'name' => 'Updated Collaborator Name',
            'city' => 'Rio de Janeiro'
        ];

        $response = $this->actingAs($manager, 'api')
            ->putJson("/api/collaborators/{$collaborator->id}", $updateData);

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'name' => 'Updated Collaborator Name',
                    'city' => 'Rio de Janeiro'
                ],
                'message' => 'Colaborador atualizado com sucesso'
            ]);

        $this->assertDatabaseHas('collaborators', [
            'id' => $collaborator->id,
            'name' => 'Updated Collaborator Name',
            'city' => 'Rio de Janeiro'
        ]);
    }

    /**
     * Test deleting collaborator
     */
    public function test_manager_can_delete_collaborator(): void
    {
        $manager = User::factory()->create();
        $manager->assignRole('manager');

        $collaborator = Collaborator::factory()->create(['user_id' => $manager->id]);

        $response = $this->actingAs($manager, 'api')
            ->deleteJson("/api/collaborators/{$collaborator->id}");

        $response->assertStatus(204); // No content

        $this->assertDatabaseMissing('collaborators', [
            'id' => $collaborator->id
        ]);
    }

    /**
     * Test collaborator search functionality
     */
    public function test_manager_can_search_collaborators(): void
    {
        $manager = User::factory()->create();
        $manager->assignRole('manager');

        $collaborator1 = Collaborator::factory()->create([
            'name' => 'João Silva',
            'user_id' => $manager->id
        ]);
        
        $collaborator2 = Collaborator::factory()->create([
            'name' => 'Maria Santos',
            'user_id' => $manager->id
        ]);

        $response = $this->actingAs($manager, 'api')
            ->getJson('/api/collaborators?search=João');

        $response->assertStatus(200);
        
        // A busca retorna uma estrutura de resource collection
        $responseData = $response->json();
        $this->assertArrayHasKey('data', $responseData);
        
        // Verificar que João está nos resultados e Maria não está
        $collaborators = $responseData['data']; // Direct access to data array
        $names = collect($collaborators)->pluck('name')->toArray();
        
        $this->assertContains('João Silva', $names, 'João Silva deveria estar nos resultados');
        $this->assertNotContains('Maria Santos', $names, 'Maria Santos não deveria estar nos resultados');
    }

    /**
     * Test collaborator filtering by city
     */
    public function test_manager_can_filter_collaborators_by_city(): void
    {
        $manager = User::factory()->create();
        $manager->assignRole('manager');

        $collaborator1 = Collaborator::factory()->create([
            'city' => 'São Paulo',
            'user_id' => $manager->id
        ]);
        
        $collaborator2 = Collaborator::factory()->create([
            'city' => 'Rio de Janeiro',
            'user_id' => $manager->id
        ]);

        $response = $this->actingAs($manager, 'api')
            ->getJson('/api/collaborators?city=São Paulo');

        $response->assertStatus(200);
        
        $responseData = $response->json();
        $collaborators = $responseData['data']; // Direct access to data array
        $cities = collect($collaborators)->pluck('city')->unique()->toArray();
        
        $this->assertContains('São Paulo', $cities);
        $this->assertNotContains('Rio de Janeiro', $cities);
    }

    /**
     * Test collaborator statistics
     */
    public function test_manager_can_view_collaborator_statistics(): void
    {
        $manager = User::factory()->create();
        $manager->assignRole('manager');

        // Create test collaborators
        Collaborator::factory()->count(3)->create(['user_id' => $manager->id]);

        $response = $this->actingAs($manager, 'api')
            ->getJson('/api/collaborators/statistics/overview');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'total_collaborators',
                    'by_state',
                    'by_city',
                    'recent_collaborators'
                ],
                'message'
            ]);
            
        // Verificar se os valores fazem sentido
        $data = $response->json('data');
        $this->assertGreaterThanOrEqual(3, $data['total_collaborators']); // pelo menos os 3 colaboradores criados
    }

    /**
     * Test that unauthenticated users cannot access collaborator endpoints
     */
    public function test_unauthenticated_users_cannot_access_collaborator_endpoints(): void
    {
        $manager = User::factory()->create();
        $manager->assignRole('manager');

        // Teste de listagem
        $response = $this->getJson('/api/collaborators');
        $response->assertStatus(401);

        // Teste de criação
        $collaboratorData = [
            'name' => 'Test Collaborator',
            'email' => 'test@example.com',
            'cpf' => '11144477735',
            'city' => 'São Paulo',
            'state' => 'SP',
            'user_id' => $manager->id
        ];

        $response = $this->postJson('/api/collaborators', $collaboratorData);
        $response->assertStatus(401);
    }

    /**
     * Test duplicate email validation
     */
    public function test_duplicate_email_validation(): void
    {
        $manager = User::factory()->create();
        $manager->assignRole('manager');

        $existingCollaborator = Collaborator::factory()->create([
            'email' => 'existing@test.com',
            'user_id' => $manager->id
        ]);

        $collaboratorData = [
            'name' => 'New Collaborator',
            'email' => 'existing@test.com', // Duplicate email
            'cpf' => '11144477735',
            'city' => 'São Paulo',
            'state' => 'SP',
            'user_id' => $manager->id
        ];

        $response = $this->actingAs($manager, 'api')
            ->postJson('/api/collaborators', $collaboratorData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    /**
     * Test duplicate CPF validation
     */
    public function test_duplicate_cpf_validation(): void
    {
        $manager = User::factory()->create();
        $manager->assignRole('manager');

        $existingCollaborator = Collaborator::factory()->create([
            'cpf' => '11144477735',
            'user_id' => $manager->id
        ]);

        $collaboratorData = [
            'name' => 'New Collaborator',
            'email' => 'new@test.com',
            'cpf' => '11144477735', // Duplicate CPF
            'city' => 'São Paulo',
            'state' => 'SP',
            'user_id' => $manager->id
        ];

        $response = $this->actingAs($manager, 'api')
            ->postJson('/api/collaborators', $collaboratorData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['cpf']);
    }
}