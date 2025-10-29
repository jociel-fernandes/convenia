<?php

namespace App\Jobs;

use App\Mail\CollaboratorImportCompleted;
use App\Models\Collaborator;
use App\Models\CollaboratorImport;
use App\Models\User;
use App\Services\CollaboratorService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class ProcessCollaboratorImportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public CollaboratorImport $import,
        public array $options = []
    ) {
        $this->onQueue('imports');
    }

    /**
     * Execute the job.
     */
    public function handle(CollaboratorService $collaboratorService): void
    {
        try {
            Log::info("Iniciando processamento do import ID: {$this->import->id}");

            $filePath = storage_path("app/imports/{$this->import->filename}");
            
            if (!file_exists($filePath)) {
                throw new \Exception("Arquivo não encontrado: {$filePath}");
            }

            // Ler o arquivo CSV
            $csvData = $this->readCsvFile($filePath);
            
            if (empty($csvData)) {
                throw new \Exception("Arquivo CSV está vazio ou não pode ser lido.");
            }

            // Marcar como iniciado
            $this->import->markAsStarted(count($csvData));

            // Processar cada linha
            foreach ($csvData as $lineNumber => $row) {
                $this->processRow($row, $lineNumber + 1, $collaboratorService);
            }

            // Marcar como completado
            $this->import->markAsCompleted();

            Log::info("Import ID {$this->import->id} processado com sucesso. Sucessos: {$this->import->successful_rows}, Falhas: {$this->import->failed_rows}");

            // Enviar email de notificação
            $this->sendNotificationEmail();

        } catch (\Exception $e) {
            Log::error("Erro no processamento do import ID {$this->import->id}: " . $e->getMessage());
            $this->import->markAsFailed(['general' => $e->getMessage()]);
            
            // Enviar email de notificação mesmo em caso de erro
            $this->sendNotificationEmail();
            
            throw $e;
        }
    }

    /**
     * Ler arquivo CSV
     */
    private function readCsvFile(string $filePath): array
    {
        $data = [];
        $delimiter = $this->options['delimiter'] ?? ',';
        $hasHeader = $this->options['has_header'] ?? true;
        $encoding = $this->options['encoding'] ?? 'utf-8';

        if (($handle = fopen($filePath, 'r')) !== false) {
            $headers = null;
            $lineNumber = 0;

            while (($row = fgetcsv($handle, 1000, $delimiter)) !== false) {
                $lineNumber++;

                // Converter encoding se necessário
                if ($encoding === 'iso-8859-1') {
                    $row = array_map(function($field) {
                        return mb_convert_encoding($field, 'UTF-8', 'ISO-8859-1');
                    }, $row);
                }

                // Primeira linha como cabeçalho
                if ($lineNumber === 1 && $hasHeader) {
                    $headers = array_map('trim', $row);
                    continue;
                }

                // Se não tem cabeçalho, usar índices numéricos
                if (!$hasHeader) {
                    $headers = $headers ?? array_keys($row);
                }

                // Combinar cabeçalhos com dados
                if ($headers && count($row) === count($headers)) {
                    $data[] = array_combine($headers, array_map('trim', $row));
                } else {
                    // Se o número de colunas não bater, adicionar erro
                    Log::warning("Linha {$lineNumber} tem número incorreto de colunas");
                }
            }

            fclose($handle);
        }

        return $data;
    }

    /**
     * Processar uma linha do CSV
     */
    private function processRow(array $row, int $lineNumber, CollaboratorService $collaboratorService): void
    {
        try {
            // Mapear campos do CSV para o modelo
            $mappedData = $this->mapCsvData($row);

            // Validar dados
            $validator = Validator::make($mappedData, [
                'name' => 'required|string|max:256',
                'email' => 'required|email|max:256|unique:collaborators,email',
                'cpf' => 'required|string|size:11|unique:collaborators,cpf',
                'city' => 'required|string|max:256',
                'state' => 'required|string|max:256',
            ]);

            if ($validator->fails()) {
                $this->import->addError($lineNumber, $validator->errors()->toArray());
                $this->import->incrementCounters(false);
                return;
            }

            // Validar CPF
            if (!$collaboratorService->validateCpf($mappedData['cpf'])) {
                $this->import->addError($lineNumber, ['cpf' => ['CPF inválido']]);
                $this->import->incrementCounters(false);
                return;
            }

            // Criar colaborador
            $collaboratorService->createCollaborator($mappedData);
            $this->import->incrementCounters(true);

        } catch (\Exception $e) {
            Log::error("Erro na linha {$lineNumber}: " . $e->getMessage());
            $this->import->addError($lineNumber, ['general' => $e->getMessage()]);
            $this->import->incrementCounters(false);
        }
    }

    /**
     * Mapear dados do CSV para o formato do modelo
     */
    private function mapCsvData(array $row): array
    {
        // Mapeamento flexível de campos
        $fieldMapping = [
            'name' => ['name', 'nome', 'colaborador', 'funcionario'],
            'email' => ['email', 'e-mail', 'correio'],
            'cpf' => ['cpf', 'documento'],
            'city' => ['city', 'cidade'],
            'state' => ['state', 'estado', 'uf'],
        ];

        $mappedData = [];

        foreach ($fieldMapping as $modelField => $csvFields) {
            foreach ($csvFields as $csvField) {
                if (isset($row[$csvField])) {
                    $value = trim($row[$csvField]);
                    
                    // Tratamentos específicos
                    if ($modelField === 'cpf') {
                        $value = preg_replace('/\D/', '', $value); // Remove caracteres não numéricos
                    } elseif ($modelField === 'email') {
                        $value = strtolower($value);
                    } elseif ($modelField === 'state') {
                        $value = strtoupper($value);
                    }
                    
                    $mappedData[$modelField] = $value;
                    break;
                }
            }
        }

        // Sempre usar o usuário que fez o upload do arquivo
        $mappedData['user_id'] = $this->import->user_id;

        return $mappedData;
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error("Job de import falhou para ID {$this->import->id}: " . $exception->getMessage());
        $this->import->markAsFailed(['job_error' => $exception->getMessage()]);
        
        // Enviar email de notificação mesmo em caso de falha do job
        $this->sendNotificationEmail();
    }

    /**
     * Enviar email de notificação do import finalizado
     */
    private function sendNotificationEmail(): void
    {
        try {
            // Recarregar o import para ter os dados atualizados
            $this->import->refresh();
            
            // Buscar o usuário que fez o upload
            $user = User::find($this->import->user_id);
            
            if (!$user) {
                Log::warning("Usuário não encontrado para envio de email do import ID: {$this->import->id}");
                return;
            }

            Log::info("Enviando email de notificação do import ID {$this->import->id} para {$user->email}");

            // Enviar email
            Mail::to($user->email)->send(new CollaboratorImportCompleted($this->import, $user));

            Log::info("Email de notificação enviado com sucesso para o import ID: {$this->import->id}");

        } catch (\Exception $e) {
            Log::error("Erro ao enviar email de notificação do import ID {$this->import->id}: " . $e->getMessage());
            // Não re-lançar exceção para não afetar o job principal
        }
    }
}