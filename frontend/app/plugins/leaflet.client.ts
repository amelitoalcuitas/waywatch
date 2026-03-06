import L from 'leaflet'
import 'leaflet/dist/leaflet.css'
import 'leaflet.markercluster/dist/MarkerCluster.css'
import 'leaflet.markercluster/dist/MarkerCluster.Default.css'

// Expose Leaflet globally so vue-leaflet and leaflet.markercluster work with use-global-leaflet="true"
;(globalThis as any).L = L

// Load marker cluster so it extends L with MarkerClusterGroup
import 'leaflet.markercluster'

export default defineNuxtPlugin(() => {})
