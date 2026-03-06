<template>
  <header class="flex shrink-0 items-center justify-between border-b border-primary/20 bg-primary px-4 py-3">
    <NuxtLink
      to="/"
      class="text-lg font-semibold text-white hover:text-white/90"
    >
      WayWatch
    </NuxtLink>
    <div class="flex items-center gap-2">
      <template v-if="authStore.isAuthenticated">
        <span class="text-sm text-white/90">{{ authStore.user?.name }}</span>
        <button
          type="button"
          class="text-sm text-white hover:underline"
          @click="authStore.logout()"
        >
          Log out
        </button>
      </template>
      <template v-else>
        <NuxtLink
          to="/login"
          class="text-sm font-medium text-white hover:underline"
        >
          Log in
        </NuxtLink>
        <NuxtLink
          to="/register"
          class="text-sm text-white/90 hover:underline"
        >
          Register
        </NuxtLink>
      </template>
    </div>
  </header>
</template>

<script setup lang="ts">
const authStore = useAuthStore()
</script>
