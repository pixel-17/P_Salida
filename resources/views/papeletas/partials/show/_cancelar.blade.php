@can('cancelar', $papeleta)
    <div class="glass-card p-5 mb-4 animate-fade-in-up"
         x-data="{ abierto: false, confirmando: false, motivo: '' }">
        <button type="button" @click="abierto = true"
                class="btn-glass !text-red-600 !bg-red-50/80 hover:!bg-red-100/80 border border-red-200/60 text-sm w-full sm:w-auto">
            Cancelar esta papeleta
        </button>

        <div x-show="abierto" x-cloak x-transition
             class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4">
            <div @click.outside="abierto = false" class="glass-panel !bg-white p-5 max-w-sm w-full">
                <template x-if="!confirmando">
                    <div>
                        <h3 class="font-semibold text-gray-800 mb-2">¿Por qué cancelas esta papeleta?</h3>
                        <textarea x-model="motivo" rows="3" maxlength="500"
                                  class="input-glass !py-2 text-sm w-full"
                                  placeholder="Cuéntanos brevemente el motivo (mínimo 5 caracteres)"></textarea>
                        <div class="flex justify-end gap-2 mt-3">
                            <button type="button" @click="abierto = false" class="btn-secondary text-sm">Volver</button>
                            <button type="button"
                                    :disabled="motivo.trim().length < 5"
                                    :class="motivo.trim().length < 5 ? 'opacity-50 cursor-not-allowed' : ''"
                                    @click="confirmando = true"
                                    class="btn-glass !text-red-600 !bg-red-50/80 border border-red-200/60 text-sm">
                                Continuar
                            </button>
                        </div>
                    </div>
                </template>

                <template x-if="confirmando">
                    <div>
                        <h3 class="font-semibold text-red-700 mb-2">¿Seguro que quieres cancelarla?</h3>
                        <p class="text-sm text-gray-600 mb-4">Esta acción no se puede deshacer. La papeleta quedará como CANCELADA.</p>
                        <div class="flex justify-end gap-2">
                            <button type="button" @click="confirmando = false" class="btn-secondary text-sm">No, volver</button>
                            <form method="POST" action="{{ route('papeletas.cancelar', $papeleta) }}">
                                @csrf
                                <input type="hidden" name="motivo" :value="motivo">
                                <button type="submit" class="btn-glass !text-white text-sm" style="background: linear-gradient(135deg, #ef4444 0%, #b91c1c 100%);">
                                    Sí, cancelar definitivamente
                                </button>
                            </form>
                        </div>
                    </div>
                </template>
            </div>
        </div>

        @error('motivo')
            <p class="text-xs text-red-600 mt-2">{{ $message }}</p>
        @enderror
    </div>
@endcan
