<x-app>
    @foreach ($vehicles as $vehicleId => $maintenances)
        @php
            $vehicle = $maintenances->first()->vehicle;

            // Agrupar mantenimientos por categoría
            $grouped = $maintenances->groupBy(function ($item) {
                return optional($item->maintenanceItem)->category ?? 'General';
            });

            // Agrupar mantenimientos por fecha para pastillas
            $brakeGroups = $maintenances->groupBy(function ($item) {
                return $item->brake_pads_checked_at ? $item->brake_pads_checked_at->format('Y-m-d') : '0000-00-00';
            });
        @endphp
        <div class="page">
            <div class="header">
                <div class="header-top">
                    <div class="logo">
                        <div class="logo-icon">
                            <img src="data:image/png;base64,{{ base64_encode(file_get_contents(public_path('img/logo.png'))) }}"
                                alt="Logo" style="width: 140px;">
                        </div>
                        {{-- <div class="logo-text">AUTO CARE PRO</div> --}}
                    </div>
                    <div class="report-title">
                        <h1>REPORTE DE MANTENIMIENTO</h1>
                        <div class="report-number">
                            <strong style="font-widget: bold;"> Placa: </strong>
                            {{ $vehicle->placa }} <br>
                            <strong style="font-widget: bold;"> Mes: </strong>
                            {{ ucfirst(\Carbon\Carbon::parse($maintenances->first()->service_date)->translatedFormat('F')) ?? 'N/A' }}
                        </div>
                    </div>
                </div>
            </div>
            <main>
                <div class="section">
                    <div class="section-title">
                        MANTENIMIENTOS REALIZADOS
                    </div>
                </div>
                 <!-- Mantenimientos realizados -->
            <table class="table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Descripción</th>
                        <th>Estado</th>
                        <th>KM</th>
                        <th>Fecha</th>
                    </tr>
                </thead>

                <body>
                    @foreach ($maintenances as $item)
                    <tr>
                        <td>{{ $loop->iteration }}</td>
                        <td>{{ $item->maintenanceItem->name ?? 'General' }}</td>
                        <td style="text-align: center;"> Realizado</td>
                        <td style="text-align: center;">{{ $item->mileage_at_service }}</td>
                        <td style="text-align: center;">{{ $item->service_date}}</td>
                    </tr>
                    @endforeach
                </body>
            </table>
            <!-- Fin Mantenimientos realizados -->
             </main>
        </div>
    @endforeach
</x-app>
