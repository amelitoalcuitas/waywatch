<template>
  <Teleport to="body">
    <div
      v-if="modelValue"
      class="fixed inset-0 z-[1000] flex items-end justify-center sm:items-center"
      @click.self="close"
    >
      <div
        class="absolute inset-0 bg-black/50"
        aria-hidden="true"
      />
      <div
        class="relative max-h-[90vh] w-full max-w-md overflow-y-auto rounded-t-2xl bg-white shadow-xl sm:rounded-2xl"
        role="dialog"
        aria-modal="true"
        aria-labelledby="add-marker-title"
      >
        <div class="sticky top-0 flex items-center justify-between border-b border-gray-200 bg-white px-4 py-3">
          <h2
            id="add-marker-title"
            class="text-lg font-semibold text-gray-900"
          >
            Add Report
          </h2>
          <button
            type="button"
            class="rounded p-2 text-gray-500 hover:bg-gray-100 hover:text-gray-700"
            aria-label="Close"
            @click="close"
          >
            <svg
              class="h-5 w-5"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M6 18L18 6M6 6l12 12"
              />
            </svg>
          </button>
        </div>

        <form
          class="space-y-4 p-4"
          @submit.prevent="onSubmit"
        >
          <div
            v-if="locationError"
            class="rounded bg-red-50 p-3 text-sm text-red-700"
          >
            {{ locationError }}
          </div>

          <div v-if="!locationError && position">
            <p class="text-xs text-gray-500">
              Location: {{ position.lat.toFixed(5) }}, {{ position.lng.toFixed(5) }}
            </p>
          </div>

          <div>
            <label
              for="category"
              class="mb-1 block text-sm font-medium text-gray-700"
            >
              Category
            </label>
            <select
              id="category"
              v-model="category"
              required
              class="w-full rounded border border-gray-300 px-3 py-2 text-sm focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary"
            >
              <option
                v-for="opt in categoryOptions"
                :key="opt.value"
                :value="opt.value"
              >
                {{ opt.label }}
              </option>
            </select>
          </div>

          <div>
            <label
              for="description"
              class="mb-1 block text-sm font-medium text-gray-700"
            >
              Description
            </label>
            <textarea
              id="description"
              v-model="description"
              required
              maxlength="2000"
              rows="4"
              class="w-full rounded border border-gray-300 px-3 py-2 text-sm focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary"
              placeholder="Describe the road condition or situation..."
            />
            <p class="mt-1 text-xs text-gray-500">
              {{ description.length }} / 2000
            </p>
          </div>

          <p
            v-if="submitError"
            class="text-sm text-red-600"
          >
            {{ submitError }}
          </p>

          <button
            type="submit"
            :disabled="submitting || !position || !!locationError"
            class="w-full rounded bg-primary px-4 py-2 font-medium text-white hover:bg-primary/90 disabled:opacity-50"
          >
            {{ submitting ? 'Submitting...' : 'Submit Report' }}
          </button>
        </form>
      </div>
    </div>
  </Teleport>
</template>

<script setup lang="ts">
import { CATEGORY_OPTIONS } from '~/composables/useMarkers'
import type { Marker } from '~/composables/useMarkers'

const categoryOptions = CATEGORY_OPTIONS.filter((o) => o.value !== 'all')

const props = defineProps<{
  modelValue: boolean
  initialCoordinates?: { lat: number; lng: number } | null
}>()

const emit = defineEmits<{
  'update:modelValue': [value: boolean]
  created: [marker: Marker]
}>()

const { apiFetch } = useApi()
const authStore = useAuthStore()

const category = ref('road_repair')
const description = ref('')
const position = ref<{ lat: number; lng: number } | null>(null)
const locationError = ref('')
const submitError = ref('')
const submitting = ref(false)

function close() {
  emit('update:modelValue', false)
}

async function getLocation(): Promise<{ lat: number; lng: number } | null> {
  return new Promise((resolve) => {
    if (!navigator.geolocation) {
      locationError.value = 'Geolocation is not supported by your browser.'
      resolve(null)
      return
    }
    navigator.geolocation.getCurrentPosition(
      (pos) => {
        resolve({ lat: pos.coords.latitude, lng: pos.coords.longitude })
      },
      (err) => {
        if (err.code === 1) {
          locationError.value = 'Location access denied. Please enable location to add a report.'
        } else {
          locationError.value = 'Could not get your location. Please try again.'
        }
        resolve(null)
      },
      { enableHighAccuracy: true, timeout: 10000, maximumAge: 60000 }
    )
  })
}

async function onSubmit() {
  if (!position.value || !authStore.token) return
  submitError.value = ''
  submitting.value = true
  try {
    const res = await apiFetch<{ data: Marker }>('/markers', {
      method: 'POST',
      body: {
        latitude: position.value.lat,
        longitude: position.value.lng,
        category: category.value,
        description: description.value.trim(),
      },
    })
    emit('created', res.data)
    category.value = 'road_repair'
    description.value = ''
    close()
  } catch (e: any) {
    const data = e?.data
    if (data?.errors) {
      const firstError = Object.values(data.errors).flat()[0]
      submitError.value = typeof firstError === 'string' ? firstError : 'Validation failed.'
    } else if (data?.message) {
      submitError.value = data.message
    } else if (e?.statusCode === 401) {
      submitError.value = 'Session expired. Please log in again.'
    } else {
      submitError.value = 'Failed to submit report. Please try again.'
    }
  } finally {
    submitting.value = false
  }
}

watch(
  () => props.modelValue,
  async (open) => {
    if (open) {
      position.value = null
      locationError.value = ''
      submitError.value = ''
      if (props.initialCoordinates) {
        position.value = props.initialCoordinates
      } else {
        position.value = await getLocation()
      }
    }
  }
)
</script>
