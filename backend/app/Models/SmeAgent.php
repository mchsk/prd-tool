<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;

class SmeAgent extends Model
{
    use HasFactory, HasUuids;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'user_id',
        'name',
        'slug',
        'description',
        'expertise',
        'system_prompt',
        'icon',
        'category',
        'is_public',
        'is_system',
        'usage_count',
    ];

    protected function casts(): array
    {
        return [
            'is_public' => 'boolean',
            'is_system' => 'boolean',
            'usage_count' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function prds(): BelongsToMany
    {
        return $this->belongsToMany(Prd::class, 'prd_sme_agents')
            ->withPivot('priority')
            ->withTimestamps();
    }

    /**
     * Generate a unique slug from name.
     */
    public static function generateSlug(string $name): string
    {
        $slug = Str::slug($name);
        $original = $slug;
        $counter = 1;

        while (self::where('slug', $slug)->exists()) {
            $slug = $original . '-' . $counter;
            $counter++;
        }

        return $slug;
    }

    /**
     * Scope to get visible agents for a user.
     */
    public function scopeVisibleTo($query, ?string $userId)
    {
        return $query->where(function ($q) use ($userId) {
            $q->where('is_public', true)
                ->orWhere('is_system', true);
            
            if ($userId) {
                $q->orWhere('user_id', $userId);
            }
        });
    }

    /**
     * Increment usage counter.
     */
    public function incrementUsage(): void
    {
        $this->increment('usage_count');
    }

    /**
     * Get available categories.
     */
    public static function getCategories(): array
    {
        return [
            'general' => 'General',
            'technical' => 'Technical',
            'business' => 'Business',
            'design' => 'Design',
            'security' => 'Security',
            'compliance' => 'Compliance',
            'industry' => 'Industry Expert',
            'domain' => 'Domain Expert',
        ];
    }

    public function toApiResponse(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'expertise' => $this->expertise,
            'icon' => $this->icon,
            'category' => $this->category,
            'is_public' => $this->is_public,
            'is_system' => $this->is_system,
            'usage_count' => $this->usage_count,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
