import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import api from '@/services/api'

// This store is the client-side half of the JWT flow.
// The token lives in localStorage, which is WHY every
// request needs it attached manually. Reading from localStorage at
// init means a page refresh doesn't log you out: the token survives, and
// isLoggedIn picks it straight back up.
export const useAuthStore = defineStore('auth', () => {
  const token = ref(localStorage.getItem('token') || null)
  /** @type {User} */
  const user = ref(JSON.parse(localStorage.getItem('user') || 'null'))

  // The single source of truth the router guard (router/index.js) and
  // every "v-if=auth.isLoggedIn" in the UI reads from.
  const isLoggedIn = computed(() => !!token.value)

  /**
   * @param {string} email
   * @param {string} password
   * @returns {Promise<void>}
   * */
  async function login(email, password) {
    /** @type {import('axios').AxiosResponse<AuthResponse>} */
    const res = await api.post('/login', { email, password })

    // Backend returns { token, user } — store both in memory (reactive)
    // AND in localStorage (persistent across refreshes/tabs).
    token.value = res.data.token
    user.value = res.data.user

    localStorage.setItem('token', token.value)
    localStorage.setItem('user', JSON.stringify(user.value))
  }

  /**
   * @param {string} name
   * @param {string} email
   * @param {string} password
   * */
  async function register(name, email, password) {
    await api.post('/register', { name, email, password })
  }

  function logout() {
    token.value = null
    user.value = null
    localStorage.removeItem('token')
    localStorage.removeItem('user')
  }

  /**
   * @param {{ name?: string, email?: string }} updates
   * */
  function updateUser(updates) {
    user.value = { ...user.value, ...updates }
    localStorage.setItem('user', JSON.stringify(user.value))
  }

  return { token, user, isLoggedIn, login, register, logout, updateUser }
})
