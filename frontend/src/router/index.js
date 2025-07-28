// src/router/index.js
import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '@/stores/auth.js'

// Import components
import LoginForm from '@/components/auth/LoginForm.vue'
import RegisterForm from '@/components/auth/RegisterForm.vue'
import Dashboard from '@/views/Home.vue'
import FreelancerDashboard from '@/views/FreelancerDashboard.vue'
import ClientDashboard from '@/views/ClientDashboard.vue'

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: [
    {
      path: '/',
      name: 'home',
      redirect: '/home'
    },
    {
      path: '/login',
      name: 'login',
      component: LoginForm,
      meta: { 
        requiresGuest: true,
        title: 'Đăng nhập'
      }
    },
    {
      path: '/register',
      name: 'register',
      component: RegisterForm,
      meta: { 
        requiresGuest: true,
        title: 'Đăng ký'
      }
    },
    {
      path: '/dashboard',
      name: 'dashboard',
      component: Dashboard,
      meta: { 
        requiresAuth: true,
        title: 'Dashboard'
      }
    },
    {
      path: '/freelancer/dashboard',
      name: 'freelancer-dashboard',
      component: FreelancerDashboard,
      meta: { 
        requiresAuth: true,
        requiresRole: 'freelancer',
        title: 'Freelancer Dashboard'
      }
    },
    {
      path: '/client/dashboard',
      name: 'client-dashboard',
      component: ClientDashboard,
      meta: { 
        requiresAuth: true,
        requiresRole: 'client',
        title: 'Client Dashboard'
      }
    },
    {
      path: '/profile',
      name: 'profile',
      component: () => import('@/views/Profile.vue'),
      meta: { 
        requiresAuth: true,
        title: 'Hồ sơ cá nhân'
      }
    }
  ]
})

// Navigation guards
router.beforeEach((to, from, next) => {
  const authStore = useAuthStore()
  
  // Initialize auth if not already done
  if (!authStore.isAuthenticated && !authStore.user) {
    authStore.initAuth()
  }
  
  // Set page title
  if (to.meta.title) {
    document.title = `${to.meta.title} - FreelanceChain`
  }
  
  // Check authentication requirements
  if (to.meta.requiresAuth && !authStore.isAuthenticated) {
    next('/login')
    return
  }
  
  // Check guest requirements (redirect authenticated users)
  if (to.meta.requiresGuest && authStore.isAuthenticated) {
    next('/dashboard')
    return
  }
  
  // Check role requirements
  if (to.meta.requiresRole && authStore.user?.role !== to.meta.requiresRole) {
    // Redirect to appropriate dashboard based on role
    if (authStore.isClient) {
      next('/client/dashboard')
    } else if (authStore.isFreelancer) {
      next('/freelancer/dashboard')
    } else {
      next('/dashboard')
    }
    return
  }
  
  next()
})

export default router