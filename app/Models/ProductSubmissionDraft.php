<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class ProductSubmissionDraft extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'uuid',
        'name',
        'link',
        'payload',
        'last_autosaved_at',
    ];

    protected $casts = [
        'payload' => 'array',
        'last_autosaved_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $draft) {
            if (blank($draft->uuid)) {
                $draft->uuid = (string) Str::uuid();
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeForUser($query, User|int $user)
    {
        $userId = $user instanceof User ? $user->getKey() : $user;

        return $query->where('user_id', $userId);
    }

    public function title(): string
    {
        $name = trim((string) ($this->name ?? ''));
        if ($name !== '') {
            return $name;
        }

        $host = parse_url((string) $this->link, PHP_URL_HOST);
        if (is_string($host) && $host !== '') {
            return preg_replace('/^www\./i', '', $host);
        }

        return 'Untitled unfinished submission';
    }

    public function toSummaryArray(): array
    {
        $savedAt = $this->last_autosaved_at ?? $this->updated_at;

        return [
            'uuid' => $this->uuid,
            'title' => $this->title(),
            'link' => $this->link,
            'resume_url' => route('products.create', ['draft' => $this->uuid]),
            'updated_at' => $savedAt?->toIso8601String(),
            'updated_at_label' => $savedAt?->format('M j, Y g:i A'),
        ];
    }
}
