<?php

namespace Tests\Feature;

use App\Jobs\ProcessCollaboratorImportJob;
use App\Mail\CollaboratorImportCompleted;
use App\Models\CollaboratorImport;
use App\Models\User;
use App\Services\CollaboratorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Laravel\Passport\Passport;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CollaboratorImportEmailTest extends TestCase
{
    use RefreshDatabase;

    protected User $manager;
    protected Role $managerRole;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Criar role de manager
        $this->managerRole = Role::create(['name' => 'manager', 'guard_name' => 'api']);
        
        // Criar usuário manager
        $this->manager = User::factory()->create([
            'email' => 'manager@test.com',
            'name' => 'Test Manager'
        ]);
        $this->manager->assignRole('manager');
        
        // Configurar autenticação
        Passport::actingAs($this->manager);
        
        // Configurar storage
        Storage::fake('local');
    }

    public function test_email_is_sent_when_import_completes_successfully()
    {
        // IMPORTANTE: Como CollaboratorImportCompleted implementa ShouldQueue,
        // o email será enfileirado, não enviado diretamente
        Mail::fake();
        
        // Criar import simples sem Factory complexa
        $import = CollaboratorImport::create([
            'user_id' => $this->manager->id,
            'filename' => 'test_success.csv',
            'original_filename' => 'test_success.csv',
            'status' => 'processing',
            'total_rows' => 0,
            'processed_rows' => 0,
            'successful_rows' => 0,
            'failed_rows' => 0,
            'errors' => null
        ]);

        // Criar arquivo CSV de teste no caminho correto para o job
        $csvContent = "name,email,cpf,city,state\n";
        $csvContent .= "João Silva,joao@test.com,12345678901,São Paulo,SP\n";
        $csvContent .= "Maria Santos,maria@test.com,98765432100,Rio de Janeiro,RJ";
        
        // Salvar no storage real que o job espera
        $realPath = storage_path("app/imports/{$import->filename}");
        if (!is_dir(dirname($realPath))) {
            mkdir(dirname($realPath), 0755, true);
        }
        file_put_contents($realPath, $csvContent);

        // Executar o job
        $job = new ProcessCollaboratorImportJob($import);
        $job->handle(app(CollaboratorService::class));

        // Como o Mailable implementa ShouldQueue, precisa usar assertQueued
        Mail::assertQueued(CollaboratorImportCompleted::class, function ($mail) use ($import) {
            return $mail->import->id === $import->id 
                && $mail->user->id === $this->manager->id;
        });

        // Limpar arquivo de teste
        if (file_exists($realPath)) {
            unlink($realPath);
        }
    }

    public function test_email_is_sent_when_import_fails()
    {
        // Mail fake (CollaboratorImportCompleted implementa ShouldQueue)
        Mail::fake();
        
        // Criar import simples
        $import = CollaboratorImport::create([
            'user_id' => $this->manager->id,
            'filename' => 'test_fail.csv',
            'original_filename' => 'test_fail.csv',
            'status' => 'processing',
            'total_rows' => 0,
            'processed_rows' => 0,
            'successful_rows' => 0,
            'failed_rows' => 0,
            'errors' => null
        ]);

        // Criar arquivo CSV com dados inválidos
        $csvContent = "name,email,cpf,city,state\n";
        $csvContent .= "João Silva,email-inválido,cpf-inválido,São Paulo,SP\n";
        $csvContent .= ",maria@test.com,,Rio de Janeiro,RJ"; // Campos obrigatórios vazios
        
        // Salvar no storage real
        $realPath = storage_path("app/imports/{$import->filename}");
        if (!is_dir(dirname($realPath))) {
            mkdir(dirname($realPath), 0755, true);
        }
        file_put_contents($realPath, $csvContent);

        // Executar o job
        $job = new ProcessCollaboratorImportJob($import);
        $job->handle(app(CollaboratorService::class));

        // Verificar se email foi enfileirado mesmo com erros
        Mail::assertQueued(CollaboratorImportCompleted::class, function ($mail) use ($import) {
            return $mail->import->id === $import->id 
                && $mail->user->id === $this->manager->id;
        });

        // Verificar se import foi marcado como completado (mesmo com erros)
        $import->refresh();
        $this->assertEquals('completed', $import->status);
        $this->assertGreaterThan(0, $import->failed_rows);

        // Limpar arquivo
        if (file_exists($realPath)) {
            unlink($realPath);
        }
    }

    public function test_email_is_sent_when_job_fails_completely()
    {
        // Mail fake
        Mail::fake();
        
        // Criar import com arquivo inexistente
        $import = CollaboratorImport::create([
            'user_id' => $this->manager->id,
            'filename' => 'arquivo_inexistente.csv',
            'original_filename' => 'arquivo_inexistente.csv',
            'status' => 'processing',
            'total_rows' => 0,
            'processed_rows' => 0,
            'successful_rows' => 0,
            'failed_rows' => 0,
            'errors' => null
        ]);

        // Executar o job (deve falhar porque arquivo não existe)
        $job = new ProcessCollaboratorImportJob($import);
        
        try {
            $job->handle(app(CollaboratorService::class));
        } catch (\Exception $e) {
            // Esperado falhar
        }

        // Verificar se email foi enfileirado mesmo com falha completa
        Mail::assertQueued(CollaboratorImportCompleted::class, function ($mail) use ($import) {
            return $mail->import->id === $import->id 
                && $mail->user->id === $this->manager->id;
        });

        // Verificar se import foi marcado como falha
        $import->refresh();
        $this->assertEquals('failed', $import->status);
    }

    public function test_email_contains_correct_import_statistics()
    {
        // Mail fake
        Mail::fake();
        
        // Criar import já com estatísticas prontas (simulando pós-processamento)
        $import = CollaboratorImport::create([
            'user_id' => $this->manager->id,
            'filename' => 'test_stats.csv',
            'original_filename' => 'test_stats.csv',
            'status' => 'completed', // Já completado
            'total_rows' => 3,
            'processed_rows' => 3,
            'successful_rows' => 2,
            'failed_rows' => 1,
            'errors' => ['line_2' => ['email' => ['Email inválido']]],
            'completed_at' => now()
        ]);

        // Enviar email diretamente (simular o que o Job faria)
        Mail::to($this->manager->email)->send(new CollaboratorImportCompleted($import, $this->manager));

        // Verificar se email foi enfileirado com dados corretos
        Mail::assertQueued(CollaboratorImportCompleted::class, function ($mail) use ($import) {
            return $mail->import->id === $import->id 
                && $mail->user->id === $this->manager->id
                && $mail->import->total_rows === 3
                && $mail->import->successful_rows === 2
                && $mail->import->failed_rows === 1;
        });
    }

    public function test_email_notification_system_works()
    {
        // Fake mail
        Mail::fake();
        
        // Criar import com dados simples
        $import = CollaboratorImport::create([
            'user_id' => $this->manager->id,
            'filename' => 'test_simple.csv',
            'original_filename' => 'test_simple.csv',
            'status' => 'completed',
            'total_rows' => 2,
            'processed_rows' => 2,
            'successful_rows' => 1,
            'failed_rows' => 1,
            'errors' => ['line_2' => ['email' => ['Email inválido']]]
        ]);

        // Simular envio de email diretamente
        Mail::to($this->manager->email)->send(new CollaboratorImportCompleted($import, $this->manager));

        // Verificar se email foi enfileirado (pois o Mailable implementa ShouldQueue)
        Mail::assertQueued(CollaboratorImportCompleted::class, function ($mail) use ($import) {
            return $mail->import->id === $import->id 
                && $mail->user->id === $this->manager->id;
        });
    }

    public function test_mailable_has_correct_content_and_structure()
    {
        // Criar import com dados de teste
        $import = CollaboratorImport::create([
            'user_id' => $this->manager->id,
            'filename' => 'test_mailable.csv',
            'original_filename' => 'test_mailable.csv',
            'status' => 'completed',
            'total_rows' => 5,
            'processed_rows' => 5,
            'successful_rows' => 4,
            'failed_rows' => 1,
            'errors' => [
                2 => ['email' => ['Email inválido']],
            ]
        ]);

        // Criar o mailable
        $mailable = new CollaboratorImportCompleted($import, $this->manager);

        // Verificar envelope
        $envelope = $mailable->envelope();
        $this->assertCount(1, $envelope->to);
        $this->assertEquals($this->manager->email, $envelope->to[0]->address ?? $envelope->to[0]);
        $this->assertStringContainsString('Import de Colaboradores', $envelope->subject);

        // Verificar conteúdo
        $content = $mailable->content();
        $this->assertEquals('emails.collaborator-import-completed', $content->view);
        
        // Verificar dados passados para a view
        $viewData = $content->with;
        $this->assertEquals($this->manager->id, $viewData['user']->id);
        $this->assertEquals($import->id, $viewData['import']->id);
        $this->assertTrue($viewData['isSuccess']);
        $this->assertTrue($viewData['hasErrors']);
        $this->assertEquals(5, $viewData['totalRows']);
        $this->assertEquals(4, $viewData['successfulRows']);
        $this->assertEquals(1, $viewData['failedRows']);
    }
}