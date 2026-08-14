<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class FichaMedica extends Model {
    use HasFactory;
    protected $table = 'fichas_medicas';
    protected $fillable = [
        'estudiante_id','tipo_sangre','peso','talla',
        'alergias','enfermedades_previas','medicamentos_actuales',
        'telefono_emergencia','contacto_emergencia',
    ];

    public function estudiante() {
        return $this->belongsTo(Estudiante::class);
    }

    // Calcula el IMC si hay peso y talla
    public function getImcAttribute(): ?float {
        if ($this->peso && $this->talla && $this->talla > 0) {
            return round($this->peso / ($this->talla * $this->talla), 1);
        }
        return null;
    }

    // Clasifica el IMC
    public function getClasificacionImcAttribute(): ?string {
        $imc = $this->imc;
        if (!$imc) return null;
        if ($imc < 18.5) return 'Bajo peso';
        if ($imc < 25)   return 'Normal';
        if ($imc < 30)   return 'Sobrepeso';
        return 'Obesidad';
    }

    public function getColorImcAttribute(): string {
        $imc = $this->imc;
        if (!$imc) return 'badge-gray';
        if ($imc < 18.5) return 'badge-warning';
        if ($imc < 25)   return 'badge-success';
        if ($imc < 30)   return 'badge-warning';
        return 'badge-danger';
    }
}
