<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IntegrantesEmpresa extends Model
{
    use HasFactory;

    /**
     * The connection name for the model.
     */
    protected $connection = 'juntify_panels';

    /**
     * The table associated with the model.
     */
    protected $table = 'integrantes_empresa';

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
        'iduser',
        'empresa_id',
        'rol',
        'permisos',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'permisos' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the empresa that owns this integrante.
     */
    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class, 'empresa_id', 'id');
    }

    /**
     * Get the user from juntify database.
     * This uses a different connection to fetch user data.
     */
    public function juntifyUser(): BelongsTo
    {
        return $this->setConnection('juntify')
            ->belongsTo(User::class, 'iduser', 'id');
    }

    /**
     * Scope to filter by DDU company members
     */
    public function scopeDduMembers($query)
    {
        return $query->whereHas('empresa', function ($q) {
            $q->where('nombre_empresa', 'DDU');
        });
    }

    /**
     * Check if user is a DDU member by user ID from juntify
     */
    public static function isDduMember($userId)
    {
        return static::whereHas('empresa', function ($q) {
            $q->where('nombre_empresa', 'DDU');
        })->where('iduser', $userId)->exists();
    }

    /**
     * Get DDU membership info for a user
     */
    public static function getDduMembership($userId)
    {
        return static::with(['empresa'])
            ->whereHas('empresa', function ($q) {
                $q->where('nombre_empresa', 'DDU');
            })
            ->where('iduser', $userId)
            ->first();
    }

    /**
     * Get all DDU members with their user info from juntify
     */
    public static function getAllDduMembersWithUsers()
    {
        $members = static::with(['empresa'])
            ->whereHas('empresa', function ($q) {
                $q->where('nombre_empresa', 'DDU');
            })
            ->get();

        // Fetch user info from juntify database for each member
        $userIds = $members->pluck('iduser');
        $juntifyUsers = \DB::connection('juntify')
            ->table('users')
            ->whereIn('id', $userIds)
            ->get()
            ->keyBy('id');

        // Attach user info to members
        $members->each(function ($member) use ($juntifyUsers) {
            $member->user_info = $juntifyUsers->get($member->iduser);
        });

        return $members;
    }
}
