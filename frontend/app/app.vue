<template>
  <UApp :toaster="toaster">
    <div class="flex h-screen flex-col overflow-hidden">
      <NuxtRouteAnnouncer />
      <UNotifications />
      <AppHeader />
      <main class="min-h-0 flex-1 overflow-hidden">
        <NuxtPage />
      </main>
    </div>
  </UApp>
</template>

<script setup lang="ts">
const authStore = useAuthStore()
const toaster = { position: 'top-right' }
onMounted(() => {
  authStore.init()
})
</script>
