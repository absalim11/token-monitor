@extends('layouts.app')

@section('header')
    <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">Dashboard</h2>
@endsection

@section('content')
<div
    x-data="dashboard({
        healthUrl: @js(route('api.health')),
        keysUrl: @js(route('api.keys')),
        keyInfoUrl: @js(route('api.keys.info')),
        dailySpendUrl: @js(route('api.daily-spend')),
        blockKeyUrl: @js(route('api.keys.block')),
        unblockKeyUrl: @js(route('api.keys.unblock')),
        csrfToken: @js(csrf_token()),
        refreshInterval: 3000
    })"
    x-init="init()"
    class="min-h-screen bg-gray-50 dark:bg-gray-900 px-4 sm:px-6 lg:px-8 py-6"
>
    <!-- Header Stats -->
    <div class="mb-6">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Token Overview</h1>
                <p class="text-sm text-gray-500 dark:text-gray-500 mt-1">Real-time monitoring of your LLM token usage</p>
            </div>
            <div class="flex items-center gap-3">
                <!-- API Status -->
                <div class="flex items-center gap-2 px-3 py-2 bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700">
                    <span class="relative flex h-2.5 w-2.5">
                        <span x-show="apiStatus === 'connected'" class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-2.5 w-2.5"
                            :class="apiStatus === 'connected' ? 'bg-green-500' : (apiStatus === 'error' ? 'bg-red-500' : (apiStatus === 'db_error' ? 'bg-orange-500' : 'bg-yellow-500'))">
                        </span>
                    </span>
                    <span class="text-sm font-medium" :class="apiStatus === 'connected' ? 'text-green-700' : (apiStatus === 'error' ? 'text-red-700' : (apiStatus === 'db_error' ? 'text-orange-700' : 'text-yellow-700'))">
                        <span x-text="apiStatus === 'db_error' ? 'DB Error' : (apiStatus.charAt(0).toUpperCase() + apiStatus.slice(1))"></span>
                    </span>
                </div>

                <!-- Refresh Button -->
                <button @click="refreshAll()" :disabled="loading || autoRefreshStopped" class="flex items-center gap-2 px-4 py-2 bg-tosca hover:bg-tosca-dark text-white rounded-lg transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                    <svg class="w-4 h-4" :class="loading ? 'animate-spin' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                    </svg>
                    <span>Refresh</span>
                </button>
            </div>
        </div>

        <!-- Last Refresh & Countdown -->
        <div class="flex items-center gap-4 text-xs text-gray-500 dark:text-gray-400">
            <span>Last refresh: <span x-text="lastRefresh"></span></span>
            <span x-show="!autoRefreshStopped">Next refresh in: <span class="font-mono text-tosca" x-text="countdown"></span>s</span>
            <span x-show="autoRefreshStopped" class="text-orange-600 font-medium">Auto-refresh stopped</span>
        </div>
    </div>

    <!-- Error Alert -->
    <div x-show="error" x-transition class="mb-4 p-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg">
        <div class="flex items-center gap-2">
            <svg class="w-5 h-5 text-red-500 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            <span class="text-red-700 dark:text-red-300" x-text="error"></span>
        </div>
    </div>

    <!-- Database Error Warning -->
    <div x-show="dbError" x-transition class="mb-4 p-4 bg-orange-50 dark:bg-orange-900/20 border border-orange-200 dark:border-orange-800 rounded-lg">
        <div class="flex items-start gap-3">
            <svg class="w-5 h-5 text-orange-500 dark:text-orange-400 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
            </svg>
            <div>
                <p class="font-medium text-orange-800 dark:text-orange-300">LiteLLM Database Connection Failed</p>
                <p class="text-sm text-orange-700 dark:text-orange-400 mt-1">The LiteLLM API reported a database connection error. Auto-refresh has been stopped to prevent excessive API calls. Please check the LiteLLM server configuration and database status.</p>
            </div>
        </div>
    </div>

    <!-- Key Cards -->
    <div class="mb-8">
        <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200 mb-4">Key Overview</h3>
        <div x-show="loading && keys.length === 0 && !autoRefreshStopped" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <template x-for="i in 3">
                <div class="bg-gray-100 dark:bg-gray-700 rounded-lg h-48 animate-pulse"></div>
            </template>
        </div>
        <div x-show="!loading || keys.length > 0" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <template x-for="key in keys" :key="key.key">
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6 hover:shadow-md transition-shadow duration-200">
                    <div class="flex justify-between items-start mb-4">
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white" x-text="(Array.isArray(key.aliases) && key.aliases.length) ? key.aliases[0] : (typeof key.aliases === 'string' && key.aliases.trim() ? key.aliases : '-')"></h3>
                            <p class="text-sm text-gray-500 dark:text-gray-400" x-text="getMaskedKey(key.key)"></p>
                        </div>
                        <span class="px-3 py-1 rounded-full text-xs font-medium" :class="getStatusColor(key)" x-text="getStatusLabel(key)"></span>
                    </div>
                    <div class="space-y-3">
                        <div>
                            <div class="flex justify-between text-sm mb-1">
                                <span class="text-gray-600 dark:text-gray-400">Usage</span>
                                <span class="font-medium dark:text-gray-200" x-text="getUsagePercent(key) + '%'"></span>
                            </div>
                            <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                                <div class="h-2 rounded-full transition-all duration-500" :class="getUsageColor(key)" :style="'width: ' + getUsagePercent(key) + '%'"></div>
                            </div>
                        </div>
                        <div class="grid grid-cols-2 gap-4 text-sm">
                            <div>
                                <span class="text-gray-500 dark:text-gray-400 block">Spend</span>
                                <span class="font-medium dark:text-gray-200" x-text="'$' + (key.spend || 0).toFixed(2)"></span>
                            </div>
                            <div>
                                <span class="text-gray-500 dark:text-gray-400 block">Budget</span>
                                <span class="font-medium dark:text-gray-200" x-text="'$' + (key.max_budget || 0).toFixed(2)"></span>
                            </div>
                        </div>
                        <div class="flex justify-between text-sm pt-2 border-t border-gray-100 dark:border-gray-700">
                            <div>
                                <span class="text-gray-500 dark:text-gray-400 block">Expires</span>
                                <span class="font-medium dark:text-gray-200" x-text="getExpiresText(key)"></span>
                            </div>
                            <div>
                                <span class="text-gray-500 dark:text-gray-400 block">User</span>
                                <span class="font-medium text-xs dark:text-gray-400" x-text="key.user_id || 'N/A'"></span>
                            </div>
                        </div>
                        <div x-show="key.models && key.models.length" class="pt-2 border-t border-gray-100 dark:border-gray-700">
                            <span class="text-gray-500 dark:text-gray-400 text-xs block mb-1">Models</span>
                            <div class="flex flex-wrap gap-1">
                                <template x-for="model in key.models.slice(0, 3)">
                                    <span class="px-2 py-1 bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 text-xs rounded" x-text="model.replace('gpt-', '').replace('claude-', '')"></span>
                                </template>
                                <span x-show="key.models.length > 3" class="px-2 py-1 bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 text-xs rounded" x-text="'+' + (key.models.length - 3)"></span>
                            </div>
                        </div>
                        <div class="pt-2 border-t border-gray-100 dark:border-gray-700">
                            <button @click="openKeyDetail(key.key)" class="text-sm font-medium text-tosca hover:text-tosca-dark transition-colors">
                                View details
                            </button>
                        </div>
                    </div>
                </div>
            </template>
        </div>
        <div x-show="!loading && keys.length === 0 && !dbError" class="text-center py-12 text-gray-500 dark:text-gray-400 bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700">
            <svg class="w-16 h-16 mx-auto text-gray-300 dark:text-gray-600 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"></path>
            </svg>
            <p class="text-lg font-medium">No keys found</p>
            <p class="text-sm mt-1">Add your first LiteLLM virtual key to start monitoring</p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
        <!-- Stats Table -->
        <div class="lg:col-span-2">
            <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200 mb-4">Detailed Statistics</h3>
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left">
                        <thead class="bg-gray-50 dark:bg-gray-900 border-b border-gray-200 dark:border-gray-700">
                            <tr>
                                <th class="px-4 py-3 font-semibold text-gray-700 dark:text-gray-300">Token</th>
                                <th class="px-4 py-3 font-semibold text-gray-700 dark:text-gray-300">Models</th>
                                <th class="px-4 py-3 font-semibold text-gray-700 dark:text-gray-300 text-right">Spend</th>
                                <th class="px-4 py-3 font-semibold text-gray-700 dark:text-gray-300 text-right">Budget</th>
                                <th class="px-4 py-3 font-semibold text-gray-700 dark:text-gray-300 text-center">Usage</th>
                                <th class="px-4 py-3 font-semibold text-gray-700 dark:text-gray-300">User</th>
                                <th class="px-4 py-3 font-semibold text-gray-700 dark:text-gray-300">Status</th>
                                <th class="px-4 py-3 font-semibold text-gray-700 dark:text-gray-300">Expires</th>
                                <th class="px-4 py-3 font-semibold text-gray-700 dark:text-gray-300 text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                            <template x-for="key in keys" :key="key.key">
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                                    <td class="px-4 py-3">
                                        <div class="font-medium text-gray-900 dark:text-white" x-text="getDisplayName(key)"></div>
                                        <div class="text-xs text-gray-500 dark:text-gray-400" x-text="getMaskedKey(key.key)"></div>
                                    </td>
                                    <td class="px-4 py-3">
                                        <div x-show="key.models && key.models.length" class="flex flex-wrap gap-1">
                                            <template x-for="model in key.models.slice(0, 2)">
                                                <span class="px-2 py-0.5 bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 text-xs rounded" x-text="model.replace('gpt-', '').replace('claude-', '')"></span>
                                            </template>
                                        </div>
                                        <span x-show="!key.models || !key.models.length" class="text-gray-400">-</span>
                                    </td>
                                    <td class="px-4 py-3 text-right font-mono dark:text-gray-200" x-text="'$' + (key.spend || 0).toFixed(2)"></td>
                                    <td class="px-4 py-3 text-right font-mono dark:text-gray-200" x-text="'$' + (key.max_budget || 0).toFixed(2)"></td>
                                    <td class="px-4 py-3">
                                        <div class="flex items-center gap-2 justify-center">
                                            <div class="w-16 bg-gray-200 dark:bg-gray-700 rounded-full h-1.5">
                                                <div class="h-1.5 rounded-full" :class="getUsageColor(key)" :style="'width: ' + Math.min(getUsagePercent(key), 100) + '%'"></div>
                                            </div>
                                            <span class="text-xs font-medium w-8 text-right dark:text-gray-200" x-text="getUsagePercent(key) + '%'"></span>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 text-xs text-gray-600 dark:text-gray-400" x-text="(Array.isArray(key.aliases) && key.aliases.length) ? key.aliases[0] : (typeof key.aliases === 'string' && key.aliases.trim() ? key.aliases : '-')"></td>
                                    <td class="px-4 py-3">
                                        <span class="px-2 py-1 rounded-full text-xs font-medium" :class="getStatusColor(key)" x-text="getStatusLabel(key)"></span>
                                    </td>
                                    <td class="px-4 py-3 text-xs text-gray-600 dark:text-gray-400" x-text="getExpiresDate(key)"></td>
                                    <td class="px-4 py-3">
                                        <div class="flex items-center gap-1 justify-center">
                                            <button @click="refreshKeys()" class="p-1.5 text-gray-400 hover:text-tosca hover:bg-tosca/5 rounded transition-colors" title="Refresh">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                                                </svg>
                                            </button>

                                            <button @click="openKeyDetail(key.key)" class="p-1.5 text-gray-400 hover:text-blue-600 hover:bg-blue-50 rounded transition-colors" title="View Details">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5s8.268 2.943 9.542 7c-1.274 4.057-5.065 7-9.542 7S3.732 16.057 2.458 12z"></path>
                                                </svg>
                                            </button>

                                            <template x-if="!key.blocked">
                                                <button @click="blockKeyAction(key.key)" class="p-1.5 text-gray-400 hover:text-yellow-600 hover:bg-yellow-50 rounded transition-colors" title="Block">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"></path>
                                                    </svg>
                                                </button>
                                            </template>

                                            <template x-if="key.blocked">
                                                <button @click="unblockKeyAction(key.key)" class="p-1.5 text-gray-400 hover:text-green-600 hover:bg-green-50 rounded transition-colors" title="Unblock">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                                    </svg>
                                                </button>
                                            </template>

                                        </div>
                                    </td>
                                </tr>
                            </template>
                            <tr x-show="keys.length === 0 && !loading && !dbError">
                                <td colspan="9" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">
                                    <div class="flex flex-col items-center gap-2">
                                        <svg class="w-12 h-12 text-gray-300 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"></path>
                                        </svg>
                                        <span>No keys found</span>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Daily Cost Tracker -->
        <div>
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6" x-data="costTracker()" x-init="init()">
                <div class="flex justify-between items-center mb-6">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Daily Cost Tracker</h3>
                    <div class="flex gap-1 bg-gray-100 dark:bg-gray-700 rounded-lg p-1">
                        <template x-for="(label, key) in periods">
                            <button
                                @click="changePeriod(key)"
                                class="px-3 py-1.5 text-sm font-medium rounded-md transition-colors"
                                :class="period === key ? 'bg-white dark:bg-gray-600 text-tosca shadow-sm' : 'text-gray-600 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white'"
                                x-text="label"
                            ></button>
                        </template>
                    </div>
                </div>

                <!-- Total Spend -->
                <div class="mb-6 p-4 bg-gradient-to-r from-tosca/5 to-tosca-light/5 rounded-lg border border-tosca/10 dark:from-tosca/10 dark:to-tosca-light/10">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <p class="text-sm text-gray-600 dark:text-gray-400 mb-1">Total Spend Overall</p>
                            <p class="text-3xl font-bold text-tosca" x-text="formatCurrency(overallSpend, 4)"></p>
                        </div>
                        <div class="text-right">
                            <p class="text-sm text-gray-500 dark:text-gray-400 mb-1">Total Max Budget</p>
                            <p class="text-lg font-semibold text-gray-700 dark:text-gray-300" x-text="formatCurrency(totalBudget, 4)"></p>
                        </div>
                    </div>
                    <div class="mt-4 grid grid-cols-2 gap-4 border-t border-tosca/10 pt-4">
                        <div>
                            <p class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">Spend <span x-text="periods[period]"></span></p>
                            <p class="mt-1 text-lg font-semibold text-gray-900 dark:text-white" x-text="formatCurrency(periodSpend, 4)"></p>
                        </div>
                        <div class="text-right">
                            <p class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">Avg. Daily</p>
                            <p class="mt-1 text-lg font-semibold text-gray-900 dark:text-white" x-text="formatCurrency(avgDailySpend, 4)"></p>
                        </div>
                    </div>
                </div>

                <!-- Daily Bars -->
                <template x-if="spendData.length > 0">
                    <div class="space-y-3">
                        <template x-for="day in reversedSpend" :key="day.date">
                            <div class="group">
                                <div class="flex items-center gap-3 mb-1">
                                    <span class="w-20 text-xs text-gray-500 dark:text-gray-400" x-text="formatFullDate(day.date)"></span>
                                    <div class="flex-1 h-8 bg-gray-100 dark:bg-gray-700 rounded-lg overflow-hidden relative">
                                        <div
                                            class="h-full bg-gradient-to-r from-tosca to-tosca-light rounded-lg transition-all duration-500"
                                            :style="'width: ' + (day.spend / maxDailySpend * 100) + '%'"></div>
                                    </div>
                                    <span class="w-20 text-right text-sm font-medium text-gray-700 dark:text-gray-300" x-text="formatCurrency(day.spend, 4)"></span>
                                </div>
                            </div>
                        </template>
                    </div>
                </template>

                <template x-if="spendData.length === 0 && !dbError">
                    <div class="text-center py-8 text-gray-500 dark:text-gray-400">
                        <svg class="w-12 h-12 mx-auto text-gray-300 dark:text-gray-600 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                        </svg>
                        <span>No spend data available</span>
                    </div>
                </template>
            </div>
        </div>
    </div>

    <div
        x-show="showDetailModal"
        x-cloak
        x-transition
        @keydown.escape.window="closeKeyDetail()"
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 px-4"
    >
        <div class="absolute inset-0" @click="closeKeyDetail()"></div>
        <div class="relative w-full max-w-2xl rounded-xl bg-white dark:bg-gray-800 shadow-2xl border border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <div>
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Virtual Key Details</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400" x-text="selectedKeyDetail ? getDisplayName(selectedKeyDetail) : 'Loading detail...'"></p>
                </div>
                <button @click="closeKeyDetail()" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>

            <div class="p-6">
                <div x-show="detailLoading" class="space-y-3">
                    <div class="h-4 rounded bg-gray-100 dark:bg-gray-700 animate-pulse"></div>
                    <div class="h-4 rounded bg-gray-100 dark:bg-gray-700 animate-pulse"></div>
                    <div class="h-24 rounded bg-gray-100 dark:bg-gray-700 animate-pulse"></div>
                </div>

                <div x-show="!detailLoading && selectedKeyDetail" class="space-y-6">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                        <div>
                            <p class="text-gray-500 dark:text-gray-400">Key</p>
                            <p class="font-mono text-gray-900 dark:text-white break-all" x-text="selectedKeyDetail?.key || '-'"></p>
                        </div>
                        <div>
                            <p class="text-gray-500 dark:text-gray-400">User ID</p>
                            <p class="text-gray-900 dark:text-white" x-text="selectedKeyDetail?.user_id || '-'"></p>
                        </div>
                        <div>
                            <p class="text-gray-500 dark:text-gray-400">Spend</p>
                            <p class="text-gray-900 dark:text-white" x-text="'$' + ((selectedKeyDetail?.spend || 0).toFixed(2))"></p>
                        </div>
                        <div>
                            <p class="text-gray-500 dark:text-gray-400">Max Budget</p>
                            <p class="text-gray-900 dark:text-white" x-text="'$' + ((selectedKeyDetail?.max_budget || 0).toFixed(2))"></p>
                        </div>
                        <div>
                            <p class="text-gray-500 dark:text-gray-400">Status</p>
                            <span class="inline-flex px-2 py-1 rounded-full text-xs font-medium" :class="getStatusColor(selectedKeyDetail)" x-text="getStatusLabel(selectedKeyDetail)"></span>
                        </div>
                        <div>
                            <p class="text-gray-500 dark:text-gray-400">Expires</p>
                            <p class="text-gray-900 dark:text-white" x-text="getExpiresDate(selectedKeyDetail)"></p>
                        </div>
                    </div>

                    <div>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mb-2">Aliases</p>
                        <div class="flex flex-wrap gap-2" x-show="selectedKeyDetail && selectedKeyDetail.aliases && selectedKeyDetail.aliases.length">
                            <template x-for="alias in ((selectedKeyDetail && selectedKeyDetail.aliases) ? selectedKeyDetail.aliases : [])" :key="alias">
                                <span class="px-2 py-1 rounded bg-gray-100 dark:bg-gray-700 text-xs text-gray-700 dark:text-gray-200" x-text="alias"></span>
                            </template>
                        </div>
                        <p x-show="!selectedKeyDetail || !selectedKeyDetail.aliases || !selectedKeyDetail.aliases.length" class="text-sm text-gray-400">-</p>
                    </div>

                    <div>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mb-2">Models</p>
                        <div class="flex flex-wrap gap-2" x-show="selectedKeyDetail && selectedKeyDetail.models && selectedKeyDetail.models.length">
                            <template x-for="model in ((selectedKeyDetail && selectedKeyDetail.models) ? selectedKeyDetail.models : [])" :key="model">
                                <span class="px-2 py-1 rounded bg-gray-100 dark:bg-gray-700 text-xs text-gray-700 dark:text-gray-200" x-text="model"></span>
                            </template>
                        </div>
                        <p x-show="!selectedKeyDetail || !selectedKeyDetail.models || !selectedKeyDetail.models.length" class="text-sm text-gray-400">-</p>
                    </div>

                    <div>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mb-2">Config</p>
                        <pre class="overflow-x-auto rounded-lg bg-gray-50 dark:bg-gray-900 p-4 text-xs text-gray-700 dark:text-gray-200" x-text="formatJson(selectedKeyDetail?.config || {})"></pre>
                    </div>

                    <div>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mb-2">Metadata</p>
                        <pre class="overflow-x-auto rounded-lg bg-gray-50 dark:bg-gray-900 p-4 text-xs text-gray-700 dark:text-gray-200" x-text="formatJson(selectedKeyDetail?.metadata || {})"></pre>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
