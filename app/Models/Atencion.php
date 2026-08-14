<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Estudiante;
use App\Models\Motivo;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
 
class Atencion extends Model {
    use HasFactory;
    protected $table = 'atenciones';
 
    protected $fillable = [
        'estudiante_id', 'user_id', 'motivo_id', 'fecha',
        'diagnostico', 'tratamiento', 'observaciones',
        'es_recurrente', 'derivado',
    ];
 
    protected $casts = [
        'fecha'         => 'datetime',
        'es_recurrente' => 'boolean',
        'derivado'      => 'boolean',
    ];
 
    // Relaciones
    public function estudiante() {
        return $this->belongsTo(Estudiante::class);
    }
 
    public function enfermera() {
        return $this->belongsTo(User::class, 'user_id');
    }
 
    public function motivo() {
        return $this->belongsTo(Motivo::class);
    }
}