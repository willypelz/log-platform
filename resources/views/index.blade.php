<!DOCTYPE html>
<html lang="en" x-data="logApp()" x-init="init()" :class="{ 'dark': darkMode }">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Log Viewer</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>tailwind.config = { darkMode: 'class' }</script>
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>
        [x-cloak] { display: none !important; }
        @keyframes spin { to { transform: rotate(360deg); } }
        .spin { animation: spin 0.8s linear infinite; }
        .mono { font-family: 'Menlo','Monaco','Courier New',monospace; font-size: 12px; line-height: 1.7; }
        pre { white-space: pre-wrap; word-break: break-word; }
        ::-webkit-scrollbar { width: 5px; height: 5px; }
        ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 3px; }
        .dark ::-webkit-scrollbar-thumb { background: #475569; }
    </style>
</head>
<body class="bg-gray-100 dark:bg-gray-950 text-gray-900 dark:text-gray-100">
<div class="flex h-screen overflow-hidden">

    <!-- ============================================================
         SIDEBAR — file list
    ============================================================ -->
    <aside class="w-72 flex-shrink-0 bg-white dark:bg-gray-900 border-r border-gray-200 dark:border-gray-700 flex flex-col">

        <!-- Header -->
        <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
            <div>
                <h1 class="font-bold text-gray-900 dark:text-white">📋 Log Viewer</h1>
                <p class="text-xs text-gray-400 mt-0.5">storage/logs</p>
            </div>
            <button @click="toggleDark()" class="text-xl p-1.5 rounded hover:bg-gray-100 dark:hover:bg-gray-800 transition" title="Toggle dark mode">
                <span x-show="!darkMode">🌙</span>
                <span x-show="darkMode" x-cloak>☀️</span>
            </button>
        </div>

        <!-- File filter input -->
        <div class="px-3 py-2 border-b border-gray-200 dark:border-gray-700">
            <input type="text" x-model="fileSearch" placeholder="Filter files…"
                   class="w-full text-sm px-3 py-1.5 rounded border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-blue-500" />
        </div>

        <!-- File list -->
        <div class="flex-1 overflow-y-auto p-2 space-y-0.5">

            <!-- Loading spinner -->
            <template x-if="loadingFiles">
                <div class="flex items-center justify-center gap-2 py-10 text-sm text-gray-400">
                    <svg class="spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/>
                    </svg>
                    Loading…
                </div>
            </template>

            <!-- Empty state -->
            <template x-if="!loadingFiles && filteredFiles.length === 0">
                <p class="text-center text-sm text-gray-400 py-10">No .log files found</p>
            </template>

            <!-- File rows -->
            <template x-for="file in filteredFiles" :key="file.name">
                <div @click="openFile(file)"
                     class="group flex items-start justify-between rounded-lg px-3 py-2.5 cursor-pointer transition-colors hover:bg-gray-100 dark:hover:bg-gray-800"
                     :class="activeFile === file.name
                         ? 'bg-blue-50 dark:bg-blue-900/40 border border-blue-200 dark:border-blue-700'
                         : ''">
                    <div class="flex-1 min-w-0 mr-2">
                        <p class="text-sm font-medium truncate"
                           :class="activeFile === file.name ? 'text-blue-700 dark:text-blue-300' : 'text-gray-800 dark:text-gray-200'"
                           x-text="file.name"></p>
                        <p class="text-xs text-gray-400 mt-0.5"
                           x-text="file.size_human + ' · ' + (file.modified_human || '').slice(0, 10)"></p>
                    </div>
                    <button @click.stop="confirmDelete(file.name)"
                            class="flex-shrink-0 opacity-0 group-hover:opacity-100 p-1 text-gray-400 hover:text-red-500 rounded transition"
                            title="Delete file">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                        </svg>
                    </button>
                </div>
            </template>
        </div>

        <!-- Sidebar footer -->
        <div class="px-3 py-2 border-t border-gray-200 dark:border-gray-700">
            <button @click="loadFiles()"
                    class="w-full text-xs py-1.5 rounded bg-gray-100 dark:bg-gray-800 hover:bg-gray-200 dark:hover:bg-gray-700 text-gray-600 dark:text-gray-300 transition">
                🔄 Refresh (<span x-text="files.length"></span> files)
            </button>
        </div>
    </aside>

    <!-- ============================================================
         MAIN PANEL — log content
    ============================================================ -->
    <main class="flex-1 flex flex-col overflow-hidden bg-white dark:bg-gray-900">

        <!-- No file selected yet -->
        <template x-if="!activeFile">
            <div class="flex-1 flex flex-col items-center justify-center text-center p-12">
                <div class="text-6xl mb-4">📂</div>
                <h2 class="text-xl font-semibold text-gray-600 dark:text-gray-300 mb-2">Select a log file</h2>
                <p class="text-sm text-gray-400">Choose a file from the sidebar to view its contents</p>
                <template x-if="!loadingFiles && files.length === 0">
                    <p class="mt-4 text-xs text-gray-400">
                        No <code class="bg-gray-100 dark:bg-gray-800 px-1 rounded">.log</code>
                        files found in
                        <code class="bg-gray-100 dark:bg-gray-800 px-1 rounded">storage/logs</code>
                    </p>
                </template>
            </div>
        </template>

        <!-- File is open -->
        <template x-if="activeFile">
            <div class="flex-1 flex flex-col overflow-hidden">

                <!-- Toolbar -->
                <div class="px-4 py-2.5 border-b border-gray-200 dark:border-gray-700 flex flex-wrap items-center gap-3 bg-white dark:bg-gray-900">

                    <!-- Filename + meta -->
                    <div class="flex items-center gap-2 mr-auto min-w-0">
                        <span class="font-semibold text-sm text-gray-800 dark:text-gray-200 truncate" x-text="activeFile"></span>
                        <template x-if="activeFileMeta">
                            <span class="text-xs text-gray-400 whitespace-nowrap"
                                  x-text="activeFileMeta.size_human + ' · ' + activeFileMeta.modified_human"></span>
                        </template>
                    </div>

                    <!-- Parsed / Raw toggle -->
                    <div class="flex rounded border border-gray-300 dark:border-gray-600 overflow-hidden text-xs flex-shrink-0">
                        <button @click="setView('parsed')"
                                :class="view==='parsed' ? 'bg-blue-600 text-white' : 'bg-white dark:bg-gray-800 text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700'"
                                class="px-3 py-1.5 transition">Parsed</button>
                        <button @click="setView('raw')"
                                :class="view==='raw' ? 'bg-blue-600 text-white' : 'bg-white dark:bg-gray-800 text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700'"
                                class="px-3 py-1.5 transition">Raw</button>
                    </div>

                    <!-- Keyword search (parsed only) -->
                    <template x-if="view === 'parsed'">
                        <input type="text" x-model="keyword"
                               @input="debounce(() => { page=1; loadContent(); }, 350)"
                               placeholder="Search message…"
                               class="text-sm px-3 py-1.5 rounded border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-blue-500 w-44"/>
                    </template>

                    <!-- Level filter (parsed only) -->
                    <template x-if="view === 'parsed'">
                        <select x-model="levelFilter" @change="page=1; loadContent()"
                                class="text-sm px-2 py-1.5 rounded border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">All levels</option>
                            <option>emergency</option><option>alert</option><option>critical</option>
                            <option>error</option><option>warning</option><option>notice</option>
                            <option>info</option><option>debug</option>
                        </select>
                    </template>

                    <!-- Download -->
                    <button @click="downloadFile()"
                            class="flex-shrink-0 text-xs px-3 py-1.5 rounded bg-green-600 hover:bg-green-700 text-white transition">
                        ⬇️ Download
                    </button>
                </div>

                <!-- Level count badges (parsed only) -->
                <template x-if="view === 'parsed' && Object.keys(levelCounts).length > 0">
                    <div class="px-4 py-2 border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/60 flex flex-wrap gap-1.5">
                        <template x-for="[lvl, cnt] in Object.entries(levelCounts)" :key="lvl">
                            <button @click="levelFilter=(levelFilter===lvl?'':lvl); page=1; loadContent()"
                                    :class="[badgeClass(lvl), levelFilter===lvl ? 'ring-2 ring-blue-400 ring-offset-1' : '']"
                                    class="px-2.5 py-0.5 rounded-full text-xs font-semibold transition"
                                    x-text="lvl.toUpperCase()+' '+cnt">
                            </button>
                        </template>
                    </div>
                </template>

                <!-- Scrollable content area -->
                <div class="flex-1 overflow-y-auto">

                    <!-- Spinner -->
                    <template x-if="loadingContent">
                        <div class="flex items-center justify-center gap-3 py-20 text-gray-400">
                            <svg class="spin w-6 h-6" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/>
                            </svg>
                            Loading…
                        </div>
                    </template>

                    <!-- ── PARSED TABLE ── -->
                    <template x-if="!loadingContent && view === 'parsed'">
                        <div>
                            <!-- Empty -->
                            <template x-if="entries.length === 0">
                                <div class="text-center py-20 text-gray-400 dark:text-gray-500">
                                    <div class="text-5xl mb-3">🔍</div>
                                    <p class="text-sm">No log entries found</p>
                                    <p class="text-xs mt-1" x-show="keyword || levelFilter">Try clearing your filters</p>
                                </div>
                            </template>

                            <table class="w-full text-sm" x-show="entries.length > 0">
                                <thead class="sticky top-0 bg-gray-100 dark:bg-gray-800 text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide">
                                    <tr>
                                        <th class="px-4 py-2.5 text-left w-28">Level</th>
                                        <th class="px-4 py-2.5 text-left w-44">Time</th>
                                        <th class="px-4 py-2.5 text-left w-20">Env</th>
                                        <th class="px-4 py-2.5 text-left">Message</th>
                                        <th class="px-4 py-2.5 text-left w-20">Context</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                                    <template x-for="(entry, i) in entries" :key="i">
                                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors">
                                            <td class="px-4 py-2.5">
                                                <span :class="badgeClass(entry.level)"
                                                      class="px-2 py-0.5 rounded-full text-xs font-semibold"
                                                      x-text="(entry.level||'?').toUpperCase()"></span>
                                            </td>
                                            <td class="px-4 py-2.5 text-xs text-gray-500 dark:text-gray-400 whitespace-nowrap"
                                                x-text="entry.logged_at || ''"></td>
                                            <td class="px-4 py-2.5 text-xs text-gray-500 dark:text-gray-400"
                                                x-text="entry.env || ''"></td>
                                            <td class="px-4 py-2.5 text-gray-800 dark:text-gray-200 break-words max-w-xl"
                                                x-text="entry.message || ''"></td>
                                            <td class="px-4 py-2.5">
                                                <template x-if="entry.context && Object.keys(entry.context).length > 0">
                                                    <button @click="expandedRow = (expandedRow===i ? null : i)"
                                                            class="text-xs text-blue-500 hover:underline">
                                                        <span x-text="expandedRow===i ? 'Hide' : 'Show'"></span>
                                                    </button>
                                                </template>
                                            </td>
                                        </tr>
                                        <!-- Expanded context -->
                                        <template x-if="expandedRow === i">
                                            <tr>
                                                <td colspan="5" class="px-4 pb-3 bg-gray-50 dark:bg-gray-800/40">
                                                    <pre class="text-xs bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded p-3 overflow-x-auto text-gray-700 dark:text-gray-300 max-h-60"
                                                         x-text="JSON.stringify(entry.context, null, 2)"></pre>
                                                </td>
                                            </tr>
                                        </template>
                                    </template>
                                </tbody>
                            </table>
                        </div>
                    </template>

                    <!-- ── RAW LINES ── -->
                    <template x-if="!loadingContent && view === 'raw'">
                        <div class="p-4">
                            <template x-if="rawLines.length === 0">
                                <p class="text-center py-20 text-sm text-gray-400">File is empty</p>
                            </template>
                            <template x-for="(line, i) in rawLines" :key="i">
                                <div class="mono flex hover:bg-gray-100 dark:hover:bg-gray-800/50 rounded px-2 py-px">
                                    <span class="w-12 flex-shrink-0 text-right pr-4 text-gray-300 dark:text-gray-600 select-none"
                                          x-text="(page-1)*perPage + i + 1"></span>
                                    <span :class="rawClass(line)" class="flex-1" x-text="line || '\u00a0'"></span>
                                </div>
                            </template>
                        </div>
                    </template>
                </div>

                <!-- Pagination footer -->
                <div class="px-4 py-2 border-t border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 flex items-center justify-between">
                    <span class="text-xs text-gray-400">
                        <template x-if="view==='raw' && totalLines > 0">
                            <span x-text="'Lines ' + ((page-1)*perPage+1).toLocaleString()
                                + '–' + Math.min(page*perPage, totalLines).toLocaleString()
                                + ' of ' + totalLines.toLocaleString()"></span>
                        </template>
                        <template x-if="view==='parsed'">
                            <span x-text="entries.length.toLocaleString() + ' entries'"></span>
                        </template>
                    </span>
                    <div class="flex items-center gap-2">
                        <button @click="page--; loadContent()" :disabled="page <= 1 || loadingContent"
                                class="px-3 py-1 text-xs rounded border border-gray-300 dark:border-gray-600 disabled:opacity-40 hover:bg-gray-100 dark:hover:bg-gray-800 transition">
                            ← Prev
                        </button>
                        <span class="text-xs text-gray-500">
                            Page <span x-text="page"></span>
                            <template x-if="view==='raw' && totalPages > 1">
                                <span> / <span x-text="totalPages"></span></span>
                            </template>
                        </span>
                        <button @click="page++; loadContent()"
                                :disabled="(view==='raw' && page>=totalPages) || (view==='parsed' && entries.length<limit) || loadingContent"
                                class="px-3 py-1 text-xs rounded border border-gray-300 dark:border-gray-600 disabled:opacity-40 hover:bg-gray-100 dark:hover:bg-gray-800 transition">
                            Next →
                        </button>
                    </div>
                </div>

            </div>
        </template>
    </main>
</div>

<!-- ── Delete confirmation modal ── -->
<template x-if="deleteTarget">
    <div class="fixed inset-0 bg-black/50 flex items-center justify-center z-50" @click.self="deleteTarget=null">
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-2xl p-6 w-80">
            <h3 class="font-semibold text-gray-900 dark:text-white mb-2">Delete log file?</h3>
            <p class="text-sm text-gray-500 dark:text-gray-400 mb-1 break-all" x-text="deleteTarget"></p>
            <p class="text-xs text-red-500 mb-5">⚠️ This cannot be undone.</p>
            <div class="flex gap-3 justify-end">
                <button @click="deleteTarget=null"
                        class="px-4 py-2 text-sm rounded border border-gray-300 dark:border-gray-600 hover:bg-gray-100 dark:hover:bg-gray-700 transition">
                    Cancel
                </button>
                <button @click="doDelete()"
                        class="px-4 py-2 text-sm rounded bg-red-600 hover:bg-red-700 text-white transition">
                    Delete
                </button>
            </div>
        </div>
    </div>
</template>

<script>
function logApp() {
    return {
        // ── state ─────────────────────────────────────────────────────────
        darkMode:       false,
        files:          [],
        fileSearch:     '',
        loadingFiles:   false,
        activeFile:     null,
        activeFileMeta: null,

        view:           'parsed',   // 'parsed' | 'raw'
        loadingContent: false,

        // parsed view
        entries:     [],
        levelCounts: {},
        levelFilter: '',
        keyword:     '',
        limit:       100,
        expandedRow: null,

        // raw view
        rawLines:   [],
        totalLines: 0,
        totalPages: 0,
        perPage:    200,

        page:         1,
        deleteTarget: null,
        _dt:          null,

        // ── computed ──────────────────────────────────────────────────────
        get filteredFiles() {
            const q = this.fileSearch.toLowerCase();
            return q ? this.files.filter(f => f.name.toLowerCase().includes(q)) : this.files;
        },

        // ── init ──────────────────────────────────────────────────────────
        init() {
            const s = localStorage.getItem('lp_dark');
            this.darkMode = s !== null
                ? s === 'true'
                : window.matchMedia('(prefers-color-scheme: dark)').matches;
            this.loadFiles();
        },

        // ── file listing ──────────────────────────────────────────────────
        async loadFiles() {
            this.loadingFiles = true;
            try {
                const res  = await fetch('/log-platform/api/files');
                const data = await res.json();

                // API returns: { files: [{name, size, size_human, modified, modified_human}], count }
                this.files = data.files || [];

                // auto-open the first file
                if (!this.activeFile && this.files.length > 0) {
                    this.openFile(this.files[0]);
                }
            } catch (e) {
                this.files = [];
            } finally {
                this.loadingFiles = false;
            }
        },

        async openFile(file) {
            this.activeFile     = file.name;
            this.activeFileMeta = file;
            this.page           = 1;
            this.keyword        = '';
            this.levelFilter    = '';
            this.expandedRow    = null;
            this.entries        = [];
            this.rawLines       = [];
            this.levelCounts    = {};
            await this.loadContent();
        },

        // ── content loading ───────────────────────────────────────────────
        setView(v) { this.view = v; this.page = 1; this.loadContent(); },

        async loadContent() {
            if (!this.activeFile) return;
            this.loadingContent = true;
            this.expandedRow    = null;
            try {
                this.view === 'parsed' ? await this.loadParsed() : await this.loadRaw();
            } catch (e) {
                console.error('log content error', e);
            } finally {
                this.loadingContent = false;
            }
        },

        async loadParsed() {
            // GET /log-platform/api/logs
            // Returns: { file, count, entries: [{level, logged_at, env, message, context}] }
            const p = new URLSearchParams({ file: this.activeFile, limit: this.limit });
            if (this.keyword)     p.set('keyword', this.keyword);
            if (this.levelFilter) p.set('level',   this.levelFilter);

            const res  = await fetch('/log-platform/api/logs?' + p);
            const data = await res.json();
            this.entries = data.entries || [];

            // tally per-level counts for the badge strip
            const c = {};
            for (const e of this.entries) {
                const l = (e.level || 'unknown').toLowerCase();
                c[l] = (c[l] || 0) + 1;
            }
            this.levelCounts = c;
        },

        async loadRaw() {
            // GET /log-platform/api/contents
            // Returns: { file, total_lines, page, per_page, total_pages, lines: [] }
            const p = new URLSearchParams({ file: this.activeFile, page: this.page, per_page: this.perPage });

            const res  = await fetch('/log-platform/api/contents?' + p);
            const data = await res.json();
            this.rawLines   = data.lines       || [];
            this.totalLines = data.total_lines || 0;
            this.totalPages = data.total_pages || 1;
        },

        // ── download ──────────────────────────────────────────────────────
        downloadFile() {
            const form  = document.createElement('form');
            form.method = 'POST';
            form.action = '/log-platform/api/files/download';
            const add = (n, v) => {
                const i = document.createElement('input');
                i.type = 'hidden'; i.name = n; i.value = v;
                form.appendChild(i);
            };
            add('file',   this.activeFile);
            add('_token', document.querySelector('meta[name="csrf-token"]')?.content || '');
            document.body.appendChild(form);
            form.submit();
            document.body.removeChild(form);
        },

        // ── delete ────────────────────────────────────────────────────────
        confirmDelete(name) { this.deleteTarget = name; },

        async doDelete() {
            const name        = this.deleteTarget;
            this.deleteTarget = null;
            try {
                await fetch('/log-platform/api/files/delete', {
                    method:  'DELETE',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                    },
                    body: JSON.stringify({ file: name }),
                });
                if (this.activeFile === name) {
                    this.activeFile     = null;
                    this.activeFileMeta = null;
                    this.entries        = [];
                    this.rawLines       = [];
                    this.levelCounts    = {};
                }
                await this.loadFiles();
            } catch (e) { console.error('delete failed', e); }
        },

        // ── helpers ───────────────────────────────────────────────────────
        toggleDark() {
            this.darkMode = !this.darkMode;
            localStorage.setItem('lp_dark', this.darkMode);
        },

        debounce(fn, ms) {
            clearTimeout(this._dt);
            this._dt = setTimeout(fn, ms);
        },

        badgeClass(level) {
            const map = {
                emergency: 'bg-red-200 text-red-900 dark:bg-red-900 dark:text-red-100',
                alert:     'bg-red-200 text-red-900 dark:bg-red-900 dark:text-red-100',
                critical:  'bg-red-200 text-red-900 dark:bg-red-900 dark:text-red-100',
                error:     'bg-red-100 text-red-800 dark:bg-red-800 dark:text-red-100',
                warning:   'bg-yellow-100 text-yellow-800 dark:bg-yellow-800 dark:text-yellow-100',
                notice:    'bg-blue-100 text-blue-800 dark:bg-blue-700 dark:text-blue-100',
                info:      'bg-green-100 text-green-800 dark:bg-green-800 dark:text-green-100',
                debug:     'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300',
            };
            return map[(level || '').toLowerCase()] || 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300';
        },

        rawClass(line) {
            const l = (line || '').toLowerCase();
            if (/\.(emergency|alert|critical|error)|\[(emergency|alert|critical|error)\]/.test(l))
                return 'text-red-600 dark:text-red-400';
            if (/\.warning|\[warning\]/.test(l))  return 'text-yellow-600 dark:text-yellow-400';
            if (/\.info|\[info\]/.test(l))         return 'text-blue-600 dark:text-blue-400';
            if (/\.debug|\[debug\]/.test(l))       return 'text-gray-400 dark:text-gray-500';
            return 'text-gray-700 dark:text-gray-300';
        },
    };
}
</script>
</body>
</html>
