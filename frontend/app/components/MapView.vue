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
      <LCircleMarker
        v-if="userLocation"
        :lat-lng="userLocation"
        :radius="8"
        color="#2563eb"
        fill-color="#3b82f6"
        :fill-opacity="0.7"
        :weight="2"
      >
        <LTooltip :options="{ permanent: true }">You are here</LTooltip>
      </LCircleMarker>
    </LMap>
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
