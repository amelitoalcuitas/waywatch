<template>
  <DefineFormTemplate>
    <form
      class="space-y-4"
      @submit.prevent="onSubmit"
    >
      <UAlert
        v-if="locationError"
        color="error"
        variant="soft"
        :description="locationError"
      />

      <div v-if="!locationError && position">
        <p class="text-xs text-muted">
          Location: {{ address || (addressLoading ? 'Looking up address...' : `${position.lat.toFixed(5)}, ${position.lng.toFixed(5)}`) }}
        </p>
      </div>

      <UFormField
        label="Category"
        name="category"
        required
      >
        <USelect
          v-model="category"
          :items="categoryOptions"
          placeholder="Select category"
          class="w-full"
        />
      </UFormField>

      <UFormField
        label="Description"
        name="description"
        required
        :help="`${description.length} / 2000`"
      >
        <UTextarea
          v-model="description"
          required
          :maxlength="2000"
          :rows="4"
          placeholder="Describe the road condition or situation..."
          class="w-full"
        />
      </UFormField>

      <UAlert
        v-if="submitError"
        color="error"
        variant="soft"
        :description="submitError"
      />

      <UButton
        type="submit"
        block
        :loading="submitting"
        :disabled="!position || !!locationError"
      >
        {{ submitting ? 'Submitting...' : 'Submit Report' }}
      </UButton>
    </form>
  </DefineFormTemplate>

  <UModal
    v-if="isDesktop"
    v-model:open="open"
    title="Add Report"
    :ui="{ footer: 'justify-stretch' }"
  >
    <div class="hidden" />
    <template #body>
      <ReuseFormTemplate />
    </template>
  </UModal>

  <UDrawer
    v-else
    v-model:open="open"
    title="Add Report"
    :ui="{ footer: 'justify-stretch' }"
  >
    <div class="hidden" />
    <template #body>
      <ReuseFormTemplate />
    </template>
  </UDrawer>
</template>

<script setup lang="ts">
import { createReusableTemplate, useMediaQuery } from '@vueuse/core'
import { reverseGeocode } from '~/composables/useGeocoding'
import { CATEGORY_OPTIONS } from '~/composables/useMarkers'
import type { Marker } from '~/composables/useMarkers'

const [DefineFormTemplate, ReuseFormTemplate] = createReusableTemplate()
const isDesktop = useMediaQuery('(min-width: 768px)')

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

const open = computed({
  get: () => props.modelValue,
  set: (v) => emit('update:modelValue', v),
})

const category = ref('road_repair')
const description = ref('')
const position = ref<{ lat: number; lng: number } | null>(null)
const address = ref<string | null>(null)
const addressLoading = ref(false)
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
        address: address.value || undefined,
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

async function fetchAddress(lat: number, lng: number) {
  address.value = null
  addressLoading.value = true
  try {
    const result = await reverseGeocode(lat, lng)
    address.value = result
  } finally {
    addressLoading.value = false
  }
}

watch(
  () => props.modelValue,
  async (open) => {
    if (open) {
      position.value = null
      address.value = null
      addressLoading.value = false
      locationError.value = ''
      submitError.value = ''
      if (props.initialCoordinates) {
        position.value = props.initialCoordinates
        await fetchAddress(props.initialCoordinates.lat, props.initialCoordinates.lng)
      } else {
        const coords = await getLocation()
        position.value = coords
        if (coords) {
          await fetchAddress(coords.lat, coords.lng)
        }
      }
    }
  }
)
</script>
