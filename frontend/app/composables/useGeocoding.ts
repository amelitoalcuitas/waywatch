/**
 * Reverse geocoding via Nominatim (OpenStreetMap).
 * Usage policy: https://operations.osmfoundation.org/policies/nominatim/
 * - Max 1 request per second
 * - Provide a descriptive User-Agent
 */

interface NominatimAddress {
  road?: string
  street?: string
  suburb?: string
  neighbourhood?: string
  village?: string
  city?: string
  municipality?: string
  state?: string
  country?: string
  display_name?: string
}

interface NominatimResponse {
  address?: NominatimAddress
  display_name?: string
}

/**
 * Reverse geocode lat/lng to a human-readable address string.
 * Returns street-level address when available, or a fallback.
 */
export async function reverseGeocode(lat: number, lng: number): Promise<string | null> {
  try {
    const res = await $fetch<NominatimResponse>(
      'https://nominatim.openstreetmap.org/reverse',
      {
        params: {
          format: 'json',
          lat,
          lon: lng,
          zoom: 18, // Street level
        },
        headers: {
          'User-Agent': 'Waywatch/1.0 (https://github.com/waywatch)',
        },
      }
    )

    const addr = res?.address
    if (!addr) return res?.display_name ?? null

    // Prefer street-level: road or street
    const street = addr.road ?? addr.street
    const locality =
      addr.suburb ?? addr.neighbourhood ?? addr.village ?? addr.city ?? addr.municipality

    if (street && locality) {
      return `${street}, ${locality}`
    }
    if (street) return street
    if (locality) return locality

    return addr.display_name ?? res?.display_name ?? null
  } catch {
    return null
  }
}
