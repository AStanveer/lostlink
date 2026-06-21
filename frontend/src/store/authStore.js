import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import api from '@/services/api'

export const useAuthStore = defineStore('auth', () => {
  const token = ref(localStorage.getItem('token') || null)
  /** @type {User} */
  const user = ref(JSON.parse(localStorage.getItem('user') || 'null'))

  const isLoggedIn = computed(() => !!token.value)

  /**
   * @param {string} email
   * @param {string} password
   * @returns {Promise<void>}
   * */
  async function login(email, password) {
    /** @type {import('axios').AxiosResponse<AuthResponse>} */
    const res = await api.post('/login', { email, password })

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

  return { token, user, isLoggedIn, login, register, logout }
})
