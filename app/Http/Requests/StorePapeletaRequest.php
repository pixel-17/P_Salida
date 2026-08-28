<?php

namespace App\Http\Requests;

use App\Models\Configuracion;
use App\Models\Papeleta;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;

class StorePapeletaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('crear', Papeleta::class);
    }

    public function rules(): array
    {
        return [
            'motivo_id' => ['required', 'exists:motivos,id'],
            'archivo' => ['nullable', 'file', 'max:5120', 'mimes:pdf,jpg,jpeg,png'],
            'destino' => ['required', 'string', 'max:255'],
            'motivo_detalle' => ['nullable', 'string'],
            'fecha_salida' => ['required', 'date', 'after_or_equal:today'],
            'hora_salida_programada' => ['required', 'date_format:H:i'],
            'hora_retorno_programada' => ['nullable', 'date_format:H:i', 'after:hora_salida_programada'],
        ];
    }

    /**
     * `after_or_equal:today` en fecha_salida solo valida el día; si la fecha
     * es HOY, falta impedir una hora_salida_programada que ya pasó. Se hace
     * aparte porque Laravel no tiene una regla nativa que compare una hora
     * contra "ahora mismo" condicionada a otro campo de fecha.
     *
     * De paso valida también horario laboral y días no laborables
     * (ver App\Models\Configuracion, editable por el ADMINISTRADOR en
     * /configuracion) — mismo motivo: son reglas que cruzan varios campos,
     * no encajan en `rules()`.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if (! $this->filled(['fecha_salida', 'hora_salida_programada'])) {
                return;
            }

            $fecha = Carbon::parse($this->input('fecha_salida'))->toDateString();

            $diasNoLaborables = Configuracion::diasNoLaborables();
            if (in_array(Carbon::parse($fecha)->dayOfWeek, $diasNoLaborables, true)) {
                $validator->errors()->add(
                    'fecha_salida',
                    'No se pueden crear papeletas en un día no laborable.'
                );
            }

            $horarioInicio = Configuracion::obtener('horario_laboral_inicio', '07:00');
            $horarioFin = Configuracion::obtener('horario_laboral_fin', '19:00');

            $horaSalida = Carbon::parse($fecha.' '.$this->input('hora_salida_programada'));
            $limiteInicio = Carbon::parse($fecha.' '.$horarioInicio);
            $limiteFin = Carbon::parse($fecha.' '.$horarioFin);

            if ($horaSalida->lessThan($limiteInicio) || $horaSalida->greaterThan($limiteFin)) {
                $validator->errors()->add(
                    'hora_salida_programada',
                    "La hora de salida debe estar dentro del horario laboral ({$horarioInicio} - {$horarioFin})."
                );
            }

            if ($this->filled('hora_retorno_programada')) {
                $horaRetorno = Carbon::parse($fecha.' '.$this->input('hora_retorno_programada'));

                if ($horaRetorno->greaterThan($limiteFin)) {
                    $validator->errors()->add(
                        'hora_retorno_programada',
                        "La hora de retorno no puede pasar del horario laboral ({$horarioFin})."
                    );
                }
            }

            if ($fecha !== now()->toDateString()) {
                return;
            }

            $horaSolicitada = Carbon::parse($fecha.' '.$this->input('hora_salida_programada'));

            if ($horaSolicitada->lessThan(now())) {
                $validator->errors()->add(
                    'hora_salida_programada',
                    'La hora de salida no puede ser anterior a la hora actual.'
                );
            }
        });
    }
}
