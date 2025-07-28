// src/services/authService.js - Updated for PHP Backend
import api from './api.js'

export const authService = {
  async login(credentials) {
    try {
      console.log('AuthService: Attempting login with:', { email: credentials.email })
      
      const response = await api.post('/auth/login.php', {
        email: credentials.email,
        password: credentials.password
      })
      
      console.log('AuthService: Login response:', response.data)
      
      // Backend trả về { success: true, message: ..., token: ..., user: ... }
      if (response.data.success === true) {
        const data = response.data
        
        // Save token
        if (data.token) {
          localStorage.setItem('freelancechain_token', data.token)
        }
        
        // Save user info với structure từ backend
        if (data.user) {
          const userInfo = {
            id: data.user.id,
            email: data.user.email,
            role: data.user.role,
            display_name: data.user.display_name,
            avatar: data.user.avatar,
            // Parse display_name thành first_name và last_name
            first_name: data.user.display_name ? data.user.display_name.split(' ')[0] : '',
            last_name: data.user.display_name ? data.user.display_name.split(' ').slice(1).join(' ') : ''
          }
          localStorage.setItem('freelancechain_user', JSON.stringify(userInfo))
        }
        
        return { 
          success: true, 
          user: data.user,
          token: data.token,
          message: data.message || 'Đăng nhập thành công!'
        }
      } else {
        // Backend trả về { error: ... }
        return { 
          success: false, 
          error: response.data.error || response.data.message || 'Đăng nhập thất bại'
        }
      }
    } catch (error) {
      console.error('AuthService: Login error:', error)
      
      if (error.response && error.response.data) {
        // Backend error response
        const errorData = error.response.data
        return { 
          success: false, 
          error: errorData.error || errorData.message || 'Có lỗi xảy ra khi đăng nhập'
        }
      }
      
      if (error.message) {
        return { success: false, error: error.message }
      }
      
      return { 
        success: false, 
        error: 'Không thể kết nối đến máy chủ. Vui lòng thử lại sau.'
      }
    }
  },

  async register(userData) {
    try {
      console.log('AuthService: Attempting registration with:', {
        email: userData.email,
        role: userData.role,
        first_name: userData.firstName,
        last_name: userData.lastName
      })
      
      const response = await api.post('/auth/register.php', {
        email: userData.email,
        password: userData.password,
        first_name: userData.firstName,
        last_name: userData.lastName,
        role: userData.role || 'freelancer'
      })
      
      console.log('AuthService: Registration response:', response.data)
      
      // Backend trả về { success: true, message: ..., token: ..., user: ... }
      if (response.data.success === true) {
        return { 
          success: true, 
          data: response.data,
          message: response.data.message || 'Đăng ký thành công!'
        }
      } else {
        // Backend trả về { error: ... }
        return { 
          success: false, 
          error: response.data.error || response.data.message || 'Đăng ký thất bại'
        }
      }
    } catch (error) {
      console.error('AuthService: Registration error:', error)
      
      if (error.response && error.response.data) {
        // Backend error response
        const errorData = error.response.data
        return { 
          success: false, 
          error: errorData.error || errorData.message || 'Đăng ký thất bại'
        }
      }
      
      if (error.message) {
        return { success: false, error: error.message }
      }
      
      return { 
        success: false, 
        error: 'Không thể kết nối đến máy chủ. Vui lòng thử lại sau.'
      }
    }
  },

  logout() {
    try {
      localStorage.removeItem('freelancechain_token')
      localStorage.removeItem('freelancechain_user')
      return { success: true, message: 'Đăng xuất thành công' }
    } catch (error) {
      console.error('Logout error:', error)
      return { success: false, error: 'Có lỗi xảy ra khi đăng xuất' }
    }
  },

  getCurrentUser() {
    try {
      const user = localStorage.getItem('freelancechain_user')
      return user ? JSON.parse(user) : null
    } catch (error) {
      console.error('Error getting current user:', error)
      return null
    }
  },

  getToken() {
    return localStorage.getItem('freelancechain_token')
  },

  isAuthenticated() {
    const token = this.getToken()
    const user = this.getCurrentUser()
    return !!(token && user)
  },

  // Get user profile from API (nếu có endpoint này)
  async getProfile() {
    try {
      const response = await api.get('/users/profile.php')
      
      if (response.data.success === true) {
        // Update stored user data
        if (response.data.user) {
          localStorage.setItem('freelancechain_user', JSON.stringify(response.data.user))
        }
        
        return { success: true, data: response.data.user }
      } else {
        return { 
          success: false, 
          error: response.data.error || response.data.message || 'Không thể tải thông tin profile'
        }
      }
    } catch (error) {
      console.error('Get profile error:', error)
      return { 
        success: false, 
        error: error.response?.data?.error || 'Không thể tải thông tin profile'
      }
    }
  }
}