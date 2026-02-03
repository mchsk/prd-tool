<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Prd extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

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
     */
    protected $fillable = [
        'id',
        'user_id',
        'team_id',
        'title',
        'file_path',
        'status',
        'estimated_tokens',
        'created_from_template_id',
    ];

    /**
     * The attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'estimated_tokens' => 'integer',
        ];
    }

    /**
     * Get the user that owns the PRD.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the rules attached to this PRD.
     */
    public function rules(): BelongsToMany
    {
        return $this->belongsToMany(Rule::class, 'prd_rules')
            ->withPivot('priority')
            ->withTimestamps();
    }

    /**
     * Scope to get PRDs for a specific user.
     */
    public function scopeForUser($query, string $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope to get active (non-archived) PRDs.
     */
    public function scopeActive($query)
    {
        return $query->whereIn('status', ['draft', 'active']);
    }

    /**
     * Convert to API response format.
     */
    public function toApiResponse(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'status' => $this->status,
            'estimated_tokens' => $this->estimated_tokens,
            'created_from_template_id' => $this->created_from_template_id,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
