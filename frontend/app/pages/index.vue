<template>
  <ClientOnly>
    <div class="flex h-screen w-screen flex-col">
      <!-- Row 1: Filters -->
      <div class="flex shrink-0 items-center justify-between gap-4 border-b border-gray-200 bg-white px-4 py-3">
        <select
          v-model="selectedCategory"
          class="rounded border border-primary px-3 py-2 text-sm focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary"
        >
          <option
            v-for="opt in CATEGORY_OPTIONS"
            :key="opt.value"
            :value="opt.value"
          >
            {{ opt.label }}
          </option>
        </select>
        <input
          v-model="selectedDate"
          type="date"
          class="rounded border border-primary px-3 py-2 text-sm focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary"
        >
      </div>

      <!-- Row 2: Map -->
      <div class="shrink-0">
        <MapView
          :markers="markers"
          @bounds-change="onBoundsChange"
        />
      </div>

      <!-- Row 3: Marker list -->
      <div class="min-h-0 flex-1 overflow-auto border-t border-gray-200 bg-gray-50">
        <div
          v-if="pending"
          class="flex items-center justify-center p-8"
        >
          <p class="text-gray-500">Loading markers...</p>
        </div>
        <div
          v-else-if="markers.length === 0"
          class="flex items-center justify-center p-8"
        >
          <p class="text-gray-500">No markers in this area</p>
        </div>
        <ul
          v-else
          class="divide-y divide-gray-200"
        >
          <li
            v-for="marker in markers"
            :key="marker.id"
            class="bg-white px-4 py-3 hover:bg-gray-50"
          >
            <div class="flex items-start gap-3">
              <span
                class="inline-flex shrink-0 rounded px-2 py-0.5 text-xs font-medium text-white"
                :style="{ backgroundColor: '#059212' }"
              >
                {{ formatCategory(marker.category) }}
              </span>
              <div class="min-w-0 flex-1">
                <p class="truncate text-sm text-gray-900">
                  {{ marker.description }}
                </p>
                <p class="mt-1 text-xs text-gray-500">
                  {{ marker.user?.name }} · {{ formatCoords(marker.latitude, marker.longitude) }}
                </p>
              </div>
            </div>
          </li>
        </ul>
      </div>
    </div>
    <template #fallback>
      <div class="flex h-screen w-screen items-center justify-center">
        <p>Loading map...</p>
      </div>
    </template>
  </ClientOnly>
</template>

<script setup lang="ts">
import { storeToRefs } from 'pinia'
import { useMarkersStore } from '~/stores/markers'
import { CATEGORY_OPTIONS } from '~/composables/useMarkers'

const store = useMarkersStore()
const { markers, pending } = storeToRefs(store)

const selectedCategory = computed({
  get: () => store.selectedCategory,
  set: (v) => store.setCategory(v),
})
const selectedDate = computed({
  get: () => store.selectedDate,
  set: (v) => store.setDate(v),
})

function onBoundsChange(bounds: { getCenter: () => { lat: number; lng: number }; getNorthEast: () => { lat: number; lng: number } }) {
  store.setBounds(bounds)
}

function formatCategory(cat: string): string {
  return CATEGORY_OPTIONS.find(o => o.value === cat)?.label ?? cat
}

function formatCoords(lat: string, lng: string): string {
  return `${parseFloat(lat).toFixed(4)}, ${parseFloat(lng).toFixed(4)}`
}
</script>
