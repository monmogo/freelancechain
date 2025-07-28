<!-- src/views/Profile.vue - Modern Bootstrap UI -->
<template>
    <div class="profile-page">
      <!-- Profile Header -->
      <section class="profile-header bg-gradient-profile text-white py-5">
        <div class="container">
          <div class="row align-items-center">
            <div class="col-lg-8">
              <div class="d-flex align-items-center">
                <!-- Avatar Section -->
                <div class="profile-avatar-container me-4">
                  <div class="profile-avatar position-relative">
                    <div class="avatar-circle bg-white text-primary d-flex align-items-center justify-content-center">
                      <span class="avatar-text">{{ authStore.userInitials }}</span>
                    </div>
                    <button class="avatar-edit-btn position-absolute">
                      <i class="bi bi-camera-fill"></i>
                    </button>
                  </div>
                </div>
                
                <!-- Profile Info -->
                <div class="profile-info">
                  <h1 class="profile-name mb-2">{{ authStore.userName }}</h1>
                  <p class="profile-role mb-3">
                    <i class="bi bi-person-badge me-2"></i>
                    {{ roleText }}
                  </p>
                  <div class="profile-stats d-flex flex-wrap gap-3">
                    <div class="stat-item">
                      <span class="stat-number">5.0</span>
                      <span class="stat-label">Rating</span>
                    </div>
                    <div class="stat-item">
                      <span class="stat-number">0</span>
                      <span class="stat-label">Projects</span>
                    </div>
                    <div class="stat-item">
                      <span class="stat-number">{{ profileCompletion }}%</span>
                      <span class="stat-label">Complete</span>
                    </div>
                  </div>
                </div>
              </div>
            </div>
            
            <div class="col-lg-4 text-lg-end mt-4 mt-lg-0">
              <div class="profile-actions">
                <button class="btn btn-light btn-lg me-2" @click="toggleEditMode">
                  <i class="bi bi-pencil-square me-2"></i>
                  {{ editMode ? 'Hủy' : 'Chỉnh sửa' }}
                </button>
                <button v-if="editMode" class="btn btn-success btn-lg" @click="saveProfile">
                  <i class="bi bi-check-lg me-2"></i>
                  Lưu thay đổi
                </button>
              </div>
            </div>
          </div>
        </div>
      </section>
  
      <div class="container py-5">
        <div class="row g-4">
          <!-- Left Column - Profile Forms -->
          <div class="col-lg-8">
            <!-- Basic Information -->
            <div class="card border-0 shadow-sm mb-4">
              <div class="card-header bg-transparent border-0 d-flex align-items-center justify-content-between">
                <h5 class="card-title mb-0">
                  <i class="bi bi-person-fill me-2 text-primary"></i>
                  Thông tin cơ bản
                </h5>
                <span class="badge bg-success bg-opacity-10 text-success">Hoàn thành</span>
              </div>
              <div class="card-body">
                <form @submit.prevent="updateBasicInfo">
                  <div class="row g-3">
                    <div class="col-md-6">
                      <label class="form-label fw-semibold">
                        <i class="bi bi-person me-1"></i>Họ
                      </label>
                      <input
                        v-model="profileForm.firstName"
                        type="text"
                        class="form-control form-control-modern"
                        :readonly="!editMode"
                        placeholder="Nhập họ của bạn"
                      />
                    </div>
                    
                    <div class="col-md-6">
                      <label class="form-label fw-semibold">
                        <i class="bi bi-person me-1"></i>Tên
                      </label>
                      <input
                        v-model="profileForm.lastName"
                        type="text"
                        class="form-control form-control-modern"
                        :readonly="!editMode"
                        placeholder="Nhập tên của bạn"
                      />
                    </div>
                    
                    <div class="col-12">
                      <label class="form-label fw-semibold">
                        <i class="bi bi-envelope me-1"></i>Email
                      </label>
                      <input
                        v-model="profileForm.email"
                        type="email"
                        class="form-control form-control-modern"
                        readonly
                        placeholder="Email không thể thay đổi"
                      />
                      <small class="text-muted">Email không thể thay đổi sau khi tạo tài khoản</small>
                    </div>
                    
                    <div class="col-md-6">
                      <label class="form-label fw-semibold">
                        <i class="bi bi-telephone me-1"></i>Số điện thoại
                      </label>
                      <input
                        v-model="profileForm.phone"
                        type="tel"
                        class="form-control form-control-modern"
                        :readonly="!editMode"
                        placeholder="+84 xxx xxx xxx"
                      />
                    </div>
                    
                    <div class="col-md-6">
                      <label class="form-label fw-semibold">
                        <i class="bi bi-geo-alt me-1"></i>Vị trí
                      </label>
                      <select 
                        v-model="profileForm.location"
                        class="form-select form-control-modern"
                        :disabled="!editMode"
                      >
                        <option value="">Chọn vị trí</option>
                        <option value="hanoi">Hà Nội</option>
                        <option value="hochiminh">TP. Hồ Chí Minh</option>
                        <option value="danang">Đà Nẵng</option>
                        <option value="other">Khác</option>
                      </select>
                    </div>
                    
                    <div class="col-12">
                      <label class="form-label fw-semibold">
                        <i class="bi bi-chat-quote me-1"></i>Giới thiệu bản thân
                      </label>
                      <textarea
                        v-model="profileForm.bio"
                        class="form-control form-control-modern"
                        rows="4"
                        :readonly="!editMode"
                        placeholder="Viết vài dòng về bản thân, kinh nghiệm và kỹ năng của bạn..."
                      ></textarea>
                      <div class="text-end mt-1">
                        <small class="text-muted">{{ profileForm.bio?.length || 0 }}/500 ký tự</small>
                      </div>
                    </div>
                  </div>
                </form>
              </div>
            </div>
  
            <!-- Professional Information -->
            <div class="card border-0 shadow-sm mb-4" v-if="authStore.isFreelancer">
              <div class="card-header bg-transparent border-0 d-flex align-items-center justify-content-between">
                <h5 class="card-title mb-0">
                  <i class="bi bi-briefcase-fill me-2 text-info"></i>
                  Thông tin nghề nghiệp
                </h5>
                <span class="badge bg-warning bg-opacity-10 text-warning">Chưa hoàn thành</span>
              </div>
              <div class="card-body">
                <div class="row g-3">
                  <div class="col-md-6">
                    <label class="form-label fw-semibold">
                      <i class="bi bi-award me-1"></i>Chức danh nghề nghiệp
                    </label>
                    <input
                      v-model="profileForm.title"
                      type="text"
                      class="form-control form-control-modern"
                      :readonly="!editMode"
                      placeholder="VD: Senior Full-stack Developer"
                    />
                  </div>
                  
                  <div class="col-md-6">
                    <label class="form-label fw-semibold">
                      <i class="bi bi-currency-dollar me-1"></i>Mức lương theo giờ (USD)
                    </label>
                    <div class="input-group">
                      <span class="input-group-text">$</span>
                      <input
                        v-model="profileForm.hourlyRate"
                        type="number"
                        class="form-control form-control-modern"
                        :readonly="!editMode"
                        placeholder="25"
                        min="5"
                        max="200"
                      />
                    </div>
                  </div>
                  
                  <div class="col-md-6">
                    <label class="form-label fw-semibold">
                      <i class="bi bi-calendar-range me-1"></i>Kinh nghiệm (năm)
                    </label>
                    <select 
                      v-model="profileForm.experience"
                      class="form-select form-control-modern"
                      :disabled="!editMode"
                    >
                      <option value="">Chọn kinh nghiệm</option>
                      <option value="0-1">0-1 năm</option>
                      <option value="1-3">1-3 năm</option>
                      <option value="3-5">3-5 năm</option>
                      <option value="5+">5+ năm</option>
                    </select>
                  </div>
                  
                  <div class="col-md-6">
                    <label class="form-label fw-semibold">
                      <i class="bi bi-clock me-1"></i>Tình trạng làm việc
                    </label>
                    <select 
                      v-model="profileForm.availability"
                      class="form-select form-control-modern"
                      :disabled="!editMode"
                    >
                      <option value="available">Sẵn sàng làm việc</option>
                      <option value="busy">Bận - nhận ít dự án</option>
                      <option value="not-available">Tạm ngưng nhận việc</option>
                    </select>
                  </div>
                  
                  <div class="col-12">
                    <label class="form-label fw-semibold">
                      <i class="bi bi-tools me-1"></i>Kỹ năng chính
                    </label>
                    <div class="skills-input" v-if="editMode">
                      <div class="input-group mb-2">
                        <input
                          v-model="newSkill"
                          type="text"
                          class="form-control form-control-modern"
                          placeholder="Nhập kỹ năng và nhấn Enter"
                          @keyup.enter="addSkill"
                        />
                        <button class="btn btn-outline-primary" type="button" @click="addSkill">
                          <i class="bi bi-plus-lg"></i>
                        </button>
                      </div>
                    </div>
                    <div class="skills-display">
                      <span 
                        v-for="(skill, index) in profileForm.skills" 
                        :key="index"
                        class="badge bg-primary bg-opacity-10 text-primary me-2 mb-2 skill-tag"
                      >
                        {{ skill }}
                        <button 
                          v-if="editMode"
                          type="button" 
                          class="btn-close-skill ms-2"
                          @click="removeSkill(index)"
                        >
                          <i class="bi bi-x"></i>
                        </button>
                      </span>
                      <span v-if="profileForm.skills.length === 0" class="text-muted">
                        Chưa có kỹ năng nào được thêm
                      </span>
                    </div>
                  </div>
                </div>
              </div>
            </div>
  
            <!-- Security Settings -->
            <div class="card border-0 shadow-sm">
              <div class="card-header bg-transparent border-0">
                <h5 class="card-title mb-0">
                  <i class="bi bi-shield-lock-fill me-2 text-danger"></i>
                  Bảo mật & Quyền riêng tư
                </h5>
              </div>
              <div class="card-body">
                <div class="security-item d-flex align-items-center justify-content-between p-3 rounded-3 bg-light mb-3">
                  <div class="security-info">
                    <h6 class="mb-1 fw-semibold">Đổi mật khẩu</h6>
                    <small class="text-muted">Cập nhật mật khẩu để bảo mật tài khoản</small>
                  </div>
                  <button class="btn btn-outline-primary">
                    <i class="bi bi-key me-1"></i>Đổi mật khẩu
                  </button>
                </div>
                
                <div class="security-item d-flex align-items-center justify-content-between p-3 rounded-3 bg-light mb-3">
                  <div class="security-info">
                    <h6 class="mb-1 fw-semibold">Xác thực hai bước</h6>
                    <small class="text-muted">Tăng cường bảo mật với xác thực 2FA</small>
                  </div>
                  <button class="btn btn-outline-success">
                    <i class="bi bi-shield-check me-1"></i>Kích hoạt
                  </button>
                </div>
                
                <div class="security-item d-flex align-items-center justify-content-between p-3 rounded-3 bg-light">
                  <div class="security-info">
                    <h6 class="mb-1 fw-semibold">Xác thực danh tính</h6>
                    <small class="text-muted">Xác minh danh tính để tăng độ tin cậy</small>
                  </div>
                  <button class="btn btn-outline-warning">
                    <i class="bi bi-person-check me-1"></i>Bắt đầu xác thực
                  </button>
                </div>
              </div>
            </div>
          </div>
  
          <!-- Right Column - Profile Stats & Actions -->
          <div class="col-lg-4">
            <!-- Profile Completion -->
            <div class="card border-0 shadow-sm mb-4">
              <div class="card-header bg-gradient-light border-0 text-center">
                <div class="completion-icon mb-2">
                  <i class="bi bi-person-check display-4 text-primary"></i>
                </div>
                <h6 class="card-title mb-0">Độ hoàn thiện hồ sơ</h6>
              </div>
              <div class="card-body text-center">
                <!-- Circular Progress -->
                <div class="progress-circle-container mb-4">
                  <svg class="progress-circle" width="120" height="120">
                    <circle cx="60" cy="60" r="50" class="progress-track"></circle>
                    <circle cx="60" cy="60" r="50" class="progress-bar" :style="circleStyle"></circle>
                  </svg>
                  <div class="progress-percentage">
                    <span class="h3 fw-bold text-primary">{{ profileCompletion }}%</span>
                  </div>
                </div>
                
                <!-- Completion Benefits -->
                <div class="completion-benefits text-start">
                  <div class="benefit-item d-flex align-items-center mb-2">
                    <i class="bi bi-check-circle text-success me-2"></i>
                    <small>Thông tin cơ bản</small>
                  </div>
                  <div class="benefit-item d-flex align-items-center mb-2">
                    <i class="bi bi-circle text-muted me-2"></i>
                    <small class="text-muted">Kỹ năng chuyên môn</small>
                  </div>
                  <div class="benefit-item d-flex align-items-center mb-2">
                    <i class="bi bi-circle text-muted me-2"></i>
                    <small class="text-muted">Portfolio</small>
                  </div>
                  <div class="benefit-item d-flex align-items-center mb-3">
                    <i class="bi bi-circle text-muted me-2"></i>
                    <small class="text-muted">Xác thực danh tính</small>
                  </div>
                </div>
                
                <button class="btn btn-primary w-100">
                  <i class="bi bi-arrow-up-right me-2"></i>Hoàn thiện hồ sơ
                </button>
              </div>
            </div>
  
            <!-- Profile Visibility -->
            <div class="card border-0 shadow-sm mb-4">
              <div class="card-header bg-transparent border-0">
                <h6 class="card-title mb-0">
                  <i class="bi bi-eye me-2 text-info"></i>
                  Hiển thị hồ sơ
                </h6>
              </div>
              <div class="card-body">
                <div class="visibility-options">
                  <div class="form-check mb-3">
                    <input 
                      class="form-check-input" 
                      type="radio" 
                      name="visibility" 
                      id="public"
                      value="public"
                      v-model="profileForm.visibility"
                      :disabled="!editMode"
                    />
                    <label class="form-check-label" for="public">
                      <div class="d-flex align-items-center">
                        <i class="bi bi-globe text-success me-2"></i>
                        <div>
                          <h6 class="mb-0">Công khai</h6>
                          <small class="text-muted">Mọi người có thể xem hồ sơ</small>
                        </div>
                      </div>
                    </label>
                  </div>
                  
                  <div class="form-check mb-3">
                    <input 
                      class="form-check-input" 
                      type="radio" 
                      name="visibility" 
                      id="freelancers"
                      value="freelancers"
                      v-model="profileForm.visibility"
                      :disabled="!editMode"
                    />
                    <label class="form-check-label" for="freelancers">
                      <div class="d-flex align-items-center">
                        <i class="bi bi-people text-warning me-2"></i>
                        <div>
                          <h6 class="mb-0">Chỉ freelancer</h6>
                          <small class="text-muted">Chỉ freelancer xem được</small>
                        </div>
                      </div>
                    </label>
                  </div>
                  
                  <div class="form-check">
                    <input 
                      class="form-check-input" 
                      type="radio" 
                      name="visibility" 
                      id="private"
                      value="private"
                      v-model="profileForm.visibility"
                      :disabled="!editMode"
                    />
                    <label class="form-check-label" for="private">
                      <div class="d-flex align-items-center">
                        <i class="bi bi-lock text-danger me-2"></i>
                        <div>
                          <h6 class="mb-0">Riêng tư</h6>
                          <small class="text-muted">Chỉ bạn xem được</small>
                        </div>
                      </div>
                    </label>
                  </div>
                </div>
              </div>
            </div>
  
            <!-- Quick Actions -->
            <div class="card border-0 shadow-sm">
              <div class="card-header bg-transparent border-0">
                <h6 class="card-title mb-0">
                  <i class="bi bi-lightning-charge me-2 text-warning"></i>
                  Hành động nhanh
                </h6>
              </div>
              <div class="card-body">
                <div class="d-grid gap-2">
                  <button class="btn btn-outline-primary btn-action">
                    <i class="bi bi-download me-2"></i>Tải CV/Resume
                  </button>
                  <button class="btn btn-outline-info btn-action">
                    <i class="bi bi-share me-2"></i>Chia sẻ hồ sơ
                  </button>
                  <button class="btn btn-outline-secondary btn-action">
                    <i class="bi bi-printer me-2"></i>In hồ sơ
                  </button>
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
  import { useAuthStore } from '@/stores/auth.js'
  import { toastService } from '@/services/toastService.js'
  
  const authStore = useAuthStore()
  
  // Form state
  const editMode = ref(false)
  const newSkill = ref('')
  
  const profileForm = reactive({
    firstName: '',
    lastName: '',
    email: '',
    phone: '',
    location: '',
    bio: '',
    title: '',
    hourlyRate: '',
    experience: '',
    availability: 'available',
    skills: ['JavaScript', 'Vue.js', 'PHP'],
    visibility: 'public'
  })
  
  // Computed properties
  const roleText = computed(() => {
    const role = authStore.user?.role
    switch (role) {
      case 'freelancer': return 'Freelancer'
      case 'client': return 'Client'
      case 'admin': return 'Administrator'
      default: return 'User'
    }
  })
  
  const profileCompletion = computed(() => {
    let completed = 0
    const total = 8
    
    if (profileForm.firstName && profileForm.lastName) completed++
    if (profileForm.email) completed++
    if (profileForm.phone) completed++
    if (profileForm.bio) completed++
    if (profileForm.title) completed++
    if (profileForm.hourlyRate) completed++
    if (profileForm.skills.length > 0) completed++
    if (profileForm.location) completed++
    
    return Math.round((completed / total) * 100)
  })
  
  const circleStyle = computed(() => {
    const circumference = 2 * Math.PI * 50
    const progress = (profileCompletion.value / 100) * circumference
    return {
      'stroke-dasharray': `${progress} ${circumference}`,
      'stroke-dashoffset': 0
    }
  })
  
  // Methods
  const toggleEditMode = () => {
    editMode.value = !editMode.value
    if (!editMode.value) {
      // Reset form if cancelled
      loadProfileData()
    }
  }
  
  const addSkill = () => {
    if (newSkill.value.trim() && !profileForm.skills.includes(newSkill.value.trim())) {
      profileForm.skills.push(newSkill.value.trim())
      newSkill.value = ''
    }
  }
  
  const removeSkill = (index) => {
    profileForm.skills.splice(index, 1)
  }
  
  const saveProfile = async () => {
    try {
      // Here you would call the API to save profile
      // const result = await authService.updateProfile(profileForm)
      
      toastService.success('Cập nhật hồ sơ thành công!')
      editMode.value = false
    } catch (error) {
      toastService.error('Có lỗi xảy ra khi cập nhật hồ sơ')
    }
  }
  
  const updateBasicInfo = () => {
    if (editMode.value) {
      saveProfile()
    }
  }
  
  const loadProfileData = () => {
    // Load existing profile data
    if (authStore.user) {
      profileForm.firstName = authStore.user.first_name || ''
      profileForm.lastName = authStore.user.last_name || ''
      profileForm.email = authStore.user.email || ''
      // Load other fields from API
    }
  }
  
  onMounted(() => {
    loadProfileData()
  })
  </script>
  
  <style scoped>
  .profile-page {
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    min-height: 100vh;
  }
  
  .bg-gradient-profile {
    background: linear-gradient(135deg, #6f42c1 0%, #e83e8c 100%);
  }
  
  .profile-header {
    position: relative;
    overflow: hidden;
  }
  
  .profile-header::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grain" width="100" height="100" patternUnits="userSpaceOnUse"><circle cx="25" cy="25" r="1" fill="white" opacity="0.1"/><circle cx="75" cy="75" r="1" fill="white" opacity="0.1"/></pattern></defs><rect width="100" height="100" fill="url(%23grain)"/></svg>');
    pointer-events: none;
  }
  
  .profile-avatar-container {
    position: relative;
  }
  
  .profile-avatar {
    position: relative;
  }
  
  .avatar-circle {
    width: 120px;
    height: 120px;
    border-radius: 50%;
    font-size: 2.5rem;
    font-weight: 700;
    border: 4px solid rgba(255,255,255,0.2);
    transition: all 0.3s ease;
  }
  
  .avatar-circle:hover {
    transform: scale(1.05);
    border-color: rgba(255,255,255,0.4);
  }
  
  .avatar-edit-btn {
    position: absolute;
    bottom: 0;
    right: 0;
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: #fff;
    color: #6f42c1;
    border: none;
    box-shadow: 0 4px 12px rgba(0,0,0,0.2);
    transition: all 0.3s ease;
  }
  
  .avatar-edit-btn:hover {
    transform: scale(1.1);
    background: #6f42c1;
    color: white;
  }
  
  .avatar-text {
    background: linear-gradient(135deg, #6f42c1, #e83e8c);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
  }
  
  .profile-name {
    font-size: 2.5rem;
    font-weight: 700;
    margin-bottom: 0.5rem;
  }
  
  .profile-role {
    font-size: 1.1rem;
    opacity: 0.9;
  }
  
  .profile-stats {
    margin-top: 1rem;
  }
  
  .stat-item {
    text-align: center;
    min-width: 80px;
  }
  
  .stat-number {
    display: block;
    font-size: 1.5rem;
    font-weight: 700;
  }
  
  .stat-label {
    display: block;
    font-size: 0.875rem;
    opacity: 0.8;
  }
  
  .card {
    border-radius: 16px !important;
    transition: all 0.3s ease;
  }
  
  .card:hover {
    transform: translateY(-2px);
  }
  
  .form-control-modern {
    border: 2px solid #e9ecef;
    border-radius: 12px;
    padding: 0.875rem 1rem;
    background: rgba(255,255,255,0.8);
    transition: all 0.3s ease;
  }
  
  .form-control-modern:focus {
    border-color: #6f42c1;
    box-shadow: 0 0 0 0.2rem rgba(111, 66, 193, 0.25);
    background: white;
  }
  
  .form-control-modern:read-only {
    background-color: #f8f9fa;
    color: #6c757d;
  }
  
  .form-select {
    border: 2px solid #e9ecef;
    border-radius: 12px;
  }
  
  .skill-tag {
    font-size: 0.875rem;
    padding: 0.5rem 0.75rem;
    border-radius: 20px;
    position: relative;
    cursor: default;
  }
  
  .btn-close-skill {
    background: none;
    border: none;
    color: inherit;
    font-size: 0.75rem;
    cursor: pointer;
    opacity: 0.6;
    transition: opacity 0.2s ease;
  }
  
  .btn-close-skill:hover {
    opacity: 1;
  }
  
  .security-item {
    transition: all 0.3s ease;
  }
  
  .security-item:hover {
    background-color: rgba(0,0,0,0.05) !important;
    transform: translateX(4px);
  }
  
  .bg-gradient-light {
    background: linear-gradient(135deg, #f8f9fa, #e9ecef);
  }
  
  .progress-circle-container {
    position: relative;
    display: inline-block;
  }
  
  .progress-circle {
    transform: rotate(-90deg);
  }
  
  .progress-track {
    fill: transparent;
    stroke: #e9ecef;
    stroke-width: 8;
  }
  
  .progress-bar {
    fill: transparent;
    stroke: #6f42c1;
    stroke-width: 8;
    stroke-linecap: round;
    transition: stroke-dasharray 0.5s ease;
  }
  
  .progress-percentage {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
  }
  
  .benefit-item {
    transition: all 0.2s ease;
  }
  
  .benefit-item:hover {
    transform: translateX(4px);
  }
  
  .btn-action {
    border-radius: 12px;
    font-weight: 600;
    transition: all 0.3s ease;
  }
  
  .btn-action:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
  }
  
  .form-check-label {
    cursor: pointer;
    width: 100%;
  }
  
  .form-check-input:checked {
    background-color: #6f42c1;
    border-color: #6f42c1;
  }
  
  .visibility-options .form-check {
    padding: 0.75rem;
    border-radius: 12px;
    transition: all 0.3s ease;
  }
  
  .visibility-options .form-check:hover {
    background-color: rgba(0,0,0,0.02);
  }
  
  /* Responsive */
  @media (max-width: 768px) {
    .profile-header {
      text-align: center;
    }
    
    .profile-name {
      font-size: 2rem;
    }
    
    .profile-avatar-container {
      margin-bottom: 2rem;
    }
    
    .avatar-circle {
      width: 100px;
      height: 100px;
      font-size: 2rem;
    }
    
    .profile-actions {
      text-align: center;
    }
    
    .profile-actions .btn {
      width: 100%;
      margin: 0.25rem 0;
    }
  }
  
  @media (max-width: 576px) {
    .profile-stats {
      justify-content: center;
    }
    
    .stat-item {
      margin: 0 1rem;
    }
  }
  </style>