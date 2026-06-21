import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '@/store/authStore'

// This is the SPA half of route protection. Every
// route below declares meta.requiresAuth or meta.guestOnly; the actual
// enforcement happens once, in the single router.beforeEach guard at the
// bottom of this file — individual views never need to check auth.user
// themselves to decide whether to render. 
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
    component: () => import('@/views/ItemDetailView.vue'),
    meta: { requiresAuth: true }
  },
  {
    path: '/report/:id?',
    name: 'Report',
    component: () => import('@/views/ReportView.vue'),
    meta: { requiresAuth: true }
  },
  {
    path: '/dashboard',
    name: 'Dashboard',
    component: () => import('@/views/DashboardView.vue'),
    meta: { requiresAuth: true }
  },
  {
    path: '/profile',
    name: 'Profile',
    component: () => import('@/views/ProfileView.vue'),
    meta: { requiresAuth: true }
  }
]

const router = createRouter({
  history: createWebHistory(),
  routes
})

// Runs before EVERY navigation, client-side route or not.
router.beforeEach((to, from, next) => {
  const auth = useAuthStore()

  // Protected page (Dashboard, Report, Profile, etc.) + no token -> bounce
  // to Login instead of ever rendering the page.
  if (to.meta.requiresAuth && !auth.isLoggedIn) {
    return next({ name: 'Login' })
  }
  // Login/Register pages don't make sense once you're already signed in.
  if (to.meta.guestOnly && auth.isLoggedIn) {
    return next({ name: 'Home' })
  }
  next()
})

export default router
