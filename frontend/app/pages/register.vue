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
        <UFormField
          label="Name"
          required
        >
          <UInput
            v-model="name"
            type="text"
            required
            autocomplete="name"
            placeholder="Your name"
            class="w-full"
          />
        </UFormField>

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
            autocomplete="new-password"
            class="w-full"
          />
        </UFormField>

        <UFormField
          label="Confirm password"
          required
        >
          <UInput
            v-model="passwordConfirmation"
            type="password"
            required
            autocomplete="new-password"
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
          {{ pending ? 'Creating account...' : 'Register' }}
        </UButton>
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
