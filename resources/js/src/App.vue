<template>
  <div id="app" class="min-h-screen bg-gray-50 dark:bg-gray-900">
    <nav class="bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
          <div class="flex">
            <div class="flex-shrink-0 flex items-center">
              <h1 class="text-xl font-bold text-gray-900 dark:text-white">
                📊 Log Platform
              </h1>
            </div>
            <div class="ml-6 flex space-x-8">
              <router-link
                to="/"
                class="inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium"
                active-class="border-blue-500 text-gray-900 dark:text-white"
                inactive-class="border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400"
              >
                Logs
              </router-link>
              <router-link
                to="/files"
                class="inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium"
                active-class="border-blue-500 text-gray-900 dark:text-white"
                inactive-class="border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400"
              >
                Files
              </router-link>
              <router-link
                to="/metrics"
                class="inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium"
                active-class="border-blue-500 text-gray-900 dark:text-white"
                inactive-class="border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400"
              >
                Metrics
              </router-link>
              <router-link
                to="/alerts"
                class="inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium"
                active-class="border-blue-500 text-gray-900 dark:text-white"
                inactive-class="border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400"
              >
                Alerts
              </router-link>
            </div>
          </div>
          <div class="flex items-center">
            <button
              @click="toggleTheme"
              class="p-2 rounded-lg text-gray-500 hover:bg-gray-100 dark:hover:bg-gray-700"
            >
              {{ isDark ? '☀️' : '🌙' }}
            </button>
          </div>
        </div>
      </div>
    </nav>

    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <router-view />
    </main>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'

const isDark = ref(false)

const toggleTheme = () => {
  isDark.value = !isDark.value
  document.documentElement.classList.toggle('dark', isDark.value)
  localStorage.setItem('theme', isDark.value ? 'dark' : 'light')
}

onMounted(() => {
  const savedTheme = localStorage.getItem('theme')
  if (savedTheme === 'dark' || (!savedTheme && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
    isDark.value = true
    document.documentElement.classList.add('dark')
  }
})
</script>

