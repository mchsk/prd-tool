<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class PrdShareLink extends Model
{
    use HasFactory, HasUuids;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'prd_id',
        'token',
        'access_level',
        'password_hash',
        'expires_at',
        'created_by',
        'is_active',
    ];

    protected $hidden = [
        'password_hash',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    public function prd(): BelongsTo
    {
        return $this->belongsTo(Prd::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Generate a secure token.
     */
    public static function generateToken(): string
    {
        return Str::random(64);
    }

    /**
     * Set password hash.
     */
    public function setPassword(?string $password): void
    {
        if ($password) {
            $this->password_hash = Hash::make($password);
        } else {
            $this->password_hash = null;
        }
    }

    /**
     * Verify password.
     */
    public function verifyPassword(string $password): bool
    {
        if (!$this->password_hash) {
            return true;
        }
        return Hash::check($password, $this->password_hash);
    }

    /**
     * Check if link is valid.
     */
    public function isValid(): bool
    {
        if (!$this->is_active) {
            return false;
        }

        if ($this->expires_at && $this->expires_at->isPast()) {
            return false;
        }

        return true;
    }

    public function toApiResponse(): array
    {
        return [
            'id' => $this->id,
            'token' => $this->token,
            'url' => config('app.frontend_url') . '/share/' . $this->token,
            'access_level' => $this->access_level,
            'has_password' => !empty($this->password_hash),
            'expires_at' => $this->expires_at?->toIso8601String(),
            'is_active' => $this->is_active,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
