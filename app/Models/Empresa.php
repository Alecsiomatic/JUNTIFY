<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Empresa extends Model
{
    use HasFactory;

    /**
     * The connection name for the model.
     */
    protected $connection = 'juntify_panels';

    /**
     * The table associated with the model.
     */
    protected $table = 'empresa';

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
        'nombre_empresa',
        'rol',
        'es_administrador',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'es_administrador' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the integrantes (members) for this empresa.
     */
    public function integrantes(): HasMany
    {
        return $this->hasMany(IntegrantesEmpresa::class, 'empresa_id', 'id');
    }

    /**
     * Get the user from juntify database that owns this empresa.
     * This uses a different connection to fetch user data.
     */
    public function juntifyUser(): BelongsTo
    {
        return $this->setConnection('juntify')
            ->belongsTo(User::class, 'iduser', 'id');
    }

    /**
     * Scope to filter by DDU company
     */
    public function scopeDdu($query)
    {
        return $query->where('nombre_empresa', 'DDU');
    }

    /**
     * Check if this empresa is DDU
     */
    public function isDdu(): bool
    {
        return $this->nombre_empresa === 'DDU';
    }
}
