<template>
  <div id="app">
    <Navbar />
    <main>
      <router-view />
    </main>
  </div>
</template>

<script setup>
import { onMounted } from 'vue'
import { useAuthStore } from '@/stores/auth.js'
import Navbar from '@/components/layout/Navbar.vue'

const authStore = useAuthStore()

onMounted(() => {
  authStore.initAuth()
})
</script>

<style>
#app {
  min-height: 100vh;
  background: #f8f9fa;
}

main {
  min-height: calc(100vh - 76px);
}
</style>