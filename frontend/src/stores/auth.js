import { defineStore } from 'pinia'
import { authService } from '../services/authService.js'

export const useAuthStore = defineStore('auth', {
  state() {
    return {
      user: null,
      isAuthenticated: false,
      loading: false,
      error: null
    }
  },

  getters: {
    isFreelancer(state) {
      return state.user && state.user.role === 'freelancer'
    },
    
    isClient(state) {
      return state.user && state.user.role === 'client'
    },
    
    userName(state) {
      if (!state.user) return 'User'
      if (state.user.display_name) return state.user.display_name
      return state.user.first_name + ' ' + state.user.last_name
    },
    
    userInitials(state) {
      if (!state.user) return 'U'
      const first = state.user.first_name ? state.user.first_name[0] : ''
      const last = state.user.last_name ? state.user.last_name[0] : ''
      return (first + last).toUpperCase() || 'U'
    },
    
    hasError(state) {
      return state.error !== null
    }
  },

  actions: {
    initAuth() {
      const user = authService.getCurrentUser()
      const isAuth = authService.isAuthenticated()
      
      if (user && isAuth) {
        this.user = user
        this.isAuthenticated = true
      }
    },

    clearAuth() {
      this.user = null
      this.isAuthenticated = false
      this.error = null
    },

    clearError() {
      this.error = null
    },

    async login(credentials) {
      this.loading = true
      this.error = null
      
      try {
        const result = await authService.login(credentials)
        
        if (result.success) {
          this.user = result.user
          this.isAuthenticated = true
          return { success: true, message: result.message }
        } else {
          this.error = result.error
          return { success: false, error: result.error }
        }
      } catch (error) {
        this.error = 'Có lỗi xảy ra'
        return { success: false, error: 'Có lỗi xảy ra' }
      } finally {
        this.loading = false
      }
    },

    async register(userData) {
      this.loading = true
      this.error = null
      
      try {
        const result = await authService.register(userData)
        return result
      } catch (error) {
        return { success: false, error: 'Có lỗi xảy ra' }
      } finally {
        this.loading = false
      }
    },

    logout() {
      authService.logout()
      this.clearAuth()
      return { success: true }
    }
  }
})