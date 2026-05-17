import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '@/store/authStore'

const routes = [
  {
    path: '/',
    name: 'Home',
    component: () => import('@/views/HomeView.vue'),
    meta: { requiresAuth: true }
  },
  {
    path: '/login',
    name: 'Login',
    component: () => import('@/views/LoginView.vue'),
    meta: { guestOnly: true }
  },
  {
    path: '/register',
    name: 'Register',
    component: () => import('@/views/RegisterView.vue'),
    meta: { guestOnly: true }
  },
  {
    path: '/items',
    name: 'Browse',
    component: () => import('@/views/BrowseView.vue')
  },
  {
    path: '/items/:id',
    name: 'ItemDetail',
    component: () => import('@/views/ItemDetailView.vue')
  },
  {
    path: '/report',
    name: 'Report',
    component: () => import('@/views/ReportView.vue'),
    meta: { requiresAuth: true }
  },
  {
    path: '/matches',
    name: 'Matches',
    component: () => import('@/views/MatchesView.vue'),
    meta: { requiresAuth: true }
  },
  {
    path: '/dashboard',
    name: 'Dashboard',
    component: () => import('@/views/DashboardView.vue'),
    meta: { requiresAuth: true }
  }
]

const router = createRouter({
  history: createWebHistory(),
  routes
})

router.beforeEach((to, from, next) => {
  const auth = useAuthStore()

  if (to.meta.requiresAuth && !auth.isLoggedIn) {
    return next({ name: 'Login' })
  }
  if (to.meta.guestOnly && auth.isLoggedIn) {
    return next({ name: 'Home' })
  }
  next()
})

export default router
