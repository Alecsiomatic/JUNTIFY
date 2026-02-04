<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class AssistantDocument extends Model
{
    use HasFactory;

    protected $fillable = [
        'assistant_conversation_id',
        'original_name',
        'path',
        'mime_type',
        'size',
        'extracted_text',
        'summary',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(AssistantConversation::class, 'assistant_conversation_id');
    }

    public function getStoragePathAttribute(): string
    {
        return storage_path('app/' . $this->path);
    }

    public function getExcerptAttribute(): ?string
    {
        if (! $this->summary && ! $this->extracted_text) {
            return null;
        }

        $source = $this->summary ?: $this->extracted_text;

        return Str::of($source)->stripTags()->squish()->limit(240);
    }
}
