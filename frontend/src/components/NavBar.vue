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
import { ref } from 'vue'
import { useAuthStore } from '@/store/authStore'
import { useRouter } from 'vue-router'

const auth = useAuthStore()
const router = useRouter()
const menuOpen = ref(false)

function closeMenu() {
  menuOpen.value = false
}

function handleLogout() {
  closeMenu()
  auth.logout()
  router.push('/')
}
</script>
