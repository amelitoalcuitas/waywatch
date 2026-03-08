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
import { createReusableTemplate, useMediaQuery } from '@vueuse/core';
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

const open = computed({
  get: () => props.modelValue,
  set: (v) => emit('update:modelValue', v)
});

const {
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
} = useAddMarkerForm({
  getInitialCoordinates: () => props.initialCoordinates,
  onCreated: (marker: Marker) => emit('created', marker),
  onClose: () => emit('update:modelValue', false)
});

watch(
  () => props.modelValue,
  async (open) => {
    if (open) {
      await initializeForOpen();
    }
  }
);

onBeforeUnmount(() => {
  revokeAllImagePreviews();
});
</script>
