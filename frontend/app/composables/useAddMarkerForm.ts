import imageCompression from 'browser-image-compression';
import { reverseGeocode } from '~/composables/useGeocoding';
import type { Marker } from '~/composables/useMarkers';

type Coordinates = { lat: number; lng: number };

interface UseAddMarkerFormOptions {
  getInitialCoordinates: () => Coordinates | null | undefined;
  onCreated: (marker: Marker) => void;
  onClose: () => void;
}

export function useAddMarkerForm(options: UseAddMarkerFormOptions) {
  const { apiFetch, apiFetchForm } = useApi();
  const authStore = useAuthStore();

  const category = ref('checkpoint');
  const description = ref('');
  const position = ref<Coordinates | null>(null);
  const userGps = ref<Coordinates | null>(null);
  const address = ref<string | null>(null);
  const addressLoading = ref(false);
  const locationError = ref('');
  const submitError = ref('');
  const submitting = ref(false);
  const fileInputRef = ref<HTMLInputElement | null>(null);
  const imageFiles = ref<Array<{ file: File; preview: string }>>([]);

  const RADIUS_KM = 5;

  function onFileSelect(e: Event) {
    const input = e.target as HTMLInputElement;
    const files = input.files;
    if (!files?.length) return;

    const remaining = 6 - imageFiles.value.length;
    for (let i = 0; i < Math.min(files.length, remaining); i++) {
      const file = files[i];
      if (!file || !file.type.startsWith('image/')) continue;
      imageFiles.value.push({
        file,
        preview: URL.createObjectURL(file)
      });
    }

    input.value = '';
  }

  function removeImage(idx: number) {
    const item = imageFiles.value[idx];
    if (item) URL.revokeObjectURL(item.preview);
    imageFiles.value.splice(idx, 1);
  }

  function revokeAllImagePreviews() {
    imageFiles.value.forEach((item) => URL.revokeObjectURL(item.preview));
    imageFiles.value = [];
  }

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

  async function getLocation(): Promise<Coordinates | null> {
    return new Promise((resolve) => {
      if (!navigator.geolocation) {
        locationError.value = 'Geolocation is not supported by your browser.';
        resolve(null);
        return;
      }

      const getPermissionState = async (): Promise<PermissionState | null> => {
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
      };

      void getPermissionState().then((permissionState) => {
        navigator.geolocation.getCurrentPosition(
          (pos) => {
            resolve({ lat: pos.coords.latitude, lng: pos.coords.longitude });
          },
          (err) => {
            if (err.code === 1 || permissionState === 'denied') {
              locationError.value =
                'Location access is blocked. Enable location for this site in your browser settings to add a report.';
            } else {
              locationError.value =
                'Could not get your location. Please try again.';
            }
            resolve(null);
          },
          { enableHighAccuracy: true, timeout: 10000, maximumAge: 60000 }
        );
      });
    });
  }

  async function fetchAddress(lat: number, lng: number) {
    address.value = null;
    addressLoading.value = true;
    try {
      const result = await reverseGeocode(lat, lng);
      address.value = result;
    } finally {
      addressLoading.value = false;
    }
  }

  async function initializeForOpen() {
    position.value = null;
    userGps.value = null;
    address.value = null;
    addressLoading.value = false;
    locationError.value = '';
    submitError.value = '';
    revokeAllImagePreviews();

    const gps = await getLocation();
    userGps.value = gps;

    const initialCoordinates = options.getInitialCoordinates();
    if (initialCoordinates) {
      position.value = initialCoordinates;

      if (!gps) {
        locationError.value =
          'Location access required to verify the marker is within range.';
        return;
      }

      const dist = haversineDistanceKm(
        initialCoordinates.lat,
        initialCoordinates.lng,
        gps.lat,
        gps.lng
      );
      if (dist > RADIUS_KM) {
        locationError.value = `Marker must be within ${RADIUS_KM} km of your current location. (${dist.toFixed(1)} km away)`;
        return;
      }

      await fetchAddress(initialCoordinates.lat, initialCoordinates.lng);
      return;
    }

    position.value = gps;
    if (gps) {
      await fetchAddress(gps.lat, gps.lng);
    }
  }

  async function onSubmit() {
    if (!position.value || !userGps.value || !authStore.token) return;

    submitError.value = '';
    submitting.value = true;

    try {
      const imagePaths: string[] = [];
      for (const item of imageFiles.value) {
        const compressed = await imageCompression(item.file, {
          maxSizeMB: 0.5,
          maxWidthOrHeight: 1024,
          useWebWorker: true
        });

        const formData = new FormData();
        formData.append('image', compressed);

        const res = await apiFetchForm<{ path: string; url: string }>(
          '/markers/images/upload',
          formData
        );
        imagePaths.push(res.path);
      }

      const body: Record<string, unknown> = {
        latitude: position.value.lat,
        longitude: position.value.lng,
        user_latitude: userGps.value.lat,
        user_longitude: userGps.value.lng,
        address: address.value || undefined,
        category: category.value,
        description: description.value.trim()
      };

      if (imagePaths.length > 0) {
        body.images = imagePaths;
      }

      const res = await apiFetch<{ data: Marker }>('/markers', {
        method: 'POST',
        body
      });

      options.onCreated(res.data);
      category.value = 'road_repair';
      description.value = '';
      revokeAllImagePreviews();
      options.onClose();
    } catch (e: any) {
      const data = e?.data;
      if (data?.errors) {
        const firstError = Object.values(data.errors).flat()[0];
        submitError.value =
          typeof firstError === 'string' ? firstError : 'Validation failed.';
      } else if (data?.message) {
        submitError.value = data.message;
      } else if (e?.statusCode === 401) {
        submitError.value = 'Session expired. Please log in again.';
      } else {
        submitError.value = 'Failed to submit report. Please try again.';
      }
    } finally {
      submitting.value = false;
    }
  }

  return {
    category,
    description,
    position,
    userGps,
    address,
    addressLoading,
    locationError,
    submitError,
    submitting,
    fileInputRef,
    imageFiles,
    onFileSelect,
    removeImage,
    onSubmit,
    initializeForOpen,
    revokeAllImagePreviews
  };
}
