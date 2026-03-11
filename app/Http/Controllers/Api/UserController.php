<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Exception;

class UserController extends Controller
{
    public function __construct(
        protected UserService $service
    ) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $users = $this->service->list(
            role: $request->filled('role')
                ? $request->input('role')
                : null,
            activeOnly: $request->boolean('active_only'),
            search: $request->filled('search')
                ? $request->input('search')
                : null,
            include: $request->has('include')
                ? explode(',', $request->input('include'))
                : null
        );

        return UserResource::collection($users);
    }

    public function store(StoreUserRequest $request): JsonResponse
    {
        $user = $this->service->create(
            $request->validated()
        );

        return (new UserResource($user))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Request $request, User $user): UserResource
    {
        $include = $request->has('include')
            ? explode(',', $request->input('include'))
            : null;

        return new UserResource(
            $this->service->show($user, $include)
        );
    }

    public function update(UpdateUserRequest $request, User $user): UserResource
    {
        return new UserResource(
            $this->service->update(
                $user,
                $request->validated()
            )
        );
    }

    public function destroy(User $user): JsonResponse
    {
        try {
            $this->service->delete($user);

            return response()->json([
                'message' => 'Usuario eliminado exitosamente'
            ]);
        } catch (Exception $e) {
            return response()->json([
                'message' => match ($e->getMessage()) {
                    'USER_HAS_SALES' =>
                    'No se puede eliminar un usuario que tiene ventas asociadas',
                    default => 'Error inesperado'
                },
                'error' => 'operation_not_allowed'
            ], 422);
        }
    }

    public function activate(User $user): UserResource
    {
        return new UserResource(
            $this->service->activate($user)
        );
    }

    public function deactivate(User $user): UserResource
    {
        return new UserResource(
            $this->service->deactivate($user)
        );
    }
}
