<template>
  <div class="space-y-6">
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
      <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-4">Metrics Dashboard</h2>

      <!-- Time Range Selector -->
      <div class="flex space-x-4 mb-6">
        <button
          v-for="range in timeRanges"
          :key="range.value"
          @click="selectTimeRange(range)"
          :class="[
            'px-4 py-2 rounded-lg',
            selectedRange === range.value
              ? 'bg-blue-600 text-white'
              : 'bg-gray-200 dark:bg-gray-700 text-gray-900 dark:text-white'
          ]"
        >
          {{ range.label }}
        </button>
      </div>

      <!-- Overview Stats -->
      <div v-if="overview" class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-gray-50 dark:bg-gray-900 rounded-lg p-4">
          <div class="text-sm text-gray-500 dark:text-gray-400">Total Logs</div>
          <div class="text-2xl font-bold text-gray-900 dark:text-white">
            {{ overview.total_logs.toLocaleString() }}
          </div>
        </div>

        <div class="bg-red-50 dark:bg-red-900/20 rounded-lg p-4">
          <div class="text-sm text-red-600 dark:text-red-400">Errors</div>
          <div class="text-2xl font-bold text-red-700 dark:text-red-300">
            {{ (overview.by_level.error || 0).toLocaleString() }}
          </div>
        </div>

        <div class="bg-yellow-50 dark:bg-yellow-900/20 rounded-lg p-4">
          <div class="text-sm text-yellow-600 dark:text-yellow-400">Warnings</div>
          <div class="text-2xl font-bold text-yellow-700 dark:text-yellow-300">
            {{ (overview.by_level.warning || 0).toLocaleString() }}
          </div>
        </div>

        <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-4">
          <div class="text-sm text-blue-600 dark:text-blue-400">Info</div>
          <div class="text-2xl font-bold text-blue-700 dark:text-blue-300">
            {{ (overview.by_level.info || 0).toLocaleString() }}
          </div>
        </div>
      </div>

      <!-- Loading -->
      <div v-if="loading" class="text-center py-8 text-gray-500 dark:text-gray-400">
        Loading metrics...
      </div>

      <!-- Charts placeholder -->
      <div v-else class="space-y-6">
        <!-- Errors Per Minute Chart -->
        <div class="bg-gray-50 dark:bg-gray-900 rounded-lg p-6">
          <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
            Errors Per Minute
          </h3>
          <div class="h-64 flex items-center justify-center text-gray-500 dark:text-gray-400">
            Chart visualization would go here (integrate Chart.js or similar)
          </div>
        </div>

        <!-- Top Error Fingerprints -->
        <div class="bg-gray-50 dark:bg-gray-900 rounded-lg p-6">
          <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
            Top Error Patterns
          </h3>
          <div v-if="overview?.top_fingerprints" class="space-y-2">
            <div
              v-for="fp in overview.top_fingerprints"
              :key="fp.fingerprint"
              class="flex justify-between items-center p-3 bg-white dark:bg-gray-800 rounded"
            >
              <div class="flex-1 truncate">
                <div class="text-sm text-gray-900 dark:text-white">{{ fp.message }}</div>
                <div class="text-xs text-gray-500 dark:text-gray-400 font-mono">
                  {{ fp.fingerprint.substring(0, 16) }}...
                </div>
              </div>
              <div class="ml-4 text-right">
                <div class="text-lg font-bold text-gray-900 dark:text-white">{{ fp.count }}</div>
                <div class="text-xs text-gray-500 dark:text-gray-400">occurrences</div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import axios from 'axios'

const overview = ref(null)
const loading = ref(false)
const selectedRange = ref('1h')

const timeRanges = [
  { label: '1 Hour', value: '1h' },
  { label: '6 Hours', value: '6h' },
  { label: '24 Hours', value: '24h' },
  { label: '7 Days', value: '7d' },
]

const selectTimeRange = (range) => {
  selectedRange.value = range.value
  fetchMetrics()
}

const fetchMetrics = async () => {
  loading.value = true
  try {
    const { data } = await axios.get('/log-platform/api/metrics/overview', {
      params: {
        from: getFromDate(),
        to: new Date().toISOString(),
      },
    })
    overview.value = data
  } catch (error) {
    console.error('Failed to fetch metrics:', error)
  } finally {
    loading.value = false
  }
}

const getFromDate = () => {
  const now = new Date()
  switch (selectedRange.value) {
    case '1h': return new Date(now - 3600000).toISOString()
    case '6h': return new Date(now - 21600000).toISOString()
    case '24h': return new Date(now - 86400000).toISOString()
    case '7d': return new Date(now - 604800000).toISOString()
    default: return new Date(now - 3600000).toISOString()
  }
}

onMounted(() => {
  fetchMetrics()
  // Auto-refresh every minute
  setInterval(fetchMetrics, 60000)
})
</script>

