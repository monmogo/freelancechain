<!-- src/views/FreelancerDashboard.vue - Modern Bootstrap UI -->
<template>
    <div class="freelancer-dashboard">
      <!-- Hero Section -->
      <section class="hero-section bg-gradient-freelancer text-white py-5">
        <div class="container">
          <div class="row align-items-center">
            <div class="col-lg-8">
              <div class="hero-content">
                <h1 class="display-5 fw-bold mb-3">
                  <i class="bi bi-laptop me-3"></i>
                  Freelancer Dashboard
                </h1>
                <p class="lead mb-4 opacity-90">
                  Chào {{ authStore.user?.first_name || 'Freelancer' }}, 
                  chào mừng bạn đến với workspace chuyên nghiệp! 🚀
                </p>
                <div class="hero-stats d-flex flex-wrap gap-4">
                  <div class="hero-stat-item">
                    <div class="stat-number h4 mb-0">0</div>
                    <small class="opacity-75">Dự án hoàn thành</small>
                  </div>
                  <div class="hero-stat-item">
                    <div class="stat-number h4 mb-0">$0</div>
                    <small class="opacity-75">Thu nhập tháng này</small>
                  </div>
                  <div class="hero-stat-item">
                    <div class="stat-number h4 mb-0">5.0</div>
                    <small class="opacity-75">Đánh giá trung bình</small>
                  </div>
                </div>
              </div>
            </div>
            <div class="col-lg-4 text-end d-none d-lg-block">
              <div class="hero-illustration">
                <i class="bi bi-person-workspace display-1 opacity-25"></i>
              </div>
            </div>
          </div>
        </div>
      </section>
  
      <div class="container py-5">
        <!-- Quick Actions Bar -->
        <div class="quick-actions-bar mb-5">
          <div class="row g-3">
            <div class="col-md-3">
              <button class="btn btn-primary btn-action w-100">
                <i class="bi bi-search me-2"></i>
                Tìm việc làm
              </button>
            </div>
            <div class="col-md-3">
              <button class="btn btn-outline-success btn-action w-100">
                <i class="bi bi-file-earmark-text me-2"></i>
                Tạo đề xuất
              </button>
            </div>
            <div class="col-md-3">
              <button class="btn btn-outline-info btn-action w-100">
                <i class="bi bi-chat-dots me-2"></i>
                Tin nhắn
                <span class="badge bg-danger ms-2">3</span>
              </button>
            </div>
            <div class="col-md-3">
              <button class="btn btn-outline-warning btn-action w-100">
                <i class="bi bi-person-gear me-2"></i>
                Cập nhật hồ sơ
              </button>
            </div>
          </div>
        </div>
  
        <!-- Main Content Grid -->
        <div class="row g-4">
          <!-- Left Column - Main Content -->
          <div class="col-lg-8">
            <!-- Performance Stats -->
            <div class="card border-0 shadow-sm mb-4">
              <div class="card-header bg-transparent border-0 pb-0">
                <h5 class="card-title mb-0">
                  <i class="bi bi-graph-up me-2 text-primary"></i>
                  Hiệu suất làm việc
                </h5>
              </div>
              <div class="card-body pt-3">
                <div class="row g-4">
                  <div class="col-md-4">
                    <div class="performance-card text-center p-3 rounded-3 bg-primary bg-opacity-10">
                      <div class="performance-icon mb-2">
                        <i class="bi bi-briefcase-fill fs-2 text-primary"></i>
                      </div>
                      <h3 class="h2 mb-1 fw-bold text-primary">0</h3>
                      <p class="mb-2 fw-semibold">Dự án hoàn thành</p>
                      <div class="progress" style="height: 4px;">
                        <div class="progress-bar bg-primary" style="width: 0%"></div>
                      </div>
                      <small class="text-muted mt-1 d-block">+0% từ tháng trước</small>
                    </div>
                  </div>
  
                  <div class="col-md-4">
                    <div class="performance-card text-center p-3 rounded-3 bg-success bg-opacity-10">
                      <div class="performance-icon mb-2">
                        <i class="bi bi-currency-dollar fs-2 text-success"></i>
                      </div>
                      <h3 class="h2 mb-1 fw-bold text-success">$0</h3>
                      <p class="mb-2 fw-semibold">Tổng thu nhập</p>
                      <div class="progress" style="height: 4px;">
                        <div class="progress-bar bg-success" style="width: 0%"></div>
                      </div>
                      <small class="text-muted mt-1 d-block">+0% từ tháng trước</small>
                    </div>
                  </div>
  
                  <div class="col-md-4">
                    <div class="performance-card text-center p-3 rounded-3 bg-warning bg-opacity-10">
                      <div class="performance-icon mb-2">
                        <i class="bi bi-star-fill fs-2 text-warning"></i>
                      </div>
                      <h3 class="h2 mb-1 fw-bold text-warning">5.0</h3>
                      <p class="mb-2 fw-semibold">Đánh giá trung bình</p>
                      <div class="progress" style="height: 4px;">
                        <div class="progress-bar bg-warning" style="width: 100%"></div>
                      </div>
                      <small class="text-muted mt-1 d-block">Xuất sắc!</small>
                    </div>
                  </div>
                </div>
              </div>
            </div>
  
            <!-- Active Projects -->
            <div class="card border-0 shadow-sm mb-4">
              <div class="card-header bg-transparent border-0 d-flex align-items-center justify-content-between pb-0">
                <h5 class="card-title mb-0">
                  <i class="bi bi-kanban me-2 text-info"></i>
                  Dự án đang thực hiện
                </h5>
                <div class="header-actions">
                  <button class="btn btn-sm btn-outline-primary">
                    <i class="bi bi-funnel me-1"></i>Lọc
                  </button>
                  <button class="btn btn-sm btn-primary ms-2">
                    <i class="bi bi-plus-lg me-1"></i>Tạo mới
                  </button>
                </div>
              </div>
              <div class="card-body pt-3">
                <!-- Empty State -->
                <div class="empty-state text-center py-5">
                  <div class="empty-icon mb-4">
                    <i class="bi bi-folder-plus display-1 text-muted opacity-50"></i>
                  </div>
                  <h5 class="text-muted mb-3">Chưa có dự án nào</h5>
                  <p class="text-muted mb-4 max-w-400 mx-auto">
                    Bắt đầu sự nghiệp freelance của bạn bằng cách tìm kiếm và apply các dự án phù hợp với kỹ năng.
                  </p>
                  <div class="empty-actions">
                    <button class="btn btn-primary btn-lg me-3">
                      <i class="bi bi-search me-2"></i>Tìm việc làm
                    </button>
                    <button class="btn btn-outline-secondary btn-lg">
                      <i class="bi bi-info-circle me-2"></i>Hướng dẫn
                    </button>
                  </div>
                </div>
              </div>
            </div>
  
            <!-- Recent Activity -->
            <div class="card border-0 shadow-sm">
              <div class="card-header bg-transparent border-0 pb-0">
                <h5 class="card-title mb-0">
                  <i class="bi bi-clock-history me-2 text-secondary"></i>
                  Hoạt động gần đây
                </h5>
              </div>
              <div class="card-body pt-3">
                <div class="activity-timeline">
                  <!-- Sample Activity Items -->
                  <div class="activity-item d-flex mb-3">
                    <div class="activity-icon me-3">
                      <div class="bg-primary bg-opacity-10 rounded-circle p-2">
                        <i class="bi bi-person-check text-primary"></i>
                      </div>
                    </div>
                    <div class="activity-content flex-grow-1">
                      <p class="mb-1 fw-semibold">Tài khoản đã được tạo thành công</p>
                      <small class="text-muted">Vừa xong</small>
                    </div>
                  </div>
  
                  <div class="activity-item d-flex">
                    <div class="activity-icon me-3">
                      <div class="bg-info bg-opacity-10 rounded-circle p-2">
                        <i class="bi bi-info-circle text-info"></i>
                      </div>
                    </div>
                    <div class="activity-content flex-grow-1">
                      <p class="mb-1 fw-semibold">Hoàn thiện hồ sơ để tăng cơ hội thành công</p>
                      <small class="text-muted">Gợi ý hệ thống</small>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
  
          <!-- Right Column - Sidebar -->
          <div class="col-lg-4">
            <!-- Profile Completion Card -->
            <div class="card border-0 shadow-sm mb-4">
              <div class="card-header bg-gradient-light border-0 text-center">
                <div class="profile-completion-icon mb-2">
                  <i class="bi bi-person-badge display-4 text-primary"></i>
                </div>
                <h6 class="card-title mb-0">Hoàn thiện hồ sơ</h6>
              </div>
              <div class="card-body">
                <!-- Progress Circle -->
                <div class="text-center mb-4">
                  <div class="progress-circle mx-auto mb-3">
                    <svg width="120" height="120" class="progress-ring">
                      <circle cx="60" cy="60" r="50" class="progress-ring-circle-bg"></circle>
                      <circle cx="60" cy="60" r="50" class="progress-ring-circle" style="stroke-dasharray: 78.5 235.5;"></circle>
                    </svg>
                    <div class="progress-text">
                      <span class="h4 fw-bold text-primary">25%</span>
                    </div>
                  </div>
                  <p class="text-muted small">1 / 4 bước hoàn thành</p>
                </div>
  
                <!-- Completion Steps -->
                <div class="completion-steps">
                  <div class="step-item d-flex align-items-center mb-3 completed">
                    <div class="step-icon me-3">
                      <i class="bi bi-check-circle-fill text-success"></i>
                    </div>
                    <div class="step-content">
                      <h6 class="mb-1 fw-semibold">Thông tin cơ bản</h6>
                      <small class="text-muted">Đã hoàn thành</small>
                    </div>
                  </div>
  
                  <div class="step-item d-flex align-items-center mb-3">
                    <div class="step-icon me-3">
                      <i class="bi bi-circle text-muted"></i>
                    </div>
                    <div class="step-content">
                      <h6 class="mb-1 fw-semibold text-muted">Kỹ năng & kinh nghiệm</h6>
                      <small class="text-muted">Chưa hoàn thành</small>
                    </div>
                  </div>
  
                  <div class="step-item d-flex align-items-center mb-3">
                    <div class="step-icon me-3">
                      <i class="bi bi-circle text-muted"></i>
                    </div>
                    <div class="step-content">
                      <h6 class="mb-1 fw-semibold text-muted">Portfolio & mẫu công việc</h6>
                      <small class="text-muted">Chưa hoàn thành</small>
                    </div>
                  </div>
  
                  <div class="step-item d-flex align-items-center mb-4">
                    <div class="step-icon me-3">
                      <i class="bi bi-circle text-muted"></i>
                    </div>
                    <div class="step-content">
                      <h6 class="mb-1 fw-semibold text-muted">Xác thực danh tính</h6>
                      <small class="text-muted">Chưa hoàn thành</small>
                    </div>
                  </div>
                </div>
  
                <button class="btn btn-primary w-100">
                  <i class="bi bi-arrow-right me-2"></i>Tiếp tục hoàn thiện
                </button>
              </div>
            </div>
  
            <!-- Job Opportunities -->
            <div class="card border-0 shadow-sm mb-4">
              <div class="card-header bg-transparent border-0 pb-0">
                <h6 class="card-title mb-0">
                  <i class="bi bi-lightning-charge me-2 text-warning"></i>
                  Cơ hội việc làm phù hợp
                </h6>
              </div>
              <div class="card-body pt-3">
                <!-- Job Item -->
                <div class="job-opportunity-item p-3 rounded-3 border mb-3">
                  <div class="d-flex align-items-start">
                    <div class="job-icon me-3">
                      <div class="bg-primary bg-opacity-10 rounded-circle p-2">
                        <i class="bi bi-code-square text-primary"></i>
                      </div>
                    </div>
                    <div class="job-content flex-grow-1">
                      <h6 class="mb-1 fw-semibold">Web Developer cần gấp</h6>
                      <p class="small text-muted mb-2">Build responsive website với React...</p>
                      <div class="job-meta d-flex justify-content-between align-items-center">
                        <span class="badge bg-success bg-opacity-10 text-success">$500 - $1000</span>
                        <small class="text-muted">2 đề xuất</small>
                      </div>
                    </div>
                  </div>
                  <button class="btn btn-outline-primary btn-sm w-100 mt-3">
                    Xem chi tiết
                  </button>
                </div>
  
                <div class="job-opportunity-item p-3 rounded-3 border mb-3">
                  <div class="d-flex align-items-start">
                    <div class="job-icon me-3">
                      <div class="bg-info bg-opacity-10 rounded-circle p-2">
                        <i class="bi bi-palette text-info"></i>
                      </div>
                    </div>
                    <div class="job-content flex-grow-1">
                      <h6 class="mb-1 fw-semibold">UI/UX Designer</h6>
                      <p class="small text-muted mb-2">Thiết kế app mobile cho startup...</p>
                      <div class="job-meta d-flex justify-content-between align-items-center">
                        <span class="badge bg-info bg-opacity-10 text-info">$300 - $600</span>
                        <small class="text-muted">5 đề xuất</small>
                      </div>
                    </div>
                  </div>
                  <button class="btn btn-outline-info btn-sm w-100 mt-3">
                    Xem chi tiết
                  </button>
                </div>
  
                <button class="btn btn-outline-secondary btn-sm w-100">
                  <i class="bi bi-arrow-right me-1"></i>Xem tất cả việc làm
                </button>
              </div>
            </div>
  
            <!-- Quick Tips -->
            <div class="card border-0 shadow-sm">
              <div class="card-header bg-transparent border-0 pb-0">
                <h6 class="card-title mb-0">
                  <i class="bi bi-lightbulb me-2 text-warning"></i>
                  Tips thành công
                </h6>
              </div>
              <div class="card-body pt-3">
                <div class="tip-item p-3 bg-light rounded-3 mb-3">
                  <div class="d-flex align-items-start">
                    <i class="bi bi-star text-warning me-2 mt-1"></i>
                    <div>
                      <h6 class="small fw-semibold mb-1">Tối ưu hồ sơ</h6>
                      <p class="small text-muted mb-0">Hồ sơ hoàn chỉnh có cơ hội được chọn cao hơn 40%</p>
                    </div>
                  </div>
                </div>
  
                <div class="tip-item p-3 bg-light rounded-3">
                  <div class="d-flex align-items-start">
                    <i class="bi bi-chat-text text-primary me-2 mt-1"></i>
                    <div>
                      <h6 class="small fw-semibold mb-1">Giao tiếp tốt</h6>
                      <p class="small text-muted mb-0">Phản hồi nhanh chóng và chuyên nghiệp với khách hàng</p>
                    </div>
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
  import { useAuthStore } from '@/stores/auth.js'
  
  const authStore = useAuthStore()
  </script>
  
  <style scoped>
  .freelancer-dashboard {
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    min-height: 100vh;
  }
  
  .bg-gradient-freelancer {
    background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
  }
  
  .hero-section {
    position: relative;
    overflow: hidden;
  }
  
  .hero-section::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grain" width="100" height="100" patternUnits="userSpaceOnUse"><circle cx="25" cy="25" r="1" fill="white" opacity="0.1"/><circle cx="75" cy="75" r="1" fill="white" opacity="0.1"/></pattern></defs><rect width="100" height="100" fill="url(%23grain)"/></svg>');
    pointer-events: none;
  }
  
  .hero-stats {
    margin-top: 2rem;
  }
  
  .hero-stat-item {
    text-align: center;
    min-width: 120px;
  }
  
  .btn-action {
    border-radius: 12px;
    padding: 0.875rem 1.5rem;
    font-weight: 600;
    transition: all 0.3s ease;
  }
  
  .btn-action:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
  }
  
  .performance-card {
    transition: all 0.3s ease;
    border: 1px solid rgba(0,0,0,0.05);
  }
  
  .performance-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.1);
  }
  
  .card {
    border-radius: 16px !important;
    transition: all 0.3s ease;
  }
  
  .card:hover {
    transform: translateY(-2px);
  }
  
  .empty-state {
    max-width: 500px;
    margin: 0 auto;
  }
  
  .empty-icon {
    animation: float 3s ease-in-out infinite;
  }
  
  @keyframes float {
    0%, 100% { transform: translateY(0px); }
    50% { transform: translateY(-10px); }
  }
  
  .max-w-400 {
    max-width: 400px;
  }
  
  .activity-item {
    padding: 0.75rem 0;
    border-bottom: 1px solid rgba(0,0,0,0.05);
  }
  
  .activity-item:last-child {
    border-bottom: none;
  }
  
  .activity-icon {
    flex-shrink: 0;
  }
  
  .bg-gradient-light {
    background: linear-gradient(135deg, #f8f9fa, #e9ecef);
  }
  
  .progress-circle {
    position: relative;
  }
  
  .progress-ring {
    transform: rotate(-90deg);
  }
  
  .progress-ring-circle-bg {
    fill: transparent;
    stroke: #e9ecef;
    stroke-width: 8;
  }
  
  .progress-ring-circle {
    fill: transparent;
    stroke: #28a745;
    stroke-width: 8;
    stroke-linecap: round;
    transition: stroke-dasharray 0.3s ease;
  }
  
  .progress-text {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
  }
  
  .step-item.completed .step-content h6 {
    color: var(--bs-success) !important;
  }
  
  .step-item {
    transition: all 0.2s ease;
  }
  
  .step-item:hover {
    background-color: rgba(0,0,0,0.02);
    border-radius: 8px;
    padding: 0.5rem;
    margin: -0.5rem;
    margin-bottom: 0.5rem;
  }
  
  .job-opportunity-item {
    transition: all 0.3s ease;
    background: rgba(255,255,255,0.5);
  }
  
  .job-opportunity-item:hover {
    background: white;
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    transform: translateY(-2px);
  }
  
  .tip-item {
    transition: all 0.3s ease;
  }
  
  .tip-item:hover {
    background-color: rgba(0,0,0,0.08) !important;
    transform: translateX(4px);
  }
  
  /* Responsive adjustments */
  @media (max-width: 768px) {
    .hero-section {
      text-align: center;
    }
    
    .display-5 {
      font-size: 2rem;
    }
    
    .hero-stats {
      justify-content: center;
    }
    
    .quick-actions-bar .btn-action {
      margin-bottom: 0.5rem;
    }
    
    .header-actions {
      flex-direction: column;
      gap: 0.5rem;
    }
    
    .header-actions .btn {
      width: 100%;
    }
  }
  
  @media (max-width: 576px) {
    .performance-card {
      margin-bottom: 1rem;
    }
    
    .empty-actions {
      flex-direction: column;
      gap: 1rem;
    }
    
    .empty-actions .btn {
      width: 100%;
    }
  }
  </style>