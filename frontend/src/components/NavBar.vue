<template>
  <nav class="navbar">
    <RouterLink to="/" class="brand">LostLink</RouterLink>
    <div class="nav-links">
      <RouterLink to="/">Home</RouterLink>
      <RouterLink to="/items">Browse Items</RouterLink>
      <template v-if="auth.isLoggedIn">
        <RouterLink to="/report">Report Item</RouterLink>
        <RouterLink to="/dashboard">Dashboard</RouterLink>
        <button @click="handleLogout" class="btn-logout">Logout</button>
      </template>
      <template v-else>
        <RouterLink to="/login">Login</RouterLink>
        <RouterLink to="/register">Register</RouterLink>
      </template>
    </div>
  </nav>
</template>

<script setup>
import { useAuthStore } from '@/store/authStore'
import { useRouter } from 'vue-router'

const auth = useAuthStore()
const router = useRouter()

function handleLogout() {
  auth.logout()
  router.push('/')
}
</script>
