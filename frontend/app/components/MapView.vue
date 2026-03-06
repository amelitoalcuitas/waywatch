<template>
  <div class="relative z-0 h-[50vh] w-full overflow-hidden">
    <LMap
      ref="mapRef"
      :zoom="zoom"
      :center="center"
      :use-global-leaflet="true"
      class="h-full w-full"
      @ready="onMapReady"
    >
      <LTileLayer
        url="https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png"
        attribution="&copy; <a href='https://www.openstreetmap.org/'>OpenStreetMap</a> contributors"
        layer-type="base"
        name="OpenStreetMap"
      />
      <LMarker
        v-if="userLocation && userLocationIcon"
        :lat-lng="userLocation"
        :icon="userLocationIcon"
      />
    </LMap>
    <button
      type="button"
      class="absolute bottom-4 right-4 z-[1000] flex h-11 w-11 items-center justify-center rounded-full bg-white shadow-lg ring-1 ring-gray-200 transition hover:bg-gray-50 active:scale-95 disabled:opacity-50"
      :disabled="!userLocation"
      :title="userLocation ? 'Center on my location' : 'Location unavailable'"
      @click="centerOnUserLocation"
    >
      <svg
        xmlns="http://www.w3.org/2000/svg"
        viewBox="0 0 24 24"
        fill="currentColor"
        class="h-5 w-5 text-blue-600"
      >
        <path
          fill-rule="evenodd"
          d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5a2.5 2.5 0 110-5 2.5 2.5 0 010 5z"
          clip-rule="evenodd"
        />
      </svg>
    </button>
  </div>
</template>

<script setup lang="ts">
import type { Marker } from '~/composables/useMarkers'
import { getCategoryColor } from '~/composables/useMarkers'

const props = defineProps<{
  markers: Marker[]
  focusMarkerId?: number | null
}>()

const emit = defineEmits<{
  boundsChange: [bounds: { getCenter: () => { lat: number; lng: number }; getNorthEast: () => { lat: number; lng: number } }]
  mapClick: [coords: { lat: number; lng: number }]
}>()

const DEFAULT_CENTER: [number, number] = [14.5995, 120.9842]

const zoom = ref(13)
const center = ref<[number, number]>(DEFAULT_CENTER)
const userLocation = ref<[number, number] | null>(null)
const mapRef = ref<any>(null)

const L = (globalThis as any).L
const userLocationIcon = L?.divIcon({
  className: 'user-location-marker',
  html: `<div style="width:24px;height:24px;background:#2563eb;border-radius:50%;display:flex;align-items:center;justify-content:center;box-shadow:0 2px 6px rgba(0,0,0,0.3);border:3px solid white">
    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="white" width="14" height="14">
      <path fill-rule="evenodd" d="M7.5 6a4.5 4.5 0 1 1 9 0 4.5 4.5 0 0 1-9 0ZM3.751 20.105a8.25 8.25 0 0 1 16.498 0 .75.75 0 0 1-.437.695A18.683 18.683 0 0 1 12 22.5c-2.786 0-5.433-.608-7.812-1.7a.75.75 0 0 1-.437-.695Z" clip-rule="evenodd"/>
    </svg>
  </div>`,
  iconSize: [24, 24],
  iconAnchor: [12, 12],
})

onMounted(() => {
  if (!navigator.geolocation) return
  navigator.geolocation.getCurrentPosition(
    (pos) => {
      const coords: [number, number] = [pos.coords.latitude, pos.coords.longitude]
      center.value = coords
      userLocation.value = coords
    },
    () => {
      // User denied or error — keep default center
    }
  )
})

function centerOnUserLocation() {
  if (!userLocation.value) return
  const map = mapRef.value?.leafletObject
  if (!map) return
  map.flyTo(userLocation.value, 18, { duration: 0.4 })
}
let clusterRef: any = null
let leafletMarkers: any[] = []
let skipNextBoundsEmit = false

function markersToClusterFormat(markers: Marker[]) {
  const L = (globalThis as any).L
  if (!L) return []

  return markers.map((m) => {
    const color = getCategoryColor(m.category)
    return {
      lat: parseFloat(m.latitude),
      lng: parseFloat(m.longitude),
      name: m.description?.slice(0, 50) ?? m.category,
      popup: `<strong>${m.category}</strong><br>${m.description ?? ''}`,
      options: {
        icon: L.divIcon({
          className: 'category-marker',
          html: `<div style="background-color:${color};border:3px solid white;border-radius:50%;width:24px;height:24px;box-shadow:0 2px 6px rgba(0,0,0,0.3)"></div>`,
          iconSize: [24, 24],
          iconAnchor: [12, 12],
        }),
      },
    }
  })
}

async function updateCluster() {
  const map = mapRef.value?.leafletObject
  if (!map) return

  if (clusterRef) {
    map.removeLayer(clusterRef)
    clusterRef = null
    leafletMarkers = []
  }

  if (props.markers.length === 0) return

  const { markerCluster, markers } = await useLMarkerCluster({
    leafletObject: map,
    markers: markersToClusterFormat(props.markers),
  })
  clusterRef = markerCluster
  leafletMarkers = markers
}

function onMapReady() {
  const map = mapRef.value?.leafletObject
  if (!map) return

  const emitBounds = () => {
    if (skipNextBoundsEmit) return
    const bounds = map.getBounds()
    if (bounds) emit('boundsChange', bounds)
  }

  emitBounds()
  map.on('moveend', emitBounds)
  map.on('zoomend', emitBounds)
  map.on('click', (e: { latlng: { lat: number; lng: number } }) => {
    emit('mapClick', { lat: e.latlng.lat, lng: e.latlng.lng })
  })

  updateCluster()
}

watch(
  () => props.markers,
  () => updateCluster(),
  { deep: true }
)

function focusOnMarker(marker: Marker) {
  const map = mapRef.value?.leafletObject
  if (!map) return

  const lat = parseFloat(marker.latitude)
  const lng = parseFloat(marker.longitude)
  const popupContent = `<strong>${marker.category}</strong><br>${marker.description ?? ''}`

  const idx = props.markers.findIndex((m) => m.id === marker.id)
  const leafletMarker = idx >= 0 && leafletMarkers[idx] ? leafletMarkers[idx] : null

  skipNextBoundsEmit = true

  const showPopup = () => {
    map.flyTo([lat, lng], 16, { duration: 0.4 })
    if (clusterRef && leafletMarker) {
      leafletMarker.bindPopup(popupContent).openPopup()
    } else {
      const L = (globalThis as any).L
      if (L) {
        L.popup()
          .setLatLng([lat, lng])
          .setContent(popupContent)
          .openOn(map)
      }
    }
    setTimeout(() => { skipNextBoundsEmit = false }, 2000)
  }

  if (clusterRef && leafletMarker) {
    clusterRef.zoomToShowLayer(leafletMarker, showPopup)
  } else {
    showPopup()
  }
}

watch(
  () => props.focusMarkerId,
  (id) => {
    if (id == null) return
    const marker = props.markers.find((m) => m.id === id)
    if (marker) {
      nextTick(() => focusOnMarker(marker))
    }
  }
)
</script>

<style>
.user-location-marker {
  background: none !important;
  border: none !important;
}
</style>
