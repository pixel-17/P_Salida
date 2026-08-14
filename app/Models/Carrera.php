<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
 
class Carrera extends Model {
    use HasFactory;
    protected $fillable = ['nombre'];
 
    public function estudiantes() {
        return $this->hasMany(Estudiante::class);
    }
}
