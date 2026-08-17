<?php

namespace App\Http\Controllers;

use App\Actions\ResponderObservacionAction;
use App\Enums\TipoObservacion;
use App\Models\Adjunto;
use App\Models\Papeleta;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;

class AdjuntoController extends Controller
{
    public function store(Request $request, Papeleta $papeleta, ResponderObservacionAction $responder): RedirectResponse
    {
        $this->authorize('adjuntar', $papeleta);

        $request->validate([
            'archivo' => ['required', 'file', 'max:5120', 'mimes:pdf,jpg,jpeg,png'],
        ]);

        // Se busca ANTES de crear el adjunto: si lo que motivó la subida
        // fue una observación pidiendo sustento, el archivo ES la
        // respuesta y queda ligado a ella (ver Adjunto::observacion) para
        // que la Policy lo trate como evidencia y ya no se pueda borrar,
        // sin importar el estado al que vuelva la papeleta después.
        $observacionPendiente = $papeleta->observaciones()
            ->where('atendida', false)
            ->where('tipo', TipoObservacion::JUSTIFICACION->value)
            ->latest()
            ->first();

        $archivo = $request->file('archivo');
        $ruta = $archivo->store("papeletas/{$papeleta->id}", 'local');

        Adjunto::create([
            'papeleta_id' => $papeleta->id,
            'observacion_id' => $observacionPendiente?->id,
            'nombre_original' => $archivo->getClientOriginalName(),
            'archivo' => $ruta,
            'extension' => $archivo->getClientOriginalExtension(),
            'peso' => $archivo->getSize(),
        ]);

        // El archivo ES la respuesta: no se le pide además escribir texto.
        // Un solo botón, una sola acción.
        if ($observacionPendiente) {
            $responder->execute($papeleta, $request->user(), "Documento adjuntado: {$archivo->getClientOriginalName()}");

            return back()->with('status', 'Documento adjuntado. La papeleta vuelve a revisión.');
        }

        return back()->with('status', 'Documento adjuntado.');
    }

    public function download(Adjunto $adjunto): Response
    {
        $this->authorize('ver', $adjunto->papeleta);

        // ->response() (no ->download()) sirve el archivo "inline": el
        // navegador lo abre/previsualiza directo (importante para PDFs),
        // en vez de forzar la descarga.
        return Storage::disk('local')->response($adjunto->archivo, $adjunto->nombre_original);
    }

    public function destroy(Adjunto $adjunto): RedirectResponse
    {
        $this->authorize('eliminarAdjunto', [$adjunto->papeleta, $adjunto]);

        // Antes solo se borraba la fila; el archivo físico en storage/app
        // quedaba huérfano para siempre (fuga de disco). Se borra el archivo
        // primero: si esto falla, el registro sigue existiendo y se puede
        // reintentar, en vez de perder el registro con el archivo aún ahí.
        Storage::disk('local')->delete($adjunto->archivo);

        $adjunto->delete();

        return back()->with('status', 'Documento eliminado.');
    }
}
