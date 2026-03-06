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
        <UFormField
          label="Email"
          required
        >
          <UInput
            v-model="email"
            type="email"
            required
            autocomplete="email"
            placeholder="you@example.com"
            class="w-full"
          />
        </UFormField>

        <UFormField
          label="Password"
          required
        >
          <UInput
            v-model="password"
            type="password"
            required
            autocomplete="current-password"
            class="w-full"
          />
        </UFormField>

        <UAlert
          v-if="error"
          color="error"
          variant="soft"
          :description="error"
        />

        <UButton
          type="submit"
          block
          :loading="pending"
        >
          {{ pending ? 'Logging in...' : 'Log in' }}
        </UButton>
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
definePageMeta({ layout: false, middleware: 'guest' })

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
