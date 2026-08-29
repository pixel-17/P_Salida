<?php

namespace App\Models;

use App\Enums\AccionFlujo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FlujoAprobacion extends Model
{
    use HasFactory;

    protected $table = 'flujo_aprobaciones';

    protected $fillable = [
        'papeleta_id', 'usuario_id', 'rol', 'accion', 'comentario',
    ];

    protected $casts = [
        'accion' => AccionFlujo::class,
    ];

    /** @return BelongsTo<Papeleta, $this> */
    public function papeleta(): BelongsTo
    {
        return $this->belongsTo(Papeleta::class);
    }

    /** @return BelongsTo<User, $this> */
    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }
}
