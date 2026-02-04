<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Permission extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     */
    protected $table = 'permissions';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'name',
        'description',
    ];

    /**
     * Get the memberships for this permission.
     */
    public function memberships()
    {
        return $this->hasMany(UserPanelMiembro::class, 'permission_id', 'id');
    }
}
