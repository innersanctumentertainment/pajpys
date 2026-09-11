<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreStaffRoleRequest;
use App\Http\Requests\Admin\UpdateStaffRoleRequest;
use App\Services\AuditLogService;
use Illuminate\Http\JsonResponse;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class StaffRoleController extends Controller
{
    public function __construct(
        private readonly AuditLogService $auditLog,
    ) {}

    public function index(): JsonResponse
    {
        $roles = Role::query()
            ->whereIn('name', ['staff', 'master_admin'])
            ->orWhere('name', 'like', 'staff_%')
            ->with('permissions')
            ->get();

        return response()->json(['roles' => $roles]);
    }

    public function permissions(): JsonResponse
    {
        return response()->json(['permissions' => Permission::query()->orderBy('name')->get()]);
    }

    public function store(StoreStaffRoleRequest $request): JsonResponse
    {
        $data = $request->validated();

        $role = Role::query()->create([
            'name' => $data['name'],
            'guard_name' => 'web',
        ]);

        $role->syncPermissions($data['permissions']);

        $this->auditLog->log(
            'admin.staff_role_created',
            newValues: ['role' => $role->name, 'permissions' => $data['permissions']],
            user: $request->user(),
        );

        return response()->json(['role' => $role->load('permissions')], 201);
    }

    public function show(Role $role): JsonResponse
    {
        return response()->json(['role' => $role->load('permissions')]);
    }

    public function update(UpdateStaffRoleRequest $request, Role $role): JsonResponse
    {
        if (in_array($role->name, ['master_admin', 'client', 'va'], true)) {
            return response()->json(['message' => 'System roles cannot be modified.'], 422);
        }

        $data = $request->validated();

        if (isset($data['name'])) {
            $role->update(['name' => $data['name']]);
        }

        if (isset($data['permissions'])) {
            $role->syncPermissions($data['permissions']);
        }

        $this->auditLog->log('admin.staff_role_updated', newValues: ['role' => $role->name], user: $request->user());

        return response()->json(['role' => $role->fresh()->load('permissions')]);
    }

    public function destroy(Role $role): JsonResponse
    {
        if (in_array($role->name, ['master_admin', 'staff', 'client', 'va'], true)) {
            return response()->json(['message' => 'System roles cannot be deleted.'], 422);
        }

        $role->delete();

        $this->auditLog->log('admin.staff_role_deleted', newValues: ['role' => $role->name], user: request()->user());

        return response()->json(['message' => 'Role deleted.']);
    }
}
