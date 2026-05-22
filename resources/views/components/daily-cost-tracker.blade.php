<div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6" x-data="{ periods: { '7d': '7 Days', '30d': '30 Days', '90d': '90 Days' } }">
    <div class="flex justify-between items-center mb-6">
        <h3 class="text-lg font-semibold text-gray-900">Daily Cost Tracker</h3>
        <div class="flex gap-1 bg-gray-100 rounded-lg p-1">
            <template x-for="(label, key) in periods">
                <button
                    @click="$dispatch('change-period', { period: key })"
                    class="px-3 py-1.5 text-sm font-medium rounded-md transition-colors"
                    :class="period === key ? 'bg-white text-tosca shadow-sm' : 'text-gray-600 hover:text-gray-900'"
                    x-text="label"
                ></button>
            </template>
        </div>
    </div>

    <!-- Total Spend -->
    <div class="mb-6 p-4 bg-gradient-to-r from-tosca/5 to-tosca-light/5 rounded-lg border border-tosca/10">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-600 mb-1">Total Spend (<span x-text="periods[period]"></span>)</p>
                <p class="text-3xl font-bold text-tosca" x-text="'$' + totalSpend.toFixed(2)"></p>
            </div>
            <div class="text-right">
                <p class="text-sm text-gray-500 mb-1">Avg. Daily</p>
                <p class="text-lg font-semibold text-gray-700" x-text="'$' + (totalSpend / Math.max(spendData.length, 1)).toFixed(2)"></p>
            </div>
        </div>
    </div>

    <!-- Daily Bars -->
    <template x-if="spendData.length > 0">
        <div class="space-y-3">
            <template x-for="day in reversedSpend" :key="day.date">
                <div class="group">
                    <div class="flex items-center gap-3 mb-1">
                        <span class="w-12 text-xs text-gray-500" x-text="formatDate(day.date)"></span>
                        <div class="flex-1 h-8 bg-gray-100 rounded-lg overflow-hidden relative">
                            <div
                                class="h-full bg-gradient-to-r from-tosca to-tosca-light rounded-lg transition-all duration-500"
                                :style="'width: ' + (day.spend / maxDailySpend * 100) + '%'"></div>
                        </div>
                        <span class="w-16 text-right text-sm font-medium text-gray-700" x-text="'$' + day.spend.toFixed(2)"></span>
                    </div>
                </div>
            </template>
        </div>
    </template>

    <template x-if="spendData.length === 0">
        <div class="text-center py-8 text-gray-500">
            <svg class="w-12 h-12 mx-auto text-gray-300 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
            </svg>
            <span>No spend data available</span>
        </div>
    </template>
</div>

@push('scripts')
<script>
// Alpine.js component logic for daily-cost-tracker
window.DailyCostTracker = function(data) {
    return {
        dailySpend: data.dailySpend || { spend: [] },
        period: data.period || '7d',

        get spendData() {
            return this.dailySpend.spend || [];
        },

        get totalSpend() {
            return this.spendData.reduce((sum, day) => sum + (day.spend || 0), 0);
        },

        get maxDailySpend() {
            const max = Math.max(...this.spendData.map(d => d.spend || 0));
            return max || 1;
        },

        get reversedSpend() {
            return [...this.spendData].reverse();
        },

        formatDate(dateStr) {
            return new Date(dateStr).toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
        }
    };
};
</script>
@endpush
