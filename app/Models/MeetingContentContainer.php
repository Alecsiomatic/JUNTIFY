<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class MeetingContentContainer extends Model
{
    use HasFactory;

    protected $fillable = [
        'username',
        'group_id',
        'name',
        'description',
        'drive_folder_id',
        'metadata',
        'is_active',
    ];

    protected $casts = [
        'metadata' => 'array',
        'is_active' => 'boolean',
    ];

    /**
     * The user that owns the container.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'username', 'username');
    }

    /**
     * Associated Google token (by username).
     */
    public function googleToken(): BelongsTo
    {
        return $this->belongsTo(GoogleToken::class, 'username', 'username');
    }

    /**
     * Meetings related to the container.
     */
    public function meetings(): BelongsToMany
    {
        return $this->belongsToMany(MeetingTranscription::class, 'meeting_content_relations', 'container_id', 'meeting_id')
            ->withTimestamps();
    }
}
