<?php

namespace App\Models;

use App\Casts\EncryptedToken;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, HasUuids, Notifiable;

    /**
     * The primary key type.
     */
    protected $keyType = 'string';

    /**
     * Indicates if the IDs are auto-incrementing.
     */
    public $incrementing = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'id',
        'google_id',
        'name',
        'email',
        'avatar_url',
        'google_access_token',
        'google_refresh_token',
        'google_token_expires_at',
        'last_prd_id',
        'preferred_language',
        'tier',
        'tier_expires_at',
        'stripe_customer_id',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'google_access_token',
        'google_refresh_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'google_access_token' => EncryptedToken::class,
            'google_refresh_token' => EncryptedToken::class,
            'google_token_expires_at' => 'datetime',
            'tier_expires_at' => 'datetime',
        ];
    }

    /**
     * Check if the user's Google token is expired.
     */
    public function isGoogleTokenExpired(): bool
    {
        return $this->google_token_expires_at->isPast();
    }

    /**
     * Check if the user is on a paid tier.
     */
    public function isPaidTier(): bool
    {
        return in_array($this->tier, ['pro', 'team', 'enterprise']);
    }

    /**
     * Get the user's display data for API responses.
     */
    public function toApiResponse(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'avatar_url' => $this->avatar_url,
            'preferred_language' => $this->preferred_language,
            'tier' => $this->tier,
            'last_prd_id' => $this->last_prd_id,
        ];
    }
}
