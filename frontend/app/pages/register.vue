<template>
  <div class="flex min-h-screen flex-col items-center justify-center bg-gray-50 px-4">
    <div class="w-full max-w-sm">
      <h1 class="mb-6 text-center text-2xl font-semibold text-gray-900">
        Create an account
      </h1>

      <form
        class="space-y-4"
        @submit.prevent="onSubmit"
      >
        <div>
          <label
            for="name"
            class="mb-1 block text-sm font-medium text-gray-700"
          >
            Name
          </label>
          <input
            id="name"
            v-model="name"
            type="text"
            required
            autocomplete="name"
            class="w-full rounded border border-gray-300 px-3 py-2 text-sm focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary"
            placeholder="Your name"
          >
        </div>

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
            autocomplete="new-password"
            class="w-full rounded border border-gray-300 px-3 py-2 text-sm focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary"
          >
        </div>

        <div>
          <label
            for="password_confirmation"
            class="mb-1 block text-sm font-medium text-gray-700"
          >
            Confirm password
          </label>
          <input
            id="password_confirmation"
            v-model="passwordConfirmation"
            type="password"
            required
            autocomplete="new-password"
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
          {{ pending ? 'Creating account...' : 'Register' }}
        </button>
      </form>

      <p class="mt-4 text-center text-sm text-gray-600">
        Already have an account?
        <NuxtLink
          to="/login"
          class="font-medium text-primary hover:underline"
        >
          Log in
        </NuxtLink>
      </p>
    </div>
  </div>
</template>

<script setup lang="ts">
definePageMeta({ layout: false })

const authStore = useAuthStore()
const { pending } = storeToRefs(authStore)

const name = ref('')
const email = ref('')
const password = ref('')
const passwordConfirmation = ref('')
const error = ref('')

async function onSubmit() {
  error.value = ''
  if (password.value !== passwordConfirmation.value) {
    error.value = 'Passwords do not match.'
    return
  }
  try {
    await authStore.register(name.value, email.value, password.value, passwordConfirmation.value)
    await navigateTo('/')
  } catch (e: any) {
    const data = e?.data
    if (data?.errors) {
      const firstError = Object.values(data.errors).flat()[0]
      error.value = typeof firstError === 'string' ? firstError : 'Validation failed.'
    } else if (data?.message) {
      error.value = data.message
    } else {
      error.value = 'Registration failed. Please try again.'
    }
  }
}
</script>
