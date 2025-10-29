<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\UserService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class UserController extends Controller
{
    use AuthorizesRequests;

    /**
     * Create a new controller instance.
     */
    public function __construct(
        private UserService $userService
    ) {}

    /**
     * Display a listing of the resource.
     * 
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', User::class);

        $filters = [
            'search' => $request->get('search'),
            'role' => $request->get('role'),
            'per_page' => $request->get('per_page', 15),
        ];

        $users = $this->userService->getPaginatedUsers($filters);

        return UserResource::collection($users)
            ->additional(['message' => __('api.users.list_retrieved')])
            ->response();
    }

    /**
     * Store a newly created resource in storage.
     * 
     */
    public function store(StoreUserRequest $request): JsonResponse
    {
        // Authorization automatically handled by StoreUserRequest::authorize()
        $user = $this->userService->createUser($request->validated());

        return response()->json([
            'data' => UserResource::make($user),
            'message' => __('api.users.user_created')
        ], 201);
    }

    /**
     * Display the specified resource.
     * 
     */
    public function show(User $user): JsonResponse
    {
        $this->authorize('view', $user);

        return response()->json([
            'data' => UserResource::make($user->load('roles')),
            'message' => __('api.users.user_retrieved')
        ], 200);
    }

    /**
     * Update the specified resource in storage.
     * 
     */
    public function update(UpdateUserRequest $request, User $user): JsonResponse
    {
        $updatedUser = $this->userService->updateUser($user->id, $request->validated());

        return response()->json([
            'data' => UserResource::make($updatedUser),
            'message' => __('api.users.user_updated')
        ], 200);
    }

    /**
     * Remove the specified resource from storage.
     * 
     */
    public function destroy(User $user): Response
    {
        $this->authorize('delete', $user);

        $this->userService->deleteUser($user->id);

        return response()->noContent();
    }

    /**
     * Search users by term
     * 
     */
    public function search(Request $request): JsonResponse
    {
        $this->authorize('viewAny', User::class);

        $search = $request->get('search', '');
        $filters = ['role' => $request->get('role')];
        $users = $this->userService->searchUsers($search, $filters);

        return UserResource::collection($users)
            ->additional([
                'message' => __('api.users.search_completed'),
                'search_term' => $search
            ])
            ->response();
    }

    /**
     * Get user statistics
     * 
     */
    public function statistics(): JsonResponse
    {
        $this->authorize('viewAny', User::class);

        $statistics = $this->userService->getUserStatistics();

        return response()->json([
            'data' => $statistics,
            'message' => __('api.users.statistics_retrieved')
        ], 200);
    }

}
