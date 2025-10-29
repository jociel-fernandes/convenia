<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ImportCollaboratorsRequest;
use App\Services\CollaboratorImportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class CollaboratorImportController extends Controller
{
    public function __construct(
        private CollaboratorImportService $importService
    ) {}

    /**
     * Upload e iniciar importação de colaboradores
     */
    public function upload(ImportCollaboratorsRequest $request): JsonResponse
    {
        Gate::authorize('create', \App\Models\Collaborator::class);

        try {
            $options = [
                'delimiter' => $request->input('delimiter', ','),
                'encoding' => $request->input('encoding', 'UTF-8'),
                'has_header' => $request->boolean('has_header', true),
                'field_mapping' => $request->input('field_mapping', [])
            ];

            $import = $this->importService->startImport(
                $request->file('file'),
                Auth::id(),
                $options
            );

            return response()->json([
                'success' => true,
                'message' => 'Import iniciado com sucesso',
                'data' => [
                    'import_id' => $import->id,
                    'status' => $import->status,
                    'filename' => $import->original_filename
                ]
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao iniciar import',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Consultar status de uma importação
     */
    public function status(int $importId): JsonResponse
    {
        Gate::authorize('create', \App\Models\Collaborator::class);

        $import = $this->importService->getImportStatus($importId);

        if (!$import) {
            return response()->json([
                'success' => false,
                'message' => 'Import não encontrado'
            ], 404);
        }

        // Verificar se o usuário é dono do import
        if ($import->user_id !== Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => 'Acesso negado'
            ], 403);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $import->id,
                'status' => $import->status,
                'filename' => $import->original_filename,
                'total_rows' => $import->total_rows,
                'processed_rows' => $import->processed_rows,
                'success_count' => $import->success_count,
                'error_count' => $import->error_count,
                'progress_percentage' => $import->progress_percentage,
                'errors' => $import->errors,
                'started_at' => $import->started_at?->toISOString(),
                'completed_at' => $import->completed_at?->toISOString(),
                'created_at' => $import->created_at->toISOString()
            ]
        ]);
    }

    /**
     * Listar importações do usuário
     */
    public function index(Request $request): JsonResponse
    {
        try {
            Gate::authorize('create', \App\Models\Collaborator::class);

            $perPage = $request->input('per_page', 15);
            $imports = $this->importService->getUserImports(Auth::id(), $perPage);

            return response()->json([
                'success' => true,
                'data' => $imports->items(),
                'meta' => [
                    'current_page' => $imports->currentPage(),
                    'last_page' => $imports->lastPage(),
                    'per_page' => $imports->perPage(),
                    'total' => $imports->total()
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao listar imports',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Cancelar importação em andamento
     */
    public function cancel(int $importId): JsonResponse
    {
        Gate::authorize('create', \App\Models\Collaborator::class);

        $import = $this->importService->getImportStatus($importId);

        if (!$import) {
            return response()->json([
                'success' => false,
                'message' => 'Import não encontrado'
            ], 404);
        }

        if ($import->user_id !== Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => 'Acesso negado'
            ], 403);
        }

        $cancelled = $this->importService->cancelImport($importId);

        if (!$cancelled) {
            return response()->json([
                'success' => false,
                'message' => 'Não é possível cancelar este import'
            ], 400);
        }

        return response()->json([
            'success' => true,
            'message' => 'Import cancelado com sucesso'
        ]);
    }

    /**
     * Validar estrutura do CSV antes do upload
     */
    public function validate(ImportCollaboratorsRequest $request): JsonResponse
    {
        Gate::authorize('create', \App\Models\Collaborator::class);

        $options = [
            'delimiter' => $request->input('delimiter', ','),
            'has_header' => $request->boolean('has_header', true)
        ];

        $validation = $this->importService->validateCsvStructure(
            $request->file('file'),
            $options
        );

        return response()->json([
            'success' => $validation['is_valid'],
            'validation' => $validation
        ]);
    }

    /**
     * Download template CSV
     */
    public function template(): JsonResponse
    {
        Gate::authorize('create', \App\Models\Collaborator::class);

        try {
            $filename = $this->importService->generateCsvTemplate();

            return response()->json([
                'success' => true,
                'message' => 'Template gerado com sucesso',
                'data' => [
                    'download_url' => asset("storage/{$filename}"),
                    'filename' => $filename
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao gerar template',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Exportar colaboradores para CSV
     */
    public function export(Request $request): JsonResponse
    {
        Gate::authorize('viewAny', \App\Models\Collaborator::class);

        $filters = $request->only(['city', 'state', 'user_id']);

        try {
            $filename = $this->importService->exportCollaborators(Auth::id(), $filters);

            return response()->json([
                'success' => true,
                'message' => 'Export gerado com sucesso',
                'data' => [
                    'download_url' => asset("storage/{$filename}"),
                    'filename' => $filename
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao gerar export',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}