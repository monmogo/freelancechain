<template>
  <div class="jobs-dashboard">
    <!-- Main Content -->
    <div class="main-container">
      <div class="container">
        <div class="dashboard-grid">
          <!-- Left Sidebar -->
          <aside class="left-sidebar">
            <div class="sidebar-section">
              <h3>Bộ lọc công việc</h3>
              
              <!-- Search -->
              <div class="filter-group">
                <label>Tìm kiếm</label>
                <input 
                  type="text" 
                  v-model="filters.search" 
                  placeholder="Nhập từ khóa..."
                  class="filter-input"
                  @input="debouncedSearch"
                />
              </div>

              <!-- Category Filter -->
              <div class="filter-group">
                <label>Danh mục</label>
                <select v-model="filters.category_id" @change="loadJobs" class="filter-select">
                  <option value="">Tất cả danh mục</option>
                  <option v-for="category in categories" :key="category.id" :value="category.id">
                    {{ category.name }} ({{ category.active_jobs }})
                  </option>
                </select>
              </div>

              <!-- Budget Filter -->
              <div class="filter-group">
                <label>Ngân sách</label>
                <select v-model="filters.budget_type" @change="loadJobs" class="filter-select">
                  <option value="">Tất cả loại</option>
                  <option value="fixed">Theo dự án</option>
                  <option value="hourly">Theo giờ</option>
                </select>
                <div class="budget-range">
                  <input 
                    type="number" 
                    v-model="filters.budget_min" 
                    placeholder="Từ ($)"
                    class="budget-input"
                    @change="loadJobs"
                  />
                  <input 
                    type="number" 
                    v-model="filters.budget_max" 
                    placeholder="Đến ($)"
                    class="budget-input"
                    @change="loadJobs"
                  />
                </div>
              </div>

              <!-- Experience Level -->
              <div class="filter-group">
                <label>Kinh nghiệm</label>
                <div class="checkbox-group">
                  <label class="checkbox-item">
                    <input type="radio" v-model="filters.experience_level" value="" @change="loadJobs" />
                    <span>Tất cả</span>
                  </label>
                  <label class="checkbox-item">
                    <input type="radio" v-model="filters.experience_level" value="entry" @change="loadJobs" />
                    <span>Mới bắt đầu</span>
                  </label>
                  <label class="checkbox-item">
                    <input type="radio" v-model="filters.experience_level" value="intermediate" @change="loadJobs" />
                    <span>Trung cấp</span>
                  </label>
                  <label class="checkbox-item">
                    <input type="radio" v-model="filters.experience_level" value="expert" @change="loadJobs" />
                    <span>Chuyên gia</span>
                  </label>
                </div>
              </div>

              <!-- Location -->
              <div class="filter-group">
                <label>Hình thức làm việc</label>
                <select v-model="filters.location_requirement" @change="loadJobs" class="filter-select">
                  <option value="">Tất cả</option>
                  <option value="remote">Remote</option>
                  <option value="onsite">Tại văn phòng</option>
                  <option value="hybrid">Hybrid</option>
                </select>
              </div>

              <!-- Quick Stats -->
              <div class="stats-section">
                <h4>Thống kê nhanh</h4>
                <div class="quick-stats">
                  <div class="stat-item">
                    <i class="fas fa-briefcase"></i>
                    <div>
                      <span class="stat-number">{{ totalJobs }}</span>
                      <span class="stat-label">Công việc</span>
                    </div>
                  </div>
                  <div class="stat-item">
                    <i class="fas fa-dollar-sign"></i>
                    <div>
                      <span class="stat-number">${{ avgBudget }}</span>
                      <span class="stat-label">TB ngân sách</span>
                    </div>
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
                <h2>Công việc mới nhất</h2>
                <p>{{ pagination.total_items }} công việc phù hợp</p>
              </div>
              
              <div class="header-actions">
                <select v-model="filters.sort" @change="loadJobs" class="sort-select">
                  <option value="newest">Mới nhất</option>
                  <option value="budget_high">Ngân sách cao</option>
                  <option value="budget_low">Ngân sách thấp</option>
                  <option value="proposals">Nhiều proposal</option>
                </select>
                
                <button v-if="user?.role === 'client'" @click="showCreateJobModal = true" class="btn-primary">
                  <i class="fas fa-plus"></i>
                  Đăng công việc
                </button>
              </div>
            </div>

            <!-- Loading State -->
            <div v-if="loading" class="loading-state">
              <div class="loading-spinner"></div>
              <p>Đang tải công việc...</p>
            </div>

            <!-- Jobs Feed -->
            <div v-else class="jobs-feed">
              <div v-for="job in jobs" :key="job.id || job.job_id" class="job-card" @click="viewJobDetail(job)">
                <!-- Job Header -->
                <div class="job-header">
                  <div class="job-title-section">
                    <h3 class="job-title">{{ job.title }}</h3>
                    <div class="job-meta">
                      <span class="job-budget">
                        <i class="fas fa-dollar-sign"></i>
                        <span v-if="job.budget_min && job.budget_max">
                          ${{ formatNumber(job.budget_min) }} - ${{ formatNumber(job.budget_max) }}
                        </span>
                        <span v-else-if="job.budget_min">
                          Từ ${{ formatNumber(job.budget_min) }}
                        </span>
                        <span v-else>
                          Thỏa thuận
                        </span>
                      </span>
                      <span class="job-type">{{ getJobTypeLabel(job) }}</span>
                      <span class="job-category">{{ job.category_name }}</span>
                    </div>
                  </div>
                  
                  <div class="job-status">
                    <span v-if="job.featured" class="badge badge-featured">
                      <i class="fas fa-star"></i>
                      Nổi bật
                    </span>
                    <span v-if="job.urgent" class="badge badge-urgent">
                      <i class="fas fa-clock"></i>
                      Gấp
                    </span>
                    <span class="job-time">{{ formatTimeAgo(job.published_at) }}</span>
                  </div>
                </div>

                <!-- Job Description -->
                <div class="job-description">
                  <p>{{ truncateText(job.description, 150) }}</p>
                </div>

                <!-- Job Skills -->
                <div v-if="getJobSkills(job).length" class="job-skills">
                  <span v-for="skill in getJobSkills(job).slice(0, 5)" :key="skill" class="skill-tag">
                    {{ skill }}
                  </span>
                  <span v-if="getJobSkills(job).length > 5" class="skill-more">
                    +{{ getJobSkills(job).length - 5 }} thêm
                  </span>
                </div>

                <!-- Job Footer -->
                <div class="job-footer">
                  <div class="client-info">
                    <img :src="job.client_avatar || defaultAvatar" :alt="job.client_name" class="client-avatar" />
                    <div class="client-details">
                      <span class="client-name">{{ job.client_name }}</span>
                      <div class="client-stats">
                        <span v-if="job.client_rating > 0" class="client-rating">
                          <i class="fas fa-star"></i>
                          {{ job.client_rating.toFixed(1) }}
                        </span>
                        <span class="client-jobs">{{ job.client_total_jobs }} việc đã đăng</span>
                        <span v-if="job.payment_verified" class="verified">
                          <i class="fas fa-check-circle"></i>
                          Đã xác minh
                        </span>
                      </div>
                    </div>
                  </div>

                  <div class="job-actions">
                    <div class="job-stats">
                      <span class="stat">
                        <i class="fas fa-eye"></i>
                        {{ job.view_count }}
                      </span>
                      <span class="stat">
                        <i class="fas fa-paper-plane"></i>
                        {{ job.proposal_count }} proposals
                      </span>
                    </div>
                    
                    <button 
                      v-if="canApplyToJob(job)" 
                      @click.stop="showProposalModal(job)"
                      class="btn-apply"
                    >
                      <i class="fas fa-paper-plane"></i>
                      Ứng tuyển
                    </button>
                    
                    <button 
                      v-else-if="user?.role === 'freelancer'"
                      class="btn-applied" 
                      disabled
                    >
                      <i class="fas fa-check"></i>
                      Đã ứng tuyển
                    </button>
                  </div>
                </div>
              </div>

              <!-- No Jobs State -->
              <div v-if="!loading && jobs.length === 0" class="empty-state">
                <div class="empty-icon">
                  <i class="fas fa-search"></i>
                </div>
                <h3>Không tìm thấy công việc phù hợp</h3>
                <p>Thử điều chỉnh bộ lọc hoặc tìm kiếm với từ khóa khác</p>
                <button @click="clearFilters" class="btn-secondary">
                  Xóa bộ lọc
                </button>
              </div>
            </div>

            <!-- Pagination -->
            <div v-if="pagination.total_pages > 1" class="pagination">
              <button 
                @click="changePage(pagination.current_page - 1)"
                :disabled="!pagination.has_prev"
                class="page-btn"
              >
                <i class="fas fa-chevron-left"></i>
              </button>
              
              <span class="page-info">
                Trang {{ pagination.current_page }} / {{ pagination.total_pages }}
              </span>
              
              <button 
                @click="changePage(pagination.current_page + 1)"
                :disabled="!pagination.has_next"
                class="page-btn"
              >
                <i class="fas fa-chevron-right"></i>
              </button>
            </div>
          </main>

          <!-- Right Sidebar -->
          <aside class="right-sidebar">
            <!-- Top Categories -->
            <div class="sidebar-section">
              <h3>Danh mục hot</h3>
              <div class="top-categories">
                <div 
                  v-for="category in topCategories" 
                  :key="category.id" 
                  class="category-item"
                  @click="filterByCategory(category.id)"
                >
                  <div class="category-icon" :style="{ backgroundColor: category.color }">
                    <i :class="category.icon"></i>
                  </div>
                  <div class="category-info">
                    <h4>{{ category.name }}</h4>
                    <span>{{ category.active_jobs }} việc</span>
                  </div>
                </div>
              </div>
            </div>

            <!-- Recent Jobs Stats -->
            <div class="sidebar-section">
              <h3>Thống kê việc làm</h3>
              <div class="job-stats-list">
                <div class="stat-row">
                  <span class="stat-label">Việc mới hôm nay</span>
                  <span class="stat-value">{{ todayJobsCount }}</span>
                </div>
                <div class="stat-row">
                  <span class="stat-label">Freelancer hoạt động</span>
                  <span class="stat-value">{{ activeFreelancers }}</span>
                </div>
                <div class="stat-row">
                  <span class="stat-label">Tổng giá trị dự án</span>
                  <span class="stat-value">${{ totalProjectValue }}</span>
                </div>
                <div class="stat-row">
                  <span class="stat-label">Tỷ lệ thành công</span>
                  <span class="stat-value">{{ successRate }}%</span>
                </div>
              </div>
            </div>

            <!-- Top Skills -->
            <div class="sidebar-section">
              <h3>Kỹ năng được tìm kiếm</h3>
              <div class="trending-skills">
                <span 
                  v-for="skill in trendingSkills" 
                  :key="skill.id" 
                  class="skill-chip"
                  @click="searchBySkill(skill.name)"
                >
                  {{ skill.name }}
                </span>
              </div>
            </div>

            <!-- Recent Activity -->
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
          </aside>
        </div>
      </div>
    </div>

    <!-- Create Job Modal -->
    <div v-if="showCreateJobModal" class="modal-overlay" @click="closeCreateJobModal">
      <div class="modal-content large-modal" @click.stop>
        <div class="modal-header">
          <h3>Đăng công việc mới</h3>
          <button class="close-btn" @click="closeCreateJobModal">
            <i class="fas fa-times"></i>
          </button>
        </div>
        <div class="modal-body">
          <form @submit.prevent="createJob">
            <div class="form-row">
              <div class="form-group">
                <label>Tiêu đề công việc *</label>
                <input 
                  type="text" 
                  v-model="newJob.title" 
                  placeholder="Ví dụ: Thiết kế website cho startup..."
                  class="form-input"
                  required
                />
              </div>
            </div>

            <div class="form-row">
              <div class="form-group">
                <label>Mô tả chi tiết *</label>
                <textarea 
                  v-model="newJob.description" 
                  placeholder="Mô tả chi tiết về dự án, yêu cầu kỹ thuật, timeline..."
                  class="form-textarea"
                  rows="5"
                  required
                ></textarea>
              </div>
            </div>

            <div class="form-row">
              <div class="form-group">
                <label>Danh mục *</label>
                <select v-model="newJob.category_id" class="form-select" required>
                  <option value="">Chọn danh mục</option>
                  <option v-for="category in categories" :key="category.id" :value="category.id">
                    {{ category.name }}
                  </option>
                </select>
              </div>
              <div class="form-group">
                <label>Kinh nghiệm yêu cầu</label>
                <select v-model="newJob.experience_level" class="form-select">
                  <option value="entry">Mới bắt đầu</option>
                  <option value="intermediate">Trung cấp</option>
                  <option value="expert">Chuyên gia</option>
                </select>
              </div>
            </div>

            <div class="form-row">
              <div class="form-group">
                <label>Loại ngân sách *</label>
                <select v-model="newJob.budget_type" class="form-select" required>
                  <option value="fixed">Theo dự án</option>
                  <option value="hourly">Theo giờ</option>
                </select>
              </div>
              <div class="form-group">
                <label>Ngân sách ($) *</label>
                <div class="budget-inputs">
                  <input 
                    type="number" 
                    v-model="newJob.budget_min" 
                    placeholder="Từ"
                    class="form-input"
                    required
                  />
                  <span>đến</span>
                  <input 
                    type="number" 
                    v-model="newJob.budget_max" 
                    placeholder="Đến"
                    class="form-input"
                    required
                  />
                </div>
              </div>
            </div>

            <div class="form-row">
              <div class="form-group">
                <label>Hình thức làm việc</label>
                <select v-model="newJob.location_requirement" class="form-select">
                  <option value="remote">Remote</option>
                  <option value="onsite">Tại văn phòng</option>
                  <option value="hybrid">Hybrid</option>
                </select>
              </div>
              <div class="form-group">
                <label>Thời hạn nộp proposal</label>
                <input 
                  type="date" 
                  v-model="newJob.proposal_deadline" 
                  class="form-input"
                />
              </div>
            </div>

            <div class="form-row">
              <div class="form-group full-width">
                <label>Yêu cầu chi tiết</label>
                <textarea 
                  v-model="newJob.requirements" 
                  placeholder="Các yêu cầu cụ thể về kỹ năng, kinh nghiệm, deliverables..."
                  class="form-textarea"
                  rows="3"
                ></textarea>
              </div>
            </div>
          </form>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn-cancel" @click="closeCreateJobModal">Hủy</button>
          <button type="button" @click="createJob" class="btn-primary" :disabled="!isValidJob">
            <i class="fas fa-plus"></i>
            Tạo công việc
          </button>
        </div>
      </div>
    </div>

    <!-- Job Detail Modal -->
    <div v-if="selectedJob" class="modal-overlay" @click="closeJobDetail">
      <div class="modal-content large-modal job-detail-modal" @click.stop>
        <div class="modal-header">
          <h3>{{ selectedJob.title }}</h3>
          <button class="close-btn" @click="closeJobDetail">
            <i class="fas fa-times"></i>
          </button>
        </div>
        <div class="modal-body">
          <!-- Job detail content will be loaded here -->
          <div class="job-detail-content">
            <div class="detail-section">
              <h4>Mô tả công việc</h4>
              <p>{{ selectedJob.description }}</p>
            </div>
            
            <div v-if="selectedJob.requirements" class="detail-section">
              <h4>Yêu cầu</h4>
              <p>{{ selectedJob.requirements }}</p>
            </div>

            <div class="detail-section">
              <h4>Thông tin dự án</h4>
              <div class="project-info">
                <div class="info-item">
                  <span class="info-label">Ngân sách:</span>
                  <span class="info-value">${{ selectedJob.budget_min }} - ${{ selectedJob.budget_max }}</span>
                </div>
                <div class="info-item">
                  <span class="info-label">Kinh nghiệm:</span>
                  <span class="info-value">{{ getExperienceLabel(selectedJob.experience_level) }}</span>
                </div>
                <div class="info-item">
                  <span class="info-label">Hình thức:</span>
                  <span class="info-value">{{ getLocationLabel(selectedJob.location_requirement) }}</span>
                </div>
              </div>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn-secondary" @click="closeJobDetail">Đóng</button>
          <button 
            v-if="canApplyToJob(selectedJob)" 
            type="button" 
            class="btn-primary"
            @click="showProposalModal(selectedJob)"
          >
            <i class="fas fa-paper-plane"></i>
            Ứng tuyển ngay
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { mapState } from 'pinia'
import { useAuthStore } from '@/stores/auth'
import apiService from '@/services/api'

export default {
  name: 'JobsHome',
  data() {
    return {
      // Loading states
      loading: false,
      
      // Jobs data
      jobs: [],
      categories: [],
      totalJobs: 0,
      
      // Pagination
      pagination: {
        current_page: 1,
        total_pages: 1,
        total_items: 0,
        has_next: false,
        has_prev: false
      },
      
      // Filters
      filters: {
        search: '',
        category_id: '',
        budget_type: '',
        budget_min: '',
        budget_max: '',
        experience_level: '',
        location_requirement: '',
        sort: 'newest',
        page: 1,
        limit: 10
      },
      
      // Modal states
      showCreateJobModal: false,
      selectedJob: null,
      
      // New job form
      newJob: {
        title: '',
        description: '',
        requirements: '',
        category_id: '',
        budget_type: 'fixed',
        budget_min: '',
        budget_max: '',
        experience_level: 'intermediate',
        location_requirement: 'remote',
        proposal_deadline: ''
      },
      
      // Stats data
      avgBudget: 0,
      todayJobsCount: 15,
      activeFreelancers: 324,
      totalProjectValue: '125K',
      successRate: 94,
      
      // Sidebar data
      topCategories: [],
      trendingSkills: [],
      recentActivities: [
        {
          id: 1,
          content: '5 việc mới được đăng trong 1 giờ qua',
          icon: 'fas fa-briefcase text-blue',
          time: new Date(Date.now() - 30 * 60 * 1000)
        },
        {
          id: 2,
          content: '23 freelancer mới tham gia hôm nay',
          icon: 'fas fa-users text-green',
          time: new Date(Date.now() - 2 * 60 * 60 * 1000)
        }
      ],
      
      // Search debounce
      searchTimeout: null,
      
      // Default avatar
      defaultAvatar: 'https://via.placeholder.com/40'
    }
  },
  
  computed: {
    ...mapState(useAuthStore, ['user']),
    
    isValidJob() {
      return this.newJob.title && 
             this.newJob.description && 
             this.newJob.category_id && 
             this.newJob.budget_min && 
             this.newJob.budget_max
    }
  },
  
  async mounted() {
    // Test API connection first
    await this.testAPIConnection()
    
    await Promise.all([
      this.loadCategories(),
      this.loadTrendingSkills()
    ])
    // Load jobs after categories are loaded
    await this.loadJobs()
  },
  
  methods: {
    // Test API connection
    async testAPIConnection() {
      try {
        console.log('Testing API connection...')
        console.log('API Base URL:', apiService.defaults?.baseURL || 'Not configured')
        
        // Test with a simple endpoint
        const response = await apiService.get('/jobs/categories.php')
        console.log('API test successful:', response.status)
        return true
      } catch (error) {
        console.warn('API connection test failed:', error.message)
        console.log('Will use mock data for development')
        return false
      }
    },
    
    // API calls
    async loadJobs() {
      this.loading = true
      try {
        const params = { ...this.filters }
        // Remove empty filters
        Object.keys(params).forEach(key => {
          if (params[key] === '' || params[key] === null) {
            delete params[key]
          }
        })
        
        // For testing with existing data, let's check what we get
        console.log('Loading jobs with params:', params)
        
        const response = await apiService.get('/jobs/list.php', { params })
        console.log('Jobs API response:', response.data)
        
        if (response.data.success) {
          this.jobs = response.data.data || []
          this.pagination = response.data.pagination || {
            current_page: 1,
            total_pages: 1,
            total_items: 0,
            has_next: false,
            has_prev: false
          }
          this.totalJobs = this.pagination.total_items
          
          // Calculate average budget
          if (this.jobs.length > 0) {
            const validBudgets = this.jobs.filter(job => job.budget_max > 0)
            if (validBudgets.length > 0) {
              const sum = validBudgets.reduce((acc, job) => acc + job.budget_max, 0)
              this.avgBudget = Math.round(sum / validBudgets.length)
            }
          }
        } else {
          console.error('API returned error:', response.data)
          this.$toast?.error(response.data.error || 'Lỗi khi tải danh sách công việc')
        }
      } catch (error) {
        console.error('Load jobs error:', error)
        
        // Fallback to mock data for development
        if (error.code === 'ERR_NETWORK' || error.response?.status === 404) {
          console.log('API not available, using mock data')
          this.loadMockJobs()
        } else {
          this.$toast?.error('Lỗi khi tải danh sách công việc')
        }
      } finally {
        this.loading = false
      }
    },
    
    // Mock data method for development/testing
    loadMockJobs() {
      // For testing with existing data from database
      this.jobs = [
        {
          id: 1, // Database uses 'id' not 'job_id'
          title: 'Build a Modern E-commerce Website',
          description: 'We need a responsive e-commerce website built with modern technologies. Must include payment integration, admin panel, and mobile optimization.',
          budget_min: 2000.00,
          budget_max: 3500.00,
          currency: 'USD',
          budget_type: 'fixed',
          category_id: 1,
          category_name: 'Technology',
          category_slug: 'technology',
          experience_level: 'intermediate',
          project_length: 'medium',
          location_requirement: 'remote',
          status: 'open',
          visibility: 'public',
          client_id: 3,
          client_name: 'Sarah Thompson',
          client_avatar: null,
          client_rating: 0.00,
          client_total_jobs: 1,
          proposal_count: 1,
          view_count: 0,
          featured: false,
          urgent: false,
          published_at: '2025-07-27 03:34:56',
          expires_at: null,
          required_skills: ['React', 'Node.js', 'JavaScript', 'E-commerce'],
          payment_verified: true,
          identity_verified: false,
          estimated_hours: null,
          deadline: null,
          max_proposals: 50
        }
      ]
      
      this.pagination = {
        current_page: 1,
        total_pages: 1,
        total_items: 1,
        has_next: false,
        has_prev: false
      }
      
      this.totalJobs = 1
      this.avgBudget = 2750
    },
    
    async loadCategories() {
      try {
        const response = await apiService.get('/jobs/categories.php')
        console.log('Categories API response:', response.data)
        
        if (response.data.success) {
          this.categories = response.data.data || []
          this.topCategories = this.categories
            .filter(cat => cat.active_jobs > 0)
            .sort((a, b) => b.active_jobs - a.active_jobs)
            .slice(0, 6)
        }
      } catch (error) {
        console.error('Load categories error:', error)
        
        // Fallback to mock categories
        this.categories = [
          { id: 1, name: 'Technology', slug: 'technology', icon: 'fas fa-code', color: '#3498db', active_jobs: 1 },
          { id: 2, name: 'Design & Creative', slug: 'design-creative', icon: 'fas fa-palette', color: '#e74c3c', active_jobs: 0 },
          { id: 3, name: 'Writing & Translation', slug: 'writing-translation', icon: 'fas fa-pen', color: '#f39c12', active_jobs: 0 },
          { id: 4, name: 'Marketing & Sales', slug: 'marketing-sales', icon: 'fas fa-bullhorn', color: '#2ecc71', active_jobs: 0 }
        ]
        
        this.topCategories = this.categories.filter(cat => cat.active_jobs > 0)
      }
    },
    
    async loadTrendingSkills() {
      try {
        const response = await apiService.get('/jobs/skills.php', {
          params: { limit: 20 }
        })
        if (response.data.success) {
          this.trendingSkills = response.data.data.slice(0, 15)
        }
      } catch (error) {
        console.error('Load skills error:', error)
        
        // Fallback to mock skills
        this.trendingSkills = [
          { id: 1, name: 'JavaScript', slug: 'javascript' },
          { id: 2, name: 'Python', slug: 'python' },
          { id: 3, name: 'React', slug: 'react' },
          { id: 4, name: 'Node.js', slug: 'nodejs' },
          { id: 5, name: 'Vue.js', slug: 'vuejs' },
          { id: 6, name: 'PHP', slug: 'php' },
          { id: 7, name: 'MySQL', slug: 'mysql' },
          { id: 8, name: 'HTML/CSS', slug: 'html-css' }
        ]
      }
    },
    
    async createJob() {
      if (!this.isValidJob) return
      
      try {
        const response = await apiService.post('/jobs/create.php', this.newJob)
        
        if (response.data.success) {
          this.$toast.success('Tạo công việc thành công!')
          this.closeCreateJobModal()
          this.loadJobs() // Reload jobs list
        }
      } catch (error) {
        this.$toast.error(error.response?.data?.error || 'Lỗi khi tạo công việc')
      }
    },
    
    async viewJobDetail(job) {
      const jobId = job.id || job.job_id
      try {
        const response = await apiService.get(`/jobs/detail.php?id=${jobId}`)
        if (response.data.success) {
          this.selectedJob = response.data.data.job
        }
      } catch (error) {
        console.error('Load job detail error:', error)
        // Fallback to current job data
        this.selectedJob = job
      }
    },
    
    // Filter methods
    debouncedSearch() {
      clearTimeout(this.searchTimeout)
      this.searchTimeout = setTimeout(() => {
        this.filters.page = 1
        this.loadJobs()
      }, 500)
    },
    
    filterByCategory(categoryId) {
      this.filters.category_id = categoryId
      this.filters.page = 1
      this.loadJobs()
    },
    
    searchBySkill(skillName) {
      this.filters.search = skillName
      this.filters.page = 1
      this.loadJobs()
    },
    
    clearFilters() {
      this.filters = {
        search: '',
        category_id: '',
        budget_type: '',
        budget_min: '',
        budget_max: '',
        experience_level: '',
        location_requirement: '',
        sort: 'newest',
        page: 1,
        limit: 10
      }
      this.loadJobs()
    },
    
    changePage(page) {
      if (page >= 1 && page <= this.pagination.total_pages) {
        this.filters.page = page
        this.loadJobs()
      }
    },
    
    // Modal methods
    closeCreateJobModal() {
      this.showCreateJobModal = false
      this.newJob = {
        title: '',
        description: '',
        requirements: '',
        category_id: '',
        budget_type: 'fixed',
        budget_min: '',
        budget_max: '',
        experience_level: 'intermediate',
        location_requirement: 'remote',
        proposal_deadline: ''
      }
    },
    
    closeJobDetail() {
      this.selectedJob = null
    },
    
    showProposalModal(job) {
      const jobId = job.id || job.job_id
      // Navigate to proposal creation or show proposal modal
      this.$router.push(`/jobs/${jobId}/proposal`)
    },
    
    // Utility methods
    canApplyToJob(job) {
      return this.user?.role === 'freelancer' && 
             job.status === 'open' && 
             // Add more conditions like checking if already applied
             true
    },
    
    formatNumber(num) {
      return new Intl.NumberFormat().format(num)
    },
    
    formatTimeAgo(date) {
      if (!date) return ''
      const now = new Date()
      const past = new Date(date)
      const diff = Math.floor((now - past) / 1000)
      
      if (diff < 60) return 'Vừa xong'
      if (diff < 3600) return `${Math.floor(diff / 60)} phút trước`
      if (diff < 86400) return `${Math.floor(diff / 3600)} giờ trước`
      return `${Math.floor(diff / 86400)} ngày trước`
    },
    
    truncateText(text, maxLength) {
      if (!text) return ''
      return text.length > maxLength ? text.substring(0, maxLength) + '...' : text
    },
    
    getJobTypeLabel(job) {
      const types = {
        fixed: 'Theo dự án',
        hourly: 'Theo giờ'
      }
      return types[job.budget_type] || job.budget_type
    },
    
    getExperienceLabel(level) {
      const levels = {
        entry: 'Mới bắt đầu',
        intermediate: 'Trung cấp',
        expert: 'Chuyên gia'
      }
      return levels[level] || level
    },
    
    getLocationLabel(location) {
      const locations = {
        remote: 'Remote',
        onsite: 'Tại văn phòng',
        hybrid: 'Hybrid'
      }
      return locations[location] || location
    },
    
    // Helper to get job skills (handle both array and comma-separated string)
    getJobSkills(job) {
      if (!job.required_skills) return []
      
      if (Array.isArray(job.required_skills)) {
        return job.required_skills
      }
      
      if (typeof job.required_skills === 'string') {
        return job.required_skills.split(',').map(skill => skill.trim()).filter(skill => skill)
      }
      
      return []
    }
  }
}
</script>

<style scoped>
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

/* Filters */
.filter-group {
  margin-bottom: 20px;
}

.filter-group label {
  display: block;
  font-size: 14px;
  font-weight: 500;
  color: #4a5568;
  margin-bottom: 8px;
}

.filter-input,
.filter-select {
  width: 100%;
  padding: 10px 12px;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  font-size: 14px;
  transition: border-color 0.2s;
}

.filter-input:focus,
.filter-select:focus {
  outline: none;
  border-color: #667eea;
  box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

.budget-range {
  display: flex;
  gap: 8px;
  margin-top: 8px;
}

.budget-input {
  flex: 1;
  padding: 8px 10px;
  border: 1px solid #e2e8f0;
  border-radius: 6px;
  font-size: 13px;
}

.checkbox-group {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.checkbox-item {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 14px;
  cursor: pointer;
}

.checkbox-item input {
  margin: 0;
}

/* Quick Stats */
.stats-section h4 {
  font-size: 16px;
  font-weight: 600;
  margin-bottom: 12px;
  color: #1a202c;
}

.quick-stats {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.stat-item {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 12px;
  background: #f7fafc;
  border-radius: 8px;
}

.stat-item i {
  width: 32px;
  height: 32px;
  background: #667eea;
  border-radius: 6px;
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  font-size: 14px;
}

.stat-number {
  font-weight: 600;
  font-size: 16px;
  color: #1a202c;
  display: block;
}

.stat-label {
  font-size: 12px;
  color: #718096;
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

/* Loading State */
.loading-state {
  text-align: center;
  padding: 60px 20px;
  background: white;
  border-radius: 12px;
  border: 1px solid #e2e8f0;
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

/* Jobs Feed */
.jobs-feed {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.job-card {
  background: white;
  border-radius: 12px;
  padding: 24px;
  border: 1px solid #e2e8f0;
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

.job-skills {
  display: flex;
  flex-wrap: wrap;
  gap: 6px;
  margin-bottom: 20px;
}

.skill-tag {
  background: #e6fffa;
  color: #234e52;
  padding: 4px 8px;
  border-radius: 4px;
  font-size: 12px;
  font-weight: 500;
}

.skill-more {
  color: #718096;
  font-size: 12px;
  font-style: italic;
}

.job-footer {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding-top: 16px;
  border-top: 1px solid #e2e8f0;
}

.client-info {
  display: flex;
  align-items: center;
  gap: 12px;
}

.client-avatar {
  width: 48px;
  height: 48px;
  border-radius: 50%;
  object-fit: cover;
}

.client-name {
  font-weight: 600;
  color: #1a202c;
  font-size: 14px;
  display: block;
  margin-bottom: 4px;
}

.client-stats {
  display: flex;
  gap: 12px;
  align-items: center;
  font-size: 12px;
  color: #718096;
}

.client-rating {
  display: flex;
  align-items: center;
  gap: 2px;
  color: #f6ad55;
}

.verified {
  display: flex;
  align-items: center;
  gap: 4px;
  color: #48bb78;
}

.job-actions {
  display: flex;
  align-items: center;
  gap: 16px;
}

.job-stats {
  display: flex;
  gap: 12px;
  align-items: center;
}

.stat {
  display: flex;
  align-items: center;
  gap: 4px;
  font-size: 12px;
  color: #718096;
}

.btn-apply {
  background: #48bb78;
  color: white;
  border: none;
  padding: 8px 16px;
  border-radius: 6px;
  font-size: 13px;
  font-weight: 500;
  cursor: pointer;
  display: flex;
  align-items: center;
  gap: 6px;
  transition: background 0.2s;
}

.btn-apply:hover {
  background: #38a169;
}

.btn-applied {
  background: #e2e8f0;
  color: #718096;
  border: none;
  padding: 8px 16px;
  border-radius: 6px;
  font-size: 13px;
  font-weight: 500;
  display: flex;
  align-items: center;
  gap: 6px;
}

/* Empty State */
.empty-state {
  text-align: center;
  padding: 60px 20px;
  background: white;
  border-radius: 12px;
  border: 1px solid #e2e8f0;
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

.btn-secondary {
  background: #edf2f7;
  color: #4a5568;
  border: none;
  padding: 10px 16px;
  border-radius: 8px;
  font-size: 14px;
  font-weight: 500;
  cursor: pointer;
}

/* Pagination */
.pagination {
  display: flex;
  justify-content: center;
  align-items: center;
  gap: 12px;
  margin-top: 24px;
  padding: 20px;
  background: white;
  border-radius: 12px;
  border: 1px solid #e2e8f0;
}

.page-btn {
  background: #f7fafc;
  border: 1px solid #e2e8f0;
  padding: 8px 12px;
  border-radius: 6px;
  cursor: pointer;
  transition: all 0.2s;
}

.page-btn:hover:not(:disabled) {
  background: #667eea;
  color: white;
  border-color: #667eea;
}

.page-btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.page-info {
  font-size: 14px;
  color: #4a5568;
  font-weight: 500;
}

/* Right Sidebar Components */
.top-categories {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.category-item {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 12px;
  background: #f7fafc;
  border-radius: 8px;
  cursor: pointer;
  transition: background 0.2s;
}

.category-item:hover {
  background: #edf2f7;
}

.category-icon {
  width: 40px;
  height: 40px;
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  font-size: 16px;
}

.category-info h4 {
  font-size: 14px;
  font-weight: 600;
  color: #1a202c;
  margin-bottom: 2px;
}

.category-info span {
  font-size: 12px;
  color: #718096;
}

.job-stats-list {
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

.trending-skills {
  display: flex;
  flex-wrap: wrap;
  gap: 6px;
}

.skill-chip {
  background: #e6fffa;
  color: #234e52;
  padding: 4px 8px;
  border-radius: 12px;
  font-size: 12px;
  font-weight: 500;
  cursor: pointer;
  transition: background 0.2s;
}

.skill-chip:hover {
  background: #b2f5ea;
}

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
.form-row {
  display: flex;
  gap: 16px;
  margin-bottom: 20px;
}

.form-group {
  flex: 1;
}

.form-group.full-width {
  flex: none;
  width: 100%;
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
  gap: 8px;
}

.budget-inputs span {
  color: #718096;
  font-size: 14px;
}

.btn-cancel {
  background: #edf2f7;
  color: #4a5568;
  border: none;
  padding: 10px 20px;
  border-radius: 8px;
  font-size: 14px;
  font-weight: 500;
  cursor: pointer;
}

/* Job Detail Modal */
.job-detail-modal .modal-body {
  max-height: 60vh;
}

.job-detail-content {
  display: flex;
  flex-direction: column;
  gap: 24px;
}

.detail-section h4 {
  font-size: 16px;
  font-weight: 600;
  color: #1a202c;
  margin-bottom: 12px;
}

.detail-section p {
  color: #4a5568;
  line-height: 1.6;
}

.project-info {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.info-item {
  display: flex;
  justify-content: space-between;
  padding: 8px 0;
  border-bottom: 1px solid #f7fafc;
}

.info-label {
  font-weight: 500;
  color: #718096;
}

.info-value {
  font-weight: 600;
  color: #1a202c;
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
    flex-direction: column;
    gap: 12px;
  }
}
</style>