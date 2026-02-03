<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Team extends Model
{
    use HasFactory, HasUuids;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'name',
        'slug',
        'owner_id',
        'description',
        'max_members',
    ];

    protected function casts(): array
    {
        return [
            'max_members' => 'integer',
        ];
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function members(): HasMany
    {
        return $this->hasMany(TeamMember::class);
    }

    public function prds(): HasMany
    {
        return $this->hasMany(Prd::class);
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
     * Check if team has room for more members.
     */
    public function hasCapacity(): bool
    {
        return $this->members()->count() < $this->max_members;
    }

    public function toApiResponse(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'owner' => $this->owner ? [
                'id' => $this->owner->id,
                'name' => $this->owner->name,
            ] : null,
            'member_count' => $this->members()->count(),
            'max_members' => $this->max_members,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
