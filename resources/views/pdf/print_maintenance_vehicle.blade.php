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
                return $item->service_date ? \Carbon\Carbon::parse($item->service_date)->format('Y-m-d') : '0000-00-00';
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
                                <td style="text-align: center;"> <span
                                        style="background-color: #27ae60; color: #fff;  font-size: 9px; padding: 2px 10px; border-radius: 6px; margin-left: 5px;">Realizado</span>
                                </td>
                                <td style="text-align: center;">{{ $item->mileage_at_service }}</td>
                                <td style="text-align: center;">{{ $item->service_date }}</td>
                            </tr>
                        @endforeach
                    </body>
                </table>
                <br />
                <!-- Fin Mantenimientos realizados -->
                <div class="section">
                    <div class="section-title">
                        VALORIZADOS MANTENIMIENTO VEHICULAR
                    </div>
                </div>
                <!-- Valorizado Mantenimientos realizados -->
                <table class="table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Descripción</th>
                            <th>Mano obra</th>
                            <th>Costo Material</th>
                            <th>Otros</th>
                            <th>Total</th>
                        </tr>
                    </thead>

                    <tbody>
                        @foreach ($maintenances as $item)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td>{{ $item->maintenanceItem->name ?? 'General' }}</td>
                                <td style="text-align: center;">{{ $item->labor_cost }}</td>
                                <td style="text-align: center;">{{ $item->parts_cost }}</td>
                                <td style="text-align: center;">{{ $item->extra_cost ?? 'N/A' }}</td>
                                <td style="text-align: center;">{{ $item->total_cost }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="4"></td>
                            <td align="right">Total S/.</td>
                            <td style="text-align: center;" class="gray">
                                {{ number_format($maintenances->sum('total_cost'), 2, ',', '.') }}</td>
                        </tr>
                    </tfoot>
                </table>
                <bt />
                <!-- Fin Valorizado Mantenimientos realizados -->
                <div class="section">
                    <div class="section-title">
                        ESTADO DE PASTILLAS DE FRENO Y ACEITES
                    </div>
                </div>
                @foreach ($brakeGroups as $date => $items)
                    <table style="border-collapse: collapse; border: none; width: 100%;">
                        <thead>
                            <div class="section">
                                <div class="section-title">
                                    {{ $date === '0000-00-00' ? 'SIN FECHA' : \Carbon\Carbon::parse($date)->format('d/m/Y') }}
                                </div>
                            </div>

                            <tr>
                                <th>Del. Izq.</th>
                                <th>Del. Der.</th>
                                <th>Tras. Izq.</th>
                                <th>Tras. Der.</th>
                                <th>Estado Aceite</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($items as $item)
                                <tr>
                                    <td>
                                        @php
                                            $value = $item->front_left_brake_pad;
                                            $colorClass =
                                                $value >= 70
                                                    ? 'progress-success'
                                                    : ($value >= 30
                                                        ? 'progress-warning'
                                                        : 'progress-danger');
                                        @endphp
                                        @if (is_null($value) || $value === '')
                                            <div style="text-align: center;">N/A</div>
                                        @else
                                            <div class="progress-container">
                                                <div class="progress-fill {{ $colorClass }}"
                                                    style="width: {{ $value }}%">
                                                    <div class="progress-value">{{ $value }}%</div>
                                                </div>
                                            </div>
                                        @endif
                                    </td>
                                    <td>
                                        @php
                                            $value = $item->front_right_brake_pad;
                                            $colorClass =
                                                $value >= 70
                                                    ? 'progress-success'
                                                    : ($value >= 30
                                                        ? 'progress-warning'
                                                        : 'progress-danger');
                                        @endphp
                                        @if (is_null($value) || $value === '')
                                            <div style="text-align: center;">N/A</div>
                                        @else
                                            <div class="progress-container">
                                                <div class="progress-fill {{ $colorClass }}"
                                                    style="width: {{ $value }}%">
                                                    <div class="progress-value">{{ $value }}%</div>
                                                </div>
                                            </div>
                                        @endif
                                    </td>
                                    <td>
                                        @php
                                            $value = $item->rear_left_brake_pad;
                                            $colorClass =
                                                $value >= 70
                                                    ? 'progress-success'
                                                    : ($value >= 30
                                                        ? 'progress-warning'
                                                        : 'progress-danger');
                                        @endphp
                                        @if (is_null($value) || $value === '')
                                            <div style="text-align: center;">N/A</div>
                                        @else
                                            <div class="progress-container">
                                                <div class="progress-fill {{ $colorClass }}"
                                                    style="width: {{ $value }}%">
                                                    <div class="progress-value">{{ $value }}%</div>
                                                </div>
                                            </div>
                                        @endif
                                    </td>
                                    <td>
                                        @php
                                            $value = $item->rear_right_brake_pad;
                                            $colorClass =
                                                $value >= 70
                                                    ? 'progress-success'
                                                    : ($value >= 30
                                                        ? 'progress-warning'
                                                        : 'progress-danger');
                                        @endphp
                                        @if (is_null($value) || $value === '')
                                            <div style="text-align: center;">N/A</div>
                                        @else
                                            <div class="progress-container">
                                                <div class="progress-fill {{ $colorClass }}"
                                                    style="width: {{ $value }}%">
                                                    <div class="progress-value">{{ $value }}%</div>
                                                </div>
                                            </div>
                                        @endif
                                    </td>
                                    <td style="border: none;">
                                        @php
                                            $value = $item->progres_bar;
                                            $colorClass =
                                                $value >= 70
                                                    ? 'progress-success'
                                                    : ($value >= 30
                                                        ? 'progress-warning'
                                                        : 'progress-danger');
                                        @endphp
                                        @if (is_null($value) || $value === '')
                                            <div style="text-align: center;">N/A</div>
                                        @else
                                            <div class="progress-container">
                                                <div class="progress-fill {{ $colorClass }}"
                                                    style="width: {{ $value }}%">
                                                    <div class="progress-value">{{ $value }}%</div>
                                                </div>
                                            </div>
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <td colspan="5" style="border: none;">{{ $item->notes_valorization }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endforeach
            </main>
        </div>
    @endforeach
</x-app>
