<!-- src/components/layout/Navbar.vue - Enhanced UI/UX -->
<template>
    <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm sticky-top">
      <div class="container">
        <!-- Brand -->
        <router-link to="/dashboard" class="navbar-brand d-flex align-items-center">
          <div class="brand-icon me-2">
            <i class="bi bi-hexagon-fill text-primary"></i>
          </div>
          <span class="brand-text fw-bold">FreelanceChain</span>
        </router-link>
        
        <!-- Mobile Toggle -->
        <button 
          class="navbar-toggler border-0 shadow-none" 
          type="button" 
          data-bs-toggle="collapse" 
          data-bs-target="#navbarNav"
          @click="toggleMobileMenu"
        >
          <span class="navbar-toggler-icon"></span>
        </button>
        
        <!-- Navigation Menu -->
        <div class="collapse navbar-collapse" id="navbarNav">
          <!-- Center Navigation (Desktop) -->
          <ul class="navbar-nav mx-auto d-none d-lg-flex">
            <li class="nav-item">
              <router-link to="/dashboard" class="nav-link px-3">
                <i class="bi bi-house me-1"></i>
                Dashboard
              </router-link>
            </li>
            <li v-if="authStore.isFreelancer" class="nav-item">
              <a href="#" class="nav-link px-3">
                <i class="bi bi-search me-1"></i>
                Tìm việc
              </a>
            </li>
            <li v-if="authStore.isClient" class="nav-item">
              <a href="#" class="nav-link px-3">
                <i class="bi bi-plus-circle me-1"></i>
                Đăng dự án
              </a>
            </li>
            <li class="nav-item">
              <a href="#" class="nav-link px-3">
                <i class="bi bi-chat-dots me-1"></i>
                Tin nhắn
                <span class="badge bg-danger ms-1">3</span>
              </a>
            </li>
          </ul>
          
          <!-- Right Side Menu -->
          <ul class="navbar-nav ms-auto">
            <!-- Notifications -->
            <li v-if="authStore.isAuthenticated" class="nav-item dropdown me-2">
              <a 
                class="nav-link position-relative p-2" 
                href="#" 
                role="button" 
                data-bs-toggle="dropdown"
              >
                <i class="bi bi-bell fs-5"></i>
                <span class="notification-badge position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                  5
                </span>
              </a>
              <ul class="dropdown-menu dropdown-menu-end notifications-dropdown p-0">
                <li class="dropdown-header d-flex align-items-center justify-content-between">
                  <span class="fw-semibold">Thông báo</span>
                  <button class="btn btn-sm btn-link text-primary p-0">
                    Đánh dấu đã đọc
                  </button>
                </li>
                <li><hr class="dropdown-divider my-0"></li>
                
                <!-- Notification Items -->
                <li>
                  <a class="dropdown-item notification-item p-3">
                    <div class="d-flex">
                      <div class="notification-avatar me-3">
                        <div class="avatar bg-primary bg-opacity-10 rounded-circle d-flex align-items-center justify-content-center">
                          <i class="bi bi-briefcase text-primary"></i>
                        </div>
                      </div>
                      <div class="flex-grow-1">
                        <h6 class="notification-title mb-1">Dự án mới phù hợp</h6>
                        <p class="notification-text text-muted small mb-1">
                          Web Developer cần gấp - $500-$1000
                        </p>
                        <small class="text-muted">2 phút trước</small>
                      </div>
                      <div class="notification-status">
                        <span class="badge bg-primary rounded-pill">Mới</span>
                      </div>
                    </div>
                  </a>
                </li>
                
                <li>
                  <a class="dropdown-item notification-item p-3">
                    <div class="d-flex">
                      <div class="notification-avatar me-3">
                        <div class="avatar bg-success bg-opacity-10 rounded-circle d-flex align-items-center justify-content-center">
                          <i class="bi bi-check-circle text-success"></i>
                        </div>
                      </div>
                      <div class="flex-grow-1">
                        <h6 class="notification-title mb-1">Thanh toán thành công</h6>
                        <p class="notification-text text-muted small mb-1">
                          Bạn đã nhận $800 cho dự án Website
                        </p>
                        <small class="text-muted">1 giờ trước</small>
                      </div>
                    </div>
                  </a>
                </li>
                
                <li><hr class="dropdown-divider my-0"></li>
                <li>
                  <button class="dropdown-item text-center py-2 text-primary">
                    Xem tất cả thông báo
                  </button>
                </li>
              </ul>
            </li>
  
            <!-- User Menu -->
            <li v-if="authStore.isAuthenticated" class="nav-item dropdown">
              <a 
                class="nav-link dropdown-toggle user-menu-toggle d-flex align-items-center" 
                href="#" 
                role="button" 
                data-bs-toggle="dropdown"
              >
                <div class="user-avatar me-2">
                  <div class="avatar bg-primary text-white rounded-circle d-flex align-items-center justify-content-center">
                    {{ authStore.userInitials }}
                  </div>
                </div>
                <div class="user-info d-none d-md-block">
                  <div class="user-name fw-semibold">{{ authStore.userName }}</div>
                  <div class="user-role small text-muted">{{ authStore.user?.role }}</div>
                </div>
              </a>
              <ul class="dropdown-menu dropdown-menu-end user-dropdown">
                <li class="dropdown-header">
                  <div class="d-flex align-items-center">
                    <div class="avatar bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-3">
                      {{ authStore.userInitials }}
                    </div>
                    <div>
                      <div class="fw-semibold">{{ authStore.userName }}</div>
                      <div class="small text-muted">{{ authStore.user?.email }}</div>
                    </div>
                  </div>
                </li>
                <li><hr class="dropdown-divider"></li>
                
                <li>
                  <router-link to="/profile" class="dropdown-item">
                    <i class="bi bi-person me-2"></i>Hồ sơ cá nhân
                  </router-link>
                </li>
                
                <li v-if="authStore.isFreelancer">
                  <router-link to="/freelancer/dashboard" class="dropdown-item">
                    <i class="bi bi-laptop me-2"></i>Freelancer Dashboard
                  </router-link>
                </li>
                
                <li v-if="authStore.isClient">
                  <router-link to="/client/dashboard" class="dropdown-item">
                    <i class="bi bi-briefcase me-2"></i>Client Dashboard
                  </router-link>
                </li>
                
                <li>
                  <a href="#" class="dropdown-item">
                    <i class="bi bi-gear me-2"></i>Cài đặt
                  </a>
                </li>
                
                <li>
                  <a href="#" class="dropdown-item">
                    <i class="bi bi-question-circle me-2"></i>Trợ giúp
                  </a>
                </li>
                
                <li><hr class="dropdown-divider"></li>
                
                <li>
                  <button @click="handleLogout" class="dropdown-item text-danger">
                    <i class="bi bi-box-arrow-right me-2"></i>Đăng xuất
                  </button>
                </li>
              </ul>
            </li>
            
            <!-- Guest Menu -->
            <template v-else>
              <li class="nav-item me-2">
                <router-link to="/login" class="nav-link">
                  <i class="bi bi-box-arrow-in-right me-1"></i>Đăng nhập
                </router-link>
              </li>
              <li class="nav-item">
                <router-link to="/register" class="btn btn-primary">
                  <i class="bi bi-person-plus me-1"></i>Đăng ký
                </router-link>
              </li>
            </template>
          </ul>
        </div>
      </div>
    </nav>
  </template>
  
  <script setup>
  import { ref } from 'vue'
  import { useRouter } from 'vue-router'
  import { useAuthStore } from '@/stores/auth.js'
  import { toastService } from '@/services/toastService.js'
  
  const router = useRouter()
  const authStore = useAuthStore()
  
  const mobileMenuOpen = ref(false)
  
  const toggleMobileMenu = () => {
    mobileMenuOpen.value = !mobileMenuOpen.value
  }
  
  const handleLogout = async () => {
    try {
      const result = await authStore.logout()
      
      if (result.success) {
        toastService.success('Đăng xuất thành công!')
        router.push('/login')
      }
    } catch (error) {
      console.error('Logout error:', error)
      toastService.error('Có lỗi xảy ra khi đăng xuất')
      authStore.clearAuth()
      router.push('/login')
    }
  }
  </script>
  
  <style scoped>
  .navbar {
    backdrop-filter: blur(10px);
    border-bottom: 1px solid rgba(0,0,0,0.05);
  }
  
  .brand-icon {
    font-size: 1.5rem;
    transition: transform 0.3s ease;
  }
  
  .navbar-brand:hover .brand-icon {
    transform: rotate(15deg) scale(1.1);
  }
  
  .brand-text {
    font-size: 1.25rem;
    background: linear-gradient(135deg, #0d6efd, #20c997);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
  }
  
  .nav-link {
    border-radius: 8px;
    transition: all 0.3s ease;
    position: relative;
  }
  
  .nav-link:hover {
    background-color: rgba(13, 110, 253, 0.1);
    color: var(--bs-primary) !important;
  }
  
  .nav-link.active {
    background-color: rgba(13, 110, 253, 0.1);
    color: var(--bs-primary) !important;
    font-weight: 600;
  }
  
  .user-menu-toggle {
    border: none !important;
    padding: 0.5rem !important;
  }
  
  .avatar {
    width: 36px;
    height: 36px;
    font-size: 0.875rem;
    font-weight: 600;
  }
  
  .user-dropdown {
    min-width: 280px;
    border-radius: 12px;
    border: none;
    box-shadow: 0 10px 40px rgba(0,0,0,0.1);
    padding: 0;
  }
  
  .user-dropdown .dropdown-header {
    background: linear-gradient(135deg, #f8f9fa, #e9ecef);
    padding: 1rem;
    border-radius: 12px 12px 0 0;
    border-bottom: 1px solid rgba(0,0,0,0.05);
  }
  
  .user-dropdown .dropdown-item {
    padding: 0.75rem 1rem;
    transition: all 0.2s ease;
  }
  
  .user-dropdown .dropdown-item:hover {
    background-color: rgba(13, 110, 253, 0.08);
    color: var(--bs-primary);
  }
  
  .notifications-dropdown {
    min-width: 320px;
    max-width: 380px;
    border-radius: 12px;
    border: none;
    box-shadow: 0 10px 40px rgba(0,0,0,0.1);
  }
  
  .notifications-dropdown .dropdown-header {
    background: linear-gradient(135deg, #f8f9fa, #e9ecef);
    padding: 1rem;
    border-radius: 12px 12px 0 0;
  }
  
  .notification-item {
    border: none;
    padding: 1rem;
    transition: all 0.2s ease;
  }
  
  .notification-item:hover {
    background-color: rgba(13, 110, 253, 0.05);
  }
  
  .notification-avatar .avatar {
    width: 40px;
    height: 40px;
  }
  
  .notification-title {
    font-size: 0.875rem;
    font-weight: 600;
    line-height: 1.2;
  }
  
  .notification-text {
    font-size: 0.8rem;
    line-height: 1.3;
  }
  
  .notification-badge {
    font-size: 0.65rem;
    width: 18px;
    height: 18px;
  }
  
  .badge {
    font-size: 0.65rem;
  }
  
  /* Mobile Styles */
  @media (max-width: 991.98px) {
    .navbar-nav {
      padding: 1rem 0;
    }
    
    .nav-link {
      padding: 0.75rem 1rem;
      margin: 0.25rem 0;
    }
    
    .user-info {
      display: block !important;
    }
    
    .notifications-dropdown,
    .user-dropdown {
      position: static !important;
      transform: none !important;
      box-shadow: none;
      border: 1px solid rgba(0,0,0,0.1);
      margin-top: 0.5rem;
    }
  }
  
  /* Active route styling */
  .router-link-active {
    color: var(--bs-primary) !important;
    font-weight: 600;
  }
  
  .router-link-active.nav-link {
    background-color: rgba(13, 110, 253, 0.1);
  }
  
  /* Smooth animations */
  .dropdown-menu {
    transition: all 0.3s ease;
  }
  
  .navbar-toggler {
    border: none;
    padding: 0.25rem 0.5rem;
  }
  
  .navbar-toggler:focus {
    box-shadow: none;
  }
  </style>