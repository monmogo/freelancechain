export const toastService = {
    success(message) {
      this.showToast(message, 'success')
    },
  
    error(message) {
      this.showToast(message, 'danger')
    },
  
    showToast(message, type = 'info') {
      console.log(`Toast ${type}: ${message}`)
      
      // Simple alert fallback for now
      if (type === 'error' || type === 'danger') {
        alert('❌ ' + message)
      } else {
        alert('✅ ' + message)
      }
    }
  }