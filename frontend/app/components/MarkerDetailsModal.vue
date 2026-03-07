<template>
  <UModal
    v-if="isDesktop"
    v-model:open="open"
    title="Report Details"
    :ui="{ footer: 'justify-stretch' }"
  >
    <template #body>
      <div v-if="marker" class="space-y-4">
        <div>
          <p class="text-xs font-medium text-gray-500">Category</p>
          <span
            class="inline-flex mt-1 rounded px-2 py-0.5 text-sm font-medium text-white"
            :style="{ backgroundColor: getCategoryColor(marker.category) }"
          >
            {{ formatCategory(marker.category) }}
          </span>
        </div>

        <div>
          <p class="text-xs font-medium text-gray-500">Description</p>
          <p class="mt-1 text-sm text-gray-900 whitespace-pre-wrap">
            {{ marker.description }}
          </p>
        </div>

        <div>
          <p class="text-xs font-medium text-gray-500">Location</p>
          <p class="mt-1 text-sm text-gray-900">
            {{ marker.address || formatCoords(marker.latitude, marker.longitude) }}
          </p>
        </div>

        <div>
          <p class="text-xs font-medium text-gray-500">Reported by</p>
          <p class="mt-1 text-sm text-gray-900">{{ marker.user?.name }}</p>
        </div>

        <div>
          <p class="text-xs font-medium text-gray-500">Posted</p>
          <p class="mt-1 text-sm text-gray-900">{{ formatDate(marker.created_at) }}</p>
        </div>

        <div v-if="marker.images?.length" class="space-y-2">
          <p class="text-xs font-medium text-gray-500">Photos</p>
          <div class="flex flex-wrap gap-2">
            <button
              v-for="(img, idx) in marker.images"
              :key="img.id"
              type="button"
              class="block overflow-hidden rounded-lg ring-2 ring-transparent transition hover:ring-gray-300 focus:outline-none focus:ring-2 focus:ring-primary-500"
              @click="openImageViewer(img.image_url, idx)"
            >
              <img
                :src="img.image_url"
                :alt="`Photo ${idx + 1}`"
                class="h-20 w-20 object-cover"
              />
            </button>
          </div>
        </div>

        <div class="flex gap-4 text-sm text-gray-500">
          <span>{{ marker.likes }} likes</span>
          <span>{{ marker.dislikes }} dislikes</span>
        </div>
      </div>
    </template>
  </UModal>

  <UDrawer
    v-else
    v-model:open="open"
    title="Report Details"
    :ui="{ footer: 'justify-stretch' }"
  >
    <template #body>
      <div v-if="marker" class="space-y-4">
        <div>
          <p class="text-xs font-medium text-gray-500">Category</p>
          <span
            class="inline-flex mt-1 rounded px-2 py-0.5 text-sm font-medium text-white"
            :style="{ backgroundColor: getCategoryColor(marker.category) }"
          >
            {{ formatCategory(marker.category) }}
          </span>
        </div>

        <div>
          <p class="text-xs font-medium text-gray-500">Description</p>
          <p class="mt-1 text-sm text-gray-900 whitespace-pre-wrap">
            {{ marker.description }}
          </p>
        </div>

        <div>
          <p class="text-xs font-medium text-gray-500">Location</p>
          <p class="mt-1 text-sm text-gray-900">
            {{ marker.address || formatCoords(marker.latitude, marker.longitude) }}
          </p>
        </div>

        <div>
          <p class="text-xs font-medium text-gray-500">Reported by</p>
          <p class="mt-1 text-sm text-gray-900">{{ marker.user?.name }}</p>
        </div>

        <div>
          <p class="text-xs font-medium text-gray-500">Posted</p>
          <p class="mt-1 text-sm text-gray-900">{{ formatDate(marker.created_at) }}</p>
        </div>

        <div v-if="marker.images?.length" class="space-y-2">
          <p class="text-xs font-medium text-gray-500">Photos</p>
          <div class="flex flex-wrap gap-2">
            <button
              v-for="(img, idx) in marker.images"
              :key="img.id"
              type="button"
              class="block overflow-hidden rounded-lg ring-2 ring-transparent transition hover:ring-gray-300 focus:outline-none focus:ring-2 focus:ring-primary-500"
              @click="openImageViewer(img.image_url, idx)"
            >
              <img
                :src="img.image_url"
                :alt="`Photo ${idx + 1}`"
                class="h-20 w-20 object-cover"
              />
            </button>
          </div>
        </div>

        <div class="flex gap-4 text-sm text-gray-500">
          <span>{{ marker.likes }} likes</span>
          <span>{{ marker.dislikes }} dislikes</span>
        </div>
      </div>
    </template>
  </UDrawer>

  <ImageViewerModal
    v-model:open="imageViewerOpen"
    :image-url="selectedImageUrl"
    :images="marker?.images?.map((i) => i.image_url) ?? []"
    :initial-index="selectedImageIndex"
  />
</template>

<script setup lang="ts">
import { useMediaQuery } from '@vueuse/core'
import type { Marker } from '~/composables/useMarkers'
import { CATEGORY_OPTIONS, getCategoryColor } from '~/composables/useMarkers'

const isDesktop = useMediaQuery('(min-width: 768px)')

const props = defineProps<{
  modelValue: boolean
  marker: Marker | null
}>()

const emit = defineEmits<{
  'update:modelValue': [value: boolean]
}>()

const open = computed({
  get: () => props.modelValue,
  set: (v) => emit('update:modelValue', v)
})

const imageViewerOpen = ref(false)
const selectedImageUrl = ref('')
const selectedImageIndex = ref(0)

function formatCategory(cat: string): string {
  return CATEGORY_OPTIONS.find((o) => o.value === cat)?.label ?? cat
}

function formatCoords(lat: string, lng: string): string {
  return `${parseFloat(lat).toFixed(4)}, ${parseFloat(lng).toFixed(4)}`
}

function formatDate(iso: string): string {
  try {
    const d = new Date(iso)
    return d.toLocaleDateString(undefined, {
      year: 'numeric',
      month: 'short',
      day: 'numeric',
      hour: '2-digit',
      minute: '2-digit'
    })
  } catch {
    return iso
  }
}

function openImageViewer(url: string, index: number) {
  selectedImageUrl.value = url
  selectedImageIndex.value = index
  imageViewerOpen.value = true
}
</script>
