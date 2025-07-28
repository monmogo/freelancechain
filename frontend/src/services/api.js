// src/services/api.js - Fixed version
import axios from 'axios'

const api = axios.create({
  baseURL: 'http://localhost/freelancechain/backend/api',
  timeout: 10000,
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json'
  }
})

// Request interceptor
api.interceptors.request.use(
  (config) => {
    const token = localStorage.getItem('freelancechain_token')
    if (token) {
      config.headers.Authorization = `Bearer ${token}`
    }
    console.log('API Request:', {
      method: config.method,
      url: config.url,
      baseURL: config.baseURL,
      fullURL: config.baseURL + config.url,
      data: config.data
    })
    return config
  },
  (error) => {
    console.error('Request interceptor error:', error)
    return Promise.reject(error)
  }
)

// Response interceptor - FIXED
api.interceptors.response.use(
  (response) => {
    console.log('API Response:', {
      status: response.status,
      data: response.data
    })
    return response
  },
  (error) => {
    console.error('API Error:', {
      status: error.response?.status,
      data: error.response?.data,
      message: error.message
    })
    
    // CHỈ redirect khi đã login và token thực sự expired
    // KHÔNG redirect khi đang login và nhận 401 do sai credentials
    if (error.response?.status === 401) {
      const isLoginRequest = error.config?.url?.includes('/auth/login.php')
      const hasToken = localStorage.getItem('freelancechain_token')
      
      // Chỉ clear session khi KHÔNG phải login request VÀ có token
      if (!isLoginRequest && hasToken) {
        console.log('Token expired, clearing session...')
        localStorage.removeItem('freelancechain_token')
        localStorage.removeItem('freelancechain_user')
        window.location.href = '/login'
        return Promise.reject(new Error('Phiên đăng nhập đã hết hạn'))
      }
      
      // Với login request, chỉ trả về error bình thường
      if (isLoginRequest) {
        console.log('Login failed - invalid credentials')
        return Promise.reject(error)
      }
    }
    
    // Handle other errors
    if (error.code === 'ECONNABORTED') {
      return Promise.reject(new Error('Kết nối timeout. Vui lòng thử lại.'))
    }
    
    if (!error.response) {
      return Promise.reject(new Error('Lỗi kết nối mạng. Kiểm tra XAMPP có chạy không.'))
    }
    
    return Promise.reject(error)
  }
)

export default api