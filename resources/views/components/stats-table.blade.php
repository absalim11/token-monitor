@props([
    'keys' => [],
])

<div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm text-left">
            <thead class="bg-gray-50 border-b border-gray-200">
                <tr>
                    <th class="px-4 py-3 font-semibold text-gray-700">Token</th>
                    <th class="px-4 py-3 font-semibold text-gray-700">Models</th>
                    <th class="px-4 py-3 font-semibold text-gray-700 text-right">Spend</th>
                    <th class="px-4 py-3 font-semibold text-gray-700 text-right">Budget</th>
                    <th class="px-4 py-3 font-semibold text-gray-700 text-center">Usage</th>
                    <th class="px-4 py-3 font-semibold text-gray-700">User</th>
                    <th class="px-4 py-3 font-semibold text-gray-700">Status</th>
                    <th class="px-4 py-3 font-semibold text-gray-700">Expires</th>
                    <th class="px-4 py-3 font-semibold text-gray-700 text-center">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($keys as $key)
                    @php
                        $usagePercent = ($key['max_budget'] ?? 0) > 0
                            ? round(($key['spend'] ?? 0) / ($key['max_budget'] ?? 1) * 100, 1)
                            : 0;

                        $status = 'normal';
                        $statusColor = 'bg-green-100 text-green-800';

                        if ($key['blocked'] ?? false) {
                            $status = 'blocked';
                            $statusColor = 'bg-gray-100 text-gray-800';
                        } elseif (isset($key['expires']) && $key['expires'] && strtotime($key['expires']) < time()) {
                            $status = 'expired';
                            $statusColor = 'bg-red-100 text-red-800';
                        } elseif ($usagePercent > 90) {
                            $status = 'critical';
                            $statusColor = 'bg-red-100 text-red-800';
                        } elseif ($usagePercent > 70) {
                            $status = 'warning';
                            $statusColor = 'bg-yellow-100 text-yellow-800';
                        }

                        $maskedKey = isset($key['key'])
                            ? substr($key['key'], 0, 8) . '...' . substr($key['key'], -4)
                            : 'N/A';

                        $displayName = isset($key['aliases']) && !empty($key['aliases'])
                            ? is_array($key['aliases']) ? $key['aliases'][0] : $key['aliases']
                            : $maskedKey;

                        $expiresText = isset($key['expires']) && $key['expires']
                            ? \Carbon\Carbon::parse($key['expires'])->format('M d, Y')
                            : 'Never';
                    @endphp

                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="px-4 py-3">
                            <div class="font-medium text-gray-900">{{ $displayName }}</div>
                            <div class="text-xs text-gray-500">{{ $maskedKey }}</div>
                        </td>
                        <td class="px-4 py-3">
                            @if(isset($key['models']) && !empty($key['models']))
                                <div class="flex flex-wrap gap-1">
                                    @foreach(is_array($key['models']) ? array_slice($key['models'], 0, 2) : [$key['models']] as $model)
                                        <span class="px-2 py-0.5 bg-gray-100 text-gray-600 text-xs rounded">
                                            {{ str_replace(['gpt-', 'claude-'], '', $model) }}
                                        </span>
                                    @endforeach
                                </div>
                            @else
                                <span class="text-gray-400">-</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-right font-mono">
                            ${{ number_format($key['spend'] ?? 0, 2) }}
                        </td>
                        <td class="px-4 py-3 text-right font-mono">
                            ${{ number_format($key['max_budget'] ?? 0, 2) }}
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-2">
                                <div class="w-16 bg-gray-200 rounded-full h-1.5">
                                    <div class="h-1.5 rounded-full {{
                                        $usagePercent > 90 ? 'bg-red-500' :
                                        ($usagePercent > 70 ? 'bg-yellow-500' : 'bg-green-500')
                                    }}" style="width: {{ min($usagePercent, 100) }}%"></div>
                                </div>
                                <span class="text-xs font-medium w-8 text-right">{{ $usagePercent }}%</span>
                            </div>
                        </td>
                        <td class="px-4 py-3 text-xs text-gray-600">
                            {{ $key['user_id'] ?? 'N/A' }}
                        </td>
                        <td class="px-4 py-3">
                            <span class="px-2 py-1 rounded-full text-xs font-medium {{ $statusColor }}">
                                {{ ucfirst($status) }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-xs text-gray-600">
                            {{ $expiresText }}
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-1 justify-center">
                                <button @click="$dispatch('refresh-keys')" class="p-1.5 text-gray-400 hover:text-tosca hover:bg-tosca/5 rounded transition-colors" title="Refresh">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                                    </svg>
                                </button>

                                @if(!($key['blocked'] ?? false))
                                    <button @click="$dispatch('block-key', { key: '{{ $key['key'] ?? '' }}' })" class="p-1.5 text-gray-400 hover:text-yellow-600 hover:bg-yellow-50 rounded transition-colors" title="Block">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"></path>
                                        </svg>
                                    </button>
                                @else
                                    <button @click="$dispatch('unblock-key', { key: '{{ $key['key'] ?? '' }}' })" class="p-1.5 text-gray-400 hover:text-green-600 hover:bg-green-50 rounded transition-colors" title="Unblock">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                        </svg>
                                    </button>
                                @endif

                                <button @click="$dispatch('delete-key', { key: '{{ $key['key'] ?? '' }}', name: '{{ $displayName }}' })" class="p-1.5 text-gray-400 hover:text-red-600 hover:bg-red-50 rounded transition-colors" title="Delete">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                    </svg>
                                </button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="px-4 py-8 text-center text-gray-500">
                            <div class="flex flex-col items-center gap-2">
                                <svg class="w-12 h-12 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"></path>
                                </svg>
                                <span>No keys found</span>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
