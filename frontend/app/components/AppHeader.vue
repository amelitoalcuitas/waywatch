<template>
  <header class="flex shrink-0 items-center justify-between border-b border-primary/20 bg-primary px-4 py-3">
    <NuxtLink to="/" class="text-lg font-semibold text-white hover:text-white/90">
      WayWatch
    </NuxtLink>
    <div class="flex items-center gap-2">
      <template v-if="authStore.isAuthenticated">
        <DialogModal
          v-model:open="isLogoutDialogOpen"
          title="Confirm logout"
          description="Are you sure you want to log out?"
          confirm-label="Log out"
          cancel-label="Cancel"
          @confirm="confirmLogout"
          @cancel="closeLogoutDialog"
        />

        <UDropdownMenu :items="userMenuItems" :content="{ align: 'end' }" size="sm">
          <UAvatar icon="i-lucide-user" :label="authStore.user?.name || 'Account'" />
        </UDropdownMenu>
      </template>

      <template v-else>
        <NuxtLink to="/login" class="text-sm font-medium text-white hover:underline">
          Log in
        </NuxtLink>
        <NuxtLink to="/register" class="text-sm text-white/90 hover:underline">
          Register
        </NuxtLink>
      </template>
    </div>
  </header>
</template>

<script setup lang="ts">
import type { DropdownMenuItem } from '@nuxt/ui'

const authStore = useAuthStore()

const isLogoutDialogOpen = ref(false)

const userMenuItems = computed<DropdownMenuItem[]>(() => [
  {
    label: authStore.user?.name || 'Account',
    type: 'label' as const
  },
  {
    type: 'separator' as const
  },
  {
    label: 'Log out',
    icon: 'i-lucide-log-out',
    color: 'error',
    onSelect(event: Event) {
      isLogoutDialogOpen.value = true
    }
  }
])

const closeLogoutDialog = () => {
  isLogoutDialogOpen.value = false
}

const confirmLogout = async () => {
  await authStore.logout()
  isLogoutDialogOpen.value = false
}
</script>
