<?php

namespace App\Services;

use App\Models\ProductSubmissionDraft;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ProductRegenerationLimiter
{
    public const LIMIT = 3;

    public function consume(User $user, string $draftUuid, string $field): array
    {
        if ($user->hasRole('admin')) {
            return ['used' => 0, 'remaining' => null, 'limit' => self::LIMIT];
        }

        return DB::transaction(function () use ($user, $draftUuid, $field) {
            $draft = ProductSubmissionDraft::query()
                ->forUser($user)
                ->where('uuid', $draftUuid)
                ->lockForUpdate()
                ->firstOrFail();

            $counts = $draft->regeneration_counts ?? [];
            $used = (int) ($counts[$field] ?? 0);

            if ($used >= self::LIMIT) {
                throw ValidationException::withMessages([
                    $field => "You have used all ".self::LIMIT." regenerations for this field.",
                ]);
            }

            $counts[$field] = ++$used;
            $draft->forceFill(['regeneration_counts' => $counts])->save();

            return ['used' => $used, 'remaining' => self::LIMIT - $used, 'limit' => self::LIMIT];
        });
    }
}
