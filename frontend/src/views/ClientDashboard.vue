<template>
    <div class="jobs-dashboard">
      <!-- Main Content -->
      <div class="main-container">
        <div class="container">
          <div class="dashboard-grid">
            <!-- Left Sidebar -->
            <aside class="left-sidebar">
              <div class="sidebar-section">
                <h3>Quản lý công việc</h3>
                <nav class="sidebar-nav">
                  <a href="#" :class="['nav-item', { active: activeTab === 'overview' }]" @click="activeTab = 'overview'">
                    <i class="fas fa-home"></i>
                    <span>Tổng quan</span>
                  </a>
                  <a href="#" :class="['nav-item', { active: activeTab === 'all' }]" @click="activeTab = 'all'">
                    <i class="fas fa-briefcase"></i>
                    <span>Tất cả việc làm</span>
                    <span class="count-badge">{{ stats.totalJobs }}</span>
                  </a>
                  <a href="#" :class="['nav-item', { active: activeTab === 'drafts' }]" @click="activeTab = 'drafts'">
                    <i class="fas fa-edit"></i>
                    <span>Bản nháp</span>
                    <span class="count-badge">{{ stats.draftJobs }}</span>
                  </a>
                  <a href="#" :class="['nav-item', { active: activeTab === 'active' }]" @click="activeTab = 'active'">
                    <i class="fas fa-clock"></i>
                    <span>Đang mở</span>
                    <span class="count-badge">{{ stats.activeJobs }}</span>
                  </a>
                  <a href="#" :class="['nav-item', { active: activeTab === 'proposals' }]" @click="activeTab = 'proposals'">
                    <i class="fas fa-paper-plane"></i>
                    <span>Proposals</span>
                    <span class="count-badge">{{ stats.totalProposals }}</span>
                  </a>
                </nav>
              </div>
  
              <div class="sidebar-section">
                <h3>Thống kê nhanh</h3>
                <div class="quick-stats">
                  <div class="stat-card">
                    <div class="stat-icon">
                      <i class="fas fa-dollar-sign"></i>
                    </div>
                    <div class="stat-info">
                      <span class="stat-value">${{ formatNumber(stats.totalSpent) }}</span>
                      <span class="stat-label">Đã chi tiêu</span>
                    </div>
                  </div>
                  <div class="stat-card">
                    <div class="stat-icon">
                      <i class="fas fa-eye"></i>
                    </div>
                    <div class="stat-info">
                      <span class="stat-value">{{ stats.totalViews }}</span>
                      <span class="stat-label">Lượt xem</span>
                    </div>
                  </div>
                  <div class="stat-card">
                    <div class="stat-icon">
                      <i class="fas fa-star"></i>
                    </div>
                    <div class="stat-info">
                      <span class="stat-value">{{ stats.avgRating.toFixed(1) }}</span>
                      <span class="stat-label">Đánh giá TB</span>
                    </div>
                  </div>
                </div>
              </div>
            </aside>
  
            <!-- Main Feed -->
            <main class="main-feed">
              <!-- Header Actions -->
              <div class="feed-header">
                <div class="header-info">
                  <h2>{{ getTabTitle() }}</h2>
                  <p>{{ getTabDescription() }}</p>
                </div>
                
                <div class="header-actions">
                  <select v-model="sortBy" @change="applySorting" class="sort-select">
                    <option value="newest">Mới nhất</option>
                    <option value="oldest">Cũ nhất</option>
                    <option value="most_proposals">Nhiều proposals</option>
                    <option value="most_views">Nhiều lượt xem</option>
                  </select>
                  
                  <button @click="showCreateJobModal = true" class="btn-primary">
                    <i class="fas fa-plus"></i>
                    Đăng việc mới
                  </button>
                </div>
              </div>
  
              <!-- Overview Tab -->
              <div v-if="activeTab === 'overview'" class="overview-content">
                <!-- Stats Cards -->
                <div class="stats-grid">
                  <div class="stat-card-large">
                    <div class="stat-icon bg-blue">
                      <i class="fas fa-briefcase"></i>
                    </div>
                    <div class="stat-details">
                      <h3>{{ stats.totalJobs }}</h3>
                      <p>Tổng việc đăng</p>
                      <span class="stat-change positive">+{{ stats.newJobsThisMonth }} tháng này</span>
                    </div>
                  </div>
  
                  <div class="stat-card-large">
                    <div class="stat-icon bg-green">
                      <i class="fas fa-clock"></i>
                    </div>
                    <div class="stat-details">
                      <h3>{{ stats.activeJobs }}</h3>
                      <p>Đang mở</p>
                      <span class="stat-change positive">{{ stats.activeJobsChange }}% so với tháng trước</span>
                    </div>
                  </div>
  
                  <div class="stat-card-large">
                    <div class="stat-icon bg-purple">
                      <i class="fas fa-paper-plane"></i>
                    </div>
                    <div class="stat-details">
                      <h3>{{ stats.totalProposals }}</h3>
                      <p>Tổng proposals</p>
                      <span class="stat-change positive">{{ (stats.totalProposals / Math.max(stats.totalJobs, 1)).toFixed(1) }} TB/việc</span>
                    </div>
                  </div>
  
                  <div class="stat-card-large">
                    <div class="stat-icon bg-yellow">
                      <i class="fas fa-dollar-sign"></i>
                    </div>
                    <div class="stat-details">
                      <h3>${{ formatNumber(stats.totalSpent) }}</h3>
                      <p>Đã chi tiêu</p>
                      <span class="stat-change positive">${{ formatNumber(stats.avgBudget) }} TB/việc</span>
                    </div>
                  </div>
                </div>
  
                <!-- Recent Jobs -->
                <div class="recent-section">
                  <h3>Việc làm gần đây</h3>
                  <div class="recent-jobs">
                    <div v-for="job in recentJobs" :key="job.id" class="recent-job-card" @click="viewJob(job)">
                      <div class="job-icon" :class="getJobStatusIcon(job.status)">
                        <i :class="getJobIcon(job.status)"></i>
                      </div>
                      <div class="job-info">
                        <h4>{{ job.title }}</h4>
                        <p>{{ getStatusLabel(job.status) }} • {{ job.proposal_count }} proposals</p>
                        <span class="job-time">{{ formatTimeAgo(job.created_at) }}</span>
                      </div>
                      <div class="job-actions">
                        <button v-if="job.status === 'draft'" @click.stop="editJob(job)" class="btn-small">
                          <i class="fas fa-edit"></i>
                        </button>
                        <button @click.stop="viewJob(job)" class="btn-small">
                          <i class="fas fa-eye"></i>
                        </button>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
  
              <!-- Jobs List (All, Drafts, Active, etc.) -->
              <div v-else class="jobs-content">
                <!-- Search and Filters -->
                <div class="filters-bar">
                  <div class="search-box">
                    <i class="fas fa-search"></i>
                    <input 
                      type="text" 
                      v-model="searchQuery" 
                      placeholder="Tìm kiếm việc làm..."
                      @input="applyFilters"
                    />
                  </div>
                  
                  <select v-model="categoryFilter" @change="applyFilters" class="filter-select">
                    <option value="">Tất cả danh mục</option>
                    <option v-for="category in categories" :key="category.id" :value="category.id">
                      {{ category.name }}
                    </option>
                  </select>
                </div>
  
                <!-- Loading State -->
                <div v-if="loading" class="loading-state">
                  <div class="loading-spinner"></div>
                  <p>Đang tải công việc...</p>
                </div>
  
                <!-- Empty State -->
                <div v-else-if="filteredJobs.length === 0" class="empty-state">
                  <div class="empty-icon">
                    <i class="fas fa-briefcase"></i>
                  </div>
                  <h3>{{ getEmptyStateTitle() }}</h3>
                  <p>{{ getEmptyStateMessage() }}</p>
                  <button @click="showCreateJobModal = true" class="btn-primary">
                    <i class="fas fa-plus"></i>
                    {{ getEmptyStateAction() }}
                  </button>
                </div>
  
                <!-- Jobs Grid -->
                <div v-else class="jobs-grid">
                  <div v-for="job in filteredJobs" :key="job.id" class="job-card" @click="viewJob(job)">
                    <!-- Job Header -->
                    <div class="job-header">
                      <div class="job-title-section">
                        <h3 class="job-title">{{ job.title }}</h3>
                        <div class="job-meta">
                          <span class="job-budget">
                            <i class="fas fa-dollar-sign"></i>
                            ${{ formatNumber(job.budget_min) }} - ${{ formatNumber(job.budget_max) }}
                          </span>
                          <span class="job-type">{{ getJobTypeLabel(job.budget_type) }}</span>
                          <span class="job-category">{{ job.category_name }}</span>
                        </div>
                      </div>
                      
                      <div class="job-status">
                        <span :class="getStatusBadgeClass(job.status)" class="status-badge">
                          {{ getStatusLabel(job.status) }}
                        </span>
                        <span v-if="job.featured" class="badge badge-featured">
                          <i class="fas fa-star"></i>
                          Nổi bật
                        </span>
                        <span v-if="job.urgent" class="badge badge-urgent">
                          <i class="fas fa-clock"></i>
                          Gấp
                        </span>
                        <span class="job-time">{{ formatTimeAgo(job.created_at) }}</span>
                      </div>
                    </div>
  
                    <!-- Job Description -->
                    <div class="job-description">
                      <p>{{ truncateText(job.description, 120) }}</p>
                    </div>
  
                    <!-- Job Footer -->
                    <div class="job-footer">
                      <div class="job-stats">
                        <span class="stat">
                          <i class="fas fa-eye"></i>
                          {{ job.view_count }}
                        </span>
                        <span class="stat">
                          <i class="fas fa-paper-plane"></i>
                          {{ job.proposal_count }} proposals
                        </span>
                        <span v-if="job.status === 'in_progress'" class="stat">
                          <i class="fas fa-chart-line"></i>
                          {{ job.progress || 0 }}% hoàn thành
                        </span>
                      </div>
  
                      <div class="job-actions" @click.stop>
                        <button v-if="job.status === 'draft'" @click="editJob(job)" class="action-btn edit">
                          <i class="fas fa-edit"></i>
                          Chỉnh sửa
                        </button>
                        
                        <button v-if="job.status === 'draft'" @click="publishJob(job)" class="action-btn publish">
                          <i class="fas fa-paper-plane"></i>
                          Đăng
                        </button>
                        
                        <button v-if="job.status === 'open'" @click="pauseJob(job)" class="action-btn pause">
                          <i class="fas fa-pause"></i>
                          Tạm dừng
                        </button>
                        
                        <div class="dropdown">
                          <button @click="toggleJobMenu(job.id)" class="action-btn more">
                            <i class="fas fa-ellipsis-h"></i>
                          </button>
                          
                          <div v-if="showJobMenu[job.id]" class="dropdown-menu">
                            <button @click="duplicateJob(job)">
                              <i class="fas fa-copy"></i>
                              Nhân bản
                            </button>
                            <button v-if="canDeleteJob(job)" @click="deleteJob(job)" class="danger">
                              <i class="fas fa-trash"></i>
                              Xóa
                            </button>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </main>
  
            <!-- Right Sidebar -->
            <aside class="right-sidebar">
              <div class="sidebar-section">
                <h3>Hoạt động gần đây</h3>
                <div class="recent-activity">
                  <div v-for="activity in recentActivities" :key="activity.id" class="activity-item">
                    <div class="activity-icon">
                      <i :class="activity.icon"></i>
                    </div>
                    <div class="activity-content">
                      <p>{{ activity.content }}</p>
                      <span class="activity-time">{{ formatTimeAgo(activity.time) }}</span>
                    </div>
                  </div>
                </div>
              </div>
  
              <div class="sidebar-section">
                <h3>Thống kê tháng này</h3>
                <div class="monthly-stats">
                  <div class="stat-row">
                    <span class="stat-label">Việc đã đăng</span>
                    <span class="stat-value">{{ stats.newJobsThisMonth }}</span>
                  </div>
                  <div class="stat-row">
                    <span class="stat-label">Proposals nhận</span>
                    <span class="stat-value">{{ stats.newProposalsThisMonth }}</span>
                  </div>
                  <div class="stat-row">
                    <span class="stat-label">Dự án hoàn thành</span>
                    <span class="stat-value">{{ stats.completedThisMonth }}</span>
                  </div>
                  <div class="stat-row">
                    <span class="stat-label">Chi tiêu</span>
                    <span class="stat-value">${{ formatNumber(stats.spentThisMonth) }}</span>
                  </div>
                </div>
              </div>
  
              <div class="sidebar-section">
                <h3>Tips hữu ích</h3>
                <div class="tips-list">
                  <div class="tip-item">
                    <i class="fas fa-lightbulb"></i>
                    <p>Viết mô tả chi tiết để thu hút freelancer chất lượng</p>
                  </div>
                  <div class="tip-item">
                    <i class="fas fa-star"></i>
                    <p>Đánh dấu "Nổi bật" để tăng khả năng hiển thị</p>
                  </div>
                  <div class="tip-item">
                    <i class="fas fa-clock"></i>
                    <p>Phản hồi proposals nhanh để không bỏ lỡ ứng viên tốt</p>
                  </div>
                </div>
              </div>
            </aside>
          </div>
        </div>
      </div>
  
      <!-- Create/Edit Job Modal -->
      <div v-if="showCreateJobModal || showEditJobModal" class="modal-overlay" @click="closeJobModal">
        <div class="modal-content large-modal" @click.stop>
          <div class="modal-header">
            <h3>{{ editingJob ? 'Chỉnh sửa việc làm' : 'Đăng việc làm mới' }}</h3>
            <button class="close-btn" @click="closeJobModal">
              <i class="fas fa-times"></i>
            </button>
          </div>
          
          <div class="modal-body">
            <form @submit.prevent="saveJob">
              <div class="form-section">
                <h4>Thông tin cơ bản</h4>
                
                <div class="form-group">
                  <label>Tiêu đề việc làm *</label>
                  <input 
                    type="text" 
                    v-model="jobForm.title" 
                    placeholder="Ví dụ: Thiết kế website cho startup công nghệ"
                    class="form-input"
                    required
                  />
                </div>
  
                <div class="form-group">
                  <label>Mô tả chi tiết *</label>
                  <textarea 
                    v-model="jobForm.description" 
                    placeholder="Mô tả chi tiết về dự án, yêu cầu kỹ thuật, timeline, deliverables..."
                    class="form-textarea"
                    rows="5"
                    required
                  ></textarea>
                </div>
  
                <div class="form-row">
                  <div class="form-group">
                    <label>Danh mục *</label>
                    <select v-model="jobForm.category_id" class="form-select" required>
                      <option value="">Chọn danh mục</option>
                      <option v-for="category in categories" :key="category.id" :value="category.id">
                        {{ category.name }}
                      </option>
                    </select>
                  </div>
                  
                  <div class="form-group">
                    <label>Kinh nghiệm yêu cầu</label>
                    <select v-model="jobForm.experience_level" class="form-select">
                      <option value="entry">Mới bắt đầu</option>
                      <option value="intermediate">Trung cấp</option>
                      <option value="expert">Chuyên gia</option>
                    </select>
                  </div>
                </div>
              </div>
  
              <div class="form-section">
                <h4>Ngân sách & Timeline</h4>
                
                <div class="form-row">
                  <div class="form-group">
                    <label>Loại ngân sách *</label>
                    <select v-model="jobForm.budget_type" class="form-select" required>
                      <option value="fixed">Theo dự án</option>
                      <option value="hourly">Theo giờ</option>
                    </select>
                  </div>
                  
                  <div class="form-group">
                    <label>Hình thức làm việc</label>
                    <select v-model="jobForm.location_requirement" class="form-select">
                      <option value="remote">Remote</option>
                      <option value="onsite">Tại văn phòng</option>
                      <option value="hybrid">Hybrid</option>
                    </select>
                  </div>
                </div>
  
                <div class="form-group">
                  <label>Ngân sách ($) *</label>
                  <div class="budget-inputs">
                    <input 
                      type="number" 
                      v-model="jobForm.budget_min" 
                      placeholder="Từ"
                      class="form-input"
                      min="1"
                      required
                    />
                    <span>đến</span>
                    <input 
                      type="number" 
                      v-model="jobForm.budget_max" 
                      placeholder="Đến"
                      class="form-input"
                      min="1"
                      required
                    />
                  </div>
                </div>
  
                <div class="form-row">
                  <div class="form-group">
                    <label>Deadline dự án</label>
                    <input 
                      type="date" 
                      v-model="jobForm.deadline" 
                      class="form-input"
                      :min="getTomorrowDate()"
                    />
                  </div>
                  
                  <div class="form-group">
                    <label>Thời hạn nộp proposal</label>
                    <input 
                      type="date" 
                      v-model="jobForm.proposal_deadline" 
                      class="form-input"
                      :min="getTomorrowDate()"
                    />
                  </div>
                </div>
              </div>
  
              <div class="form-section">
                <h4>Yêu cầu & Tùy chọn</h4>
                
                <div class="form-group">
                  <label>Yêu cầu chi tiết</label>
                  <textarea 
                    v-model="jobForm.requirements" 
                    placeholder="Các yêu cầu cụ thể về kỹ năng, kinh nghiệm, portfolio, chứng chỉ..."
                    class="form-textarea"
                    rows="4"
                  ></textarea>
                </div>
  
                <div class="form-group">
                  <label>Tùy chọn thêm</label>
                  <div class="checkbox-group">
                    <label class="checkbox-item">
                      <input type="checkbox" v-model="jobForm.featured" />
                      <span>Đánh dấu nổi bật (+$10) - Tăng khả năng hiển thị</span>
                    </label>
                    <label class="checkbox-item">
                      <input type="checkbox" v-model="jobForm.urgent" />
                      <span>Đánh dấu gấp (+$5) - Ưu tiên trong kết quả tìm kiếm</span>
                    </label>
                  </div>
                </div>
              </div>
            </form>
          </div>
  
          <div class="modal-footer">
            <button type="button" class="btn-cancel" @click="closeJobModal">
              Hủy
            </button>
            <button 
              v-if="!editingJob"
              type="button" 
              @click="saveJob('draft')" 
              :disabled="saving || !canSaveDraft"
              class="btn-secondary"
            >
              {{ saving ? 'Đang lưu...' : 'Lưu nháp' }}
            </button>
            <button 
              type="button" 
              @click="saveJob(editingJob ? 'update' : 'publish')" 
              :disabled="saving || !isValidJob"
              class="btn-primary"
            >
              {{ saving ? 'Đang xử lý...' : (editingJob ? 'Cập nhật' : 'Đăng ngay') }}
            </button>
          </div>
        </div>
      </div>
  
      <!-- Job Detail Modal -->
      <div v-if="selectedJob" class="modal-overlay" @click="closeJobDetail">
        <div class="modal-content large-modal" @click.stop>
          <div class="modal-header">
            <h3>{{ selectedJob.title }}</h3>
            <button class="close-btn" @click="closeJobDetail">
              <i class="fas fa-times"></i>
            </button>
          </div>
          
          <div class="modal-body">
            <div class="job-detail-content">
              <!-- Status and Actions -->
              <div class="detail-header">
                <div class="status-badges">
                  <span :class="getStatusBadgeClass(selectedJob.status)" class="status-badge">
                    {{ getStatusLabel(selectedJob.status) }}
                  </span>
                  <span v-if="selectedJob.featured" class="badge badge-featured">
                    <i class="fas fa-star"></i>
                    Nổi bật
                  </span>
                  <span v-if="selectedJob.urgent" class="badge badge-urgent">
                    <i class="fas fa-clock"></i>
                    Gấp
                  </span>
                </div>
                
                <div class="job-actions">
                  <button v-if="selectedJob.status === 'draft'" @click="editJob(selectedJob)" class="btn-secondary">
                    <i class="fas fa-edit"></i>
                    Chỉnh sửa
                  </button>
                  <button v-if="selectedJob.status === 'draft'" @click="publishJob(selectedJob)" class="btn-primary">
                    <i class="fas fa-paper-plane"></i>
                    Đăng ngay
                  </button>
                </div>
              </div>
  
              <!-- Job Stats -->
              <div class="detail-stats">
                <div class="stat-item">
                  <i class="fas fa-eye"></i>
                  <span>{{ selectedJob.view_count }} lượt xem</span>
                </div>
                <div class="stat-item">
                  <i class="fas fa-paper-plane"></i>
                  <span>{{ selectedJob.proposal_count }} proposals</span>
                </div>
                <div class="stat-item">
                  <i class="fas fa-calendar"></i>
                  <span>{{ formatDate(selectedJob.created_at) }}</span>
                </div>
              </div>
  
              <!-- Description -->
              <div class="detail-section">
                <h4>Mô tả dự án</h4>
                <div class="content-text">{{ selectedJob.description }}</div>
              </div>
  
              <!-- Requirements -->
              <div v-if="selectedJob.requirements" class="detail-section">
                <h4>Yêu cầu</h4>
                <div class="content-text">{{ selectedJob.requirements }}</div>
              </div>
  
              <!-- Project Info -->
              <div class="detail-section">
                <h4>Thông tin dự án</h4>
                <div class="project-info-grid">
                  <div class="info-item">
                    <span class="info-label">Ngân sách:</span>
                    <span class="info-value">
                      ${{ formatNumber(selectedJob.budget_min) }} - ${{ formatNumber(selectedJob.budget_max) }}
                    </span>
                  </div>
                  <div class="info-item">
                    <span class="info-label">Loại:</span>
                    <span class="info-value">{{ getJobTypeLabel(selectedJob.budget_type) }}</span>
                  </div>
                  <div class="info-item">
                    <span class="info-label">Kinh nghiệm:</span>
                    <span class="info-value">{{ getExperienceLabel(selectedJob.experience_level) }}</span>
                  </div>
                  <div class="info-item">
                    <span class="info-label">Hình thức:</span>
                    <span class="info-value">{{ getLocationLabel(selectedJob.location_requirement) }}</span>
                  </div>
                  <div class="info-item">
                    <span class="info-label">Danh mục:</span>
                    <span class="info-value">{{ selectedJob.category_name }}</span>
                  </div>
                  <div v-if="selectedJob.deadline" class="info-item">
                    <span class="info-label">Deadline:</span>
                    <span class="info-value">{{ formatDate(selectedJob.deadline) }}</span>
                  </div>
                </div>
              </div>
            </div>
          </div>
  
          <div class="modal-footer">
            <button class="btn-secondary" @click="closeJobDetail">
              Đóng
            </button>
          </div>
        </div>
      </div>
    </div>
  </template>
  
  <script>
  import { useAuthStore } from '@/stores/auth.js'
  import apiService from '@/services/api'
  
  export default {
    name: 'ClientDashboard',
    setup() {
      const authStore = useAuthStore()
      return { authStore }
    },
    
    data() {
      return {
        // Tab management
        activeTab: 'overview',
        
        // Loading states
        loading: false,
        saving: false,
        
        // Data
        jobs: [],
        categories: [],
        
        // Stats
        stats: {
          totalJobs: 0,
          activeJobs: 0,
          draftJobs: 0,
          totalProposals: 0,
          totalSpent: 0,
          totalViews: 0,
          avgRating: 0,
          avgBudget: 0,
          newJobsThisMonth: 0,
          newProposalsThisMonth: 0,
          completedThisMonth: 0,
          spentThisMonth: 0,
          activeJobsChange: 0
        },
        
        // Filters
        searchQuery: '',
        categoryFilter: '',
        sortBy: 'newest',
        
        // Modals
        showCreateJobModal: false,
        showEditJobModal: false,
        selectedJob: null,
        editingJob: null,
        
        // Form
        jobForm: {
          title: '',
          description: '',
          requirements: '',
          category_id: '',
          budget_type: 'fixed',
          budget_min: '',
          budget_max: '',
          experience_level: 'intermediate',
          location_requirement: 'remote',
          deadline: '',
          proposal_deadline: '',
          featured: false,
          urgent: false
        },
        
        // UI states
        showJobMenu: {},
        
        // Activities
        recentActivities: [
          {
            id: 1,
            content: 'Bạn nhận được 3 proposals mới cho "Website Design"',
            icon: 'fas fa-paper-plane text-blue',
            time: new Date(Date.now() - 30 * 60 * 1000)
          },
          {
            id: 2,
            content: 'Dự án "Mobile App" đã hoàn thành 75%',
            icon: 'fas fa-chart-line text-green',
            time: new Date(Date.now() - 2 * 60 * 60 * 1000)
          },
          {
            id: 3,
            content: 'Freelancer John Nguyen đã gửi milestone report',
            icon: 'fas fa-file-alt text-purple',
            time: new Date(Date.now() - 4 * 60 * 60 * 1000)
          }
        ]
      }
    },
    
    computed: {
      recentJobs() {
        return this.jobs.slice(0, 5)
      },
      
      filteredJobs() {
        let filtered = this.jobs
        
        // Filter by tab
        if (this.activeTab === 'drafts') {
          filtered = filtered.filter(job => job.status === 'draft')
        } else if (this.activeTab === 'active') {
          filtered = filtered.filter(job => job.status === 'open')
        }
        
        // Search filter
        if (this.searchQuery) {
          const query = this.searchQuery.toLowerCase()
          filtered = filtered.filter(job => 
            job.title.toLowerCase().includes(query) ||
            job.description.toLowerCase().includes(query)
          )
        }
        
        // Category filter
        if (this.categoryFilter) {
          filtered = filtered.filter(job => job.category_id == this.categoryFilter)
        }
        
        return filtered
      },
      
      isValidJob() {
        return this.jobForm.title && 
               this.jobForm.description && 
               this.jobForm.category_id && 
               this.jobForm.budget_min && 
               this.jobForm.budget_max &&
               parseFloat(this.jobForm.budget_min) < parseFloat(this.jobForm.budget_max)
      },
      
      canSaveDraft() {
        return this.jobForm.title && this.jobForm.description
      }
    },
    
    async mounted() {
      await this.loadInitialData()
    },
    
    methods: {
      async loadInitialData() {
        await Promise.all([
          this.loadCategories(),
          this.loadJobs()
        ])
        this.calculateStats()
      },
      
      async loadCategories() {
        try {
          const response = await apiService.get('/api/jobs/categories.php')
          if (response.data.success) {
            this.categories = response.data.data
          }
        } catch (error) {
          console.error('Load categories error:', error)
          // Fallback categories
          this.categories = [
            { id: 1, name: 'Technology' },
            { id: 2, name: 'Design & Creative' },
            { id: 3, name: 'Writing & Translation' },
            { id: 4, name: 'Marketing & Sales' },
            { id: 5, name: 'Business & Finance' }
          ]
        }
      },
      
      async loadJobs() {
        this.loading = true
        try {
          // Mock data for now - replace with actual API call
          this.loadMockJobs()
          
          // TODO: Replace with actual API
          // const response = await apiService.get('/api/jobs/my-jobs.php')
          // if (response.data.success) {
          //   this.jobs = response.data.data
          // }
        } catch (error) {
          console.error('Load jobs error:', error)
          this.$toast?.error('Lỗi khi tải danh sách việc làm')
        } finally {
          this.loading = false
        }
      },
      
      loadMockJobs() {
        this.jobs = [
          {
            id: 1,
            title: 'Build a Modern E-commerce Website',
            description: 'We need a responsive e-commerce website built with modern technologies. Must include payment integration, admin panel, and mobile optimization. The website should handle thousands of products and users.',
            requirements: '3+ years experience in web development, proficiency in React/Vue.js, experience with payment gateways, knowledge of e-commerce best practices',
            budget_min: 2000,
            budget_max: 3500,
            budget_type: 'fixed',
            category_id: 1,
            category_name: 'Technology',
            experience_level: 'intermediate',
            location_requirement: 'remote',
            status: 'open',
            featured: true,
            urgent: false,
            proposal_count: 12,
            view_count: 156,
            published_at: '2025-07-27 03:34:56',
            created_at: '2025-07-27 03:34:56',
            deadline: '2025-09-15'
          },
          {
            id: 2,
            title: 'Logo Design for Tech Startup',
            description: 'Need a modern, professional logo for our technology startup. Should work well on both digital and print media.',
            requirements: 'Portfolio of logo designs, experience with tech companies, provide multiple concepts',
            budget_min: 300,
            budget_max: 800,
            budget_type: 'fixed',
            category_id: 2,
            category_name: 'Design & Creative',
            experience_level: 'intermediate',
            location_requirement: 'remote',
            status: 'draft',
            featured: false,
            urgent: false,
            proposal_count: 0,
            view_count: 0,
            created_at: '2025-07-27 08:15:00'
          },
          {
            id: 3,
            title: 'Mobile App UI/UX Design',
            description: 'Design user interface and user experience for our mobile application. Need modern, intuitive design.',
            requirements: 'Experience with mobile app design, proficiency in Figma, understanding of iOS/Android guidelines',
            budget_min: 1500,
            budget_max: 2500,
            budget_type: 'fixed',
            category_id: 2,
            category_name: 'Design & Creative',
            experience_level: 'expert',
            location_requirement: 'remote',
            status: 'in_progress',
            featured: false,
            urgent: true,
            proposal_count: 8,
            view_count: 89,
            progress: 65,
            published_at: '2025-07-25 10:20:00',
            created_at: '2025-07-25 10:20:00'
          },
          {
            id: 4,
            title: 'Content Writing for Tech Blog',
            description: 'Write engaging blog posts about latest technology trends, AI, and software development.',
            requirements: 'Strong writing skills, knowledge of tech industry, SEO experience',
            budget_min: 25,
            budget_max: 40,
            budget_type: 'hourly',
            category_id: 3,
            category_name: 'Writing & Translation',
            experience_level: 'intermediate',
            location_requirement: 'remote',
            status: 'completed',
            featured: false,
            urgent: false,
            proposal_count: 15,
            view_count: 203,
            published_at: '2025-07-20 14:30:00',
            created_at: '2025-07-20 14:30:00'
          },
          {
            id: 5,
            title: 'React Dashboard Development',
            description: 'Create a comprehensive admin dashboard using React with data visualization and real-time updates.',
            requirements: 'Expert React skills, experience with chart libraries, API integration',
            budget_min: 1800,
            budget_max: 2800,
            budget_type: 'fixed',
            category_id: 1,
            category_name: 'Technology',
            experience_level: 'expert',
            location_requirement: 'remote',
            status: 'open',
            featured: false,
            urgent: true,
            proposal_count: 6,
            view_count: 78,
            published_at: '2025-07-26 16:45:00',
            created_at: '2025-07-26 16:45:00'
          }
        ]
      },
      
      calculateStats() {
        this.stats = {
          totalJobs: this.jobs.length,
          activeJobs: this.jobs.filter(job => job.status === 'open').length,
          draftJobs: this.jobs.filter(job => job.status === 'draft').length,
          totalProposals: this.jobs.reduce((sum, job) => sum + job.proposal_count, 0),
          totalViews: this.jobs.reduce((sum, job) => sum + job.view_count, 0),
          totalSpent: this.jobs
            .filter(job => job.status === 'completed')
            .reduce((sum, job) => sum + job.budget_max, 0),
          avgRating: 4.8, // Mock rating
          avgBudget: this.jobs.length > 0 ? 
            this.jobs.reduce((sum, job) => sum + job.budget_max, 0) / this.jobs.length : 0,
          newJobsThisMonth: 3,
          newProposalsThisMonth: 24,
          completedThisMonth: 1,
          spentThisMonth: 2500,
          activeJobsChange: 15
        }
      },
      
      // Job Actions
      async saveJob(action = 'publish') {
        if (action === 'publish' && !this.isValidJob) return
        if (action === 'draft' && !this.canSaveDraft) return
        
        this.saving = true
        try {
          const jobData = { ...this.jobForm }
          
          // Convert string numbers to numbers
          jobData.budget_min = parseFloat(jobData.budget_min)
          jobData.budget_max = parseFloat(jobData.budget_max)
          jobData.category_id = parseInt(jobData.category_id)
          
          console.log('Saving job with data:', jobData)
          
          let response
          if (this.editingJob) {
            // Update existing job
            response = await apiService.put(`/api/jobs/update.php?id=${this.editingJob.id}`, jobData)
          } else {
            // Create new job
            response = await apiService.post('/api/jobs/create.php', jobData)
            
            if (response.data.success && action === 'publish') {
              // Publish immediately after creation
              const jobId = response.data.job_id
              await apiService.post(`/api/jobs/publish.php?id=${jobId}`)
            }
          }
          
          if (response.data.success) {
            const message = this.editingJob ? 'Cập nhật thành công!' : 
              (action === 'draft' ? 'Lưu nháp thành công!' : 'Đăng việc thành công!')
            
            this.$toast?.success(message)
            this.closeJobModal()
            await this.loadJobs()
          }
        } catch (error) {
          console.error('Save job error:', error)
          const errorMsg = error.response?.data?.error || 'Có lỗi xảy ra khi lưu việc làm'
          this.$toast?.error(errorMsg)
        } finally {
          this.saving = false
        }
      },
      
      async publishJob(job) {
        try {
          const response = await apiService.post(`/api/jobs/publish.php?id=${job.id}`)
          if (response.data.success) {
            this.$toast?.success('Đăng việc thành công!')
            await this.loadJobs()
          }
        } catch (error) {
          console.error('Publish job error:', error)
          this.$toast?.error(error.response?.data?.error || 'Lỗi khi đăng việc')
        }
      },
      
      async deleteJob(job) {
        if (!confirm('Bạn có chắc muốn xóa việc làm này?')) return
        
        try {
          const response = await apiService.delete(`/api/jobs/delete.php?id=${job.id}`)
          if (response.data.success) {
            this.$toast?.success('Xóa việc làm thành công!')
            await this.loadJobs()
          }
        } catch (error) {
          this.$toast?.error(error.response?.data?.error || 'Lỗi khi xóa việc làm')
        }
        
        this.showJobMenu[job.id] = false
      },
      
      editJob(job) {
        this.editingJob = job
        this.jobForm = {
          title: job.title,
          description: job.description,
          requirements: job.requirements || '',
          category_id: job.category_id,
          budget_type: job.budget_type,
          budget_min: job.budget_min,
          budget_max: job.budget_max,
          experience_level: job.experience_level,
          location_requirement: job.location_requirement,
          deadline: job.deadline || '',
          proposal_deadline: job.proposal_deadline || '',
          featured: job.featured || false,
          urgent: job.urgent || false
        }
        this.showEditJobModal = true
        this.selectedJob = null
      },
      
      viewJob(job) {
        this.selectedJob = job
      },
      
      duplicateJob(job) {
        this.jobForm = {
          title: `Copy of ${job.title}`,
          description: job.description,
          requirements: job.requirements || '',
          category_id: job.category_id,
          budget_type: job.budget_type,
          budget_min: job.budget_min,
          budget_max: job.budget_max,
          experience_level: job.experience_level,
          location_requirement: job.location_requirement,
          deadline: '',
          proposal_deadline: '',
          featured: false,
          urgent: false
        }
        this.showCreateJobModal = true
        this.showJobMenu[job.id] = false
      },
      
      pauseJob(job) {
        this.$toast?.info('Tính năng tạm dừng việc làm sẽ có sớm')
      },
      
      closeJobModal() {
        this.showCreateJobModal = false
        this.showEditJobModal = false
        this.editingJob = null
        this.resetJobForm()
      },
      
      closeJobDetail() {
        this.selectedJob = null
      },
      
      resetJobForm() {
        this.jobForm = {
          title: '',
          description: '',
          requirements: '',
          category_id: '',
          budget_type: 'fixed',
          budget_min: '',
          budget_max: '',
          experience_level: 'intermediate',
          location_requirement: 'remote',
          deadline: '',
          proposal_deadline: '',
          featured: false,
          urgent: false
        }
      },
      
      // UI Methods
      toggleJobMenu(jobId) {
        // Close all other menus
        Object.keys(this.showJobMenu).forEach(id => {
          if (id != jobId) this.showJobMenu[id] = false
        })
        // Toggle current menu
        this.showJobMenu[jobId] = !this.showJobMenu[jobId]
      },
      
      applyFilters() {
        // Filters are applied via computed property
      },
      
      applySorting() {
        const sortFunctions = {
          newest: (a, b) => new Date(b.created_at) - new Date(a.created_at),
          oldest: (a, b) => new Date(a.created_at) - new Date(b.created_at),
          most_proposals: (a, b) => b.proposal_count - a.proposal_count,
          most_views: (a, b) => b.view_count - a.view_count
        }
        
        if (sortFunctions[this.sortBy]) {
          this.jobs.sort(sortFunctions[this.sortBy])
        }
      },
      
      // Utility Methods
      getTabTitle() {
        const titles = {
          overview: 'Tổng quan',
          all: 'Tất cả việc làm',
          drafts: 'Bản nháp',
          active: 'Đang mở',
          proposals: 'Proposals nhận được'
        }
        return titles[this.activeTab] || 'Dashboard'
      },
      
      getTabDescription() {
        const descriptions = {
          overview: `Xin chào ${this.authStore.user?.first_name || 'Client'}, đây là tổng quan về các dự án của bạn`,
          all: `Quản lý tất cả ${this.stats.totalJobs} việc làm đã đăng`,
          drafts: `${this.stats.draftJobs} bản nháp chưa được đăng`,
          active: `${this.stats.activeJobs} việc làm đang mở nhận proposals`,
          proposals: `${this.stats.totalProposals} proposals đã nhận từ freelancers`
        }
        return descriptions[this.activeTab] || ''
      },
      
      getEmptyStateTitle() {
        const titles = {
          all: 'Chưa có việc làm nào',
          drafts: 'Chưa có bản nháp nào',
          active: 'Chưa có việc làm đang mở',
          proposals: 'Chưa có proposals nào'
        }
        return titles[this.activeTab] || 'Không có dữ liệu'
      },
      
      getEmptyStateMessage() {
        const messages = {
          all: 'Bắt đầu bằng cách đăng việc làm đầu tiên của bạn',
          drafts: 'Các bản nháp sẽ được lưu tại đây',
          active: 'Đăng việc làm để bắt đầu nhận proposals',
          proposals: 'Proposals sẽ hiển thị khi có freelancer quan tâm'
        }
        return messages[this.activeTab] || 'Bắt đầu tạo nội dung mới'
      },
      
      getEmptyStateAction() {
        return this.activeTab === 'proposals' ? 'Đăng việc làm' : 'Đăng việc đầu tiên'
      },
      
      canDeleteJob(job) {
        return (job.status === 'draft' || (job.status === 'open' && job.proposal_count === 0))
      },
      
      getStatusLabel(status) {
        const labels = {
          draft: 'Nháp',
          open: 'Đang mở',
          in_progress: 'Đang thực hiện',
          completed: 'Hoàn thành',
          cancelled: 'Đã hủy',
          paused: 'Tạm dừng'
        }
        return labels[status] || status
      },
      
      getStatusBadgeClass(status) {
        const classes = {
          draft: 'status-draft',
          open: 'status-open',
          in_progress: 'status-progress',
          completed: 'status-completed',
          cancelled: 'status-cancelled',
          paused: 'status-paused'
        }
        return classes[status] || 'status-default'
      },
      
      getJobStatusIcon(status) {
        const icons = {
          draft: 'bg-gray',
          open: 'bg-green',
          in_progress: 'bg-blue',
          completed: 'bg-green',
          cancelled: 'bg-red',
          paused: 'bg-yellow'
        }
        return icons[status] || 'bg-gray'
      },
      
      getJobIcon(status) {
        const icons = {
          draft: 'fas fa-edit',
          open: 'fas fa-clock',
          in_progress: 'fas fa-cog',
          completed: 'fas fa-check',
          cancelled: 'fas fa-times',
          paused: 'fas fa-pause'
        }
        return icons[status] || 'fas fa-briefcase'
      },
      
      getJobTypeLabel(type) {
        return type === 'fixed' ? 'Theo dự án' : 'Theo giờ'
      },
      
      getExperienceLabel(level) {
        const labels = {
          entry: 'Mới bắt đầu',
          intermediate: 'Trung cấp',
          expert: 'Chuyên gia'
        }
        return labels[level] || level
      },
      
      getLocationLabel(location) {
        const labels = {
          remote: 'Remote',
          onsite: 'Tại văn phòng',
          hybrid: 'Hybrid'
        }
        return labels[location] || location
      },
      
      getTomorrowDate() {
        const tomorrow = new Date()
        tomorrow.setDate(tomorrow.getDate() + 1)
        return tomorrow.toISOString().split('T')[0]
      },
      
      formatNumber(num) {
        return new Intl.NumberFormat().format(num || 0)
      },
      
      formatDate(dateString) {
        if (!dateString) return ''
        return new Date(dateString).toLocaleDateString('vi-VN')
      },
      
      formatTimeAgo(dateString) {
        if (!dateString) return ''
        const now = new Date()
        const date = new Date(dateString)
        const diff = Math.floor((now - date) / 1000)
        
        if (diff < 60) return 'Vừa xong'
        if (diff < 3600) return `${Math.floor(diff / 60)} phút trước`
        if (diff < 86400) return `${Math.floor(diff / 3600)} giờ trước`
        return `${Math.floor(diff / 86400)} ngày trước`
      },
      
      truncateText(text, maxLength) {
        if (!text) return ''
        return text.length > maxLength ? text.substring(0, maxLength) + '...' : text
      }
    }
  }
  </script>
  
  <style scoped>
  /* Import styles from Home.vue and extend */
  /* Reset and base styles */
  * {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
  }
  
  .jobs-dashboard {
    min-height: 100vh;
    background: #f8fafc;
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
  }
  
  .container {
    max-width: 1400px;
    margin: 0 auto;
    padding: 0 20px;
  }
  
  /* Main Layout */
  .main-container {
    padding: 20px 0;
  }
  
  .dashboard-grid {
    display: grid;
    grid-template-columns: 300px 1fr 320px;
    gap: 24px;
    align-items: start;
  }
  
  /* Sidebars */
  .left-sidebar,
  .right-sidebar {
    position: sticky;
    top: 20px;
  }
  
  .sidebar-section {
    background: white;
    border-radius: 12px;
    padding: 24px;
    margin-bottom: 20px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    border: 1px solid #e2e8f0;
  }
  
  .sidebar-section h3 {
    font-size: 18px;
    font-weight: 600;
    margin-bottom: 16px;
    color: #1a202c;
  }
  
  /* Left Sidebar Navigation */
  .sidebar-nav {
    display: flex;
    flex-direction: column;
    gap: 4px;
  }
  
  .nav-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    padding: 12px;
    border-radius: 8px;
    text-decoration: none;
    color: #666;
    transition: all 0.2s;
    cursor: pointer;
  }
  
  .nav-item:hover,
  .nav-item.active {
    background: #667eea;
    color: white;
  }
  
  .nav-item i {
    width: 20px;
    text-align: center;
  }
  
  .count-badge {
    background: rgba(255, 255, 255, 0.2);
    color: white;
    padding: 2px 6px;
    border-radius: 10px;
    font-size: 11px;
    font-weight: 500;
  }
  
  .nav-item:not(.active) .count-badge {
    background: #e2e8f0;
    color: #4a5568;
  }
  
  /* Quick Stats */
  .quick-stats {
    display: flex;
    flex-direction: column;
    gap: 12px;
  }
  
  .stat-card {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px;
    background: #f8f9fa;
    border-radius: 8px;
  }
  
  .stat-icon {
    width: 40px;
    height: 40px;
    background: #667eea;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
  }
  
  .stat-value {
    font-weight: 600;
    font-size: 18px;
    color: #333;
    display: block;
  }
  
  .stat-label {
    font-size: 12px;
    color: #666;
  }
  
  /* Main Feed */
  .main-feed {
    max-width: 100%;
  }
  
  .feed-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 24px;
    background: white;
    padding: 24px;
    border-radius: 12px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    border: 1px solid #e2e8f0;
  }
  
  .header-info h2 {
    font-size: 24px;
    font-weight: 600;
    color: #1a202c;
    margin-bottom: 4px;
  }
  
  .header-info p {
    color: #718096;
    font-size: 14px;
  }
  
  .header-actions {
    display: flex;
    align-items: center;
    gap: 12px;
  }
  
  .sort-select {
    padding: 8px 12px;
    border: 1px solid #e2e8f0;
    border-radius: 6px;
    font-size: 14px;
  }
  
  .btn-primary {
    background: #667eea;
    color: white;
    border: none;
    padding: 10px 16px;
    border-radius: 8px;
    font-size: 14px;
    font-weight: 500;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 8px;
    transition: background 0.2s;
  }
  
  .btn-primary:hover {
    background: #5a67d8;
  }
  
  .btn-primary:disabled {
    background: #a0aec0;
    cursor: not-allowed;
  }
  
  .btn-secondary {
    background: #edf2f7;
    color: #4a5568;
    border: none;
    padding: 10px 16px;
    border-radius: 8px;
    font-size: 14px;
    font-weight: 500;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 8px;
    transition: background 0.2s;
  }
  
  .btn-secondary:hover {
    background: #e2e8f0;
  }
  
  .btn-cancel {
    background: #f7fafc;
    color: #4a5568;
    border: 1px solid #e2e8f0;
    padding: 10px 16px;
    border-radius: 8px;
    font-size: 14px;
    font-weight: 500;
    cursor: pointer;
    transition: background 0.2s;
  }
  
  .btn-cancel:hover {
    background: #edf2f7;
  }
  
  /* Overview Content */
  .overview-content {
    background: white;
    border-radius: 12px;
    padding: 24px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    border: 1px solid #e2e8f0;
  }
  
  .stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
    margin-bottom: 32px;
  }
  
  .stat-card-large {
    display: flex;
    align-items: center;
    gap: 16px;
    padding: 20px;
    background: #f8fafc;
    border-radius: 12px;
    border: 1px solid #e2e8f0;
  }
  
  .stat-card-large .stat-icon {
    width: 50px;
    height: 50px;
    border-radius: 10px;
    font-size: 20px;
  }
  
  .stat-icon.bg-blue { background: #667eea; }
  .stat-icon.bg-green { background: #48bb78; }
  .stat-icon.bg-purple { background: #9f7aea; }
  .stat-icon.bg-yellow { background: #ed8936; }
  .stat-icon.bg-red { background: #f56565; }
  .stat-icon.bg-gray { background: #a0aec0; }
  
  .stat-details h3 {
    font-size: 28px;
    font-weight: 700;
    color: #1a202c;
    margin-bottom: 4px;
  }
  
  .stat-details p {
    font-size: 14px;
    color: #4a5568;
    margin-bottom: 4px;
  }
  
  .stat-change {
    font-size: 12px;
    font-weight: 500;
  }
  
  .stat-change.positive {
    color: #48bb78;
  }
  
  .stat-change.negative {
    color: #f56565;
  }
  
  /* Recent Section */
  .recent-section {
    margin-top: 32px;
  }
  
  .recent-section h3 {
    font-size: 18px;
    font-weight: 600;
    color: #1a202c;
    margin-bottom: 16px;
  }
  
  .recent-jobs {
    display: flex;
    flex-direction: column;
    gap: 12px;
  }
  
  .recent-job-card {
    display: flex;
    align-items: center;
    gap: 16px;
    padding: 16px;
    background: #f8fafc;
    border-radius: 8px;
    cursor: pointer;
    transition: background 0.2s;
  }
  
  .recent-job-card:hover {
    background: #edf2f7;
  }
  
  .job-icon {
    width: 40px;
    height: 40px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 16px;
  }
  
  .job-info {
    flex: 1;
  }
  
  .job-info h4 {
    font-size: 14px;
    font-weight: 600;
    color: #1a202c;
    margin-bottom: 4px;
  }
  
  .job-info p {
    font-size: 12px;
    color: #4a5568;
    margin-bottom: 2px;
  }
  
  .job-time {
    font-size: 11px;
    color: #a0aec0;
  }
  
  .job-actions {
    display: flex;
    gap: 8px;
  }
  
  .btn-small {
    width: 32px;
    height: 32px;
    background: #f7fafc;
    border: 1px solid #e2e8f0;
    border-radius: 6px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #4a5568;
    cursor: pointer;
    transition: all 0.2s;
  }
  
  .btn-small:hover {
    background: #edf2f7;
    color: #2d3748;
  }
  
  /* Jobs Content */
  .jobs-content {
    background: white;
    border-radius: 12px;
    padding: 24px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    border: 1px solid #e2e8f0;
  }
  
  /* Filters Bar */
  .filters-bar {
    display: flex;
    gap: 16px;
    margin-bottom: 24px;
    align-items: center;
  }
  
  .search-box {
    position: relative;
    flex: 1;
    max-width: 400px;
  }
  
  .search-box i {
    position: absolute;
    left: 12px;
    top: 50%;
    transform: translateY(-50%);
    color: #a0aec0;
  }
  
  .search-box input {
    width: 100%;
    padding: 10px 12px 10px 40px;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    font-size: 14px;
    transition: border-color 0.2s;
  }
  
  .search-box input:focus {
    outline: none;
    border-color: #667eea;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
  }
  
  .filter-select {
    padding: 10px 12px;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    font-size: 14px;
    min-width: 150px;
  }
  
  /* Loading State */
  .loading-state {
  
    padding: 60px 20px;
  }
  
  .loading-spinner {
    width: 40px;
    height: 40px;
    border: 3px solid #e2e8f0;
    border-top: 3px solid #667eea;
    border-radius: 50%;
    animation: spin 1s linear infinite;
    margin: 0 auto 16px;
  }
  
  @keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
  }
  
  /* Empty State */
  .empty-state {

    padding: 60px 20px;
  }
  
  .empty-icon {
    width: 64px;
    height: 64px;
    background: #f7fafc;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 20px;
    font-size: 24px;
    color: #a0aec0;
  }
  
  .empty-state h3 {
    font-size: 18px;
    color: #1a202c;
    margin-bottom: 8px;
  }
  
  .empty-state p {
    color: #718096;
    margin-bottom: 20px;
  }
  
  /* Jobs Grid */
  .jobs-grid {
    display: flex;
    flex-direction: column;
    gap: 16px;
  }
  
  .job-card {
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    padding: 24px;
    cursor: pointer;
    transition: all 0.2s;
  }
  
  .job-card:hover {
    border-color: #667eea;
    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.15);
    transform: translateY(-1px);
  }
  
  .job-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 16px;
  }
  
  .job-title {
    font-size: 20px;
    font-weight: 600;
    color: #1a202c;
    margin-bottom: 8px;
    line-height: 1.3;
  }
  
  .job-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 16px;
    align-items: center;
  }
  
  .job-budget {
    display: flex;
    align-items: center;
    gap: 4px;
    font-weight: 600;
    color: #48bb78;
    font-size: 16px;
  }
  
  .job-type,
  .job-category {
    background: #edf2f7;
    color: #4a5568;
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 12px;
    font-weight: 500;
  }
  
  .job-status {
    display: flex;
    flex-direction: column;
    align-items: flex-end;
    gap: 8px;
  }
  
  .status-badge {
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 11px;
    font-weight: 500;
  }
  
  .status-draft { background: #f7fafc; color: #4a5568; }
  .status-open { background: #c6f6d5; color: #276749; }
  .status-progress { background: #bee3f8; color: #2c5282; }
  .status-completed { background: #c6f6d5; color: #276749; }
  .status-cancelled { background: #fed7d7; color: #c53030; }
  .status-paused { background: #feebc8; color: #dd6b20; }
  
  .badge {
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 11px;
    font-weight: 500;
    display: flex;
    align-items: center;
    gap: 4px;
  }
  
  .badge-featured {
    background: #fed7d7;
    color: #c53030;
  }
  
  .badge-urgent {
    background: #feebc8;
    color: #dd6b20;
  }
  
  .job-time {
    font-size: 12px;
    color: #a0aec0;
  }
  
  .job-description {
    margin-bottom: 16px;
  }
  
  .job-description p {
    color: #4a5568;
    line-height: 1.6;
    font-size: 14px;
  }
  
  .job-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding-top: 16px;
    border-top: 1px solid #e2e8f0;
  }
  
  .job-stats {
    display: flex;
    gap: 16px;
    align-items: center;
  }
  
  .stat {
    display: flex;
    align-items: center;
    gap: 4px;
    font-size: 12px;
    color: #718096;
  }
  
  .job-actions {
    display: flex;
    align-items: center;
    gap: 8px;
  }
  
  .action-btn {
    padding: 6px 12px;
    border: 1px solid #e2e8f0;
    border-radius: 6px;
    background: white;
    color: #4a5568;
    font-size: 12px;
    font-weight: 500;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 4px;
    transition: all 0.2s;
  }
  
  .action-btn:hover {
    background: #f7fafc;
  }
  
  .action-btn.edit:hover {
    background: #fef5e7;
    border-color: #ed8936;
    color: #ed8936;
  }
  
  .action-btn.publish:hover {
    background: #f0fff4;
    border-color: #48bb78;
    color: #48bb78;
  }
  
  .action-btn.pause:hover {
    background: #feebc8;
    border-color: #dd6b20;
    color: #dd6b20;
  }
  
  .dropdown {
    position: relative;
  }
  
  .dropdown-menu {
    position: absolute;
    right: 0;
    top: 100%;
    margin-top: 4px;
    background: white;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    z-index: 10;
    min-width: 150px;
  }
  
  .dropdown-menu button {
    width: 100%;
    padding: 8px 12px;
    border: none;
    background: none;
    text-align: left;
    font-size: 13px;
    color: #4a5568;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 8px;
    transition: background 0.2s;
  }
  
  .dropdown-menu button:hover {
    background: #f7fafc;
  }
  
  .dropdown-menu button.danger {
    color: #e53e3e;
  }
  
  .dropdown-menu button.danger:hover {
    background: #fed7d7;
  }
  
  /* Right Sidebar */
  .recent-activity {
    display: flex;
    flex-direction: column;
    gap: 12px;
  }
  
  .activity-item {
    display: flex;
    gap: 12px;
    padding: 12px;
    background: #f7fafc;
    border-radius: 8px;
  }
  
  .activity-icon {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 12px;
    flex-shrink: 0;
  }
  
  .activity-content p {
    font-size: 13px;
    color: #4a5568;
    margin-bottom: 4px;
  }
  
  .activity-time {
    font-size: 11px;
    color: #a0aec0;
  }
  
  .text-blue { color: #3182ce; background: #ebf8ff; }
  .text-green { color: #38a169; background: #f0fff4; }
  .text-purple { color: #805ad5; background: #faf5ff; }
  
  .monthly-stats {
    display: flex;
    flex-direction: column;
    gap: 12px;
  }
  
  .stat-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 8px 0;
  }
  
  .stat-label {
    font-size: 14px;
    color: #4a5568;
  }
  
  .stat-value {
    font-weight: 600;
    color: #1a202c;
  }
  
  .tips-list {
    display: flex;
    flex-direction: column;
    gap: 12px;
  }
  
  .tip-item {
    display: flex;
    gap: 12px;
    padding: 12px;
    background: #f7fafc;
    border-radius: 8px;
  }
  
  .tip-item i {
    color: #667eea;
    margin-top: 2px;
    flex-shrink: 0;
  }
  
  .tip-item p {
    font-size: 13px;
    color: #4a5568;
    line-height: 1.4;
  }
  
  /* Modal Styles */
  .modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.5);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 2000;
    padding: 20px;
  }
  
  .modal-content {
    background: white;
    border-radius: 12px;
    width: 100%;
    max-width: 500px;
    max-height: 90vh;
    overflow: hidden;
    display: flex;
    flex-direction: column;
  }
  
  .large-modal {
    max-width: 800px;
  }
  
  .modal-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 24px;
    border-bottom: 1px solid #e2e8f0;
  }
  
  .modal-header h3 {
    font-size: 20px;
    font-weight: 600;
    color: #1a202c;
  }
  
  .close-btn {
    background: #f7fafc;
    border: none;
    width: 40px;
    height: 40px;
    border-radius: 50%;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #718096;
    transition: background 0.2s;
  }
  
  .close-btn:hover {
    background: #edf2f7;
  }
  
  .modal-body {
    padding: 24px;
    overflow-y: auto;
    flex: 1;
  }
  
  .modal-footer {
    display: flex;
    justify-content: flex-end;
    gap: 12px;
    padding: 24px;
    border-top: 1px solid #e2e8f0;
  }
  
  /* Form Styles */
  .form-section {
    margin-bottom: 32px;
  }
  
  .form-section h4 {
    font-size: 16px;
    font-weight: 600;
    color: #1a202c;
    margin-bottom: 16px;
    padding-bottom: 8px;
    border-bottom: 1px solid #e2e8f0;
  }
  
  .form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 16px;
    margin-bottom: 16px;
  }
  
  .form-group {
    margin-bottom: 16px;
  }
  
  .form-group label {
    display: block;
    font-size: 14px;
    font-weight: 500;
    color: #4a5568;
    margin-bottom: 8px;
  }
  
  .form-input,
  .form-select,
  .form-textarea {
    width: 100%;
    padding: 12px;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    font-size: 14px;
    transition: border-color 0.2s;
  }
  
  .form-input:focus,
  .form-select:focus,
  .form-textarea:focus {
    outline: none;
    border-color: #667eea;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
  }
  
  .form-textarea {
    resize: vertical;
    min-height: 80px;
  }
  
  .budget-inputs {
    display: flex;
    align-items: center;
    gap: 12px;
  }
  
  .budget-inputs span {
    color: #718096;
    font-size: 14px;
    font-weight: 500;
  }
  
  .checkbox-group {
    display: flex;
    flex-direction: column;
    gap: 12px;
  }
  
  .checkbox-item {
    display: flex;
    align-items: center;
    gap: 8px;
    cursor: pointer;
  }
  
  .checkbox-item input[type="checkbox"] {
    width: 16px;
    height: 16px;
    accent-color: #667eea;
  }
  
  .checkbox-item span {
    font-size: 14px;
    color: #4a5568;
  }
  
  /* Job Detail Modal */
  .job-detail-content {
    display: flex;
    flex-direction: column;
    gap: 24px;
  }
  
  .detail-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    padding-bottom: 16px;
    border-bottom: 1px solid #e2e8f0;
  }
  
  .status-badges {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
  }
  
  .detail-stats {
    display: flex;
    gap: 24px;
    flex-wrap: wrap;
    padding: 16px 0;
    border-bottom: 1px solid #e2e8f0;
  }
  
  .detail-stats .stat-item {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 14px;
    color: #4a5568;
  }
  
  .detail-stats .stat-item i {
    color: #718096;
  }
  
  .detail-section {
    margin-bottom: 24px;
  }
  
  .detail-section h4 {
    font-size: 16px;
    font-weight: 600;
    color: #1a202c;
    margin-bottom: 12px;
  }
  
  .content-text {
    color: #4a5568;
    line-height: 1.6;
    white-space: pre-line;
  }
  
  .project-info-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 16px;
  }
  
  .info-item {
    display: flex;
    flex-direction: column;
    gap: 4px;
  }
  
  .info-label {
    font-size: 12px;
    color: #718096;
    font-weight: 500;
  }
  
  .info-value {
    font-size: 14px;
    color: #1a202c;
    font-weight: 500;
  }
  
  /* Responsive */
  @media (max-width: 1200px) {
    .dashboard-grid {
      grid-template-columns: 280px 1fr;
      gap: 20px;
    }
    
    .right-sidebar {
      display: none;
    }
  }
  
  @media (max-width: 768px) {
    .dashboard-grid {
      grid-template-columns: 1fr;
      gap: 16px;
    }
    
    .left-sidebar {
      display: none;
    }
    
    .container {
      padding: 0 16px;
    }
    
    .main-container {
      padding: 16px 0;
    }
    
    .feed-header {
      flex-direction: column;
      gap: 16px;
      align-items: stretch;
    }
    
    .header-actions {
      justify-content: space-between;
    }
    
    .job-card {
      padding: 16px;
    }
    
    .job-header {
      flex-direction: column;
      gap: 12px;
      align-items: stretch;
    }
    
    .job-footer {
      flex-direction: column;
      gap: 12px;
      align-items: stretch;
    }
    
    .modal-content {
      margin: 10px;
      max-height: calc(100vh - 20px);
    }
    
    .form-row {
      grid-template-columns: 1fr;
      gap: 12px;
    }
    
    .stats-grid {
      grid-template-columns: 1fr;
    }
    
    .filters-bar {
      flex-direction: column;
      align-items: stretch;
    }
    
    .search-box {
      max-width: none;
    }
  }</style>