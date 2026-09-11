<?php

namespace App\Services;

use App\Models\StoredFile;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FileStorageService
{
    private const PRIVATE_DISK = 'local';

    public function storePrivate(
        UploadedFile $file,
        User $owner,
        array $allowedUserIds = [],
    ): StoredFile {
        $path = sprintf(
            'private/%d/%s_%s',
            $owner->id,
            Str::uuid()->toString(),
            $file->getClientOriginalName(),
        );

        Storage::disk(self::PRIVATE_DISK)->put($path, $file->get());

        return StoredFile::query()->create([
            'owner_id' => $owner->id,
            'disk' => self::PRIVATE_DISK,
            'path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getClientMimeType(),
            'size' => $file->getSize() ?: 0,
            'visibility' => 'private',
            'allowed_user_ids' => array_values(array_unique(array_merge(
                [$owner->id],
                $allowedUserIds,
            ))),
        ]);
    }

    public function canAccess(StoredFile $file, User $user): bool
    {
        if ($file->owner_id === $user->id) {
            return true;
        }

        return in_array($user->id, $file->allowed_user_ids ?? [], true);
    }

    public function grantAccess(StoredFile $file, User $user): StoredFile
    {
        $allowed = collect($file->allowed_user_ids ?? [])
            ->push($user->id)
            ->unique()
            ->values()
            ->all();

        $file->update(['allowed_user_ids' => $allowed]);

        return $file->fresh();
    }

    public function revokeAccess(StoredFile $file, User $user): StoredFile
    {
        if ($file->owner_id === $user->id) {
            throw new RuntimeException('Cannot revoke access from file owner.');
        }

        $allowed = collect($file->allowed_user_ids ?? [])
            ->reject(fn (int $id) => $id === $user->id)
            ->values()
            ->all();

        $file->update(['allowed_user_ids' => $allowed]);

        return $file->fresh();
    }

    public function download(StoredFile $file, User $user): StreamedResponse
    {
        if (! $this->canAccess($file, $user)) {
            abort(403, 'You do not have access to this file.');
        }

        if (! Storage::disk($file->disk)->exists($file->path)) {
            abort(404, 'File not found.');
        }

        return Storage::disk($file->disk)->download($file->path, $file->original_name);
    }

    public function delete(StoredFile $file, User $user): void
    {
        if ($file->owner_id !== $user->id) {
            abort(403, 'Only the file owner can delete this file.');
        }

        Storage::disk($file->disk)->delete($file->path);
        $file->delete();
    }
}
