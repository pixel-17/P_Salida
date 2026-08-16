@push('scripts')
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.4/chart.umd.min.js" crossorigin="anonymous"></script>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const trabajadores = @json($rankingTrabajadores->values());
            const areas = @json($rankingAreas->values());
            const motivos = @json($motivosMasUsados->values());

            function crearGrafico(id, datos, color, etiqueta) {
                const canvas = document.getElementById(id);
                if (!canvas || !datos.length) {
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

            crearGrafico('chartTrabajadores', trabajadores, '#2549ea', 'Salidas');
            crearGrafico('chartAreas', areas, '#a855f7', 'Salidas');
            crearGrafico('chartMotivos', motivos, '#22c55e', 'Salidas');
        });
    </script>
@endpush
