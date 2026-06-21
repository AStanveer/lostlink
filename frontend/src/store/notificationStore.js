import { defineStore } from 'pinia'
import { ref } from 'vue'
import api from '@/services/api'

export const useNotificationStore = defineStore('notifications', () => {
  const notifications = ref([])
  const unreadCount = ref(0)
  let pollTimer = null

  async function fetchNotifications() {
    try {
      const { data } = await api.get('/notifications')
      notifications.value = data.notifications
      unreadCount.value = data.unread_count
    } catch (e) {
      // Notifications are non-critical — fail silently
    }
  }

  async function markRead(id) {
    const n = notifications.value.find(n => n.notification_id === id)
    if (n && !n.is_read) {
      n.is_read = 1
      unreadCount.value = Math.max(0, unreadCount.value - 1)
    }
    try {
      await api.put(`/notifications/${id}/read`)
    } catch (e) {
      // ignore
    }
  }

  async function markAllRead() {
    notifications.value.forEach(n => { n.is_read = 1 })
    unreadCount.value = 0
    try {
      await api.put('/notifications/read-all')
    } catch (e) {
      // ignore
    }
  }

  function startPolling() {
    fetchNotifications()
    if (pollTimer) return
    pollTimer = setInterval(fetchNotifications, 20000)
  }

  function stopPolling() {
    if (pollTimer) {
      clearInterval(pollTimer)
      pollTimer = null
    }
    notifications.value = []
    unreadCount.value = 0
  }

  return { notifications, unreadCount, fetchNotifications, markRead, markAllRead, startPolling, stopPolling }
})
