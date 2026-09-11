<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Request;

class AuditLogService
{
    public function log(
        string $event,
        ?Model $auditable = null,
        array $oldValues = [],
        array $newValues = [],
        ?User $user = null,
        ?string $ipAddress = null,
        ?string $userAgent = null,
    ): AuditLog {
        $previousHash = AuditLog::query()->orderByDesc('id')->value('entry_hash');
        $occurredAt = now();

        $entryData = [
            'user_id' => $user?->id ?? auth()->id(),
            'event' => $event,
            'auditable_type' => $auditable?->getMorphClass(),
            'auditable_id' => $auditable?->getKey(),
            'old_values' => $oldValues ?: null,
            'new_values' => $newValues ?: null,
            'previous_hash' => $previousHash,
            'ip_address' => $ipAddress ?? Request::ip(),
            'user_agent' => $userAgent ?? Request::userAgent(),
            'occurred_at' => $occurredAt,
            'created_at' => $occurredAt,
        ];

        $entryData['entry_hash'] = $this->computeHash($entryData, $previousHash);

        return AuditLog::query()->create($entryData);
    }

    public function verifyChain(int $limit = 1000): bool
    {
        $previousHash = null;

        foreach (AuditLog::query()->orderBy('id')->limit($limit)->cursor() as $entry) {
            $expected = $this->computeHash([
                'user_id' => $entry->user_id,
                'event' => $entry->event,
                'auditable_type' => $entry->auditable_type,
                'auditable_id' => $entry->auditable_id,
                'old_values' => $entry->old_values,
                'new_values' => $entry->new_values,
                'previous_hash' => $entry->previous_hash,
                'ip_address' => $entry->ip_address,
                'user_agent' => $entry->user_agent,
                'occurred_at' => $entry->occurred_at?->toIso8601String(),
            ], $entry->previous_hash);

            if ($entry->entry_hash === null || ! hash_equals($expected, $entry->entry_hash)) {
                return false;
            }

            if ($entry->previous_hash !== $previousHash) {
                return false;
            }

            $previousHash = $entry->entry_hash;
        }

        return true;
    }

    /**
     * @param  array<string, mixed>  $entryData
     */
    private function computeHash(array $entryData, ?string $previousHash): string
    {
        $canonical = json_encode([
            'user_id' => $entryData['user_id'] ?? null,
            'event' => $entryData['event'] ?? null,
            'auditable_type' => $entryData['auditable_type'] ?? null,
            'auditable_id' => $entryData['auditable_id'] ?? null,
            'old_values' => $entryData['old_values'] ?? null,
            'new_values' => $entryData['new_values'] ?? null,
            'previous_hash' => $previousHash,
            'ip_address' => $entryData['ip_address'] ?? null,
            'user_agent' => $entryData['user_agent'] ?? null,
            'occurred_at' => $entryData['occurred_at'] ?? null,
        ], JSON_THROW_ON_ERROR);

        return hash('sha256', ($previousHash ?? '').$canonical);
    }
}
