<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Spatie\Permission\Traits\HasRoles;
use LemonSqueezy\Laravel\Billable;

class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasRoles, Billable;

    public const RESERVED_PUBLIC_HANDLES = [
        'admin',
        'api',
        'app',
        'articles',
        'collections',
        'create',
        'dashboard',
        'edit',
        'help',
        'login',
        'manage',
        'my',
        'new',
        'profile',
        'register',
        'search',
        'settings',
        'support',
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'public_handle',
        'email',
        'password',
        'google_id',
        'google_avatar',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function products()
    {
        return $this->hasMany(Product::class);
    }

    public function productClaims(): HasMany
    {
        return $this->hasMany(ProductClaim::class);
    }

    public function profile(): HasOne
    {
        return $this->hasOne(UserProfile::class);
    }

    /**
     * Get the product upvotes for the user.
     */
    public function productUpvotes(): HasMany
    {
        return $this->hasMany(UserProductUpvote::class);
    }

    public function productCollections(): HasMany
    {
        return $this->hasMany(ProductCollection::class);
    }

    public function productSubmissionDrafts(): HasMany
    {
        return $this->hasMany(ProductSubmissionDraft::class);
    }

    public static function reservedPublicHandles(): array
    {
        return self::RESERVED_PUBLIC_HANDLES;
    }

    public function suggestedPublicHandle(): string
    {
        $baseHandle = Str::slug($this->name ?: Str::before((string) $this->email, '@'));

        return $baseHandle !== '' ? $baseHandle : 'member';
    }

    public function ensurePublicHandle(): string
    {
        if (filled($this->public_handle)) {
            return $this->public_handle;
        }

        $baseHandle = $this->suggestedPublicHandle();
        $handle = $baseHandle;
        $counter = 2;

        while (static::query()
            ->where('public_handle', $handle)
            ->whereKeyNot($this->getKey())
            ->exists()) {
            $handle = $baseHandle . '-' . $counter++;
        }

        $this->forceFill(['public_handle' => $handle])->saveQuietly();

        return $handle;
    }

    public function avatar()
    {
        if ($this->google_avatar) {
            return $this->google_avatar;
        }

        $hash = md5(strtolower(trim($this->email)));
        return "https://www.gravatar.com/avatar/$hash?d=mp";
    }
public function articles()
    {
        return $this->hasMany(Article::class);
    }
    public function is_admin()
    {
        return $this->hasRole('admin');
    }

    public function todoLists()
    {
        return $this->hasMany(TodoList::class);
    }
    public static function getAdmins()
    {
        return self::role('admin')->get();
    }
}
