<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PrdVersion extends Model
{
    use HasFactory, HasUuids;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'prd_id',
        'created_by',
        'version_number',
        'title',
        'content',
        'content_hash',
        'content_size',
        'change_summary',
        'change_source',
    ];

    protected function casts(): array
    {
        return [
            'version_number' => 'integer',
            'content_size' => 'integer',
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
     * Create a version from PRD content.
     */
    public static function createFromContent(
        Prd $prd,
        string $content,
        ?User $user = null,
        string $source = 'manual',
        ?string $summary = null
    ): self {
        $lastVersion = self::where('prd_id', $prd->id)
            ->orderByDesc('version_number')
            ->first();

        $versionNumber = ($lastVersion?->version_number ?? 0) + 1;

        return self::create([
            'prd_id' => $prd->id,
            'created_by' => $user?->id,
            'version_number' => $versionNumber,
            'title' => $prd->title,
            'content' => $content,
            'content_hash' => md5($content),
            'content_size' => strlen($content),
            'change_summary' => $summary,
            'change_source' => $source,
        ]);
    }

    public function toApiResponse(): array
    {
        return [
            'id' => $this->id,
            'version_number' => $this->version_number,
            'title' => $this->title,
            'content_size' => $this->content_size,
            'change_summary' => $this->change_summary,
            'change_source' => $this->change_source,
            'created_by' => $this->creator ? [
                'id' => $this->creator->id,
                'name' => $this->creator->name,
            ] : null,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
