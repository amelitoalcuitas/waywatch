<template>
  <UModal
    v-model:open="open"
    fullscreen
    :ui="{ content: 'flex items-center justify-center bg-black/95' }"
  >
    <template #content>
      <div class="relative flex h-full w-full items-center justify-center p-4">
        <button
          type="button"
          class="absolute right-4 top-4 z-10 rounded-full bg-white/10 p-2 text-white transition hover:bg-white/20"
          aria-label="Close"
          @click="close"
        >
          <svg
            xmlns="http://www.w3.org/2000/svg"
            viewBox="0 0 24 24"
            fill="currentColor"
            class="h-6 w-6"
          >
            <path
              fill-rule="evenodd"
              d="M5.47 5.47a.75.75 0 011.06 0L12 10.94l5.47-5.47a.75.75 0 111.06 1.06L13.06 12l5.47 5.47a.75.75 0 11-1.06 1.06L12 13.06l-5.47 5.47a.75.75 0 01-1.06-1.06L10.94 12 5.47 6.53z"
              clip-rule="evenodd"
            />
          </svg>
        </button>

        <img
          v-if="currentImageUrl"
          :src="currentImageUrl"
          alt="Full size"
          class="max-h-full max-w-full object-contain"
          @click="close"
        />

        <button
          v-if="hasPrev"
          type="button"
          class="absolute left-4 top-1/2 z-10 -translate-y-1/2 rounded-full bg-white/10 p-2 text-white transition hover:bg-white/20"
          aria-label="Previous"
          @click="prev"
        >
          <svg
            xmlns="http://www.w3.org/2000/svg"
            viewBox="0 0 24 24"
            fill="currentColor"
            class="h-8 w-8"
          >
            <path
              fill-rule="evenodd"
              d="M7.72 12.53a.75.75 0 010-1.06l7.5-7.5a.75.75 0 111.06 1.06L9.31 12l6.97 6.97a.75.75 0 11-1.06 1.06l-7.5-7.5z"
              clip-rule="evenodd"
            />
          </svg>
        </button>

        <button
          v-if="hasNext"
          type="button"
          class="absolute right-4 top-1/2 z-10 -translate-y-1/2 rounded-full bg-white/10 p-2 text-white transition hover:bg-white/20"
          aria-label="Next"
          @click="next"
        >
          <svg
            xmlns="http://www.w3.org/2000/svg"
            viewBox="0 0 24 24"
            fill="currentColor"
            class="h-8 w-8"
          >
            <path
              fill-rule="evenodd"
              d="M16.28 11.47a.75.75 0 010 1.06l-7.5 7.5a.75.75 0 01-1.06-1.06L14.69 12 7.72 5.03a.75.75 0 011.06-1.06l7.5 7.5z"
              clip-rule="evenodd"
            />
          </svg>
        </button>

        <p
          v-if="images.length > 1"
          class="absolute bottom-4 left-1/2 -translate-x-1/2 text-sm text-white/80"
        >
          {{ currentIndex + 1 }} / {{ images.length }}
        </p>
      </div>
    </template>
  </UModal>
</template>

<script setup lang="ts">
const props = defineProps<{
  modelValue: boolean;
  imageUrl?: string;
  images?: string[];
  initialIndex?: number;
}>();

const emit = defineEmits<{
  'update:modelValue': [value: boolean];
}>();

const open = computed({
  get: () => props.modelValue,
  set: (v) => emit('update:modelValue', v)
});

const images = computed(
  () => props.images ?? (props.imageUrl ? [props.imageUrl] : [])
);
const currentIndex = ref(props.initialIndex ?? 0);

const currentImageUrl = computed(() => images.value[currentIndex.value] ?? '');

const hasPrev = computed(
  () => images.value.length > 1 && currentIndex.value > 0
);
const hasNext = computed(
  () => images.value.length > 1 && currentIndex.value < images.value.length - 1
);

function close() {
  emit('update:modelValue', false);
}

function prev() {
  if (hasPrev.value) currentIndex.value--;
}

function next() {
  if (hasNext.value) currentIndex.value++;
}

watch(
  () => props.modelValue,
  (open) => {
    if (open) {
      const idx = props.initialIndex ?? 0;
      currentIndex.value = Math.min(idx, Math.max(0, images.value.length - 1));
    }
  }
);

watch(
  () => props.initialIndex,
  (idx) => {
    if (idx != null && props.modelValue) {
      currentIndex.value = Math.min(idx, Math.max(0, images.value.length - 1));
    }
  }
);
</script>
