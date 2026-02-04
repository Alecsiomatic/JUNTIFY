<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Crypt;

class AssistantSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'openai_api_key',
        'enable_drive_calendar',
    ];

    protected $casts = [
        'enable_drive_calendar' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function setOpenaiApiKeyAttribute(?string $value): void
    {
        $this->attributes['openai_api_key'] = $value ? Crypt::encryptString($value) : null;
    }

    public function getOpenaiApiKeyAttribute(?string $value): ?string
    {
        if (! $value) {
            return null;
        }

        try {
            return Crypt::decryptString($value);
        } catch (\Throwable $exception) {
            report($exception);

            return null;
        }
    }
}
