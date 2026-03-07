<template>
  <ClientOnly>
    <div class="flex h-full w-full flex-col">
      <!-- Row 1: Filters -->
      <div
        class="flex shrink-0 items-center justify-between gap-4 border-b border-gray-200 bg-white px-4 py-3"
      >
        <USelect
          v-model="selectedCategory"
          :items="CATEGORY_OPTIONS"
          placeholder="All categories"
          class="min-w-[140px]"
        />
        <DatePicker v-model="selectedDate" />
      </div>

      <!-- Row 2: Map -->
      <div class="relative z-0 shrink-0 overflow-hidden">
        <MapView
          :markers="markers"
          :focus-marker-id="focusMarkerId"
          @bounds-change="onBoundsChange"
          @map-click="onMapClick"
          @marker-click="onMarkerClick"
        />
      </div>

      <!-- Row 3: Marker list -->
      <div
        class="min-h-0 flex-1 overflow-auto border-t border-gray-200 bg-gray-50"
      >
        <div v-if="pending" class="flex items-center justify-center p-8">
          <p class="text-gray-500">Loading markers...</p>
        </div>
        <div
          v-else-if="markers.length === 0"
          class="flex items-center justify-center p-8"
        >
          <p class="text-gray-500">No markers in this area</p>
        </div>
        <ul v-else class="divide-y divide-gray-200">
          <li
            v-for="marker in markers"
            :key="marker.id"
            class="cursor-pointer bg-white px-4 py-3 hover:bg-gray-50"
            @click="focusOnMarker(marker)"
          >
            <div class="flex items-center gap-3">
              <span
                class="inline-flex w-24 shrink-0 items-center justify-center rounded px-2 py-0.5 text-xs font-medium text-white"
                :style="{ backgroundColor: getCategoryColor(marker.category) }"
              >
                {{ formatCategory(marker.category) }}
              </span>
              <div class="min-w-0 flex-1">
                <p class="truncate text-sm text-gray-900">
                  {{ marker.description }}
                </p>
                <p class="mt-1 text-xs text-gray-500">
                  {{ marker.user?.name }} ·
                  {{ marker.address || formatCoords(marker.latitude, marker.longitude) }}
                </p>
              </div>
            </div>
          </li>
        </ul>
      </div>

      <AddMarkerModal
        :model-value="showAddModal"
        :initial-coordinates="addMarkerCoords"
        @update:model-value="
          (v) => {
            showAddModal = v;
            if (!v) addMarkerCoords = null;
          }
        "
        @created="onMarkerCreated"
      />

      <MarkerDetailsModal
        :model-value="showDetailsModal"
        :marker="selectedMarker"
        @update:model-value="showDetailsModal = false"
      />
    </div>
    <template #fallback>
      <div class="flex h-screen w-screen items-center justify-center">
        <p>Loading map...</p>
      </div>
    </template>
  </ClientOnly>
</template>

<script setup lang="ts">
import { storeToRefs } from 'pinia';
import { useMarkersStore } from '~/stores/markers';
import { CATEGORY_OPTIONS, getCategoryColor } from '~/composables/useMarkers';
import type { Marker } from '~/composables/useMarkers';

const store = useMarkersStore();
const authStore = useAuthStore();
const { markers, pending } = storeToRefs(store);

const showAddModal = ref(false);
const addMarkerCoords = ref<{ lat: number; lng: number } | null>(null);
const focusMarkerId = ref<number | null>(null);
const showDetailsModal = ref(false);
const selectedMarker = ref<Marker | null>(null);

function onMapClick(coords: { lat: number; lng: number }) {
  if (!authStore.isAuthenticated) return;
  addMarkerCoords.value = coords;
  showAddModal.value = true;
}

const selectedCategory = computed({
  get: () => store.selectedCategory,
  set: (v) => store.setCategory(v)
});
const selectedDate = computed({
  get: () => store.selectedDate,
  set: (v) => store.setDate(v)
});

function onBoundsChange(bounds: {
  getCenter: () => { lat: number; lng: number };
  getNorthEast: () => { lat: number; lng: number };
}) {
  store.setBounds(bounds);
}

function onMarkerCreated() {
  addMarkerCoords.value = null;
  store.doFetch();
}

function formatCategory(cat: string): string {
  return CATEGORY_OPTIONS.find((o) => o.value === cat)?.label ?? cat;
}

function formatCoords(lat: string, lng: string): string {
  return `${parseFloat(lat).toFixed(4)}, ${parseFloat(lng).toFixed(4)}`;
}

function focusOnMarker(marker: Marker) {
  focusMarkerId.value = null;
  nextTick(() => {
    focusMarkerId.value = marker.id;
  });
}

function onMarkerClick(marker: Marker) {
  selectedMarker.value = marker;
  showDetailsModal.value = true;
}
</script>
