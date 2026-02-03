<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContextSummary extends Model
{
    use HasFactory, HasUuids;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'prd_id',
        'summary',
        'messages_summarized',
        'last_message_id',
    ];

    protected function casts(): array
    {
        return [
            'messages_summarized' => 'integer',
        ];
    }

    public function prd(): BelongsTo
    {
        return $this->belongsTo(Prd::class);
    }

    public function lastMessage(): BelongsTo
    {
        return $this->belongsTo(Message::class, 'last_message_id');
    }
}
