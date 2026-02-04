<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

class GoogleToken extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'username',
        'access_token',
        'refresh_token',
        'expiry_date',
        'scope',
        'token_type',
        'id_token',
        'token_created_at',
        'recordings_folder_id',
    ];

    protected $casts = [
        'expiry_date' => 'datetime',
        'token_created_at' => 'datetime',
    ];

    /**
     * User owner of the token.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'username', 'username');
    }

    /**
     * Root folder associated to the token.
     */
    public function rootFolder(): HasOne
    {
        return $this->hasOne(Folder::class)->whereNull('parent_id');
    }

    /**
     * All folders synced with the token.
     */
    public function folders(): HasMany
    {
        return $this->hasMany(Folder::class);
    }

    /**
     * Determine if the token is expired.
     */
    public function getIsExpiredAttribute(): bool
    {
        if (! $this->expiry_date instanceof Carbon) {
            return false;
        }

        return $this->expiry_date->isPast();
    }
}
