<template>
  <ClientOnly>
    <div class="flex h-full w-full flex-col">
      <!-- Row 1: Filters -->
      <div class="flex shrink-0 items-center justify-between gap-4 px-4 py-3">
        <USelect
          v-model="selectedCategory"
          :items="CATEGORY_OPTIONS"
          placeholder="All categories"
          class="min-w-[140px]"
        >
          <template #item="{ item }">
            <div class="flex items-center gap-2 w-full">
              <div
                v-if="item.value !== 'all'"
                class="inline-block w-3 h-3 rounded-full self-center"
                :style="{ backgroundColor: getCategoryColor(item.value) }"
              ></div>
              <span v-else class="w-3 h-3"> </span>
              {{ item.label }}
            </div>
          </template>
        </USelect>
        <DatePicker v-model="selectedDate" />
      </div>

      <!-- Row 2: Map -->
      <div class="relative z-0 h-[50vh] shrink-0 overflow-hidden">
        <MapView
          :markers="markers"
          :all-markers="allMarkers"
          :focus-marker-id="focusMarkerId"
          @bounds-change="onBoundsChange"
          @map-click="onMapClick"
          @marker-click="onMarkerClick"
          @location-error="onLocationError"
        />
      </div>

      <!-- Row 3: Marker list -->
      <div
        class="min-h-0 flex-1 overflow-auto border-t border-gray-200 bg-gray-50"
      >
        <!-- First load: show full loading state -->
        <div v-if="pending" class="flex items-center justify-center p-8">
          <p class="text-gray-500">Loading markers...</p>
        </div>

        <template v-else>
          <!-- Subsequent fetches: subtle top bar so the list never disappears -->
          <div
            v-if="refreshing"
            class="h-0.5 w-full bg-blue-100 overflow-hidden"
          >
            <div
              class="h-full bg-blue-400 animate-[slide_1.2s_ease-in-out_infinite]"
              style="width: 40%"
            />
          </div>

          <div
            v-if="allMarkers.length === 0"
            class="flex items-center justify-center p-8"
          >
            <p class="text-gray-500">No markers in this area</p>
          </div>

          <ul v-else class="divide-y divide-gray-200">
            <li
              v-for="marker in allMarkers"
              :key="marker.id"
              class="cursor-pointer bg-white px-4 py-3 hover:bg-gray-50"
              @click="focusOnMarker(marker)"
            >
              <div class="flex items-center gap-3">
                <span
                  class="inline-flex w-24 shrink-0 items-center justify-center rounded px-2 py-0.5 text-xs font-medium text-white"
                  :style="{
                    backgroundColor: getCategoryColor(marker.category)
                  }"
                >
                  {{ formatCategory(marker.category) }}
                </span>
                <div class="min-w-0 flex-1">
                  <p class="truncate text-sm text-gray-900">
                    {{ marker.description }}
                  </p>
                  <p class="mt-1 text-xs text-gray-500">
                    {{ marker.user?.name }} ·
                    {{
                      marker.address ||
                      formatCoords(marker.latitude, marker.longitude)
                    }}
                  </p>
                </div>
              </div>
            </li>
          </ul>
        </template>
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
        @update:model-value="(v) => (showDetailsModal = v)"
        @marker-updated="onMarkerUpdated"
        @marker-deleted="onMarkerDeleted"
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
import {
  CATEGORY_OPTIONS,
  formatDateForInput,
  getCategoryColor
} from '~/composables/useMarkers';
import type { Marker } from '~/composables/useMarkers';

const store = useMarkersStore();
const authStore = useAuthStore();
const { markers, allMarkers, pending, refreshing } = storeToRefs(store);

if (import.meta.client && !store.selectedDate) {
  store.setDate(formatDateForInput(new Date()));
}

const RADIUS_KM = 5;

const showAddModal = ref(false);
const addMarkerCoords = ref<{ lat: number; lng: number } | null>(null);
const focusMarkerId = ref<number | null>(null);
const showDetailsModal = ref(false);
const selectedMarker = ref<Marker | null>(null);
const toast = useToast();

function haversineDistanceKm(
  lat1: number,
  lon1: number,
  lat2: number,
  lon2: number
): number {
  const R = 6371;
  const dLat = ((lat2 - lat1) * Math.PI) / 180;
  const dLon = ((lon2 - lon1) * Math.PI) / 180;
  const a =
    Math.sin(dLat / 2) ** 2 +
    Math.cos((lat1 * Math.PI) / 180) *
      Math.cos((lat2 * Math.PI) / 180) *
      Math.sin(dLon / 2) ** 2;
  const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
  return R * c;
}

async function getLocation(): Promise<{ lat: number; lng: number } | null> {
  return new Promise((resolve) => {
    if (!navigator.geolocation) {
      resolve(null);
      return;
    }
    navigator.geolocation.getCurrentPosition(
      (pos) => {
        resolve({ lat: pos.coords.latitude, lng: pos.coords.longitude });
      },
      () => {
        resolve(null);
      },
      { enableHighAccuracy: true, timeout: 10000, maximumAge: 60000 }
    );
  });
}

async function onMapClick(coords: { lat: number; lng: number }) {
  if (!authStore.isAuthenticated) {
    toast.add({
      title: 'Login required',
      description: 'Please log in to add a report.',
      color: 'warning'
    });
    return;
  }

  const userLoc = await getLocation();
  if (!userLoc) {
    toast.add({
      title: 'Location required',
      description:
        'Location access is required to add a report. Please enable location in your browser.',
      color: 'warning'
    });
    return;
  }

  const dist = haversineDistanceKm(
    coords.lat,
    coords.lng,
    userLoc.lat,
    userLoc.lng
  );

  if (dist > RADIUS_KM) {
    toast.add({
      title: 'Marker out of range',
      description: `Marker must be within ${RADIUS_KM} km of your current location. (${dist.toFixed(
        1
      )} km away)`,
      color: 'error'
    });
    return;
  }

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

function onMarkerUpdated(updated: Marker) {
  const idx = store.markers.findIndex((m) => m.id === updated.id);
  if (idx !== -1) store.markers[idx] = updated;

  // Also update in allMarkers cache so the map popup reflects changes
  const allIdx = store.allMarkers.findIndex((m) => m.id === updated.id);
  if (allIdx !== -1) store.allMarkers[allIdx] = updated;

  selectedMarker.value = updated;
}

function onMarkerDeleted(markerId: number) {
  store.markers = store.markers.filter((m) => m.id !== markerId);
  store.allMarkers = store.allMarkers.filter((m) => m.id !== markerId);

  if (selectedMarker.value?.id === markerId) {
    selectedMarker.value = null;
  }
  showDetailsModal.value = false;
}

function onLocationError(message: string) {
  toast.add({
    title: 'Location unavailable',
    description: message,
    color: 'warning'
  });
}
</script>
