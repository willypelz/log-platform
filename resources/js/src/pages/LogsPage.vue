<template>
  <div class="space-y-6">
    <!-- Header & Filters -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
      <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-4">Log Viewer</h2>

      <!-- Query Input -->
      <div class="mb-4">
        <input
          v-model="query"
          type="text"
          placeholder="Structured query: level:error AND user_id:123"
          class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
          @keyup.enter="fetchLogs"
        />
      </div>

      <!-- Filters -->
      <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <select
          v-model="filters.level"
          class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
        >
          <option value="">All Levels</option>
          <option value="debug">Debug</option>
          <option value="info">Info</option>
          <option value="warning">Warning</option>
          <option value="error">Error</option>
          <option value="critical">Critical</option>
        </select>

        <input
          v-model="filters.keyword"
          type="text"
          placeholder="Keyword search..."
          class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
        />

        <input
          v-model="filters.from"
          type="datetime-local"
          class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
        />

        <button
          @click="fetchLogs"
          class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700"
        >
          🔍 Search
        </button>
      </div>

      <!-- Actions -->
      <div class="mt-4 flex space-x-4">
        <button
          @click="toggleLiveTail"
          :class="[
            'px-4 py-2 rounded-lg',
            isLiveTail ? 'bg-green-600 text-white' : 'bg-gray-200 dark:bg-gray-700 text-gray-900 dark:text-white'
          ]"
        >
          {{ isLiveTail ? '⏸ Pause' : '▶️ Live Tail' }}
        </button>
        <button
          @click="clearFilters"
          class="px-4 py-2 bg-gray-200 dark:bg-gray-700 text-gray-900 dark:text-white rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600"
        >
          Clear Filters
        </button>
      </div>
    </div>

    <!-- Logs List -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
      <div v-if="loading" class="p-8 text-center text-gray-500 dark:text-gray-400">
        Loading logs...
      </div>

      <div v-else-if="logs.length === 0" class="p-8 text-center text-gray-500 dark:text-gray-400">
        No logs found
      </div>

      <div v-else class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
          <thead class="bg-gray-50 dark:bg-gray-900">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                Time
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                Level
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                Message
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                Env
              </th>
            </tr>
          </thead>
          <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
            <tr
              v-for="log in logs"
              :key="log.id"
              @click="selectedLog = log"
              class="hover:bg-gray-50 dark:hover:bg-gray-700 cursor-pointer"
            >
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-300">
                {{ formatDate(log.logged_at) }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <span :class="`log-level-${log.level} px-2 py-1 text-xs font-medium rounded`">
                  {{ log.level.toUpperCase() }}
                </span>
              </td>
              <td class="px-6 py-4 text-sm text-gray-900 dark:text-gray-300 truncate max-w-xl">
                {{ log.message }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                {{ log.env }}
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Load More -->
      <div v-if="hasMore" class="p-4 border-t border-gray-200 dark:border-gray-700 text-center">
        <button
          @click="loadMore"
          class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700"
        >
          Load More
        </button>
      </div>
    </div>

    <!-- Detail Modal -->
    <div
      v-if="selectedLog"
      class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50"
      @click="selectedLog = null"
    >
      <div
        class="bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-4xl w-full mx-4 max-h-[90vh] overflow-y-auto"
        @click.stop
      >
        <div class="p-6">
          <div class="flex justify-between items-start mb-4">
            <h3 class="text-xl font-bold text-gray-900 dark:text-white">Log Details</h3>
            <button @click="selectedLog = null" class="text-gray-500 hover:text-gray-700">
              ✕
            </button>
          </div>

          <dl class="space-y-4">
            <div>
              <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Level</dt>
              <dd>
                <span :class="`log-level-${selectedLog.level} px-2 py-1 text-xs font-medium rounded`">
                  {{ selectedLog.level.toUpperCase() }}
                </span>
              </dd>
            </div>
            <div>
              <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Time</dt>
              <dd class="text-gray-900 dark:text-white">{{ selectedLog.logged_at }}</dd>
            </div>
            <div>
              <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Message</dt>
              <dd class="text-gray-900 dark:text-white whitespace-pre-wrap">{{ selectedLog.message }}</dd>
            </div>
            <div v-if="selectedLog.request_id">
              <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Request ID</dt>
              <dd class="text-gray-900 dark:text-white font-mono text-sm">{{ selectedLog.request_id }}</dd>
            </div>
            <div v-if="selectedLog.context">
              <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Context</dt>
              <dd>
                <pre class="bg-gray-100 dark:bg-gray-900 p-4 rounded text-sm overflow-x-auto">{{ JSON.stringify(selectedLog.context, null, 2) }}</pre>
              </dd>
            </div>
          </dl>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue'
import { format } from 'date-fns'
import axios from 'axios'

const logs = ref([])
const loading = ref(false)
const query = ref('')
const filters = ref({
  level: '',
  keyword: '',
  from: '',
  env: '',
})
const cursor = ref(null)
const hasMore = ref(false)
const selectedLog = ref(null)
const isLiveTail = ref(false)
let eventSource = null

const fetchLogs = async (append = false) => {
  loading.value = true
  try {
    const params = {
      query: query.value,
      ...filters.value,
      cursor: append ? cursor.value : null,
    }
    const { data } = await axios.get('/log-platform/api/logs', { params })

    if (append) {
      logs.value.push(...data.data)
    } else {
      logs.value = data.data
    }

    cursor.value = data.cursor
    hasMore.value = data.hasMore
  } catch (error) {
    console.error('Failed to fetch logs:', error)
  } finally {
    loading.value = false
  }
}

const loadMore = () => {
  fetchLogs(true)
}

const clearFilters = () => {
  query.value = ''
  filters.value = { level: '', keyword: '', from: '', env: '' }
  fetchLogs()
}

const formatDate = (date) => {
  return format(new Date(date), 'yyyy-MM-dd HH:mm:ss')
}

const toggleLiveTail = () => {
  if (isLiveTail.value) {
    stopLiveTail()
  } else {
    startLiveTail()
  }
}

const startLiveTail = () => {
  isLiveTail.value = true
  eventSource = new EventSource('/log-platform/api/logs/stream')

  eventSource.onmessage = (event) => {
    const log = JSON.parse(event.data)
    logs.value.unshift(log)
    if (logs.value.length > 500) {
      logs.value = logs.value.slice(0, 500)
    }
  }

  eventSource.onerror = () => {
    console.error('SSE connection error')
    stopLiveTail()
  }
}

const stopLiveTail = () => {
  isLiveTail.value = false
  if (eventSource) {
    eventSource.close()
    eventSource = null
  }
}

onMounted(() => {
  fetchLogs()
})

onUnmounted(() => {
  stopLiveTail()
})
</script>

