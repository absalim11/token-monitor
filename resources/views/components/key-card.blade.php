@props([
    'key' => [],
])

@php
$usagePercent = $key['max_budget'] > 0
    ? round(($key['spend'] / $key['max_budget']) * 100, 1)
    : 0;

$status = 'normal';
$statusColor = 'text-green-600 bg-green-50';

if ($key['blocked']) {
    $status = 'blocked';
    $statusColor = 'text-gray-600 bg-gray-100';
} elseif (isset($key['expires']) && $key['expires'] && strtotime($key['expires']) < time()) {
    $status = 'expired';
    $statusColor = 'text-red-600 bg-red-50';
} elseif ($usagePercent > 90) {
    $status = 'critical';
    $statusColor = 'text-red-600 bg-red-50';
} elseif ($usagePercent > 70) {
    $status = 'warning';
    $statusColor = 'text-yellow-600 bg-yellow-50';
}

$usageColor = $usagePercent > 90 ? 'bg-red-500' : ($usagePercent > 70 ? 'bg-yellow-500' : 'bg-green-500');

$maskedKey = isset($key['key'])
    ? substr($key['key'], 0, 8) . '...' . substr($key['key'], -4)
    : 'N/A';

$displayName = isset($key['aliases']) && !empty($key['aliases'])
    ? is_array($key['aliases']) ? $key['aliases'][0] : $key['aliases']
    : $maskedKey;

$expiresText = isset($key['expires']) && $key['expires']
    ? \Carbon\Carbon::parse($key['expires'])->diffForHumans()
    : 'Never';
@endphp

<div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 hover:shadow-md transition-shadow duration-200">
    <div class="flex justify-between items-start mb-4">
        <div>
            <h3 class="text-lg font-semibold text-gray-900">{{ $displayName }}</h3>
            <p class="text-sm text-gray-500">{{ $maskedKey }}</p>
        </div>
        <span class="px-3 py-1 rounded-full text-xs font-medium {{ $statusColor }}">
            {{ ucfirst($status) }}
        </span>
    </div>

    <div class="space-y-3">
        <div>
            <div class="flex justify-between text-sm mb-1">
                <span class="text-gray-600">Usage</span>
                <span class="font-medium">{{ $usagePercent }}%</span>
            </div>
            <div class="w-full bg-gray-200 rounded-full h-2">
                <div class="{{ $usageColor }} h-2 rounded-full transition-all duration-500" style="width: {{ min($usagePercent, 100) }}%"></div>
            </div>
        </div>

        <div class="grid grid-cols-2 gap-4 text-sm">
            <div>
                <span class="text-gray-500 block">Spend</span>
                <span class="font-medium">${{ number_format($key['spend'] ?? 0, 2) }}</span>
            </div>
            <div>
                <span class="text-gray-500 block">Budget</span>
                <span class="font-medium">${{ number_format($key['max_budget'] ?? 0, 2) }}</span>
            </div>
        </div>

        <div class="flex justify-between text-sm pt-2 border-t border-gray-100">
            <div>
                <span class="text-gray-500 block">Expires</span>
                <span class="font-medium">{{ $expiresText }}</span>
            </div>
            <div>
                <span class="text-gray-500 block">User</span>
                <span class="font-medium text-xs">{{ $key['user_id'] ?? 'N/A' }}</span>
            </div>
        </div>

        @if(isset($key['models']) && !empty($key['models']))
        <div class="pt-2 border-t border-gray-100">
            <span class="text-gray-500 text-xs block mb-1">Models</span>
            <div class="flex flex-wrap gap-1">
                @foreach(is_array($key['models']) ? array_slice($key['models'], 0, 3) : [$key['models']] as $model)
                    <span class="px-2 py-1 bg-gray-100 text-gray-600 text-xs rounded">
                        {{ str_replace(['gpt-', 'claude-'], '', $model) }}
                    </span>
                @endforeach
                @if(is_array($key['models']) && count($key['models']) > 3)
                    <span class="px-2 py-1 bg-gray-100 text-gray-600 text-xs rounded">
                        +{{ count($key['models']) - 3 }}
                    </span>
                @endif
            </div>
        </div>
        @endif
    </div>
</div>
