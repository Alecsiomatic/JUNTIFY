<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Folder extends Model
{
    use HasFactory;

    protected $fillable = [
        'google_token_id',
        'google_id',
        'name',
        'parent_id',
    ];

    /**
     * Token that owns the folder.
     */
    public function token(): BelongsTo
    {
        return $this->belongsTo(GoogleToken::class, 'google_token_id');
    }

    /**
     * Parent folder relation.
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    /**
     * Child folders relation.
     */
    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    /**
     * Subfolders configured for the folder.
     */
    public function subfolders(): HasMany
    {
        return $this->hasMany(Subfolder::class);
    }
}
