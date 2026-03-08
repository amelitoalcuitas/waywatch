<template>
  <DefineFormTemplate>
    <form class="space-y-4" @submit.prevent="onSubmit">
      <UAlert
        v-if="locationError"
        color="error"
        variant="soft"
        :description="locationError"
      />

      <div v-if="!locationError && position">
        <p class="text-xs text-muted">
          Location:
          {{
            address ||
            (addressLoading
              ? 'Looking up address...'
              : `${position.lat.toFixed(5)}, ${position.lng.toFixed(5)}`)
          }}
        </p>
      </div>

      <UFormField label="Category" name="category" required>
        <USelect
          v-model="category"
          :items="categoryOptions"
          placeholder="Select category"
          class="w-full"
        />
      </UFormField>

      <UFormField
        label="Description"
        name="description"
        required
        :help="`${description.length} / 2000`"
      >
        <UTextarea
          v-model="description"
          required
          :maxlength="2000"
          :rows="4"
          placeholder="Describe the road condition or situation..."
          class="w-full"
        />
      </UFormField>

      <UFormField
        label="Images"
        name="images"
        :help="`${imageFiles.length} / 6`"
      >
        <div class="space-y-2">
          <input
            ref="fileInputRef"
            type="file"
            accept="image/jpeg,image/jpg,image/png,image/webp"
            multiple
            class="hidden"
            @change="onFileSelect"
          />
          <UButton
            type="button"
            variant="outline"
            size="sm"
            :disabled="imageFiles.length >= 6"
            @click="fileInputRef?.click()"
          >
            Add photos (max 6)
          </UButton>
          <div v-if="imageFiles.length > 0" class="flex flex-wrap gap-2">
            <div v-for="(item, idx) in imageFiles" :key="idx" class="relative">
              <img
                :src="item.preview"
                alt="Preview"
                class="h-16 w-16 rounded object-cover"
              />
              <button
                type="button"
                class="absolute -right-1 -top-1 flex h-5 w-5 items-center justify-center rounded-full bg-red-500 text-white"
                @click="removeImage(idx)"
              >
                ×
              </button>
            </div>
          </div>
        </div>
      </UFormField>

      <UAlert
        v-if="submitError"
        color="error"
        variant="soft"
        :description="submitError"
      />

      <UButton
        type="submit"
        block
        :loading="submitting"
        :disabled="!position || !userGps || !!locationError"
      >
        {{ submitting ? 'Submitting...' : 'Submit Report' }}
      </UButton>
    </form>
  </DefineFormTemplate>

  <UModal
    v-if="isDesktop"
    v-model:open="open"
    title="Add Report"
    :ui="{ footer: 'justify-stretch' }"
  >
    <div class="hidden" />
    <template #body>
      <ReuseFormTemplate />
    </template>
  </UModal>

  <UDrawer
    v-else
    v-model:open="open"
    title="Add Report"
    :ui="{ footer: 'justify-stretch' }"
  >
    <div class="hidden" />
    <template #body>
      <ReuseFormTemplate />
    </template>
  </UDrawer>
</template>

<script setup lang="ts">
import imageCompression from 'browser-image-compression';
import { createReusableTemplate, useMediaQuery } from '@vueuse/core';
import { reverseGeocode } from '~/composables/useGeocoding';
import { CATEGORY_OPTIONS } from '~/composables/useMarkers';
import type { Marker } from '~/composables/useMarkers';

const [DefineFormTemplate, ReuseFormTemplate] = createReusableTemplate();
const isDesktop = useMediaQuery('(min-width: 768px)');

const categoryOptions = CATEGORY_OPTIONS.filter((o) => o.value !== 'all');

const props = defineProps<{
  modelValue: boolean;
  initialCoordinates?: { lat: number; lng: number } | null;
}>();

const emit = defineEmits<{
  'update:modelValue': [value: boolean];
  'created': [marker: Marker];
}>();

const { apiFetch, apiFetchForm } = useApi();
const authStore = useAuthStore();

const open = computed({
  get: () => props.modelValue,
  set: (v) => emit('update:modelValue', v)
});

const RADIUS_KM = 5;

const category = ref('checkpoint');
const description = ref('');
const position = ref<{ lat: number; lng: number } | null>(null);
const userGps = ref<{ lat: number; lng: number } | null>(null);
const address = ref<string | null>(null);
const addressLoading = ref(false);
const locationError = ref('');
const submitError = ref('');
const submitting = ref(false);
const fileInputRef = ref<HTMLInputElement | null>(null);
const imageFiles = ref<Array<{ file: File; preview: string }>>([]);

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

function close() {
  emit('update:modelValue', false);
}

async function getLocation(): Promise<{ lat: number; lng: number } | null> {
  return new Promise((resolve) => {
    if (!navigator.geolocation) {
      locationError.value = 'Geolocation is not supported by your browser.';
      resolve(null);
      return;
    }
    navigator.geolocation.getCurrentPosition(
      (pos) => {
        resolve({ lat: pos.coords.latitude, lng: pos.coords.longitude });
      },
      (err) => {
        if (err.code === 1) {
          locationError.value =
            'Location access denied. Please enable location to add a report.';
        } else {
          locationError.value =
            'Could not get your location. Please try again.';
        }
        resolve(null);
      },
      { enableHighAccuracy: true, timeout: 10000, maximumAge: 60000 }
    );
  });
}

async function onSubmit() {
  if (!position.value || !userGps.value || !authStore.token) return;
  submitError.value = '';
  submitting.value = true;
  try {
    const imagePaths: string[] = [];
    for (const item of imageFiles.value) {
      const compressed = await imageCompression(item.file, {
        maxSizeMB: 1,
        maxWidthOrHeight: 1200,
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
    emit('created', res.data);
    category.value = 'road_repair';
    description.value = '';
    imageFiles.value.forEach((i) => URL.revokeObjectURL(i.preview));
    imageFiles.value = [];
    close();
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

watch(
  () => props.modelValue,
  async (open) => {
    if (open) {
      position.value = null;
      userGps.value = null;
      address.value = null;
      addressLoading.value = false;
      locationError.value = '';
      submitError.value = '';
      imageFiles.value.forEach((i) => URL.revokeObjectURL(i.preview));
      imageFiles.value = [];
      const gps = await getLocation();
      userGps.value = gps;
      if (props.initialCoordinates) {
        position.value = props.initialCoordinates;
        if (gps) {
          const dist = haversineDistanceKm(
            props.initialCoordinates.lat,
            props.initialCoordinates.lng,
            gps.lat,
            gps.lng
          );
          if (dist > RADIUS_KM) {
            locationError.value = `Marker must be within ${RADIUS_KM} km of your current location. (${dist.toFixed(1)} km away)`;
          } else {
            await fetchAddress(
              props.initialCoordinates.lat,
              props.initialCoordinates.lng
            );
          }
        } else {
          locationError.value =
            'Location access required to verify the marker is within range.';
        }
      } else {
        position.value = gps;
        if (gps) {
          await fetchAddress(gps.lat, gps.lng);
        }
      }
    }
  }
);
</script>
