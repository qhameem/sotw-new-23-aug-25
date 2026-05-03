<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class ProductClaim extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_CANCELLED = 'cancelled';

    public const PROOF_TYPES = [
        'email_domain' => 'Verified email domain match',
        'dns_txt' => 'DNS TXT record',
        'html_meta' => 'HTML file or meta tag',
        'website_page' => 'Official website page',
        'social_profile' => 'Official social profile',
        'search_console' => 'Search Console / hosting dashboard',
        'other' => 'Other proof',
    ];

    protected $fillable = [
        'product_id',
        'user_id',
        'status',
        'proof_type',
        'proof_value',
        'message_to_admin',
        'auto_email_domain_match',
        'reviewed_by',
        'reviewed_at',
        'admin_note',
    ];

    protected $casts = [
        'auto_email_domain_match' => 'boolean',
        'reviewed_at' => 'datetime',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function proofTypeLabel(): string
    {
        return self::PROOF_TYPES[$this->proof_type] ?? Str::headline($this->proof_type);
    }

    public static function extractEmailDomain(?string $email): ?string
    {
        if (!is_string($email) || !str_contains($email, '@')) {
            return null;
        }

        return strtolower(trim((string) Str::afterLast($email, '@')));
    }

    public static function extractProductHost(?string $url): ?string
    {
        if (!is_string($url) || trim($url) === '') {
            return null;
        }

        $host = parse_url($url, PHP_URL_HOST);

        if (!is_string($host) || $host === '') {
            return null;
        }

        return strtolower(preg_replace('/^www\./i', '', $host));
    }

    public static function emailDomainMatchesProduct(?string $email, ?string $productUrl): bool
    {
        $emailDomain = static::extractEmailDomain($email);
        $productHost = static::extractProductHost($productUrl);

        if (!$emailDomain || !$productHost) {
            return false;
        }

        return $emailDomain === $productHost
            || Str::endsWith($productHost, '.' . $emailDomain)
            || Str::endsWith($emailDomain, '.' . $productHost);
    }
}
