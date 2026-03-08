<template>
  <div class="relative z-0 h-[50vh] w-full overflow-hidden">
    <div ref="mapContainer" class="h-full w-full" />

    <button
      type="button"
      class="absolute bottom-2 right-2 z-[1000] flex h-10 w-10 items-center justify-center rounded-full bg-white shadow-lg ring-1 ring-gray-200 transition hover:bg-gray-50 active:scale-95 disabled:opacity-50"
      :title="userLocation ? 'Center on my location' : 'Enable location'"
      @click="centerOnUserLocation"
    >
      <svg
        xmlns="http://www.w3.org/2000/svg"
        viewBox="0 0 24 24"
        fill="currentColor"
        class="h-8 w-8 text-gray-500"
      >
        <path
          fill-rule="evenodd"
          clip-rule="evenodd"
          d="M11 2a1 1 0 0 1 2 0v2.062A8.004 8.004 0 0 1 19.938 11H22a1 1 0 0 1 0 2h-2.062A8.004 8.004 0 0 1 13 19.938V22a1 1 0 0 1-2 0v-2.062A8.004 8.004 0 0 1 4.062 13H2a1 1 0 0 1 0-2h2.062A8.004 8.004 0 0 1 11 4.062V2zm7 10a6 6 0 1 0-12 0 6 6 0 0 0 12 0zm-3 0a3 3 0 1 1-6 0 3 3 0 0 1 6 0z"
        />
      </svg>
    </button>
  </div>
</template>

<script setup lang="ts">
import type { Map as MapLibreMap, Marker as MapLibreMarker } from 'maplibre-gl';
import type { Marker } from '~/composables/useMarkers';
import {
  formatCategoryLabel,
  getCategoryColor
} from '~/composables/useMarkers';

const props = defineProps<{
  markers: Marker[];
  focusMarkerId?: number | null;
}>();

const emit = defineEmits<{
  boundsChange: [
    bounds: {
      getCenter: () => { lat: number; lng: number };
      getNorthEast: () => { lat: number; lng: number };
    }
  ];
  mapClick: [coords: { lat: number; lng: number }];
  markerClick: [marker: Marker];
  locationError: [message: string];
}>();

const DEFAULT_CENTER: [number, number] = [120.9842, 14.5995];
const SOURCE_ID = 'markers-source';
const CLUSTER_LAYER_ID = 'clusters';
const CLUSTER_COUNT_LAYER_ID = 'cluster-count';
const UNCLUSTERED_LAYER_ID = 'unclustered-point';

const mapContainer = ref<HTMLDivElement | null>(null);
const userLocation = ref<[number, number] | null>(null);

let map: MapLibreMap | null = null;
let maplibregl: any = null;
let userMarker: MapLibreMarker | null = null;
let suppressNextBoundsEmit = false;

const htmlMarkers: Map<number, MapLibreMarker> = new Map();

// ─── Throttle helper ──────────────────────────────────────────────────────────
// Limits syncHtmlMarkers to at most once every `wait` ms even though
// map.on('render') fires up to 60 times/sec. This keeps markers smooth
// during panning without hammering queryRenderedFeatures every frame.
function throttle<T extends (...args: any[]) => void>(fn: T, wait: number): T {
  let last = 0;
  let raf: ReturnType<typeof requestAnimationFrame> | null = null;
  return ((...args: any[]) => {
    const now = performance.now();
    if (now - last >= wait) {
      last = now;
      fn(...args);
    } else if (!raf) {
      // Ensure a final call fires once the throttle window expires,
      // so markers always end up correct after panning stops.
      raf = requestAnimationFrame(() => {
        raf = null;
        last = performance.now();
        fn(...args);
      });
    }
  }) as T;
}

// ─── Helpers ──────────────────────────────────────────────────────────────────

function markersToGeoJSON(markers: Marker[]): GeoJSON.FeatureCollection {
  return {
    type: 'FeatureCollection',
    features: markers.map((m) => ({
      type: 'Feature',
      geometry: {
        type: 'Point',
        coordinates: [parseFloat(m.longitude), parseFloat(m.latitude)]
      },
      properties: {
        id: m.id,
        category: m.category,
        description: m.description ?? '',
        address: m.address ?? '',
        color: getCategoryColor(m.category)
      }
    }))
  };
}

function createDotEl(color: string): HTMLElement {
  const el = document.createElement('div');
  el.style.cssText = `
    background-color:${color};border:3px solid white;border-radius:50%;
    width:24px;height:24px;box-shadow:0 2px 6px rgba(0,0,0,0.3);cursor:pointer;
  `;
  return el;
}

function createUserEl(): HTMLElement {
  const el = document.createElement('div');
  el.style.cssText = `
    width:24px;height:24px;background:#2563eb;border-radius:50%;
    display:flex;align-items:center;justify-content:center;
    box-shadow:0 2px 6px rgba(0,0,0,0.3);border:3px solid white;
  `;
  el.innerHTML = `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="white" width="14" height="14">
    <path fill-rule="evenodd" d="M7.5 6a4.5 4.5 0 1 1 9 0 4.5 4.5 0 0 1-9 0ZM3.751 20.105a8.25 8.25 0 0 1 16.498 0 .75.75 0 0 1-.437.695A18.683 18.683 0 0 1 12 22.5c-2.786 0-5.433-.608-7.812-1.7a.75.75 0 0 1-.437-.695Z" clip-rule="evenodd"/>
  </svg>`;
  return el;
}

function emitBounds() {
  if (!map) return;
  if (suppressNextBoundsEmit) {
    suppressNextBoundsEmit = false;
    return;
  }
  const b = map.getBounds();
  emit('boundsChange', {
    getCenter: () => ({ lat: b.getCenter().lat, lng: b.getCenter().lng }),
    getNorthEast: () => ({
      lat: b.getNorthEast().lat,
      lng: b.getNorthEast().lng
    })
  });
}

// ─── Cluster source + GL layers ───────────────────────────────────────────────

function addClusterLayers() {
  if (!map) return;

  map.addSource(SOURCE_ID, {
    type: 'geojson',
    data: markersToGeoJSON(props.markers),
    cluster: true,
    clusterMaxZoom: 16,
    clusterRadius: 50
  });

  // Cluster bubble
  map.addLayer({
    id: CLUSTER_LAYER_ID,
    type: 'circle',
    source: SOURCE_ID,
    filter: ['has', 'point_count'],
    paint: {
      'circle-color': [
        'step',
        ['get', 'point_count'],
        '#3b82f6',
        10,
        '#8b5cf6',
        50,
        '#ec4899'
      ],
      'circle-radius': ['step', ['get', 'point_count'], 22, 10, 30, 50, 38],
      'circle-stroke-width': 3,
      'circle-stroke-color': '#ffffff'
    }
  });

  // Count label inside cluster
  map.addLayer({
    id: CLUSTER_COUNT_LAYER_ID,
    type: 'symbol',
    source: SOURCE_ID,
    filter: ['has', 'point_count'],
    layout: {
      'text-field': '{point_count_abbreviated}',
      'text-font': ['Open Sans Bold'],
      'text-size': 13
    },
    paint: {
      'text-color': '#ffffff'
    }
  });

  // Invisible hit-test layer — radius must be > 0 so queryRenderedFeatures
  // can detect clicks on individual marker positions.
  map.addLayer({
    id: UNCLUSTERED_LAYER_ID,
    type: 'circle',
    source: SOURCE_ID,
    filter: ['!', ['has', 'point_count']],
    paint: {
      'circle-radius': 16,
      'circle-opacity': 0,
      'circle-stroke-width': 0
    }
  });

  // Expand cluster on click
  map.on('click', CLUSTER_LAYER_ID, async (e) => {
    const features = map!.queryRenderedFeatures(e.point, {
      layers: [CLUSTER_LAYER_ID]
    });
    if (!features.length) return;
    const clusterId = features[0]?.properties?.cluster_id ?? 0;
    const source = map!.getSource(SOURCE_ID) as any;
    const zoom = await source.getClusterExpansionZoom(clusterId);
    const coords = (features[0]?.geometry as GeoJSON.Point)?.coordinates as [
      number,
      number
    ];
    map!.flyTo({ center: coords, zoom, duration: 400 });
  });

  map.on('mouseenter', CLUSTER_LAYER_ID, () => {
    map!.getCanvas().style.cursor = 'pointer';
  });
  map.on('mouseleave', CLUSTER_LAYER_ID, () => {
    map!.getCanvas().style.cursor = '';
  });

  // Throttled render: runs at most every 100ms instead of every frame (~60/sec)
  map.on('render', throttledSyncHtmlMarkers);
}

// ─── HTML markers for individual (unclustered) points ────────────────────────

function syncHtmlMarkers() {
  if (!map || !maplibregl) return;

  const visibleIds = new Set<number>();

  const rendered = map.queryRenderedFeatures(undefined, {
    layers: [UNCLUSTERED_LAYER_ID]
  });

  for (const feature of rendered) {
    const id: number = feature.properties?.id;
    if (id == null) continue;
    visibleIds.add(id);

    if (!htmlMarkers.has(id)) {
      const marker = props.markers.find((m) => m.id === id);
      if (!marker) continue;

      const color = getCategoryColor(marker.category);
      const el = createDotEl(color);
      const categoryLabel = formatCategoryLabel(marker.category);
      const description = truncatePopupText(marker.description ?? '', 90);

      const locationLine = marker.address
        ? `<br><small>${escapeHtml(marker.address)}</small>`
        : '';
      const popup = new maplibregl.Popup({ offset: 16 }).setHTML(
        `<strong>${escapeHtml(categoryLabel)}</strong><div style="margin-top:4px;display:-webkit-box;-webkit-box-orient:vertical;-webkit-line-clamp:2;overflow:hidden;line-height:1.35;max-width:220px;">${escapeHtml(description)}</div>${locationLine}`
      );

      const m: MapLibreMarker = new maplibregl.Marker({ element: el })
        .setLngLat(
          (feature.geometry as GeoJSON.Point).coordinates as [number, number]
        )
        .setPopup(popup)
        .addTo(map!);

      // stopPropagation prevents the click bubbling to the map canvas
      // which would trigger mapClick → AddMarkerModal.
      el.addEventListener('click', (e) => {
        e.stopPropagation();
        emit('markerClick', marker);
      });

      htmlMarkers.set(id, m);
    }
  }

  // Remove markers that have moved into a cluster or scrolled off-screen
  for (const [id, m] of htmlMarkers) {
    if (!visibleIds.has(id)) {
      m.remove();
      htmlMarkers.delete(id);
    }
  }
}

function truncatePopupText(text: string, maxChars: number): string {
  if (text.length <= maxChars) return text;
  return `${text.slice(0, maxChars).trimEnd()}...`;
}

function escapeHtml(value: string): string {
  return value
    .replaceAll('&', '&amp;')
    .replaceAll('<', '&lt;')
    .replaceAll('>', '&gt;')
    .replaceAll('"', '&quot;')
    .replaceAll("'", '&#39;');
}

// Throttled version registered with map.on('render')
const throttledSyncHtmlMarkers = throttle(syncHtmlMarkers, 100);

// ─── Update GeoJSON data when props change ────────────────────────────────────

function updateSource() {
  if (!map) return;
  const source = map.getSource(SOURCE_ID) as any;
  if (!source) return;

  for (const m of htmlMarkers.values()) m.remove();
  htmlMarkers.clear();

  source.setData(markersToGeoJSON(props.markers));
}

// ─── Public actions ───────────────────────────────────────────────────────────

function centerOnUserLocation() {
  if (!map) return;

  if (userLocation.value) {
    map.flyTo({ center: userLocation.value, zoom: 18, duration: 400 });
    return;
  }

  void requestUserLocation({ centerMap: true, silent: false });
}

async function getLocationPermissionState(): Promise<PermissionState | null> {
  if (!('permissions' in navigator) || !navigator.permissions?.query) {
    return null;
  }

  try {
    const status = await navigator.permissions.query({
      name: 'geolocation'
    } as PermissionDescriptor);
    return status.state;
  } catch {
    return null;
  }
}

function getLocationErrorMessage(
  permissionState: PermissionState | null
): string {
  if (permissionState === 'denied') {
    return 'Location access is blocked. Enable location for this site in your browser settings.';
  }

  return 'Unable to access your location. Please allow location permission and try again.';
}

async function requestUserLocation(options?: {
  centerMap?: boolean;
  silent?: boolean;
}) {
  if (!navigator.geolocation) {
    if (!options?.silent) {
      emit('locationError', 'Geolocation is not supported by your browser.');
    }
    return;
  }

  if (!maplibregl || !map) {
    return;
  }

  const permissionState = await getLocationPermissionState();

  navigator.geolocation.getCurrentPosition(
    (pos) => {
      if (!map || !maplibregl) return;

      const coords: [number, number] = [
        pos.coords.longitude,
        pos.coords.latitude
      ];

      userLocation.value = coords;
      if (options?.centerMap) {
        map.flyTo({ center: coords, zoom: 13, duration: 400 });
      }

      userMarker?.remove();
      userMarker = new maplibregl.Marker({ element: createUserEl() })
        .setLngLat(coords)
        .addTo(map);
    },
    () => {
      if (!options?.silent) {
        emit('locationError', getLocationErrorMessage(permissionState));
      }
    },
    { enableHighAccuracy: true, timeout: 10000, maximumAge: 60000 }
  );
}

function focusOnMarker(marker: Marker) {
  if (!map) return;
  suppressNextBoundsEmit = true;
  map.flyTo({
    center: [parseFloat(marker.longitude), parseFloat(marker.latitude)],
    zoom: 17,
    duration: 400
  });
  setTimeout(() => {
    const m = htmlMarkers.get(marker.id);
    if (m) m.togglePopup();
  }, 500);
  emit('markerClick', marker);
}

// ─── Lifecycle ────────────────────────────────────────────────────────────────

onMounted(async () => {
  if (!mapContainer.value) return;

  maplibregl = (await import('maplibre-gl')).default;

  map = new maplibregl.Map({
    container: mapContainer.value,
    style: 'https://tiles.openfreemap.org/styles/bright',
    center: DEFAULT_CENTER,
    zoom: 13
  });

  if (!map) return;

  map.on('load', () => {
    addClusterLayers();
    emitBounds();
  });

  map.on('moveend', emitBounds);

  map.on('click', (e) => {
    const hit = map!.queryRenderedFeatures(e.point, {
      layers: [CLUSTER_LAYER_ID, UNCLUSTERED_LAYER_ID]
    });
    if (!hit.length) emit('mapClick', { lat: e.lngLat.lat, lng: e.lngLat.lng });
  });

  void requestUserLocation({ centerMap: true, silent: true });
});

onUnmounted(() => {
  map?.off('render', throttledSyncHtmlMarkers);
  for (const m of htmlMarkers.values()) m.remove();
  htmlMarkers.clear();
  userMarker?.remove();
  map?.remove();
  map = null;
});

watch(
  () => props.markers,
  () => {
    if (map?.loaded()) updateSource();
  },
  { deep: true }
);

watch(
  () => props.focusMarkerId,
  (id) => {
    if (id == null) return;
    const marker = props.markers.find((m) => m.id === id);
    if (marker) nextTick(() => focusOnMarker(marker));
  }
);
</script>
