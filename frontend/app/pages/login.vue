<template>
  <div class="flex min-h-screen flex-col items-center justify-center bg-gray-50 px-4">
    <div class="w-full max-w-sm">
      <h1 class="mb-6 text-center text-2xl font-semibold text-gray-900">
        Log in to WayWatch
      </h1>

      <form
        class="space-y-4"
        @submit.prevent="onSubmit"
      >
        <div>
          <label
            for="email"
            class="mb-1 block text-sm font-medium text-gray-700"
          >
            Email
          </label>
          <input
            id="email"
            v-model="email"
            type="email"
            required
            autocomplete="email"
            class="w-full rounded border border-gray-300 px-3 py-2 text-sm focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary"
            placeholder="you@example.com"
          >
        </div>

        <div>
          <label
            for="password"
            class="mb-1 block text-sm font-medium text-gray-700"
          >
            Password
          </label>
          <input
            id="password"
            v-model="password"
            type="password"
            required
            autocomplete="current-password"
            class="w-full rounded border border-gray-300 px-3 py-2 text-sm focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary"
          >
        </div>

        <p
          v-if="error"
          class="text-sm text-red-600"
        >
          {{ error }}
        </p>

        <button
          type="submit"
          :disabled="pending"
          class="w-full rounded bg-primary px-4 py-2 font-medium text-white hover:bg-primary/90 disabled:opacity-50"
        >
          {{ pending ? 'Logging in...' : 'Log in' }}
        </button>
      </form>

      <p class="mt-4 text-center text-sm text-gray-600">
        Don't have an account?
        <NuxtLink
          to="/register"
          class="font-medium text-primary hover:underline"
        >
          Register
        </NuxtLink>
      </p>
    </div>
  </div>
</template>

<script setup lang="ts">
definePageMeta({ layout: false })

const authStore = useAuthStore()
const { pending } = storeToRefs(authStore)

const email = ref('')
const password = ref('')
const error = ref('')

async function onSubmit() {
  error.value = ''
  try {
    await authStore.login(email.value, password.value)
    await navigateTo('/')
  } catch (e: any) {
    const data = e?.data
    if (data?.errors?.email) {
      error.value = data.errors.email[0]
    } else if (data?.message) {
      error.value = data.message
    } else {
      error.value = 'Login failed. Please try again.'
    }
  }
}
</script>
