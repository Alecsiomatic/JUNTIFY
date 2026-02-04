<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MeetingTask extends Model
{
    use HasFactory;

    protected $table = 'tasks_laravel';

    protected $fillable = [
        'username',
        'meeting_id',
        'tarea',
        'prioridad',
        'fecha_inicio',
        'fecha_limite',
        'descripcion',
        'progreso',
    ];

    protected $casts = [
        'fecha_inicio' => 'date',
        'fecha_limite' => 'date',
        'progreso' => 'integer',
    ];

    public function meeting(): BelongsTo
    {
        return $this->belongsTo(MeetingTranscription::class, 'meeting_id');
    }
}
