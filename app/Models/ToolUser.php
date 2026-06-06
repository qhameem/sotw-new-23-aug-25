<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;

class ToolUser extends Authenticatable
{
    use HasFactory;
    use Notifiable;

    protected $fillable = [
        'name',
        'email',
        'google_id',
        'google_avatar',
    ];

    protected $hidden = [
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
        ];
    }

    public function scans(): HasMany
    {
        return $this->hasMany(ToolScan::class);
    }

    public function isAdmin(): bool
    {
        return in_array(strtolower($this->email), config('launch_readiness.admin_emails', []), true);
    }

    public function initials(): string
    {
        $source = $this->name ?: Str::before($this->email, '@');

        return Str::of($source)
            ->explode(' ')
            ->filter()
            ->take(2)
            ->map(fn (string $part) => Str::upper(Str::substr($part, 0, 1)))
            ->implode('');
    }

    public function avatarUrl(): ?string
    {
        return $this->google_avatar ?: null;
    }
}
