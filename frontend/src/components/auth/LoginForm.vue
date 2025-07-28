<!-- src/components/auth/LoginForm.vue - Enhanced UI/UX -->
<template>
    <div class="auth-container">
      <div class="auth-background">
        <div class="auth-pattern"></div>
      </div>
      
      <div class="container-fluid vh-100 d-flex align-items-center justify-content-center">
        <div class="row w-100 max-w-1200">
          <!-- Left Side - Branding -->
          <div class="col-lg-6 d-none d-lg-flex auth-branding">
            <div class="branding-content">
              <div class="brand-logo mb-4">
                <i class="bi bi-hexagon-fill text-primary display-3"></i>
              </div>
              <h1 class="display-4 fw-bold text-white mb-4">
                Chào mừng trở lại
              </h1>
              <p class="lead text-white-50 mb-5">
                Kết nối với hàng nghìn freelancer tài năng và khách hàng tin cậy trên FreelanceChain
              </p>
              
              <!-- Features -->
              <div class="features-list">
                <div class="feature-item d-flex align-items-center mb-3">
                  <div class="feature-icon me-3">
                    <i class="bi bi-shield-check text-success"></i>
                  </div>
                  <div>
                    <h6 class="text-white mb-1">Bảo mật tuyệt đối</h6>
                    <small class="text-white-50">Thanh toán an toàn với blockchain</small>
                  </div>
                </div>
                
                <div class="feature-item d-flex align-items-center mb-3">
                  <div class="feature-icon me-3">
                    <i class="bi bi-people text-info"></i>
                  </div>
                  <div>
                    <h6 class="text-white mb-1">Cộng đồng chất lượng</h6>
                    <small class="text-white-50">Freelancer được xác thực kỹ lưỡng</small>
                  </div>
                </div>
                
                <div class="feature-item d-flex align-items-center">
                  <div class="feature-icon me-3">
                    <i class="bi bi-lightning-charge text-warning"></i>
                  </div>
                  <div>
                    <h6 class="text-white mb-1">Giao dịch nhanh chóng</h6>
                    <small class="text-white-50">Smart contract tự động</small>
                  </div>
                </div>
              </div>
            </div>
          </div>
          
          <!-- Right Side - Login Form -->
          <div class="col-lg-6 d-flex align-items-center">
            <div class="auth-form-container w-100">
              <div class="auth-form-card">
                <!-- Header -->
                <div class="auth-header text-center mb-4">
                  <div class="auth-icon mb-3">
                    <i class="bi bi-person-check text-primary"></i>
                  </div>
                  <h2 class="fw-bold mb-2">Đăng nhập</h2>
                  <p class="text-muted">
                    Chưa có tài khoản? 
                    <router-link to="/register" class="text-decoration-none fw-semibold">
                      Đăng ký ngay
                    </router-link>
                  </p>
                </div>
                
                <!-- Error Alert -->
                <div v-if="authStore.hasError" class="alert alert-danger alert-modern" role="alert">
                  <div class="d-flex align-items-center">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>
                    <div class="flex-grow-1">{{ authStore.error }}</div>
                    <button type="button" class="btn-close" @click="authStore.clearError()"></button>
                  </div>
                </div>
                
                <!-- Login Form -->
                <form @submit.prevent="handleLogin" class="auth-form">
                  <div class="form-floating mb-3">
                    <input
                      id="email"
                      v-model="form.email"
                      type="email"
                      class="form-control form-control-modern"
                      :class="{ 'is-invalid': errors.email }"
                      placeholder="Email"
                      required
                      :disabled="authStore.loading"
                    />
                    <label for="email">
                      <i class="bi bi-envelope me-2"></i>Email
                    </label>
                    <div v-if="errors.email" class="invalid-feedback">
                      {{ errors.email }}
                    </div>
                  </div>
                  
                  <div class="form-floating mb-3">
                    <input
                      id="password"
                      v-model="form.password"
                      :type="showPassword ? 'text' : 'password'"
                      class="form-control form-control-modern"
                      :class="{ 'is-invalid': errors.password }"
                      placeholder="Mật khẩu"
                      required
                      :disabled="authStore.loading"
                    />
                    <label for="password">
                      <i class="bi bi-lock me-2"></i>Mật khẩu
                    </label>
                    <button
                      type="button"
                      class="password-toggle"
                      @click="showPassword = !showPassword"
                      :disabled="authStore.loading"
                    >
                      <i :class="showPassword ? 'bi bi-eye-slash' : 'bi bi-eye'"></i>
                    </button>
                    <div v-if="errors.password" class="invalid-feedback">
                      {{ errors.password }}
                    </div>
                  </div>
  
                  <div class="form-options d-flex justify-content-between align-items-center mb-4">
                    <div class="form-check">
                      <input
                        id="remember"
                        v-model="form.remember"
                        type="checkbox"
                        class="form-check-input"
                        :disabled="authStore.loading"
                      />
                      <label for="remember" class="form-check-label">
                        Ghi nhớ đăng nhập
                      </label>
                    </div>
                    <a href="#" class="forgot-password-link">
                      Quên mật khẩu?
                    </a>
                  </div>
  
                  <button
                    type="submit"
                    :disabled="authStore.loading || !isFormValid"
                    class="btn btn-primary btn-modern w-100 mb-4"
                  >
                    <span v-if="authStore.loading" class="spinner-border spinner-border-sm me-2"></span>
                    <i v-else class="bi bi-box-arrow-in-right me-2"></i>
                    {{ authStore.loading ? 'Đang đăng nhập...' : 'Đăng nhập' }}
                  </button>
                </form>
                
                <!-- Demo Credentials -->
                <div class="demo-section">
                  <div class="demo-divider mb-3">
                    <span>Tài khoản demo</span>
                  </div>
                  
                  <div class="demo-card">
                    <div class="demo-header mb-2">
                      <i class="bi bi-info-circle text-info me-2"></i>
                      <small class="fw-semibold">Thông tin đăng nhập demo</small>
                    </div>
                    <div class="demo-credentials mb-3">
                      <div class="credential-item">
                        <small class="text-muted">Email:</small>
                        <code class="ms-2">john.dev@email.com</code>
                      </div>
                      <div class="credential-item">
                        <small class="text-muted">Password:</small>
                        <code class="ms-2">password123</code>
                      </div>
                    </div>
                    <button 
                      @click="fillDemoCredentials" 
                      class="btn btn-outline-secondary btn-sm w-100"
                      :disabled="authStore.loading"
                    >
                      <i class="bi bi-arrow-down-circle me-1"></i>
                      Sử dụng thông tin demo
                    </button>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </template>
  
  <script setup>
  import { ref, reactive, computed, onMounted } from 'vue'
  import { useRouter } from 'vue-router'
  import { useAuthStore } from '@/stores/auth.js'
  import { toastService } from '@/services/toastService.js'
  
  const router = useRouter()
  const authStore = useAuthStore()
  
  const showPassword = ref(false)
  
  const form = reactive({
    email: '',
    password: '',
    remember: false
  })
  
  const errors = reactive({
    email: '',
    password: ''
  })
  
  const isFormValid = computed(() => {
    return form.email && 
           form.password && 
           !errors.email && 
           !errors.password
  })
  
  const fillDemoCredentials = () => {
    form.email = 'john.dev@email.com'
    form.password = 'password123'
  }
  
  const validateForm = () => {
    errors.email = ''
    errors.password = ''
  
    if (!form.email) {
      errors.email = 'Email là bắt buộc'
    } else if (!/\S+@\S+\.\S+/.test(form.email)) {
      errors.email = 'Email không hợp lệ'
    }
  
    if (!form.password) {
      errors.password = 'Mật khẩu là bắt buộc'
    } else if (form.password.length < 6) {
      errors.password = 'Mật khẩu phải có ít nhất 6 ký tự'
    }
  
    return !errors.email && !errors.password
  }
  
  const handleLogin = async () => {
    if (!validateForm()) {
      toastService.error('Vui lòng kiểm tra lại thông tin đăng nhập')
      return
    }
  
    authStore.clearError()
  
    try {
      const result = await authStore.login({
        email: form.email.trim(),
        password: form.password
      })
      
      if (result.success) {
        toastService.success(result.message || 'Đăng nhập thành công!')
        
        setTimeout(() => {
          if (authStore.isClient) {
            router.push('/client/dashboard')
          } else if (authStore.isFreelancer) {
            router.push('/freelancer/dashboard')
          } else {
            router.push('/dashboard')
          }
        }, 1000)
      } else {
        toastService.error(result.error)
      }
    } catch (error) {
      console.error('Login error:', error)
      toastService.error('Có lỗi xảy ra. Vui lòng thử lại sau.')
    }
  }
  
  onMounted(() => {
    authStore.initAuth()
    
    if (authStore.isAuthenticated) {
      if (authStore.isClient) {
        router.push('/client/dashboard')
      } else if (authStore.isFreelancer) {
        router.push('/freelancer/dashboard')
      } else {
        router.push('/dashboard')
      }
    }
  })
  </script>
  
  <style scoped>
  .auth-container {
    position: relative;
    min-height: 100vh;
    overflow: hidden;
  }
  
  .auth-background {
    position: absolute;
    top: 0;
    left: 0;
    width: 50%;
    height: 100%;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    z-index: 1;
  }
  
  .auth-pattern {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-image: 
      radial-gradient(circle at 25% 25%, rgba(255,255,255,0.1) 2px, transparent 2px),
      radial-gradient(circle at 75% 75%, rgba(255,255,255,0.1) 2px, transparent 2px);
    background-size: 60px 60px;
    animation: float 20s ease-in-out infinite;
  }
  
  @keyframes float {
    0%, 100% { transform: translateY(0px) rotate(0deg); }
    50% { transform: translateY(-20px) rotate(3deg); }
  }
  
  .max-w-1200 {
    max-width: 1200px;
    margin: 0 auto;
  }
  
  .auth-branding {
    position: relative;
    z-index: 2;
    padding: 2rem;
    align-items: center;
    justify-content: center;
  }
  
  .branding-content {
    max-width: 400px;
  }
  
  .feature-icon {
    width: 40px;
    height: 40px;
    background: rgba(255,255,255,0.1);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.25rem;
  }
  
  .auth-form-container {
    position: relative;
    z-index: 2;
    padding: 2rem;
  }
  
  .auth-form-card {
    background: rgba(255,255,255,0.95);
    backdrop-filter: blur(20px);
    border-radius: 24px;
    padding: 3rem;
    box-shadow: 0 20px 60px rgba(0,0,0,0.1);
    border: 1px solid rgba(255,255,255,0.2);
    max-width: 480px;
    margin: 0 auto;
  }
  
  .auth-icon {
    width: 64px;
    height: 64px;
    background: linear-gradient(135deg, #667eea, #764ba2);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    color: white;
    margin: 0 auto;
  }
  
  .form-control-modern {
    border: 2px solid #e9ecef;
    border-radius: 12px;
    padding: 1rem 1rem 1rem 3rem;
    background: rgba(255,255,255,0.8);
    transition: all 0.3s ease;
    font-size: 1rem;
  }
  
  .form-control-modern:focus {
    border-color: #667eea;
    box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
    background: white;
  }
  
  .form-floating > label {
    padding: 1rem 1rem 1rem 3rem;
    color: #6c757d;
    font-weight: 500;
  }
  
  .form-floating > .form-control:focus ~ label,
  .form-floating > .form-control:not(:placeholder-shown) ~ label {
    color: #667eea;
    transform: scale(0.85) translateY(-0.5rem) translateX(0.15rem);
  }
  
  .password-toggle {
    position: absolute;
    right: 1rem;
    top: 50%;
    transform: translateY(-50%);
    background: none;
    border: none;
    color: #6c757d;
    font-size: 1.1rem;
    cursor: pointer;
    z-index: 10;
    transition: color 0.3s ease;
  }
  
  .password-toggle:hover {
    color: #667eea;
  }
  
  .form-check-input:checked {
    background-color: #667eea;
    border-color: #667eea;
  }
  
  .forgot-password-link {
    color: #667eea;
    text-decoration: none;
    font-size: 0.875rem;
    font-weight: 500;
    transition: color 0.3s ease;
  }
  
  .forgot-password-link:hover {
    color: #5a6fd8;
  }
  
  .btn-modern {
    border-radius: 12px;
    padding: 1rem 2rem;
    font-weight: 600;
    font-size: 1rem;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border: none;
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
  }
  
  .btn-modern::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
    transition: left 0.6s ease;
  }
  
  .btn-modern:hover::before {
    left: 100%;
  }
  
  .btn-modern:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(102, 126, 234, 0.3);
  }
  
  .btn-modern:disabled {
    opacity: 0.7;
    transform: none;
    box-shadow: none;
  }
  
  .alert-modern {
    border-radius: 12px;
    border: none;
    background: linear-gradient(135deg, #f8d7da, #f5c6cb);
    color: #721c24;
  }
  
  .demo-section {
    margin-top: 2rem;
    padding-top: 2rem;
    border-top: 1px solid #e9ecef;
  }
  
  .demo-divider {
    position: relative;
    text-align: center;
    color: #6c757d;
    font-size: 0.875rem;
    font-weight: 500;
  }
  
  .demo-divider span {
    background: white;
    padding: 0 1rem;
  }
  
  .demo-divider::before {
    content: '';
    position: absolute;
    top: 50%;
    left: 0;
    right: 0;
    height: 1px;
    background: #e9ecef;
    z-index: -1;
  }
  
  .demo-card {
    background: #f8f9fa;
    border-radius: 12px;
    padding: 1.25rem;
    border: 1px solid #e9ecef;
  }
  
  .credential-item {
    display: flex;
    align-items: center;
    margin-bottom: 0.5rem;
  }
  
  .credential-item:last-child {
    margin-bottom: 0;
  }
  
  code {
    background: #e9ecef;
    color: #495057;
    padding: 0.25rem 0.5rem;
    border-radius: 4px;
    font-size: 0.875rem;
  }
  
  /* Mobile Styles */
  @media (max-width: 991.98px) {
    .auth-background {
      width: 100%;
      height: 40%;
    }
    
    .auth-form-card {
      margin-top: -2rem;
      position: relative;
      z-index: 3;
    }
    
    .branding-content {
      text-align: center;
      padding: 2rem 1rem;
    }
    
    .auth-form-container {
      padding: 1rem;
    }
    
    .auth-form-card {
      padding: 2rem 1.5rem;
    }
  }
  
  @media (max-width: 576px) {
    .auth-form-card {
      padding: 1.5rem 1rem;
      border-radius: 16px;
    }
    
    .form-control-modern {
      padding: 0.875rem 0.875rem 0.875rem 2.5rem;
    }
    
    .form-floating > label {
      padding: 0.875rem 0.875rem 0.875rem 2.5rem;
    }
  }
  </style>