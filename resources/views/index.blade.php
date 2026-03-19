<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Log Platform - Laravel Log Monitoring</title>

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Alpine.js for reactivity -->
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <style>
        [x-cloak] { display: none !important; }
        .loading { animation: spin 1s linear infinite; }
        @keyframes spin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }
    </style>
</head>
<body class="bg-gray-100 min-h-screen" x-data="logPlatform()" x-init="init()">
    <div class="container mx-auto px-4 py-8">
        <!-- Header -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <h1 class="text-3xl font-bold text-gray-800 mb-2">Laravel Log Platform</h1>
            <p class="text-gray-600">Monitor and analyze your Laravel application logs</p>
        </div>

        <!-- Filters -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Search</label>
                    <input
                        type="text"
                        x-model="filters.keyword"
                        @input="debouncedSearch()"
                        placeholder="Search logs..."
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                    >
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Level</label>
                    <select
                        x-model="filters.level"
                        @change="loadLogs()"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                    >
                        <option value="">All Levels</option>
                        <option value="emergency">Emergency</option>
                        <option value="alert">Alert</option>
                        <option value="critical">Critical</option>
                        <option value="error">Error</option>
                        <option value="warning">Warning</option>
                        <option value="notice">Notice</option>
                        <option value="info">Info</option>
                        <option value="debug">Debug</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">From Date</label>
                    <input
                        type="date"
                        x-model="filters.from"
                        @change="loadLogs()"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                    >
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">To Date</label>
                    <input
                        type="date"
                        x-model="filters.to"
                        @change="loadLogs()"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                    >
                </div>
            </div>
            <div class="mt-4 flex justify-between items-center">
                <button
                    @click="loadLogs()"
                    :disabled="loading"
                    class="bg-blue-500 hover:bg-blue-600 disabled:bg-blue-300 text-white px-4 py-2 rounded-md flex items-center"
                >
                    <svg x-show="loading" class="loading w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                    </svg>
                    <span x-show="!loading">Refresh</span>
                    <span x-show="loading">Loading...</span>
                </button>
                <div class="text-sm text-gray-600">
                    Showing <span x-text="logs.length"></span> logs
                </div>
            </div>
        </div>

        <!-- Logs List -->
        <div class="bg-white rounded-lg shadow-md">
            <div x-show="loading" class="p-8 text-center">
                <div class="inline-block loading w-8 h-8 border-4 border-blue-500 border-t-transparent rounded-full"></div>
                <p class="mt-2 text-gray-600">Loading logs...</p>
            </div>

            <div x-show="!loading && logs.length === 0" class="p-8 text-center text-gray-500">
                No logs found matching your criteria.
            </div>

            <div x-show="!loading && logs.length > 0" class="divide-y divide-gray-200">
                <template x-for="log in logs" :key="log.id || Math.random()">
                    <div class="p-4 hover:bg-gray-50">
                        <div class="flex items-start justify-between">
                            <div class="flex-1">
                                <div class="flex items-center space-x-2 mb-1">
                                    <span
                                        :class="getLevelClass(log.level)"
                                        class="px-2 py-1 text-xs font-medium rounded-full"
                                        x-text="log.level.toUpperCase()"
                                    ></span>
                                    <span class="text-sm text-gray-500" x-text="formatDate(log.logged_at)"></span>
                                    <span class="text-sm text-gray-500" x-text="'[' + log.env + ']'"></span>
                                </div>
                                <div class="text-gray-900 mb-2" x-text="log.message"></div>
                                <div x-show="log.context && Object.keys(log.context).length > 0" class="text-sm text-gray-600">
                                    <strong>Context:</strong>
                                    <pre class="mt-1 text-xs bg-gray-100 p-2 rounded overflow-x-auto" x-text="JSON.stringify(log.context, null, 2)"></pre>
                                </div>
                            </div>
                        </div>
                    </div>
                </template>
            </div>

            <!-- Load More -->
            <div x-show="!loading && logs.length > 0 && hasMore" class="p-4 border-t border-gray-200 text-center">
                <button
                    @click="loadMore()"
                    :disabled="loading"
                    class="bg-gray-500 hover:bg-gray-600 disabled:bg-gray-300 text-white px-4 py-2 rounded-md"
                >
                    Load More
                </button>
            </div>
        </div>
    </div>

    <script>
        function logPlatform() {
            return {
                logs: [],
                loading: false,
                hasMore: false,
                cursor: null,
                filters: {
                    keyword: '',
                    level: '',
                    from: '',
                    to: ''
                },
                searchTimeout: null,

                init() {
                    this.loadLogs();
                },

                async loadLogs() {
                    this.loading = true;
                    try {
                        const params = new URLSearchParams();
                        if (this.filters.keyword) params.append('keyword', this.filters.keyword);
                        if (this.filters.level) params.append('level', this.filters.level);
                        if (this.filters.from) params.append('from', this.filters.from);
                        if (this.filters.to) params.append('to', this.filters.to);

                        const response = await fetch(`/log-platform/api/logs?${params}`);
                        const data = await response.json();

                        this.logs = data.data || [];
                        this.hasMore = data.hasMore || false;
                        this.cursor = data.cursor || null;
                    } catch (error) {
                        console.error('Error loading logs:', error);
                        this.logs = [];
                    } finally {
                        this.loading = false;
                    }
                },

                async loadMore() {
                    if (!this.hasMore || this.loading) return;

                    this.loading = true;
                    try {
                        const params = new URLSearchParams();
                        if (this.filters.keyword) params.append('keyword', this.filters.keyword);
                        if (this.filters.level) params.append('level', this.filters.level);
                        if (this.filters.from) params.append('from', this.filters.from);
                        if (this.filters.to) params.append('to', this.filters.to);
                        if (this.cursor) params.append('cursor', this.cursor);

                        const response = await fetch(`/log-platform/api/logs?${params}`);
                        const data = await response.json();

                        this.logs = [...this.logs, ...(data.data || [])];
                        this.hasMore = data.hasMore || false;
                        this.cursor = data.cursor || null;
                    } catch (error) {
                        console.error('Error loading more logs:', error);
                    } finally {
                        this.loading = false;
                    }
                },

                debouncedSearch() {
                    clearTimeout(this.searchTimeout);
                    this.searchTimeout = setTimeout(() => {
                        this.loadLogs();
                    }, 300);
                },

                formatDate(dateString) {
                    const date = new Date(dateString);
                    return date.toLocaleString();
                },

                getLevelClass(level) {
                    const classes = {
                        emergency: 'bg-red-100 text-red-800',
                        alert: 'bg-red-100 text-red-800',
                        critical: 'bg-red-100 text-red-800',
                        error: 'bg-red-100 text-red-800',
                        warning: 'bg-orange-100 text-orange-800',
                        notice: 'bg-blue-100 text-blue-800',
                        info: 'bg-green-100 text-green-800',
                        debug: 'bg-gray-100 text-gray-800'
                    };
                    return classes[level] || 'bg-gray-100 text-gray-800';
                }
            }
        }
    </script>
</body>
</html>
