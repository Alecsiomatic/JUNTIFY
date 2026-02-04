<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class UserPanelAdministrativo extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     */
    protected $table = 'user_panel_administrativo';

    /**
     * The primary key associated with the table.
     */
    protected $primaryKey = 'id';

    /**
     * Indicates if the model should be timestamped.
     */
    public $timestamps = true;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'company_name',
        'administrator_id',
        'panel_url',
        'created_at',
        'updated_at',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the members for this panel.
     */
    public function members(): HasMany
    {
        return $this->hasMany(UserPanelMiembro::class, 'panel_id', 'id');
    }

    /**
     * Get the administrator user.
     */
    public function administrator()
    {
        return $this->belongsTo(User::class, 'administrator_id', 'id');
    }

    /**
     * Scope to filter DDU panels
     */
    public function scopeDdu($query)
    {
        return $query->where('company_name', 'DDU');
    }
}
