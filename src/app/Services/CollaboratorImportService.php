<?php

namespace App\Services;

use App\Jobs\ProcessCollaboratorImportJob;
use App\Models\CollaboratorImport;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CollaboratorImportService
{
    /**
     * Iniciar processo de import
     */
    public function startImport(
        UploadedFile $file, 
        int $userId, 
        array $options = []
    ): CollaboratorImport {
        // Gerar nome único para o arquivo
        $filename = $this->generateUniqueFilename($file);
        
        // Salvar arquivo na pasta de imports
        $filePath = $file->storeAs('imports', $filename);
        
        // Criar registro do import
        $import = CollaboratorImport::create([
            'user_id' => $userId,
            'filename' => $filename,
            'original_filename' => $file->getClientOriginalName(),
            'status' => 'processing',
        ]);

        // Despachar job para processamento em background
        ProcessCollaboratorImportJob::dispatch($import, $options);

        return $import;
    }

    /**
     * Obter status de um import
     */
    public function getImportStatus(int $importId): ?CollaboratorImport
    {
        return CollaboratorImport::find($importId);
    }

    /**
     * Listar imports do usuário
     */
    public function getUserImports(int $userId, int $perPage = 15)
    {
        return CollaboratorImport::where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * Cancelar import em processamento
     */
    public function cancelImport(int $importId): bool
    {
        $import = CollaboratorImport::find($importId);
        
        if (!$import || $import->status !== 'processing') {
            return false;
        }

        $import->markAsFailed(['message' => 'Import cancelado pelo usuário']);
        return true;
    }

    /**
     * Limpar arquivos antigos de import
     */
    public function cleanupOldImports(int $daysOld = 30): int
    {
        $oldImports = CollaboratorImport::where('created_at', '<', now()->subDays($daysOld))
            ->get();

        $deletedCount = 0;

        foreach ($oldImports as $import) {
            // Deletar arquivo físico
            if (Storage::exists("imports/{$import->filename}")) {
                Storage::delete("imports/{$import->filename}");
            }

            // Deletar registro
            $import->delete();
            $deletedCount++;
        }

        return $deletedCount;
    }

    /**
     * Gerar template CSV para download
     */
    public function generateCsvTemplate(): string
    {
        $headers = [
            'name',
            'email', 
            'cpf',
            'city',
            'state'
        ];

        $sampleData = [
            [
                'João Silva',
                'joao.silva@email.com',
                '11144477735',
                'São Paulo',
                'SP'
            ],
            [
                'Maria Santos',
                'maria.santos@email.com',
                '22233366644',
                'Rio de Janeiro',
                'RJ'
            ]
        ];

        $filename = 'template_colaboradores_' . date('Y-m-d_H-i-s') . '.csv';
        $filePath = storage_path("app/public/{$filename}");

        $file = fopen($filePath, 'w');
        
        // Adicionar BOM para UTF-8
        fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));
        
        // Cabeçalho
        fputcsv($file, $headers);
        
        // Dados de exemplo
        foreach ($sampleData as $row) {
            fputcsv($file, $row);
        }
        
        fclose($file);

        return $filename;
    }

    /**
     * Exportar colaboradores para CSV
     */
    public function exportCollaborators(int $userId, array $filters = []): string
    {
        $query = \App\Models\Collaborator::with('user:id,name,email');

        // Aplicar filtros se fornecidos
        if (!empty($filters['city'])) {
            $query->where('city', $filters['city']);
        }

        if (!empty($filters['state'])) {
            $query->where('state', $filters['state']);
        }

        if (!empty($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        $collaborators = $query->get();

        $filename = 'colaboradores_export_' . date('Y-m-d_H-i-s') . '.csv';
        $filePath = storage_path("app/public/{$filename}");

        $file = fopen($filePath, 'w');
        
        // Adicionar BOM para UTF-8
        fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));
        
        // Cabeçalho
        $headers = [
            'ID',
            'Nome',
            'Email',
            'CPF',
            'Cidade',
            'Estado',
            'Responsável',
            'Email do Responsável',
            'Data de Criação',
            'Data de Atualização'
        ];
        fputcsv($file, $headers);
        
        // Dados
        foreach ($collaborators as $collaborator) {
            $row = [
                $collaborator->id,
                $collaborator->name,
                $collaborator->email,
                $collaborator->formatted_cpf,
                $collaborator->city,
                $collaborator->state,
                $collaborator->user->name,
                $collaborator->user->email,
                $collaborator->created_at->format('Y-m-d H:i:s'),
                $collaborator->updated_at->format('Y-m-d H:i:s'),
            ];
            fputcsv($file, $row);
        }
        
        fclose($file);

        return $filename;
    }

    /**
     * Gerar nome único para arquivo
     */
    private function generateUniqueFilename(UploadedFile $file): string
    {
        $extension = $file->getClientOriginalExtension();
        $name = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $uniqueId = Str::uuid();
        
        return "{$name}_{$uniqueId}.{$extension}";
    }

    /**
     * Validar estrutura do CSV
     */
    public function validateCsvStructure(UploadedFile $file, array $options = []): array
    {
        $delimiter = $options['delimiter'] ?? ',';
        $hasHeader = $options['has_header'] ?? true;
        $requiredFields = ['name', 'email', 'cpf', 'city', 'state'];

        $validation = [
            'is_valid' => false,
            'errors' => [],
            'warnings' => [],
            'preview' => [],
            'detected_fields' => [],
            'total_rows' => 0
        ];

        try {
            $filePath = $file->getPathname();
            
            if (($handle = fopen($filePath, 'r')) !== false) {
                $headers = null;
                $previewRows = [];
                $rowCount = 0;

                while (($row = fgetcsv($handle, 1000, $delimiter)) !== false && $rowCount < 10) {
                    $rowCount++;

                    if ($rowCount === 1 && $hasHeader) {
                        $headers = array_map('trim', $row);
                        $validation['detected_fields'] = $headers;
                        continue;
                    }

                    if (!$hasHeader && !$headers) {
                        $headers = array_map(function($i) { return "campo_$i"; }, array_keys($row));
                        $validation['detected_fields'] = $headers;
                    }

                    if ($headers && count($row) === count($headers)) {
                        $previewRows[] = array_combine($headers, array_map('trim', $row));
                    }
                }

                // Contar total de linhas
                while (fgetcsv($handle) !== false) {
                    $rowCount++;
                }

                $validation['total_rows'] = $hasHeader ? $rowCount - 1 : $rowCount;
                $validation['preview'] = $previewRows;

                fclose($handle);

                // Validar campos obrigatórios
                $missingFields = [];
                foreach ($requiredFields as $required) {
                    $found = false;
                    foreach ($headers as $header) {
                        if (in_array(strtolower(trim($header)), [strtolower($required), $required])) {
                            $found = true;
                            break;
                        }
                    }
                    if (!$found) {
                        $missingFields[] = $required;
                    }
                }

                if (!empty($missingFields)) {
                    $validation['errors'][] = 'Campos obrigatórios não encontrados: ' . implode(', ', $missingFields);
                } else {
                    $validation['is_valid'] = true;
                }

                // Verificações adicionais
                if ($validation['total_rows'] === 0) {
                    $validation['errors'][] = 'Arquivo não contém dados para importar.';
                    $validation['is_valid'] = false;
                }

                if ($validation['total_rows'] > 10000) {
                    $validation['warnings'][] = "Arquivo muito grande ({$validation['total_rows']} linhas). Considere dividir em arquivos menores.";
                }

            } else {
                $validation['errors'][] = 'Não foi possível abrir o arquivo.';
            }

        } catch (\Exception $e) {
            $validation['errors'][] = 'Erro ao processar arquivo: ' . $e->getMessage();
        }

        return $validation;
    }
}