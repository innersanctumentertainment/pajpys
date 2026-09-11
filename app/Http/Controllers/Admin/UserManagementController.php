<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateUserRequest;
use App\Models\User;
use App\Services\AuditLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserManagementController extends Controller
{
    public function __construct(
        private readonly AuditLogService $auditLog,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $users = User::query()
            ->with(['roles', 'vaProfile', 'clientProfile'])
            ->when($request->query('role'), fn ($q, $role) => $q->role($role))
            ->when($request->query('search'), function ($q, $search) {
                $q->where(function ($query) use ($search) {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->latest()
            ->paginate(30);

        return response()->json($users);
    }

    public function show(User $user): JsonResponse
    {
        return response()->json([
            'user' => $user->load(['roles', 'permissions', 'vaProfile', 'clientProfile', 'wallets']),
        ]);
    }

    public function update(UpdateUserRequest $request, User $user): JsonResponse
    {
        $data = $request->validated();
        $oldValues = $user->only(['name', 'email', 'locked_until']);

        if (isset($data['name'], $data['email'])) {
            $user->update(collect($data)->only(['name', 'email', 'locked_until'])->filter()->all());
        }

        if (isset($data['roles'])) {
            $user->syncRoles($data['roles']);
        }

        $this->auditLog->log(
            'admin.user_updated',
            $user,
            oldValues: $oldValues,
            newValues: $data,
            user: $request->user(),
        );

        return response()->json(['user' => $user->fresh()->load('roles')]);
    }
}
