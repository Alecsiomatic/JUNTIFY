<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

use App\Models\MeetingTranscription;
use App\Models\User;

/** @mixin \Eloquent */
class MeetingGroup extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'owner_id',
    ];

    /**
     * Owner of the group.
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    /**
     * Members that belong to the group.
     */
    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'meeting_group_user')
            ->withTimestamps();
    }

    /**
     * Meetings shared with this group.
     */
    public function meetings(): BelongsToMany
    {
        return $this->belongsToMany(MeetingTranscription::class, 'meeting_group_meeting', 'meeting_group_id', 'meeting_id')
            ->withPivot('shared_by', 'created_at', 'updated_at')
            ->withTimestamps();
    }

    /**
     * Scope groups accessible for a given user.
     */
    public function scopeForUser($query, object $user)
    {
        return $query->where('owner_id', $user->id)
            ->orWhereHas('members', function ($memberQuery) use ($user) {
                $memberQuery->where('users.id', $user->id);
            });
    }
}
