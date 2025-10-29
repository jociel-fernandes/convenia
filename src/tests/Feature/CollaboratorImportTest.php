<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Collaborator;
use App\Models\CollaboratorImport;
use App\Jobs\ProcessCollaboratorImportJob;
use App\Services\CollaboratorImportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CollaboratorImportTest extends TestCase
{
    use RefreshDatabase;

    protected User $manager;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Criar roles e permissions
        $this->artisan('db:seed', ['--class' => 'RolePermissionSeeder']);
        
        // Criar usuário manager
        $this->manager = User::factory()->create([
            'email' => 'manager@test.com',
            'password' => bcrypt('password123'),
        ]);
        
        // Atribuir role manager
        $this->manager->assignRole('manager');
        
        // Configurar storage fake
        Storage::fake('local');
    }

    protected function tearDown(): void
    {
        // Limpar arquivos de teste
        $importPath = storage_path('app/imports');
        if (is_dir($importPath)) {
            $files = glob($importPath . '/test_*.csv');
            foreach ($files as $file) {
                if (file_exists($file)) {
                    unlink($file);
                }
            }
        }
        
        parent::tearDown();
    }

    public function test_manager_can_upload_csv_file_for_import()
    {
        Queue::fake();

        // Criar arquivo CSV de teste
        $csvContent = "name,email,cpf,city,state\n";
        $csvContent .= "João Silva,joao@test.com,11144477735,São Paulo,SP\n";
        $csvContent .= "Maria Santos,maria@test.com,22233366644,Rio de Janeiro,RJ";

        $file = UploadedFile::fake()->createWithContent('collaborators.csv', $csvContent);

        $response = $this->actingAs($this->manager, 'api')
            ->postJson('/api/collaborators/import', [
                'file' => $file
            ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'import_id',
                    'status',
                    'filename'
                ]
            ]);

        // Verificar se o import foi criado no banco
        $this->assertDatabaseHas('collaborator_imports', [
            'user_id' => $this->manager->id,
            'status' => 'processing'
        ]);

        // Verificar se o job foi despachado
        Queue::assertPushed(ProcessCollaboratorImportJob::class);
    }

    public function test_csv_validation_rejects_invalid_files()
    {
        // Arquivo que não é CSV
        $file = UploadedFile::fake()->create('document.pdf', 100);

        $response = $this->actingAs($this->manager, 'api')
            ->postJson('/api/collaborators/import', [
                'file' => $file
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['file']);
    }

    public function test_csv_validation_rejects_files_too_large()
    {
        // Arquivo maior que 10MB (simulado)
        $file = UploadedFile::fake()->create('large.csv', 11000); // 11MB

        $response = $this->actingAs($this->manager, 'api')
            ->postJson('/api/collaborators/import', [
                'file' => $file
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['file']);
    }

    public function test_manager_can_validate_csv_structure_before_import()
    {
        // CSV com estrutura correta
        $csvContent = "name,email,cpf,city,state\n";
        $csvContent .= "João Silva,joao@test.com,11144477735,São Paulo,SP";

        $file = UploadedFile::fake()->createWithContent('test.csv', $csvContent);

        $response = $this->actingAs($this->manager, 'api')
            ->postJson('/api/collaborators/import/validate', [
                'file' => $file,
                'has_header' => true
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'validation' => [
                    'is_valid' => true,
                    'total_rows' => 1
                ]
            ]);
    }

    public function test_csv_validation_detects_missing_required_fields()
    {
        // CSV sem campo obrigatório
        $csvContent = "name,email\n";
        $csvContent .= "João Silva,joao@test.com";

        $file = UploadedFile::fake()->createWithContent('incomplete.csv', $csvContent);

        $response = $this->actingAs($this->manager, 'api')
            ->postJson('/api/collaborators/import/validate', [
                'file' => $file,
                'has_header' => true
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => false,
                'validation' => [
                    'is_valid' => false
                ]
            ]);

        $responseData = $response->json();
        $this->assertStringContainsString('cpf', $responseData['validation']['errors'][0]);
    }

    public function test_manager_can_check_import_status()
    {
        $import = CollaboratorImport::create([
            'user_id' => $this->manager->id,
            'filename' => 'test.csv',
            'original_filename' => 'collaborators.csv',
            'status' => 'processing',
            'total_rows' => 5,
            'processed_rows' => 3,
            'successful_rows' => 2,
            'failed_rows' => 1
        ]);

        $response = $this->actingAs($this->manager, 'api')
            ->getJson("/api/collaborators/import/{$import->id}/status");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'id',
                    'status',
                    'filename',
                    'total_rows',
                    'processed_rows',
                    'success_count',
                    'error_count',
                    'progress_percentage'
                ]
            ]);
    }

    public function test_manager_cannot_access_other_users_imports()
    {
        $otherUser = User::factory()->create();
        $otherUser->assignRole('manager');

        $import = CollaboratorImport::create([
            'user_id' => $otherUser->id,
            'filename' => 'test.csv',
            'original_filename' => 'collaborators.csv',
            'status' => 'processing'
        ]);

        $response = $this->actingAs($this->manager, 'api')
            ->getJson("/api/collaborators/import/{$import->id}/status");

        $response->assertStatus(403);
    }

    public function test_manager_can_list_their_imports()
    {
        // Criar alguns imports para o usuário atual
        CollaboratorImport::factory(3)->create([
            'user_id' => $this->manager->id
        ]);

        // Criar import de outro usuário (não deve aparecer)
        $otherUser = User::factory()->create();
        CollaboratorImport::factory(2)->create([
            'user_id' => $otherUser->id
        ]);

        $response = $this->actingAs($this->manager, 'api')
            ->getJson('/api/collaborators/import');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data',
                'meta' => [
                    'current_page',
                    'last_page',
                    'per_page',
                    'total'
                ]
            ]);

        $this->assertCount(3, $response->json('data'));
    }

    public function test_manager_can_cancel_processing_import()
    {
        $import = CollaboratorImport::create([
            'user_id' => $this->manager->id,
            'filename' => 'test.csv',
            'original_filename' => 'collaborators.csv',
            'status' => 'processing'
        ]);

        $response = $this->actingAs($this->manager, 'api')
            ->postJson("/api/collaborators/import/{$import->id}/cancel");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Import cancelado com sucesso'
            ]);

        $this->assertDatabaseHas('collaborator_imports', [
            'id' => $import->id,
            'status' => 'failed'
        ]);
    }

    public function test_manager_can_generate_csv_template()
    {
        Storage::fake('public');

        $response = $this->actingAs($this->manager, 'api')
            ->getJson('/api/collaborators/import/template');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'download_url',
                    'filename'
                ]
            ]);
    }

    public function test_manager_can_export_collaborators_to_csv()
    {
        Storage::fake('public');

        // Criar alguns colaboradores
        Collaborator::factory(5)->create([
            'user_id' => $this->manager->id,
            'city' => 'São Paulo'
        ]);

        $response = $this->actingAs($this->manager, 'api')
            ->postJson('/api/collaborators/export', [
                'city' => 'São Paulo'
            ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'download_url',
                    'filename'
                ]
            ]);
    }

    public function test_unauthenticated_users_cannot_access_import_endpoints()
    {
        $file = UploadedFile::fake()->create('test.csv');

        // Test upload
        $response = $this->postJson('/api/collaborators/import', ['file' => $file]);
        $response->assertStatus(401);

        // Test validation
        $response = $this->postJson('/api/collaborators/import/validate', ['file' => $file]);
        $response->assertStatus(401);

        // Test status
        $response = $this->getJson('/api/collaborators/import/1/status');
        $response->assertStatus(401);

        // Test list
        $response = $this->getJson('/api/collaborators/import');
        $response->assertStatus(401);

        // Test template
        $response = $this->getJson('/api/collaborators/import/template');
        $response->assertStatus(401);

        // Test export
        $response = $this->postJson('/api/collaborators/export');
        $response->assertStatus(401);
    }

    public function test_import_service_always_uses_authenticated_user_id()
    {
        $importService = app(CollaboratorImportService::class);

        // Simular arquivo CSV com user_id diferente
        $csvContent = "name,email,cpf,city,state,user_id\n";
        $csvContent .= "João Silva,joao@test.com,11144477735,São Paulo,SP,999"; // ID diferente

        $file = UploadedFile::fake()->createWithContent('test.csv', $csvContent);

        $import = $importService->startImport($file, $this->manager->id);

        // Verificar se o import usa o ID do usuário autenticado, não o do CSV
        $this->assertEquals($this->manager->id, $import->user_id);
    }
}