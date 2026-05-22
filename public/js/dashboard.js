(function () {
    function registerDashboardComponents() {
        if (!window.Alpine) {
            return;
        }

        window.Alpine.data('appShell', function () {
            return {
                darkMode: false,

                init() {
                    this.darkMode = this.resolveInitialTheme();
                    this.applyTheme(this.darkMode);

                    this.$watch('darkMode', (value) => {
                        this.applyTheme(value);
                    });
                },

                resolveInitialTheme() {
                    const stored = localStorage.getItem('theme');

                    if (stored === 'dark') {
                        return true;
                    }

                    if (stored === 'light') {
                        return false;
                    }

                    return window.matchMedia('(prefers-color-scheme: dark)').matches;
                },

                applyTheme(value) {
                    const isDark = !!value;

                    localStorage.setItem('theme', isDark ? 'dark' : 'light');
                    document.documentElement.classList.toggle('dark', isDark);
                },

                toggleTheme() {
                    this.darkMode = !this.darkMode;
                },
            };
        });

        window.Alpine.data('dashboard', function (config) {
            return {
                keys: [],
                dailySpend: { spend: [] },
                period: '7d',
                loading: false,
                error: null,
                dbError: false,
                autoRefreshStopped: false,
                apiStatus: 'checking',
                lastRefresh: '-',
                countdown: Math.floor((config.refreshInterval || 3000) / 1000),
                timer: null,
                countdownTimer: null,
                showDetailModal: false,
                detailLoading: false,
                selectedKeyDetail: null,

                async init() {
                    await this.checkApiHealth();
                    await this.refreshAll();
                    this.startAutoRefresh();
                    this.startCountdown();

                    window.addEventListener('change-period', (event) => {
                        this.period = event.detail.period;
                        window.dispatchEvent(new CustomEvent('period-update', { detail: this.period }));
                        this.refreshDailySpend();
                    });
                },

                getDisplayName(key) {
                    if (!key) {
                        return 'N/A';
                    }

                    if (key.aliases && key.aliases.length) {
                        return Array.isArray(key.aliases) ? key.aliases[0] : key.aliases;
                    }

                    return this.getMaskedKey(key.key);
                },

                getMaskedKey(key) {
                    if (!key) return 'N/A';
                    return key.substring(0, 8) + '...' + key.substring(key.length - 4);
                },

                getUsagePercent(key) {
                    if (!key) return 0;
                    if (!key.max_budget || key.max_budget <= 0) return 0;
                    return Math.round(((key.spend || 0) / key.max_budget) * 100);
                },

                getStatus(key) {
                    if (!key) return 'normal';
                    if (key.blocked) return 'blocked';
                    if (key.expires && new Date(key.expires) < new Date()) return 'expired';

                    const usage = this.getUsagePercent(key);
                    if (usage > 90) return 'critical';
                    if (usage > 70) return 'warning';

                    return 'normal';
                },

                getStatusLabel(key) {
                    const status = this.getStatus(key);
                    return status.charAt(0).toUpperCase() + status.slice(1);
                },

                getStatusColor(key) {
                    const status = this.getStatus(key);
                    const colors = {
                        blocked: 'text-gray-600 dark:text-gray-400 bg-gray-100 dark:bg-gray-700',
                        expired: 'text-red-600 bg-red-50',
                        critical: 'text-red-600 bg-red-50',
                        warning: 'text-yellow-600 bg-yellow-50',
                        normal: 'text-green-600 bg-green-50',
                    };

                    return colors[status];
                },

                getUsageColor(key) {
                    const usage = this.getUsagePercent(key);
                    return usage > 90 ? 'bg-red-500' : (usage > 70 ? 'bg-yellow-500' : 'bg-green-500');
                },

                getExpiresText(key) {
                    if (!key.expires) return 'Never';

                    const diff = new Date(key.expires) - new Date();
                    if (diff < 0) return 'Expired';

                    const days = Math.floor(diff / (1000 * 60 * 60 * 24));
                    if (days > 30) return Math.floor(days / 30) + ' months';
                    if (days > 0) return days + ' days';

                    const hours = Math.floor(diff / (1000 * 60 * 60));
                    return hours + ' hours';
                },

                getExpiresDate(key) {
                    if (!key) return '-';
                    if (!key.expires) return 'Never';

                    return new Date(key.expires).toLocaleDateString('en-US', {
                        month: 'short',
                        day: 'numeric',
                        year: 'numeric',
                    });
                },

                formatJson(value) {
                    if (!value || (Array.isArray(value) && value.length === 0)) {
                        return '-';
                    }

                    try {
                        return JSON.stringify(value, null, 2);
                    } catch (error) {
                        return '-';
                    }
                },

                stopAutoRefresh() {
                    if (this.timer) {
                        clearInterval(this.timer);
                        this.timer = null;
                    }

                    if (this.countdownTimer) {
                        clearInterval(this.countdownTimer);
                        this.countdownTimer = null;
                    }

                    this.autoRefreshStopped = true;
                    this.countdown = '-';
                },

                async requestJson(url, options) {
                    const response = await fetch(url, options || {});
                    const data = await response.json();

                    if (data.stop_reload) {
                        this.stopAutoRefresh();
                        this.dbError = true;
                        this.apiStatus = 'db_error';
                        this.error = data.error || 'Database connection failed. Auto-refresh stopped.';
                        return data;
                    }

                    if (!response.ok) {
                        throw new Error(data.error || ('Request failed with status ' + response.status));
                    }

                    return data;
                },

                async checkApiHealth() {
                    try {
                        const data = await this.requestJson(config.healthUrl);
                        if (!data.stop_reload) {
                            this.apiStatus = data.status;
                        }
                    } catch (error) {
                        this.apiStatus = 'error';
                    }
                },

                async refreshAll() {
                    if (this.autoRefreshStopped) return;

                    this.loading = true;
                    this.error = null;

                    try {
                        await Promise.all([
                            this.refreshKeys(),
                            this.refreshDailySpend(),
                        ]);
                        this.lastRefresh = new Date().toLocaleTimeString();
                        await this.checkApiHealth();
                    } catch (error) {
                        this.error = 'Failed to refresh data: ' + error.message;
                    } finally {
                        this.loading = false;
                    }
                },

                async refreshKeys() {
                    if (this.autoRefreshStopped) return;

                    try {
                        const data = await this.requestJson(config.keysUrl);
                        if (!data.stop_reload) {
                            this.keys = data.keys || [];
                        }
                    } catch (error) {
                        this.error = 'Failed to load keys: ' + error.message;
                        console.error('Failed to load keys:', error);
                    }
                },

                async openKeyDetail(key) {
                    this.showDetailModal = true;
                    this.detailLoading = true;
                    this.selectedKeyDetail = null;

                    try {
                        const params = new URLSearchParams({ key: key });
                        const response = await this.requestJson(config.keyInfoUrl + '?' + params.toString());
                        this.selectedKeyDetail = response.data || null;
                    } catch (error) {
                        this.error = 'Failed to load key detail: ' + error.message;
                        this.showDetailModal = false;
                    } finally {
                        this.detailLoading = false;
                    }
                },

                closeKeyDetail() {
                    this.showDetailModal = false;
                    this.detailLoading = false;
                    this.selectedKeyDetail = null;
                },

                async refreshDailySpend() {
                    if (this.autoRefreshStopped) return;

                    try {
                        const endDate = new Date().toISOString().split('T')[0];
                        const startDate = new Date();

                        if (this.period === '7d') startDate.setDate(startDate.getDate() - 7);
                        else if (this.period === '30d') startDate.setDate(startDate.getDate() - 30);
                        else startDate.setDate(startDate.getDate() - 90);

                        const params = new URLSearchParams({
                            start: startDate.toISOString().split('T')[0],
                            end: endDate,
                        });

                        const data = await this.requestJson(config.dailySpendUrl + '?' + params.toString());
                        if (!data.stop_reload) {
                            this.dailySpend = data;
                            window.dispatchEvent(new CustomEvent('daily-spend-update', { detail: data }));
                        }
                    } catch (error) {
                        this.error = 'Failed to load daily spend: ' + error.message;
                        console.error('Failed to load daily spend:', error);
                    }
                },

                async blockKeyAction(key) {
                    if (confirm('Block this key?')) {
                        await this.blockKey(key);
                    }
                },

                async unblockKeyAction(key) {
                    if (confirm('Unblock this key?')) {
                        await this.unblockKey(key);
                    }
                },

                async deleteKeyAction(key, name) {
                    if (confirm('Delete key "' + name + '"? This action cannot be undone.')) {
                        await this.deleteKey(key);
                    }
                },

                async postKeyAction(url, key, failureMessage) {
                    try {
                        await this.requestJson(url, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': config.csrfToken,
                            },
                            body: JSON.stringify({ key: key }),
                        });

                        await this.refreshKeys();
                    } catch (error) {
                        this.error = failureMessage + ': ' + error.message;
                        alert(failureMessage + ': ' + error.message);
                    }
                },

                async blockKey(key) {
                    await this.postKeyAction(config.blockKeyUrl, key, 'Failed to block key');
                },

                async unblockKey(key) {
                    await this.postKeyAction(config.unblockKeyUrl, key, 'Failed to unblock key');
                },

                async deleteKey(key) {
                    await this.postKeyAction(config.deleteKeyUrl, key, 'Failed to delete key');
                },

                startAutoRefresh() {
                    const refreshInterval = config.refreshInterval || 3000;
                    const resetValue = Math.floor(refreshInterval / 1000);

                    this.timer = setInterval(() => {
                        if (!this.autoRefreshStopped) {
                            this.refreshAll();
                            this.countdown = resetValue;
                        }
                    }, refreshInterval);
                },

                startCountdown() {
                    const resetValue = Math.floor((config.refreshInterval || 3000) / 1000);

                    this.countdownTimer = setInterval(() => {
                        if (!this.autoRefreshStopped && this.countdown > 0) {
                            this.countdown--;
                        } else if (!this.autoRefreshStopped && this.countdown <= 0) {
                            this.countdown = resetValue;
                        }
                    }, 1000);
                },
            };
        });

        window.Alpine.data('costTracker', function () {
            return {
                periods: { '7d': '7 Days', '30d': '30 Days', '90d': '90 Days' },
                period: '7d',
                dailySpend: { spend: [] },

                init() {
                    window.addEventListener('daily-spend-update', (event) => {
                        this.dailySpend = event.detail;
                    });

                    window.addEventListener('period-update', (event) => {
                        this.period = event.detail;
                    });
                },

                get spendData() {
                    return this.dailySpend.spend || [];
                },

                get totalSpend() {
                    return this.spendData.reduce((sum, day) => sum + (day.spend || 0), 0);
                },

                get avgDailySpend() {
                    return this.totalSpend / Math.max(this.spendData.length, 1);
                },

                get maxDailySpend() {
                    const values = this.spendData.map((day) => day.spend || 0);
                    const max = values.length ? Math.max.apply(null, values) : 0;
                    return max || 1;
                },

                get reversedSpend() {
                    return this.spendData.slice().reverse();
                },

                changePeriod(newPeriod) {
                    this.period = newPeriod;
                    window.dispatchEvent(new CustomEvent('change-period', { detail: { period: newPeriod } }));
                },

                formatDate(dateStr) {
                    return new Date(dateStr).toLocaleDateString('en-US', {
                        month: 'short',
                        day: 'numeric',
                    });
                },
            };
        });
    }

    document.addEventListener('alpine:init', registerDashboardComponents);
})();
