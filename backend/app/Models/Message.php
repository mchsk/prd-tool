<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Message extends Model
{
    use HasFactory, HasUuids;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'prd_id',
        'role',
        'content',
        'prd_update_suggestion',
        'update_applied',
        'token_count',
    ];

    protected function casts(): array
    {
        return [
            'update_applied' => 'boolean',
            'token_count' => 'integer',
        ];
    }

    public function prd(): BelongsTo
    {
        return $this->belongsTo(Prd::class);
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(MessageAttachment::class);
    }

    public function toApiResponse(): array
    {
        return [
            'id' => $this->id,
            'role' => $this->role,
            'content' => $this->content,
            'prd_update_suggestion' => $this->prd_update_suggestion,
            'update_applied' => $this->update_applied,
            'token_count' => $this->token_count,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
