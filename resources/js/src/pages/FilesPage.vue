<template>
  <div class="space-y-6">
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
      <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Log Files</h2>
        <button
          @click="fetchFiles"
          class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700"
        >
          🔄 Refresh
        </button>
      </div>

      <!-- Loading -->
      <div v-if="loading" class="text-center py-8 text-gray-500 dark:text-gray-400">
        Loading files...
      </div>

      <!-- Files List -->
      <div v-else-if="files.length > 0" class="space-y-4">
        <div
          v-for="file in files"
          :key="file.name"
          class="border border-gray-200 dark:border-gray-700 rounded-lg p-4 hover:bg-gray-50 dark:hover:bg-gray-700"
        >
          <div class="flex justify-between items-start">
            <div class="flex-1">
              <div class="flex items-center space-x-3">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                  📄 {{ file.name }}
                </h3>
                <span class="px-2 py-1 text-xs font-medium rounded bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300">
                  {{ file.size_human }}
                </span>
              </div>

              <div class="mt-2 space-y-1 text-sm text-gray-600 dark:text-gray-400">
                <div>
                  <strong>Modified:</strong> {{ file.modified_human }}
                </div>
                <div v-if="file.indexed_count !== undefined">
                  <strong>Indexed Entries:</strong> {{ file.indexed_count?.toLocaleString() || 0 }}
                </div>
              </div>
            </div>

            <div class="flex space-x-2 ml-4">
              <button
                @click="viewFile(file)"
                class="px-3 py-1 text-sm bg-blue-600 text-white rounded hover:bg-blue-700"
                title="View logs"
              >
                👁️ View
              </button>
              <button
                @click="downloadFile(file)"
                class="px-3 py-1 text-sm bg-green-600 text-white rounded hover:bg-green-700"
                title="Download file"
              >
                ⬇️ Download
              </button>
              <button
                @click="deleteFile(file)"
                class="px-3 py-1 text-sm bg-red-600 text-white rounded hover:bg-red-700"
                title="Delete file"
              >
                🗑️ Delete
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Empty State -->
      <div v-else class="text-center py-8 text-gray-500 dark:text-gray-400">
        No log files found
      </div>
    </div>

    <!-- Multi-Host Support -->
    <div v-if="hosts.length > 1" class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
      <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-4">Hosts</h3>
      <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div
          v-for="host in hosts"
          :key="host.identifier"
          @click="selectHost(host)"
          :class="[
            'p-4 rounded-lg border-2 cursor-pointer',
            selectedHost === host.identifier
              ? 'border-blue-600 bg-blue-50 dark:bg-blue-900/20'
              : 'border-gray-200 dark:border-gray-700'
          ]"
        >
          <div class="font-semibold text-gray-900 dark:text-white">{{ host.name }}</div>
          <div class="text-sm text-gray-500 dark:text-gray-400">
            {{ host.is_remote ? '🌐 Remote' : '💻 Local' }}
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import axios from 'axios'
import { useRouter } from 'vue-router'

const router = useRouter()
const files = ref([])
const hosts = ref([])
const selectedHost = ref('local')
const loading = ref(false)

const fetchFiles = async () => {
  loading.value = true
  try {
    const { data } = await axios.get('/log-platform/api/files')
    files.value = data.files

    // Fetch metadata for each file
    for (const file of files.value) {
      try {
        const { data: metadata } = await axios.get(`/log-platform/api/files/${file.name}`)
        Object.assign(file, metadata)
      } catch (e) {
        console.warn('Failed to fetch metadata for', file.name)
      }
    }
  } catch (error) {
    console.error('Failed to fetch files:', error)
  } finally {
    loading.value = false
  }
}

const fetchHosts = async () => {
  try {
    const { data } = await axios.get('/log-platform/api/hosts')
    hosts.value = Object.values(data)
  } catch (error) {
    console.error('Failed to fetch hosts:', error)
  }
}

const viewFile = (file) => {
  router.push({
    path: '/',
    query: { file: file.name }
  })
}

const downloadFile = async (file) => {
  try {
    const response = await axios.post('/log-platform/api/files/download', {
      file: file.name
    }, {
      responseType: 'blob'
    })

    const url = window.URL.createObjectURL(new Blob([response.data]))
    const link = document.createElement('a')
    link.href = url
    link.setAttribute('download', file.name)
    document.body.appendChild(link)
    link.click()
    link.remove()
  } catch (error) {
    alert('Failed to download file: ' + error.message)
  }
}

const deleteFile = async (file) => {
  if (!confirm(`Delete ${file.name}? This will also remove all indexed logs.`)) {
    return
  }

  try {
    await axios.delete('/log-platform/api/files/delete', {
      data: { file: file.name }
    })

    files.value = files.value.filter(f => f.name !== file.name)
    alert('File deleted successfully')
  } catch (error) {
    alert('Failed to delete file: ' + error.message)
  }
}

const selectHost = (host) => {
  selectedHost.value = host.identifier
  fetchFiles()
}

onMounted(() => {
  fetchFiles()
  fetchHosts()
})
</script>

