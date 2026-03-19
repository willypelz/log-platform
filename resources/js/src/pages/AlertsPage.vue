<template>
  <div class="space-y-6">
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
      <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Alert Rules</h2>
        <button
          @click="showCreateModal = true"
          class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700"
        >
          + Create Alert
        </button>
      </div>

      <!-- Loading -->
      <div v-if="loading" class="text-center py-8 text-gray-500 dark:text-gray-400">
        Loading alerts...
      </div>

      <!-- Empty State -->
      <div v-else-if="rules.length === 0" class="text-center py-8 text-gray-500 dark:text-gray-400">
        No alert rules configured yet
      </div>

      <!-- Rules List -->
      <div v-else class="space-y-4">
        <div
          v-for="rule in rules"
          :key="rule.id"
          class="border border-gray-200 dark:border-gray-700 rounded-lg p-4"
        >
          <div class="flex justify-between items-start">
            <div class="flex-1">
              <div class="flex items-center space-x-3">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                  {{ rule.name }}
                </h3>
                <span
                  :class="[
                    'px-2 py-1 text-xs font-medium rounded',
                    rule.enabled
                      ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200'
                      : 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300'
                  ]"
                >
                  {{ rule.enabled ? 'Enabled' : 'Disabled' }}
                </span>
              </div>

              <div class="mt-2 space-y-1 text-sm text-gray-600 dark:text-gray-400">
                <div><strong>Query:</strong> {{ rule.query }}</div>
                <div>
                  <strong>Threshold:</strong> {{ rule.threshold_count }} events in {{ rule.window_seconds }}s
                </div>
                <div><strong>Channels:</strong> {{ rule.channels.join(', ') }}</div>
              </div>

              <!-- Recent Events -->
              <div v-if="rule.events && rule.events.length > 0" class="mt-3">
                <div class="text-xs text-gray-500 dark:text-gray-400">
                  Last triggered: {{ formatDate(rule.events[0].triggered_at) }}
                  ({{ rule.events[0].match_count }} matches)
                </div>
              </div>
            </div>

            <div class="flex space-x-2">
              <button
                @click="toggleRule(rule)"
                class="px-3 py-1 text-sm bg-gray-200 dark:bg-gray-700 text-gray-900 dark:text-white rounded hover:bg-gray-300 dark:hover:bg-gray-600"
              >
                {{ rule.enabled ? 'Disable' : 'Enable' }}
              </button>
              <button
                @click="deleteRule(rule)"
                class="px-3 py-1 text-sm bg-red-600 text-white rounded hover:bg-red-700"
              >
                Delete
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Create Modal -->
    <div
      v-if="showCreateModal"
      class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50"
      @click="showCreateModal = false"
    >
      <div
        class="bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-2xl w-full mx-4"
        @click.stop
      >
        <div class="p-6">
          <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-4">Create Alert Rule</h3>

          <form @submit.prevent="createRule" class="space-y-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                Name
              </label>
              <input
                v-model="newRule.name"
                type="text"
                required
                class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
              />
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                Query
              </label>
              <input
                v-model="newRule.query"
                type="text"
                required
                placeholder="level:error"
                class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
              />
            </div>

            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                  Window (seconds)
                </label>
                <input
                  v-model.number="newRule.window_seconds"
                  type="number"
                  required
                  class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
                />
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                  Threshold Count
                </label>
                <input
                  v-model.number="newRule.threshold_count"
                  type="number"
                  required
                  class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
                />
              </div>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                Notification Channels
              </label>
              <div class="space-y-2">
                <label class="flex items-center">
                  <input v-model="newRule.channels" type="checkbox" value="mail" class="mr-2" />
                  <span class="text-gray-900 dark:text-white">Email</span>
                </label>
                <label class="flex items-center">
                  <input v-model="newRule.channels" type="checkbox" value="slack" class="mr-2" />
                  <span class="text-gray-900 dark:text-white">Slack</span>
                </label>
              </div>
            </div>

            <div class="flex justify-end space-x-3 pt-4">
              <button
                type="button"
                @click="showCreateModal = false"
                class="px-4 py-2 bg-gray-200 dark:bg-gray-700 text-gray-900 dark:text-white rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600"
              >
                Cancel
              </button>
              <button
                type="submit"
                class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700"
              >
                Create
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { format } from 'date-fns'
import axios from 'axios'

const rules = ref([])
const loading = ref(false)
const showCreateModal = ref(false)
const newRule = ref({
  name: '',
  query: 'level:error',
  window_seconds: 60,
  threshold_count: 10,
  channels: ['mail'],
})

const fetchRules = async () => {
  loading.value = true
  try {
    const { data } = await axios.get('/log-platform/api/alerts/rules')
    rules.value = data
  } catch (error) {
    console.error('Failed to fetch rules:', error)
  } finally {
    loading.value = false
  }
}

const createRule = async () => {
  try {
    await axios.post('/log-platform/api/alerts/rules', newRule.value)
    showCreateModal.value = false
    newRule.value = {
      name: '',
      query: 'level:error',
      window_seconds: 60,
      threshold_count: 10,
      channels: ['mail'],
    }
    fetchRules()
  } catch (error) {
    console.error('Failed to create rule:', error)
  }
}

const toggleRule = async (rule) => {
  try {
    await axios.patch(`/log-platform/api/alerts/rules/${rule.id}`, {
      enabled: !rule.enabled,
    })
    fetchRules()
  } catch (error) {
    console.error('Failed to toggle rule:', error)
  }
}

const deleteRule = async (rule) => {
  if (!confirm(`Delete alert rule "${rule.name}"?`)) return

  try {
    await axios.delete(`/log-platform/api/alerts/rules/${rule.id}`)
    fetchRules()
  } catch (error) {
    console.error('Failed to delete rule:', error)
  }
}

const formatDate = (date) => {
  return format(new Date(date), 'yyyy-MM-dd HH:mm:ss')
}

onMounted(() => {
  fetchRules()
})
</script>

