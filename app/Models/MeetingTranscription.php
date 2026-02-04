<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

class MeetingTranscription extends Model
{
    use HasFactory;

    protected $table = 'transcriptions_laravel';

    public const STATUS_SCHEDULED = 'scheduled';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_FAILED = 'failed';

    protected $fillable = [
        'username',
        'meeting_name',
        'transcript_drive_id',
        'transcript_download_url',
        'audio_drive_id',
        'audio_download_url',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    /**
     * Containers that include this meeting.
     */
    public function containers(): BelongsToMany
    {
        return $this->belongsToMany(MeetingContentContainer::class, 'meeting_content_relations', 'meeting_id', 'container_id')
            ->withTimestamps();
    }

    /**
     * Groups that can access this meeting.
     */
    public function groups(): BelongsToMany
    {
        return $this->belongsToMany(MeetingGroup::class, 'meeting_group_meeting', 'meeting_id', 'meeting_group_id')
            ->withPivot('shared_by', 'created_at', 'updated_at')
            ->withTimestamps();
    }

    /**
     * Tasks generated for this meeting.
     */
    public function tasks(): HasMany
    {
        return $this->hasMany(MeetingTask::class, 'meeting_id');
    }

    /**
     * Scope meetings for a given user.
     */
    public function scopeForUser($query, User $user)
    {
        return $query->where('username', $user->username);
    }

    /**
     * Human readable status label.
     */
    public function getStatusLabelAttribute(): string
    {
        // Todas las reuniones almacenadas se consideran finalizadas
        return 'Finalizada';
    }

    /**
     * CSS classes for status badge.
     */
    public function getStatusBadgeColorAttribute(): string
    {
        // Todas las reuniones se muestran como completadas
        return 'bg-green-100 text-green-700';
    }

    /**
     * Duration in minutes helper.
     */
    public function getDurationMinutesAttribute(): ?int
    {
        if ($this->duration_seconds) {
            return (int) ceil($this->duration_seconds / 60);
        }

        return null;
    }
}
