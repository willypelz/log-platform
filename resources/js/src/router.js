import { createRouter, createWebHistory } from 'vue-router'
import LogsPage from './pages/LogsPage.vue'
import MetricsPage from './pages/MetricsPage.vue'
import AlertsPage from './pages/AlertsPage.vue'
import FilesPage from './pages/FilesPage.vue'

const routes = [
  {
    path: '/',
    name: 'logs',
    component: LogsPage,
  },
  {
    path: '/files',
    name: 'files',
    component: FilesPage,
  },
  {
    path: '/metrics',
    name: 'metrics',
    component: MetricsPage,
  },
  {
    path: '/alerts',
    name: 'alerts',
    component: AlertsPage,
  },
]

const router = createRouter({
  history: createWebHistory('/log-platform'),
  routes,
})

export default router

