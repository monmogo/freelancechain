<!-- src/components/auth/RegisterForm.vue - With API Logic -->
<template>
    <div class="container-fluid vh-100 d-flex align-items-center justify-content-center bg-light">
      <div class="row w-100">
        <div class="col-md-8 col-lg-6 mx-auto">
          <div class="card shadow">
            <div class="card-body p-5">
              <div class="text-center mb-4">
                <div class="bg-success bg-opacity-10 rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 60px; height: 60px;">
                  <i class="bi bi-person-plus fs-3 text-success"></i>
                </div>
                <h2 class="h3 mb-3 fw-bold">Tạo tài khoản FreelanceChain</h2>
                <p class="text-muted">
                  Hoặc 
                  <router-link to="/login" class="text-decoration-none">
                    đăng nhập nếu đã có tài khoản
                  </router-link>
                </p>
              </div>
              
              <!-- Error Alert -->
              <div v-if="authStore.hasError" class="alert alert-danger d-flex align-items-center" role="alert">
                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                <div>{{ authStore.error }}</div>
                <button type="button" class="btn-close ms-auto" @click="authStore.clearError()"></button>
              </div>
              
              <!-- Success Alert -->
              <div v-if="registrationSuccess" class="alert alert-success d-flex align-items-center" role="alert">
                <i class="bi bi-check-circle-fill me-2"></i>
                <div>{{ successMessage }}</div>
              </div>
              
              <form @submit.prevent="handleRegister" v-if="!registrationSuccess">
                <!-- Role Selection -->
                <div class="mb-4">
                  <label class="form-label fw-semibold">Bạn muốn: <span class="text-danger">*</span></label>
                  <div class="row g-3">
                    <div class="col-6">
                      <input
                        id="freelancer"
                        v-model="form.role"
                        type="radio"
                        value="freelancer"
                        class="btn-check"
                        :disabled="authStore.loading"
                      />
                      <label for="freelancer" class="btn btn-outline-primary w-100">
                        <i class="bi bi-laptop me-2"></i>
                        Làm freelancer
                      </label>
                    </div>
                    <div class="col-6">
                      <input
                        id="client"
                        v-model="form.role"
                        type="radio"
                        value="client"
                        class="btn-check"
                        :disabled="authStore.loading"
                      />
                      <label for="client" class="btn btn-outline-success w-100">
                        <i class="bi bi-briefcase me-2"></i>
                        Thuê freelancer
                      </label>
                    </div>
                  </div>
                </div>
  
                <div class="row g-3 mb-3">
                  <div class="col-6">
                    <label for="firstName" class="form-label">Họ <span class="text-danger">*</span></label>
                    <input
                      id="firstName"
                      v-model="form.firstName"
                      type="text"
                      class="form-control"
                      :class="{ 'is-invalid': errors.firstName }"
                      placeholder="Nguyễn"
                      required
                      :disabled="authStore.loading"
                    />
                    <div v-if="errors.firstName" class="invalid-feedback">
                      {{ errors.firstName }}
                    </div>
                  </div>
                  <div class="col-6">
                    <label for="lastName" class="form-label">Tên <span class="text-danger">*</span></label>
                    <input
                      id="lastName"
                      v-model="form.lastName"
                      type="text"
                      class="form-control"
                      :class="{ 'is-invalid': errors.lastName }"
                      placeholder="Văn A"
                      required
                      :disabled="authStore.loading"
                    />
                    <div v-if="errors.lastName" class="invalid-feedback">
                      {{ errors.lastName }}
                    </div>
                  </div>
                </div>
                
                <div class="mb-3">
                  <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
                  <div class="input-group">
                    <span class="input-group-text">
                      <i class="bi bi-envelope"></i>
                    </span>
                    <input
                      id="email"
                      v-model="form.email"
                      type="email"
                      class="form-control"
                      :class="{ 'is-invalid': errors.email }"
                      placeholder="example@email.com"
                      required
                      :disabled="authStore.loading"
                    />
                    <div v-if="errors.email" class="invalid-feedback">
                      {{ errors.email }}
                    </div>
                  </div>
                </div>
                
                <div class="mb-3">
                  <label for="password" class="form-label">Mật khẩu <span class="text-danger">*</span></label>
                  <div class="input-group">
                    <span class="input-group-text">
                      <i class="bi bi-lock"></i>
                    </span>
                    <input
                      id="password"
                      v-model="form.password"
                      :type="showPassword ? 'text' : 'password'"
                      class="form-control"
                      :class="{ 'is-invalid': errors.password }"
                      placeholder="••••••••"
                      required
                      :disabled="authStore.loading"
                    />
                    <button
                      type="button"
                      class="btn btn-outline-secondary"
                      @click="showPassword = !showPassword"
                      :disabled="authStore.loading"
                    >
                      <i :class="showPassword ? 'bi bi-eye-slash' : 'bi bi-eye'"></i>
                    </button>
                    <div v-if="errors.password" class="invalid-feedback">
                      {{ errors.password }}
                    </div>
                  </div>
                  <div class="form-text">
                    Mật khẩu phải có ít nhất 6 ký tự
                  </div>
                </div>
                
                <div class="mb-3">
                  <label for="confirmPassword" class="form-label">Xác nhận mật khẩu <span class="text-danger">*</span></label>
                  <div class="input-group">
                    <span class="input-group-text">
                      <i class="bi bi-lock-fill"></i>
                    </span>
                    <input
                      id="confirmPassword"
                      v-model="form.confirmPassword"
                      :type="showConfirmPassword ? 'text' : 'password'"
                      class="form-control"
                      :class="{ 'is-invalid': errors.confirmPassword }"
                      placeholder="••••••••"
                      required
                      :disabled="authStore.loading"
                    />
                    <button
                      type="button"
                      class="btn btn-outline-secondary"
                      @click="showConfirmPassword = !showConfirmPassword"
                      :disabled="authStore.loading"
                    >
                      <i :class="showConfirmPassword ? 'bi bi-eye-slash' : 'bi bi-eye'"></i>
                    </button>
                    <div v-if="errors.confirmPassword" class="invalid-feedback">
                      {{ errors.confirmPassword }}
                    </div>
                  </div>
                </div>
  
                <div class="mb-3 form-check">
                  <input
                    id="agreeTerms"
                    v-model="form.agreeTerms"
                    type="checkbox"
                    class="form-check-input"
                    :class="{ 'is-invalid': errors.agreeTerms }"
                    required
                    :disabled="authStore.loading"
                  />
                  <label for="agreeTerms" class="form-check-label">
                    Tôi đồng ý với 
                    <a href="#" class="text-decoration-none">Điều khoản dịch vụ</a>
                    và 
                    <a href="#" class="text-decoration-none">Chính sách bảo mật</a>
                    <span class="text-danger">*</span>
                  </label>
                  <div v-if="errors.agreeTerms" class="invalid-feedback">
                    {{ errors.agreeTerms }}
                  </div>
                </div>
  
                <div class="d-grid">
                  <button
                    type="submit"
                    :disabled="authStore.loading || !isFormValid"
                    class="btn btn-success btn-lg"
                  >
                    <span v-if="authStore.loading" class="spinner-border spinner-border-sm me-2"></span>
                    <i v-else class="bi bi-person-plus me-2"></i>
                    {{ authStore.loading ? 'Đang tạo tài khoản...' : 'Tạo tài khoản' }}
                  </button>
                </div>
              </form>
              
              <!-- Success State -->
              <div v-if="registrationSuccess" class="text-center">
                <router-link to="/login" class="btn btn-primary btn-lg">
                  <i class="bi bi-box-arrow-in-right me-2"></i>
                  Đăng nhập ngay
                </router-link>
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
  const showConfirmPassword = ref(false)
  const registrationSuccess = ref(false)
  const successMessage = ref('')
  
  const form = reactive({
    firstName: '',
    lastName: '',
    email: '',
    password: '',
    confirmPassword: '',
    role: 'freelancer',
    agreeTerms: false
  })
  
  const errors = reactive({
    firstName: '',
    lastName: '',
    email: '',
    password: '',
    confirmPassword: '',
    agreeTerms: ''
  })
  
  const isFormValid = computed(() => {
    return form.firstName && 
           form.lastName && 
           form.email && 
           form.password && 
           form.confirmPassword && 
           form.password === form.confirmPassword &&
           form.agreeTerms &&
           !Object.values(errors).some(error => error)
  })
  
  // Validate form
  const validateForm = () => {
    // Clear previous errors
    Object.keys(errors).forEach(key => {
      errors[key] = ''
    })
  
    let isValid = true
  
    // First name validation
    if (!form.firstName.trim()) {
      errors.firstName = 'Họ là bắt buộc'
      isValid = false
    } else if (form.firstName.trim().length < 2) {
      errors.firstName = 'Họ phải có ít nhất 2 ký tự'
      isValid = false
    }
  
    // Last name validation
    if (!form.lastName.trim()) {
      errors.lastName = 'Tên là bắt buộc'
      isValid = false
    } else if (form.lastName.trim().length < 2) {
      errors.lastName = 'Tên phải có ít nhất 2 ký tự'
      isValid = false
    }
  
    // Email validation
    if (!form.email.trim()) {
      errors.email = 'Email là bắt buộc'
      isValid = false
    } else if (!/\S+@\S+\.\S+/.test(form.email)) {
      errors.email = 'Email không hợp lệ'
      isValid = false
    }
  
    // Password validation
    if (!form.password) {
      errors.password = 'Mật khẩu là bắt buộc'
      isValid = false
    } else if (form.password.length < 6) {
      errors.password = 'Mật khẩu phải có ít nhất 6 ký tự'
      isValid = false
    }
  
    // Confirm password validation
    if (!form.confirmPassword) {
      errors.confirmPassword = 'Xác nhận mật khẩu là bắt buộc'
      isValid = false
    } else if (form.password !== form.confirmPassword) {
      errors.confirmPassword = 'Mật khẩu không khớp'
      isValid = false
    }
  
    // Terms validation
    if (!form.agreeTerms) {
      errors.agreeTerms = 'Bạn phải đồng ý với điều khoản dịch vụ'
      isValid = false
    }
  
    return isValid
  }
  
  const handleRegister = async () => {
    // Validate form
    if (!validateForm()) {
      toastService.error('Vui lòng kiểm tra lại thông tin đăng ký')
      return
    }
  
    // Clear any previous errors
    authStore.clearError()
  
    try {
      console.log('Attempting registration...')
      
      const result = await authStore.register({
        firstName: form.firstName.trim(),
        lastName: form.lastName.trim(),
        email: form.email.trim(),
        password: form.password,
        role: form.role
      })
      
      if (result.success) {
        registrationSuccess.value = true
        successMessage.value = result.message || 'Đăng ký thành công!'
        toastService.success(successMessage.value)
        
        // Auto redirect after 3 seconds
        setTimeout(() => {
          router.push('/login')
        }, 3000)
      } else {
        toastService.error(result.error)
      }
    } catch (error) {
      console.error('Registration component error:', error)
      toastService.error('Có lỗi xảy ra. Vui lòng thử lại sau.')
    }
  }
  
  // Initialize auth store when component mounts
  onMounted(() => {
    authStore.initAuth()
    
    // If already logged in, redirect
    if (authStore.isAuthenticated) {
      router.push('/dashboard')
    }
  })
  </script>