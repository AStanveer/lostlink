import axios from 'axios'

// Every API call in the app goes through this one
// axios instance, which is what makes the two interceptors below work
// globally instead of having to be repeated at every call site.

const api = axios.create({
  baseURL: '/api',
  headers: {
    'Content-Type': 'application/json'
  }
})

// This is the OTHER half of the JWT flow. Every outgoing request gets the token
// attached here automatically — controllers on the backend then read it
// back via JwtMiddleware. No component ever manually sets this header.
api.interceptors.request.use((config) => {
  const token = localStorage.getItem('token')
  if (token) {
    config.headers.Authorization = `Bearer ${token}`
  }
  return config
})

// Handle 401 globally — but not for the login request itself, where a 401
// just means "wrong credentials" and should be shown inline, not redirected.
api.interceptors.response.use(
  (response) => response,
  (error) => {
    const isLoginRequest = error.config?.url?.includes('/login')
    if (error.response?.status === 401 && !isLoginRequest) {
      localStorage.removeItem('token')
      localStorage.removeItem('user')
      window.location.href = '/login'
    }
    return Promise.reject(error)
  }
)

export default api
