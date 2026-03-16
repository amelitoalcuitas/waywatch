import { defineStore } from 'pinia';
import { boundsToParams, fetchMarkers } from '~/composables/useMarkers';
import type { BoundsParams, Marker } from '~/composables/useMarkers';

type BoundsLike = {
  getCenter: () => { lat: number; lng: number };
  getNorthEast: () => { lat: number; lng: number };
};

let debounceTimer: ReturnType<typeof setTimeout> | null = null;

function haversineKm(
  lat1: number,
  lng1: number,
  lat2: number,
  lng2: number
): number {
  const R = 6371;
  const dLat = ((lat2 - lat1) * Math.PI) / 180;
  const dLng = ((lng2 - lng1) * Math.PI) / 180;
  const a =
    Math.sin(dLat / 2) ** 2 +
    Math.cos((lat1 * Math.PI) / 180) *
      Math.cos((lat2 * Math.PI) / 180) *
      Math.sin(dLng / 2) ** 2;
  return R * 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
}

export const useMarkersStore = defineStore('markers', {
  state: () => ({
    selectedCategory: 'all',
    selectedDate: '',
    boundsParams: null as BoundsParams | null,

    // Markers for the current viewport — drives the sidebar list
    markers: [] as Marker[],

    // Accumulated cache of every marker fetched since page load — drives the map
    allMarkers: [] as Marker[],

    // pending = true only on the very first load (list is empty)
    pending: false,
    // refreshing = true on subsequent fetches (list stays visible, subtle indicator)
    refreshing: false,

    // Center of the last completed fetch — used to decide if we need to re-fetch
    lastFetchedCenter: null as { lat: number; lng: number } | null,
    // Radius of the last fetch in km
    lastFetchedRadius: 0
  }),

  actions: {
    setCategory(value: string) {
      this.selectedCategory = value;
      // Category/date filter changes always re-fetch immediately
      this.lastFetchedCenter = null;
      this.debouncedFetch();
    },

    setDate(value: string) {
      this.selectedDate = value;
      this.lastFetchedCenter = null;
      this.debouncedFetch();
    },

    setBounds(bounds: BoundsLike) {
      const params = boundsToParams(bounds);
      this.boundsParams = params;

      // Skip fetch if the new center is still well within the last fetched area.
      // We use 60% of the last fetch radius as the tolerance — meaning we only
      // re-fetch once the user has panned more than 60% of the way to the edge
      // of what was already loaded.
      if (this.lastFetchedCenter) {
        const dist = haversineKm(
          this.lastFetchedCenter.lat,
          this.lastFetchedCenter.lng,
          params.latitude,
          params.longitude
        );
        const tolerance = this.lastFetchedRadius * 0.6;
        if (dist < tolerance) return; // still comfortably inside — skip
      }

      this.debouncedFetch();
    },

    debouncedFetch() {
      if (debounceTimer) clearTimeout(debounceTimer);
      debounceTimer = setTimeout(() => this.doFetch(), 300);
    },

    async doFetch() {
      if (!this.boundsParams) return;

      // First load: show full loading state. Subsequent loads: subtle refresh.
      if (this.markers.length === 0) {
        this.pending = true;
      } else {
        this.refreshing = true;
      }

      try {
        const fresh = await fetchMarkers(
          this.boundsParams,
          this.selectedDate,
          this.selectedCategory
        );

        // Update sidebar list to reflect current viewport/filters
        this.markers = fresh;

        // Merge into the accumulated map cache — never remove, only add
        const existingIds = new Set(this.allMarkers.map((m) => m.id));
        for (const m of fresh) {
          if (!existingIds.has(m.id)) {
            this.allMarkers.push(m);
          }
        }

        // Record where we fetched so setBounds can compare next time
        this.lastFetchedCenter = {
          lat: this.boundsParams.latitude,
          lng: this.boundsParams.longitude
        };
        this.lastFetchedRadius = this.boundsParams.radius;
      } finally {
        this.pending = false;
        this.refreshing = false;
      }
    }
  }
});
