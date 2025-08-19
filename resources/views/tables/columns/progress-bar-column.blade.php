@php
    $value = $getState();
    $hasValue = is_numeric($value);
@endphp
@if ($hasValue)
    <div class="flex items-center gap-2 w-full">
        <div class="w-full bg-gray-100 dark:bg-gray-800 rounded-full shadow-inner overflow-hidden" style="height: 8px;">
            @php
                $color = $value > 70 ? '#16a34a' : ($value >= 30 ? '#f59e0b' : '#dc2626');
            @endphp
            <div class="rounded-r-full shadow-inner transition-all"
                style="width: {{ $value }}%; background: {{ $color }}; height: 100%;">
            </div>
        </div>
        <span class="text-sm text-gray-700 dark:text-gray-200" style="min-width: 32px; text-align: right;">
            {{ $value }}%
        </span>
    </div>
@else
    <span class="text-gray-400 text-xs font-bold">N/A</span>
@endif