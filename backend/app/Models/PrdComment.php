<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PrdComment extends Model
{
    use HasFactory, HasUuids;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'prd_id',
        'user_id',
        'author_name',
        'content',
        'line_number',
        'anchor_text',
        'parent_id',
        'is_resolved',
    ];

    protected function casts(): array
    {
        return [
            'line_number' => 'integer',
            'is_resolved' => 'boolean',
        ];
    }

    public function prd(): BelongsTo
    {
        return $this->belongsTo(Prd::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(PrdComment::class, 'parent_id');
    }

    public function replies(): HasMany
    {
        return $this->hasMany(PrdComment::class, 'parent_id');
    }

    /**
     * Get the author name (from user or anonymous name).
     */
    public function getAuthorNameAttribute(?string $value): string
    {
        if ($this->user) {
            return $this->user->name;
        }
        return $value ?? 'Anonymous';
    }

    public function toApiResponse(): array
    {
        return [
            'id' => $this->id,
            'author' => [
                'id' => $this->user_id,
                'name' => $this->author_name,
                'avatar_url' => $this->user?->avatar_url,
            ],
            'content' => $this->content,
            'line_number' => $this->line_number,
            'anchor_text' => $this->anchor_text,
            'parent_id' => $this->parent_id,
            'is_resolved' => $this->is_resolved,
            'replies_count' => $this->replies()->count(),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
