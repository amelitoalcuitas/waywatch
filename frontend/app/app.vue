<template>
  <UApp>
    <div class="flex h-screen flex-col overflow-hidden">
      <NuxtRouteAnnouncer />
      <AppHeader />
      <main class="min-h-0 flex-1 overflow-hidden">
        <NuxtPage />
      </main>
    </div>
  </UApp>
</template>

<script setup lang="ts">
const authStore = useAuthStore()
onMounted(() => {
  authStore.init()
})
</script>
