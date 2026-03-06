<template>
  <ClientOnly>
    <div class="flex h-full w-full flex-col">
      <MapFilterBar
        v-model:category="selectedCategory"
        v-model:date="selectedDate"
      />
      <MapSection
        :markers="markers"
        :focus-marker-id="focusMarkerId"
        @bounds-change="onBoundsChange"
        @map-click="onMapClick"
      />
      <MarkerList
        :markers="markers"
        :pending="pending"
        @marker-click="focusOnMarker"
      />
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

const store = useMarkersStore();
const authStore = useAuthStore();
const { markers, pending } = storeToRefs(store);

const showAddModal = ref(false);
const addMarkerCoords = ref<{ lat: number; lng: number } | null>(null);
const focusMarkerId = ref<number | null>(null);

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

function focusOnMarker(marker: { id: number }) {
  focusMarkerId.value = null;
  nextTick(() => {
    focusMarkerId.value = marker.id;
  });
}
</script>
