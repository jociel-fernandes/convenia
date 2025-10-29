<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCollaboratorRequest;
use App\Http\Requests\UpdateCollaboratorRequest;
use App\Http\Resources\CollaboratorResource;
use App\Models\Collaborator;
use App\Services\CollaboratorService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class CollaboratorController extends Controller
{
    use AuthorizesRequests;

    /**
     * Create a new controller instance.
     */
    public function __construct(
        private CollaboratorService $collaboratorService
    ) {}
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Collaborator::class);

        $filters = [
            'search' => $request->get('search'),
            'city' => $request->get('city'),
            'state' => $request->get('state'),
            'user_id' => $request->get('user_id'),
            'per_page' => $request->get('per_page', 15),
        ];

        $collaborators = $this->collaboratorService->getPaginatedCollaborators($filters);

        return CollaboratorResource::collection($collaborators)
            ->additional(['message' => 'Colaboradores listados com sucesso'])
            ->response();
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreCollaboratorRequest $request): JsonResponse
    {
        // Authorization automatically handled by StoreCollaboratorRequest::authorize()
        $collaborator = $this->collaboratorService->createCollaborator($request->validated());

        return response()->json([
            'data' => CollaboratorResource::make($collaborator),
            'message' => 'Colaborador criado com sucesso'
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Collaborator $collaborator): JsonResponse
    {
        $this->authorize('view', $collaborator);

        return response()->json([
            'data' => CollaboratorResource::make($collaborator->load('user:id,name,email')),
            'message' => 'Colaborador encontrado'
        ], 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateCollaboratorRequest $request, Collaborator $collaborator): JsonResponse
    {
        $updatedCollaborator = $this->collaboratorService->updateCollaborator($collaborator->id, $request->validated());

        return response()->json([
            'data' => CollaboratorResource::make($updatedCollaborator),
            'message' => 'Colaborador atualizado com sucesso'
        ], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Collaborator $collaborator): Response
    {
        $this->authorize('delete', $collaborator);

        $this->collaboratorService->deleteCollaborator($collaborator->id);

        return response()->noContent();
    }

    /**
     * Get collaborators statistics
     */
    public function statistics(): JsonResponse
    {
        $this->authorize('viewAny', Collaborator::class);

        $stats = $this->collaboratorService->getCollaboratorStatistics();

        return response()->json([
            'status' => true,
            'message' => 'Estatísticas de colaboradores',
            'data' => $stats,
        ]);
    }
}