<!DOCTYPE html>
<html lang="en" x-data="logPlatform()" x-init="init()" :class="darkMode ? 'dark' : ''">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Log Platform - Laravel Log Monitoring</title>

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>
        [x-cloak] { display: none !important; }
        .loading { animation: spin 1s linear infinite; }
        @keyframes spin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }
    </style>
</head>
<body class="bg-gradient-to-br from-blue-100 to-purple-100 dark:from-gray-900 dark:to-gray-800 min-h-screen transition-colors duration-300">
    <div class="flex flex-col md:flex-row h-screen">
        <!-- Sidebar -->
        <aside class="w-full md:w-64 bg-white dark:bg-gray-900 shadow-md p-4 flex flex-col">
            <h2 class="text-xl font-bold text-gray-800 dark:text-gray-100 mb-4">Log Viewer</h2>
            <div class="mb-4">
                <template x-for="file in files" :key="file.name">
                    <div class="flex items-center justify-between mb-2 hover:bg-gray-100 dark:hover:bg-gray-800 rounded px-2 py-1 cursor-pointer" @click="selectFile(file.name)">
                        <span class="text-sm font-medium text-gray-700 dark:text-gray-200" x-text="file.name"></span>
                        <span class="text-xs text-gray-500 dark:text-gray-400" x-text="file.size"></span>
                        <button class="ml-2 text-gray-400 hover:text-red-500" @click.stop="deleteFile(file.name)">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>
                </template>
            </div>
            <div class="mt-auto flex items-center justify-between">
                <button @click="toggleDarkMode" class="bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-200 px-3 py-1 rounded transition-colors">
                    <span x-show="!darkMode">🌙 Dark Mode</span>
                    <span x-show="darkMode">☀️ Light Mode</span>
                </button>
                <button @click="refreshFiles" class="bg-blue-500 hover:bg-blue-600 text-white px-3 py-1 rounded transition-colors">Refresh</button>
            </div>
        </aside>
        <!-- Main Content -->
        <main class="flex-1 flex flex-col bg-white dark:bg-gray-900 p-6 overflow-auto">
            <!-- Top badges -->
            <div class="flex flex-wrap gap-2 mb-6">
                <template x-for="badge in levelBadges" :key="badge.level">
                    <span :class="badge.class" class="px-3 py-1 rounded-full text-xs font-semibold">
                        {{ badge.label }}: <span x-text="badge.count"></span>
                    </span>
                </template>
            </div>
            <!-- Search bar -->
            <div class="flex items-center mb-4">
                <input type="text" x-model="filters.keyword" @input="debouncedSearch()" placeholder="Search... RegEx welcome!" class="flex-1 px-3 py-2 border border-gray-300 dark:border-gray-700 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 dark:bg-gray-800 dark:text-gray-100" />
                <select x-model="filters.level" @change="loadLogs()" class="ml-2 px-3 py-2 border border-gray-300 dark:border-gray-700 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 dark:bg-gray-800 dark:text-gray-100">
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
                <button @click="loadLogs()" :disabled="loading" class="ml-2 bg-blue-500 hover:bg-blue-600 disabled:bg-blue-300 text-white px-4 py-2 rounded-md flex items-center">
                    <svg x-show="loading" class="loading w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                    <span x-show="!loading">Refresh</span>
                    <span x-show="loading">Loading...</span>
                </button>
            </div>
            <!-- Log Table -->
            <div class="overflow-x-auto rounded-lg shadow-md">
                <table class="min-w-full text-sm dark:text-gray-100">
                    <thead class="bg-gray-100 dark:bg-gray-800">
                        <tr>
                            <th class="px-4 py-2 text-left">Level</th>
                            <th class="px-4 py-2 text-left">Time</th>
                            <th class="px-4 py-2 text-left">Env</th>
                            <th class="px-4 py-2 text-left">Description</th>
                            <th class="px-4 py-2 text-left">Context</th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-for="log in logs" :key="log.id || Math.random()">
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                                <td class="px-4 py-2">
                                    <span :class="getLevelClass(log.level)" class="px-2 py-1 text-xs font-medium rounded-full" x-text="log.level.toUpperCase()"></span>
                                </td>
                                <td class="px-4 py-2" x-text="formatDate(log.logged_at)"></td>
                                <td class="px-4 py-2" x-text="log.env"></td>
                                <td class="px-4 py-2" x-text="log.message"></td>
                                <td class="px-4 py-2">
                                    <template x-if="log.context && Object.keys(log.context).length > 0">
                                        <pre class="text-xs bg-gray-100 dark:bg-gray-800 p-2 rounded overflow-x-auto" x-text="JSON.stringify(log.context, null, 2)"></pre>
                                    </template>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
                <div x-show="loading" class="p-8 text-center">
                    <div class="inline-block loading w-8 h-8 border-4 border-blue-500 border-t-transparent rounded-full"></div>
                    <p class="mt-2 text-gray-600 dark:text-gray-400">Loading logs...</p>
                </div>
                <div x-show="!loading && logs.length === 0" class="p-8 text-center text-gray-500 dark:text-gray-400">
                    No logs found matching your criteria.
                </div>
            </div>
            <!-- Pagination -->
            <div class="mt-4 flex justify-center items-center">
                <button @click="prevPage()" :disabled="!hasPrev || loading" class="bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-200 px-3 py-1 rounded mr-2">Prev</button>
                <span class="text-sm text-gray-600 dark:text-gray-300">Page <span x-text="page"></span></span>
                <button @click="nextPage()" :disabled="!hasMore || loading" class="bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-200 px-3 py-1 rounded ml-2">Next</button>
            </div>
        </main>
    </div>
    <script>
        function logPlatform() {
            return {
                logs: [],
                files: [],
                loading: false,
                hasMore: false,
                hasPrev: false,
                page: 1,
                filters: {
                    keyword: '',
                    level: '',
                    file: '',
                },
                searchTimeout: null,
                darkMode: false,
                levelBadges: [
                    { level: 'debug', label: 'Debug', class: 'bg-blue-100 text-blue-800 dark:bg-blue-800 dark:text-blue-100', count: 0 },
                    { level: 'info', label: 'Info', class: 'bg-green-100 text-green-800 dark:bg-green-800 dark:text-green-100', count: 0 },
                    { level: 'warning', label: 'Warning', class: 'bg-orange-100 text-orange-800 dark:bg-orange-800 dark:text-orange-100', count: 0 },
                    { level: 'error', label: 'Error', class: 'bg-red-100 text-red-800 dark:bg-red-800 dark:text-red-100', count: 0 }
                ],
                init() {
                    this.loadFiles();
                    this.loadLogs();
                    this.darkMode = localStorage.getItem('logPlatformDarkMode') === 'true';
                },
                async loadFiles() {
                    try {
                        const response = await fetch('/log-platform/api/files');
                        const data = await response.json();
                        this.files = (data.data || []).map(f => ({ name: f.filename, size: f.size }));
                        if (!this.filters.file && this.files.length > 0) {
                            this.filters.file = this.files[0].name;
                        }
                    } catch (error) {
                        this.files = [];
                    }
                },
                async refreshFiles() {
                    await this.loadFiles();
                },
                selectFile(filename) {
                    this.filters.file = filename;
                    this.page = 1;
                    this.loadLogs();
                },
                async loadLogs() {
                    this.loading = true;
                    try {
                        const params = new URLSearchParams();
                        if (this.filters.keyword) params.append('keyword', this.filters.keyword);
                        if (this.filters.level) params.append('level', this.filters.level);
                        if (this.filters.file) params.append('file', this.filters.file);
                        params.append('page', this.page);
                        const response = await fetch(`/log-platform/api/logs?${params}`);
                        const data = await response.json();
                        this.logs = data.data || [];
                        this.hasMore = data.hasMore || false;
                        this.hasPrev = this.page > 1;
                        this.updateBadges();
                    } catch (error) {
                        this.logs = [];
                    } finally {
                        this.loading = false;
                    }
                },
                debouncedSearch() {
                    clearTimeout(this.searchTimeout);
                    this.searchTimeout = setTimeout(() => {
                        this.page = 1;
                        this.loadLogs();
                    }, 300);
                },
                nextPage() {
                    if (this.hasMore && !this.loading) {
                        this.page++;
                        this.loadLogs();
                    }
                },
                prevPage() {
                    if (this.hasPrev && !this.loading) {
                        this.page--;
                        this.loadLogs();
                    }
                },
                deleteFile(filename) {
                    if (confirm('Delete file ' + filename + '?')) {
                        fetch('/log-platform/api/files/delete', {
                            method: 'DELETE',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({ file: filename })
                        }).then(() => {
                            this.refreshFiles();
                        });
                    }
                },
                toggleDarkMode() {
                    this.darkMode = !this.darkMode;
                    localStorage.setItem('logPlatformDarkMode', this.darkMode);
                },
                formatDate(dateString) {
                    const date = new Date(dateString);
                    return date.toLocaleString();
                },
                getLevelClass(level) {
                    const classes = {
                        emergency: 'bg-red-100 text-red-800 dark:bg-red-800 dark:text-red-100',
                        alert: 'bg-red-100 text-red-800 dark:bg-red-800 dark:text-red-100',
                        critical: 'bg-red-100 text-red-800 dark:bg-red-800 dark:text-red-100',
                        error: 'bg-red-100 text-red-800 dark:bg-red-800 dark:text-red-100',
                        warning: 'bg-orange-100 text-orange-800 dark:bg-orange-800 dark:text-orange-100',
                        notice: 'bg-blue-100 text-blue-800 dark:bg-blue-800 dark:text-blue-100',
                        info: 'bg-green-100 text-green-800 dark:bg-green-800 dark:text-green-100',
                        debug: 'bg-blue-100 text-blue-800 dark:bg-blue-800 dark:text-blue-100'
                    };
                    return classes[level] || 'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-100';
                },
                updateBadges() {
                    const counts = { debug: 0, info: 0, warning: 0, error: 0 };
                    for (const log of this.logs) {
                        const lvl = log.level.toLowerCase();
                        if (counts[lvl] !== undefined) counts[lvl]++;
                    }
                    for (const badge of this.levelBadges) {
                        badge.count = counts[badge.level];
                    }
                }
            }
        }
    </script>
</body>
</html>
