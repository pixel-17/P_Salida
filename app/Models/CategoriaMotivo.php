<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
 
class CategoriaMotivo extends Model {
    use HasFactory;
    protected $table = 'categorias_motivo';
    protected $fillable = ['nombre'];
 
    public function motivos() {
        return $this->hasMany(Motivo::class, 'categoria_id');
    }
}
