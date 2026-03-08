<template>
  <UModal
    v-if="isDesktop"
    v-model:open="open"
    :ui="{ footer: 'justify-stretch' }"
  >
    <template #header>
      <div class="flex w-full items-center justify-between gap-3">
        <span class="text-base font-semibold text-gray-900"
          >Report Details</span
        >
        <span v-if="marker" class="text-xs text-gray-500">
          {{ marker.likes }} still there · {{ marker.dislikes }} not there
        </span>
      </div>
    </template>

    <template #body>
      <div v-if="marker" class="space-y-4">
        <div>
          <p class="text-xs font-medium text-gray-500">Category</p>
          <span
            class="inline-flex mt-1 rounded px-2 py-0.5 text-sm font-medium text-white"
            :style="{ backgroundColor: getCategoryColor(marker.category) }"
          >
            {{ formatCategory(marker.category) }}
          </span>
        </div>

        <div>
          <p class="text-xs font-medium text-gray-500">Description</p>
          <p class="mt-1 text-sm text-gray-900 whitespace-pre-wrap">
            {{ marker.description }}
          </p>
        </div>

        <div>
          <p class="text-xs font-medium text-gray-500">Location</p>
          <p class="mt-1 text-sm text-gray-900">
            {{
              marker.address || formatCoords(marker.latitude, marker.longitude)
            }}
          </p>
        </div>

        <div>
          <p class="text-xs font-medium text-gray-500">Added by</p>
          <p class="mt-1 text-sm text-gray-900">{{ marker.user?.name }}</p>
        </div>

        <div>
          <p class="text-xs font-medium text-gray-500">Posted</p>
          <p class="mt-1 text-sm text-gray-900">
            {{ formatDate(marker.created_at) }}
          </p>
        </div>

        <div class="space-y-2">
          <p class="text-xs font-medium text-gray-500">
            Is this report still there?
          </p>
          <div class="flex gap-2">
            <UButton
              color="success"
              :variant="voteVariant('still_there')"
              icon="i-lucide-thumbs-up"
              :loading="voting === 'still_there'"
              :disabled="!!voting"
              :class="{
                'ring-2 ring-green-500': localVoteType === 'still_there'
              }"
              @click="onVote('still_there')"
            >
              {{ voteLabel('still_there') }}
            </UButton>
            <UButton
              color="error"
              :variant="voteVariant('not_there')"
              icon="i-lucide-thumbs-down"
              :loading="voting === 'not_there'"
              :disabled="!!voting"
              :class="{ 'ring-2 ring-red-500': localVoteType === 'not_there' }"
              @click="onVote('not_there')"
            >
              {{ voteLabel('not_there') }}
            </UButton>
          </div>
        </div>

        <div v-if="canReportMarker || canDeleteMarker" class="space-y-2">
          <p class="text-xs font-medium text-gray-500">Manage marker</p>
          <UButton
            v-if="canReportMarker"
            color="warning"
            variant="soft"
            icon="i-lucide-flag"
            :disabled="deleting || reporting"
            @click="openReportDialog"
          >
            Report marker
          </UButton>
          <UButton
            v-if="canDeleteMarker"
            color="error"
            variant="soft"
            icon="i-lucide-trash-2"
            :disabled="deleting"
            @click="openDeleteDialog"
          >
            Delete report
          </UButton>
        </div>

        <div v-if="marker.images?.length" class="space-y-2">
          <p class="text-xs font-medium text-gray-500">Photos</p>
          <div class="flex flex-wrap gap-2">
            <button
              v-for="(img, idx) in marker.images"
              :key="img.id"
              type="button"
              class="block overflow-hidden rounded-lg ring-2 ring-transparent transition hover:ring-gray-300 focus:outline-none focus:ring-2 focus:ring-primary-500"
              @click="openImageViewer(img.image_url, idx)"
            >
              <img
                :src="img.image_url"
                :alt="`Photo ${idx + 1}`"
                class="h-20 w-20 object-cover"
              />
            </button>
          </div>
        </div>
      </div>
    </template>
  </UModal>

  <UDrawer v-else v-model:open="open" :ui="{ footer: 'justify-stretch' }">
    <template #header>
      <div class="flex w-full items-center justify-between gap-3">
        <span class="text-base font-semibold text-gray-900"
          >Report Details</span
        >
        <span v-if="marker" class="text-xs text-gray-500">
          {{ marker.likes }} still there · {{ marker.dislikes }} not there
        </span>
      </div>
    </template>

    <template #body>
      <div v-if="marker" class="space-y-4">
        <div>
          <p class="text-xs font-medium text-gray-500">Category</p>
          <span
            class="inline-flex mt-1 rounded px-2 py-0.5 text-sm font-medium text-white"
            :style="{ backgroundColor: getCategoryColor(marker.category) }"
          >
            {{ formatCategory(marker.category) }}
          </span>
        </div>

        <div>
          <p class="text-xs font-medium text-gray-500">Description</p>
          <p class="mt-1 text-sm text-gray-900 whitespace-pre-wrap">
            {{ marker.description }}
          </p>
        </div>

        <div>
          <p class="text-xs font-medium text-gray-500">Location</p>
          <p class="mt-1 text-sm text-gray-900">
            {{
              marker.address || formatCoords(marker.latitude, marker.longitude)
            }}
          </p>
        </div>

        <div v-if="marker.images?.length" class="space-y-2">
          <p class="text-xs font-medium text-gray-500">Photos</p>
          <div class="flex flex-wrap gap-2">
            <button
              v-for="(img, idx) in marker.images"
              :key="img.id"
              type="button"
              class="block overflow-hidden rounded-lg ring-2 ring-transparent transition hover:ring-gray-300 focus:outline-none focus:ring-2 focus:ring-primary-500"
              @click="openImageViewer(img.image_url, idx)"
            >
              <img
                :src="img.image_url"
                :alt="`Photo ${idx + 1}`"
                class="h-20 w-20 object-cover"
              />
            </button>
          </div>
        </div>

        <div>
          <p class="text-xs font-medium text-gray-500">Added by</p>
          <p class="mt-1 text-sm text-gray-900">{{ marker.user?.name }}</p>
        </div>

        <div>
          <p class="text-xs font-medium text-gray-500">Posted</p>
          <p class="mt-1 text-sm text-gray-900">
            {{ formatDate(marker.created_at) }}
          </p>
        </div>

        <div class="space-y-2">
          <p class="text-xs font-medium text-gray-500">
            Is this report still there?
          </p>
          <div class="flex gap-2">
            <UButton
              color="success"
              :variant="voteVariant('still_there')"
              icon="i-lucide-thumbs-up"
              :loading="voting === 'still_there'"
              :disabled="!!voting"
              :class="{
                'ring-2 ring-green-500': localVoteType === 'still_there'
              }"
              @click="onVote('still_there')"
            >
              {{ voteLabel('still_there') }}
            </UButton>
            <UButton
              color="error"
              :variant="voteVariant('not_there')"
              icon="i-lucide-thumbs-down"
              :loading="voting === 'not_there'"
              :disabled="!!voting"
              :class="{ 'ring-2 ring-red-500': localVoteType === 'not_there' }"
              @click="onVote('not_there')"
            >
              {{ voteLabel('not_there') }}
            </UButton>
          </div>
        </div>

        <div v-if="canReportMarker || canDeleteMarker" class="space-y-2">
          <p class="text-xs font-medium text-gray-500">Manage Marker</p>
          <div class="flex gap-2">
            <UButton
              v-if="canReportMarker"
              color="warning"
              variant="soft"
              icon="i-lucide-flag"
              :disabled="deleting || reporting"
              @click="openReportDialog"
            >
              Report
            </UButton>
            <UButton
              v-if="canDeleteMarker"
              color="error"
              variant="soft"
              icon="i-lucide-trash-2"
              :disabled="deleting"
              @click="openDeleteDialog"
            >
            </UButton>
          </div>
        </div>
      </div>
    </template>
  </UDrawer>

  <DialogModal
    v-model:open="isReportDialogOpen"
    title="Report Marker"
    description="Help us moderate by telling us why this marker should be reviewed."
    confirm-label="Submit report"
    cancel-label="Cancel"
    @confirm="submitReport"
    @cancel="closeReportDialog"
  >
    <template #body>
      <div class="space-y-3">
        <div>
          <p class="mb-1 text-sm font-medium text-gray-700">Reason</p>
          <USelect
            v-model="selectedReportReason"
            :items="reportReasonOptions"
            placeholder="Select a reason"
            class="w-full"
          />
        </div>
        <div>
          <p class="mb-1 text-sm font-medium text-gray-700">
            Additional details{{ isOtherReasonSelected ? '' : ' (optional)' }}
          </p>
          <UTextarea
            v-model="reportDetails"
            :rows="4"
            :placeholder="
              isOtherReasonSelected
                ? 'Please provide details for the Other reason.'
                : 'Add context to help moderators review this marker.'
            "
            class="w-full"
          />
        </div>
      </div>
    </template>
  </DialogModal>

  <DialogModal
    v-model:open="isDeleteDialogOpen"
    title="Delete Marker"
    description="This action permanently deletes this report and cannot be undone."
    confirm-label="Delete"
    cancel-label="Cancel"
    @confirm="confirmDeleteMarker"
    @cancel="closeDeleteDialog"
  />

  <ImageViewerModal
    v-model="imageViewerOpen"
    :image-url="selectedImageUrl"
    :images="marker?.images?.map((i) => i.image_url) ?? []"
    :initial-index="selectedImageIndex"
  />
</template>

<script setup lang="ts">
import { useMediaQuery } from '@vueuse/core';
import type { Marker } from '~/composables/useMarkers';
import { CATEGORY_OPTIONS, getCategoryColor } from '~/composables/useMarkers';
import { useMarkerDelete } from '~/composables/useMarkerDelete';
import { useMarkerReport } from '~/composables/useMarkerReport';
import type { VoteType } from '~/composables/useMarkerVote';

const isDesktop = useMediaQuery('(min-width: 768px)');
const { vote } = useMarkerVote();
const { deleteMarker } = useMarkerDelete();
const { reportMarker } = useMarkerReport();
const authStore = useAuthStore();
const toast = useToast();

const props = defineProps<{
  modelValue: boolean;
  marker: Marker | null;
}>();

const emit = defineEmits<{
  'update:modelValue': [value: boolean];
  'marker-updated': [marker: Marker];
  'marker-deleted': [markerId: number];
}>();

const open = computed({
  get: () => props.modelValue,
  set: (v) => emit('update:modelValue', v)
});

const imageViewerOpen = ref(false);
const selectedImageUrl = ref('');
const selectedImageIndex = ref(0);
const voting = ref<VoteType | null>(null);
const localVoteType = ref<VoteType | null>(null);
const deleting = ref(false);
const reporting = ref(false);
const isDeleteDialogOpen = ref(false);
const isReportDialogOpen = ref(false);
const selectedReportReason = ref('');
const reportDetails = ref('');

const reportReasonOptions = [
  { label: 'Spam or fake report', value: 'spam' },
  { label: 'Inaccurate information', value: 'inaccurate' },
  { label: 'Offensive content', value: 'offensive' },
  { label: 'Other', value: 'other' }
];

const canDeleteMarker = computed(() => {
  if (!props.marker || !authStore.user) return false;
  return props.marker.user?.id === authStore.user.id || authStore.user.is_admin;
});

const canReportMarker = computed(() => {
  return !!props.marker && authStore.isAuthenticated;
});

const isOtherReasonSelected = computed(() => {
  return selectedReportReason.value === 'other';
});

watch(
  () => props.marker,
  (marker) => {
    localVoteType.value = marker?.user_vote_type ?? null;
  },
  { immediate: true }
);

function formatCategory(cat: string): string {
  return CATEGORY_OPTIONS.find((o) => o.value === cat)?.label ?? cat;
}

function formatCoords(lat: string, lng: string): string {
  return `${parseFloat(lat).toFixed(4)}, ${parseFloat(lng).toFixed(4)}`;
}

function formatDate(iso: string): string {
  try {
    const d = new Date(iso);
    return d.toLocaleDateString(undefined, {
      year: 'numeric',
      month: 'short',
      day: 'numeric',
      hour: '2-digit',
      minute: '2-digit'
    });
  } catch {
    return iso;
  }
}

function openImageViewer(url: string, index: number) {
  selectedImageUrl.value = url;
  selectedImageIndex.value = index;
  imageViewerOpen.value = true;
}

function voteVariant(voteType: VoteType): 'soft' | 'solid' {
  return localVoteType.value === voteType ? 'solid' : 'soft';
}

function voteLabel(voteType: VoteType): string {
  if (voteType === 'still_there') {
    return localVoteType.value === 'still_there'
      ? 'Marked still there'
      : 'Still there';
  }

  return localVoteType.value === 'not_there' ? 'Marked not there' : 'Not there';
}

async function onVote(voteType: VoteType) {
  if (!props.marker) return;

  if (!authStore.isAuthenticated) {
    toast.add({
      title: 'Login required',
      description: 'Please log in to vote on reports.',
      color: 'warning'
    });
    return;
  }

  voting.value = voteType;

  try {
    const response = await vote(props.marker.id, voteType);
    localVoteType.value = response.user_vote_type;
    const updatedMarker: Marker = {
      ...response.data,
      user_vote_type: response.user_vote_type
    };
    emit('marker-updated', updatedMarker);
  } catch (e: any) {
    const message =
      e?.data?.message ?? 'Unable to record vote. Please try again.';
    toast.add({ title: 'Vote failed', description: message, color: 'error' });
  } finally {
    voting.value = null;
  }
}

function openDeleteDialog() {
  if (!canDeleteMarker.value || deleting.value) return;
  isDeleteDialogOpen.value = true;
}

function closeDeleteDialog() {
  isDeleteDialogOpen.value = false;
}

function openReportDialog() {
  if (!canReportMarker.value || reporting.value) return;
  isReportDialogOpen.value = true;
}

function closeReportDialog() {
  isReportDialogOpen.value = false;
  selectedReportReason.value = '';
  reportDetails.value = '';
}

async function submitReport() {
  if (!props.marker || reporting.value || !canReportMarker.value) return;

  if (!selectedReportReason.value) {
    toast.add({
      title: 'Reason required',
      description: 'Please select a reason before submitting your report.',
      color: 'warning'
    });
    return;
  }

  if (isOtherReasonSelected.value && !reportDetails.value.trim()) {
    toast.add({
      title: 'Details required',
      description: 'Please add additional details when selecting Other.',
      color: 'warning'
    });
    return;
  }

  reporting.value = true;
  try {
    await reportMarker(props.marker.id, {
      reason: selectedReportReason.value,
      details: reportDetails.value.trim() || undefined
    });
    toast.add({
      title: 'Report submitted',
      description: 'Thank you. This marker has been flagged for review.',
      color: 'success'
    });
    closeReportDialog();
  } catch (e: any) {
    const message =
      e?.data?.message ?? 'Unable to submit report. Please try again.';
    toast.add({ title: 'Report failed', description: message, color: 'error' });
  } finally {
    reporting.value = false;
  }
}

async function confirmDeleteMarker() {
  if (!props.marker || deleting.value || !canDeleteMarker.value) return;

  deleting.value = true;
  try {
    await deleteMarker(props.marker.id);
    toast.add({
      title: 'Report deleted',
      description: 'The report has been removed.',
      color: 'success'
    });
    closeDeleteDialog();
    emit('marker-deleted', props.marker.id);
    open.value = false;
  } catch (e: any) {
    const message =
      e?.data?.message ?? 'Failed to delete report. Please try again.';
    toast.add({
      title: 'Delete failed',
      description: message,
      color: 'error'
    });
  } finally {
    deleting.value = false;
  }
}
</script>
