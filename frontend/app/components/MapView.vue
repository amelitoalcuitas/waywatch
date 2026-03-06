<template>
  <div class="h-[50vh] w-full">
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
    </LMap>
  </div>
</template>

<script setup lang="ts">
import type { Marker } from '~/composables/useMarkers'

const props = defineProps<{
  markers: Marker[]
}>()

const emit = defineEmits<{
  boundsChange: [bounds: { getCenter: () => { lat: number; lng: number }; getNorthEast: () => { lat: number; lng: number } }]
}>()

const zoom = ref(13)
const center = ref<[number, number]>([14.5995, 120.9842])
const mapRef = ref<any>(null)
let clusterRef: any = null

function markersToClusterFormat(markers: Marker[]) {
  return markers.map((m) => ({
    lat: parseFloat(m.latitude),
    lng: parseFloat(m.longitude),
    name: m.description?.slice(0, 50) ?? m.category,
    popup: `<strong>${m.category}</strong><br>${m.description ?? ''}`,
  }))
}

function updateCluster() {
  const map = mapRef.value?.leafletObject
  if (!map) return

  if (clusterRef) {
    map.removeLayer(clusterRef)
    clusterRef = null
  }

  if (props.markers.length === 0) return

  const { markerCluster } = useLMarkerCluster({
    leafletObject: map,
    markers: markersToClusterFormat(props.markers),
  })
  clusterRef = markerCluster
}

function onMapReady() {
  const map = mapRef.value?.leafletObject
  if (!map) return

  const emitBounds = () => {
    const bounds = map.getBounds()
    if (bounds) emit('boundsChange', bounds)
  }

  emitBounds()
  map.on('moveend', emitBounds)
  map.on('zoomend', emitBounds)

  updateCluster()
}

watch(
  () => props.markers,
  () => updateCluster(),
  { deep: true }
)
</script>
