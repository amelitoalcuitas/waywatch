import { defineStore } from 'pinia'
import { boundsToParams, fetchMarkers, formatDateForInput } from '~/composables/useMarkers'
import type { BoundsParams, Marker } from '~/composables/useMarkers'

type BoundsLike = {
  getCenter: () => { lat: number; lng: number }
  getNorthEast: () => { lat: number; lng: number }
}

let debounceTimer: ReturnType<typeof setTimeout> | null = null

export const useMarkersStore = defineStore('markers', {
  state: () => ({
    selectedCategory: '',
    selectedDate: formatDateForInput(new Date()),
    boundsParams: null as BoundsParams | null,
    markers: [] as Marker[],
    pending: false,
  }),

  actions: {
    setCategory(value: string) {
      this.selectedCategory = value
      this.debouncedFetch()
    },

    setDate(value: string) {
      this.selectedDate = value
      this.debouncedFetch()
    },

    setBounds(bounds: BoundsLike) {
      this.boundsParams = boundsToParams(bounds)
      this.debouncedFetch()
    },

    debouncedFetch() {
      if (debounceTimer) clearTimeout(debounceTimer)
      debounceTimer = setTimeout(() => this.doFetch(), 300)
    },

    async doFetch() {
      if (!this.boundsParams) return
      this.pending = true
      try {
        this.markers = await fetchMarkers(
          this.boundsParams,
          this.selectedDate,
          this.selectedCategory
        )
      } finally {
        this.pending = false
      }
    },
  },
})
