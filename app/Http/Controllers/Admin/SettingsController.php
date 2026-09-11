<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateSettingsRequest;
use App\Services\AuditLogService;
use App\Services\PlatformSettingsService;
use Illuminate\Http\JsonResponse;

class SettingsController extends Controller
{
    public function __construct(
        private readonly PlatformSettingsService $settings,
        private readonly AuditLogService $auditLog,
    ) {}

    public function index(): JsonResponse
    {
        return response()->json(['settings' => $this->settings->all()]);
    }

    public function show(string $key): JsonResponse
    {
        return response()->json([
            'key' => $key,
            'value' => $this->settings->get($key),
        ]);
    }

    public function update(UpdateSettingsRequest $request): JsonResponse
    {
        $updated = [];

        foreach ($request->validated('settings') as $setting) {
            $updated[] = $this->settings->set(
                $setting['key'],
                $setting['value'],
                $setting['type'] ?? 'string',
                $setting['group'] ?? null,
            );
        }

        $this->auditLog->log(
            'admin.settings_updated',
            newValues: ['keys' => collect($updated)->pluck('key')->all()],
            user: $request->user(),
        );

        return response()->json(['settings' => $updated]);
    }

    public function destroy(string $key): JsonResponse
    {
        $deleted = $this->settings->forget($key);

        if (! $deleted) {
            return response()->json(['message' => 'Setting not found.'], 404);
        }

        return response()->json(['message' => 'Setting removed.']);
    }
}
