<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserPanelMiembro extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     */
    protected $table = 'user_panel_miembros';

    /**
     * The primary key associated with the table.
     */
    protected $primaryKey = 'id';

    /**
     * Indicates if the IDs are auto-incrementing.
     */
    public $incrementing = true;

    /**
     * The "type" of the primary key ID.
     */
    protected $keyType = 'int';

    /**
     * Indicates if the model should be timestamped.
     */
    public $timestamps = true;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'panel_id',
        'user_id',
        'role',
        'permission_id',
        'is_active',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the user that owns the membership.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    /**
     * Get the panel that owns the membership.
     */
    public function panel(): BelongsTo
    {
        return $this->belongsTo(UserPanelAdministrativo::class, 'panel_id', 'id');
    }

    /**
     * Get the permission for this membership.
     */
    public function permission(): BelongsTo
    {
        return $this->belongsTo(Permission::class, 'permission_id', 'id');
    }

    /**
     * Scope to filter by DDU company
     */
    public function scopeDduMembers($query)
    {
        return $query->whereHas('panel', function ($q) {
            $q->where('company_name', 'DDU');
        });
    }

    /**
     * Check if user is a DDU member by email or user_id
     */
    public static function isDduMember($userIdentifier, $requiredRole = null)
    {
        // Si es un email, buscar por email; si es numérico, buscar por ID
        if (filter_var($userIdentifier, FILTER_VALIDATE_EMAIL)) {
            $query = static::whereHas('user', function ($q) use ($userIdentifier) {
                $q->where('email', $userIdentifier);
            });
        } else {
            $query = static::where('user_id', $userIdentifier);
        }

        if ($requiredRole) {
            $query->where('role', $requiredRole);
        }

        return $query->where('is_active', true)->exists();
    }

    /**
     * Get user's DDU membership info
     */
    public static function getDduMembership($userId)
    {
        return static::with(['panel', 'user'])
            ->where('user_id', $userId)
            ->whereHas('panel', function ($q) {
                $q->where('company_name', 'DDU');
            })
            ->first();
    }
}
