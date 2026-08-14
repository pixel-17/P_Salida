<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Estudiante extends Model
{
    use HasFactory;
    protected $table = 'estudiantes';
    
    protected $fillable = [
        'dni', 'nombres', 'apellidos','email', 'fecha_nacimiento',
        'genero', 'telefono', 'carrera_id', 'semestre',
    ];
 
    protected $casts = [
        'fecha_nacimiento' => 'date',
    ];
 
    // Relaciones
    public function carrera() {
        return $this->belongsTo(Carrera::class);
    }
 
    public function fichaMedica() {
        return $this->hasOne(FichaMedica::class);
    }
 
    public function atenciones() {
        return $this->hasMany(Atencion::class);
    }
 
    // Helpers
    public function getNombreCompletoAttribute(): string {
        return "{$this->nombres} {$this->apellidos}";
    }
 
    public function getEdadAttribute(): int {
        return $this->fecha_nacimiento
            ? $this->fecha_nacimiento->age
            : 0;
    }
 
    // Detecta si el estudiante tiene recurrencia en un motivo (>= 3 veces)
    public function tieneRecurrencia(int $motivoId): bool {
        return $this->atenciones()
            ->where('motivo_id', $motivoId)
            ->count() >= 3;
    }
}
