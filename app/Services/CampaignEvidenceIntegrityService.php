<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class CampaignEvidenceIntegrityService
{
    public function validate(
        int $contestId,
        int $submissionId,
        object $evidence
    ): string {
        $path = $evidence->file_path ?? null;
        $expectedHash = $evidence->file_hash ?? null;

        $prefix = "campaign-results/{$contestId}/{$submissionId}/";

        if (
            ! is_string($path)
            || ! str_starts_with($path, $prefix)
            || strlen($path) <= strlen($prefix)
            || str_contains($path, '..')
            || str_contains($path, '\\')
            || str_contains($path, "\0")
        ) {
            throw ValidationException::withMessages([
                'evidence' => 'Evidence storage path is invalid.',
            ]);
        }

        $disk = Storage::disk('local');

        if (! $disk->exists($path)) {
            throw ValidationException::withMessages([
                'evidence' => 'Evidence file is missing.',
            ]);
        }

        $absolutePath = $disk->path($path);
        $root = realpath(
            dirname($disk->path('__kuraguard_storage_root_marker__'))
        );
        $resolved = realpath($absolutePath);

        if (
            $root === false
            || $resolved === false
            || ! str_starts_with(
                $resolved,
                rtrim($root, DIRECTORY_SEPARATOR)
                    . DIRECTORY_SEPARATOR
            )
            || ! is_file($resolved)
            || ! is_readable($resolved)
        ) {
            throw ValidationException::withMessages([
                'evidence' => 'Evidence file is inaccessible.',
            ]);
        }

        $actualHash = hash_file('sha256', $resolved);

        if (
            ! is_string($actualHash)
            || ! is_string($expectedHash)
            || ! preg_match('/^[a-fA-F0-9]{64}$/D', $expectedHash)
            || ! hash_equals(
                strtolower($expectedHash),
                strtolower($actualHash)
            )
        ) {
            throw ValidationException::withMessages([
                'evidence' => 'Evidence integrity verification failed.',
            ]);
        }

        return $resolved;
    }
}
