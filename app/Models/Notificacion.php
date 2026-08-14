<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Notificacion extends Model
{
    protected $table = 'notificaciones';

    protected $fillable = [
        'estudiante_id',
        'atencion_id',
        'email',
        'enviado',
        'enviado_en',
    ];

    protected $casts = [
        'enviado' => 'boolean',
        'enviado_en' => 'datetime',
    ];

    public function estudiante()
    {
        return $this->belongsTo(Estudiante::class);
    }

    public function atencion()
    {
        return $this->belongsTo(Atencion::class);
    }
}