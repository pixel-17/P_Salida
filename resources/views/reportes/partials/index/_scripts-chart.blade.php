@push('scripts')
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.4/chart.umd.min.js" crossorigin="anonymous"></script>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const trabajadores = @json($rankingTrabajadores->values());
            const areas = @json($rankingAreas->values());
            const motivos = @json($motivosMasUsados->values());

            function crearGrafico(id, datos, color, etiqueta) {
                const canvas = document.getElementById(id);
                if (!canvas || !datos.length || Chart.getChart(canvas)) {
                    return;
                }

                new Chart(canvas, {
                    type: 'bar',
                    data: {
                        labels: datos.map(item => item.nombre),
                        datasets: [{
                            label: etiqueta,
                            data: datos.map(item => item.total),
                            backgroundColor: color,
                            borderRadius: 6
                        }]
                    },
                    options: {
                        indexAxis: 'y',
                        responsive: true,
                        plugins: {
                            legend: { display: false }
                        },
                        scales: {
                            x: {
                                beginAtZero: true,
                                ticks: { precision: 0 }
                            }
                        }
                    }
                });
            }

            // "Resumen" es la pestaña activa por defecto, así que su
            // gráfico (trabajadores) se puede crear de inmediato: el
            // canvas ya tiene ancho real al montar.
            crearGrafico('chartTrabajadores', trabajadores, '#2549ea', 'Salidas');

            // Los gráficos de "Área y motivo" viven detrás de un x-show
            // (display:none) mientras esa pestaña no está activa. Chart.js
            // no puede medir un canvas oculto, así que se crean/redibujan
            // recién cuando la pestaña se abre por primera vez — ver el
            // x-effect de _panel-area-motivo.blade.php, que llama a esta
            // función cada vez que "tab" pasa a 'area_motivo'.
            window.dibujarGraficosAreaMotivo = () => {
                crearGrafico('chartAreas', areas, '#a855f7', 'Salidas');
                crearGrafico('chartMotivos', motivos, '#22c55e', 'Salidas');
            };
        });
    </script>
@endpush
