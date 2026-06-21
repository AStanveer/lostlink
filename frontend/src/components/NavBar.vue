<template>
  <nav class="navbar">
    <RouterLink to="/" class="brand" @click="closeMenu">LostLink</RouterLink>

    <button class="nav-toggle" @click="menuOpen = !menuOpen" :aria-expanded="menuOpen" aria-label="Toggle navigation menu">
      <span></span>
      <span></span>
      <span></span>
    </button>

    <div class="nav-links" :class="{ open: menuOpen }">
      <RouterLink to="/" @click="closeMenu">Home</RouterLink>
      <RouterLink to="/items" @click="closeMenu">Browse Items</RouterLink>
      <template v-if="auth.isLoggedIn">
        <RouterLink to="/report" @click="closeMenu">Report Item</RouterLink>
        <RouterLink to="/dashboard" @click="closeMenu">Dashboard</RouterLink>
        <RouterLink to="/profile" @click="closeMenu">Profile</RouterLink>

        <div class="notif-wrap" ref="notifWrapRef">
          <button class="notif-bell" @click.stop="toggleNotifPanel" aria-label="Notifications">
            🔔
            <span v-if="notif.unreadCount > 0" class="notif-badge">{{ notif.unreadCount > 9 ? '9+' : notif.unreadCount }}</span>
          </button>
          <div v-if="notifOpen" class="notif-panel">
            <div class="notif-panel-header">
              <span>Notifications</span>
              <button v-if="notif.unreadCount > 0" class="notif-markall" @click="notif.markAllRead()">Mark all read</button>
            </div>
            <div v-if="notif.notifications.length === 0" class="notif-empty">No notifications yet.</div>
            <RouterLink
                v-for="n in notif.notifications"
                :key="n.notification_id"
                :to="n.link || '/dashboard'"
                class="notif-item"
                :class="{ unread: !n.is_read }"
                @click="handleNotifClick(n)"
            >
              <p class="notif-message">{{ n.message }}</p>
              <p class="notif-time">{{ timeAgo(n.created_at) }}</p>
            </RouterLink>
          </div>
        </div>

        <button @click="handleLogout" class="btn-logout">Logout</button>
      </template>
      <template v-else>
        <RouterLink to="/login" @click="closeMenu">Login</RouterLink>
        <RouterLink to="/register" @click="closeMenu">Register</RouterLink>
      </template>
    </div>
  </nav>
</template>

<script setup>
import { ref, onMounted, onBeforeUnmount, watch } from 'vue'
import { useAuthStore } from '@/store/authStore'
import { useNotificationStore } from '@/store/notificationStore'
import { useRouter } from 'vue-router'

const auth = useAuthStore()
const notif = useNotificationStore()
const router = useRouter()
const menuOpen = ref(false)
const notifOpen = ref(false)
const notifWrapRef = ref(null)

function closeMenu() {
  menuOpen.value = false
}

function toggleNotifPanel() {
  notifOpen.value = !notifOpen.value
  if (notifOpen.value) notif.fetchNotifications()
}

function handleNotifClick(n) {
  notif.markRead(n.notification_id)
  notifOpen.value = false
  closeMenu()
}

function handleClickOutside(e) {
  if (notifWrapRef.value && !notifWrapRef.value.contains(e.target)) {
    notifOpen.value = false
  }
}

function timeAgo(dateStr) {
  const diffMs = Date.now() - new Date(dateStr).getTime()
  const minutes = Math.floor(diffMs / 60000)
  if (minutes < 1) return 'Just now'
  if (minutes < 60) return `${minutes}m ago`
  const hours = Math.floor(minutes / 60)
  if (hours < 24) return `${hours}h ago`
  const days = Math.floor(hours / 24)
  return `${days}d ago`
}

onMounted(() => {
  document.addEventListener('click', handleClickOutside)
  if (auth.isLoggedIn) notif.startPolling()
})

onBeforeUnmount(() => {
  document.removeEventListener('click', handleClickOutside)
  notif.stopPolling()
})

watch(() => auth.isLoggedIn, (loggedIn) => {
  if (loggedIn) notif.startPolling()
  else notif.stopPolling()
})

function handleLogout() {
  closeMenu()
  auth.logout()
  router.push('/login')
}
</script>
