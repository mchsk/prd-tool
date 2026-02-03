<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Template extends Model
{
    use HasFactory, HasUuids;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'user_id',
        'name',
        'description',
        'content',
        'category',
        'is_public',
        'usage_count',
    ];

    protected function casts(): array
    {
        return [
            'is_public' => 'boolean',
            'usage_count' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Check if template is a system template.
     */
    public function isSystemTemplate(): bool
    {
        return $this->user_id === null;
    }

    /**
     * Increment usage count.
     */
    public function incrementUsage(): void
    {
        $this->increment('usage_count');
    }

    /**
     * Scope for templates visible to a user.
     */
    public function scopeVisibleTo($query, ?string $userId)
    {
        return $query->where(function ($q) use ($userId) {
            // System templates (no user_id)
            $q->whereNull('user_id')
                // Public templates
                ->orWhere('is_public', true);

            // User's own templates
            if ($userId) {
                $q->orWhere('user_id', $userId);
            }
        });
    }

    public function toApiResponse(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'content' => $this->content,
            'content_preview' => Str::limit($this->content, 200),
            'category' => $this->category,
            'is_public' => $this->is_public,
            'is_system' => $this->isSystemTemplate(),
            'is_owner' => false, // Set in controller
            'usage_count' => $this->usage_count,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
