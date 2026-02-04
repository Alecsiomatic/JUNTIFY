<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AssistantConversation extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'title',
        'description',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(AssistantMessage::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(AssistantDocument::class);
    }

    protected static function boot()
    {
        parent::boot();

        static::deleting(function (AssistantConversation $conversation) {
            // Eliminar todos los mensajes asociados
            $conversation->messages()->delete();

            // Eliminar todos los documentos asociados
            $conversation->documents()->delete();
        });
    }
}
