<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Subfolder extends Model
{
    use HasFactory;

    protected $fillable = [
        'folder_id',
        'google_id',
        'name',
    ];

    /**
     * Parent folder relation.
     */
    public function folder(): BelongsTo
    {
        return $this->belongsTo(Folder::class);
    }
}
