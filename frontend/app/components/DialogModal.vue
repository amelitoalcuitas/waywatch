<template>
  <UModal v-model:open="internalOpen">
    <template #header>
      <slot name="header">
        <div class="text-base font-semibold">
          {{ title }}
        </div>
      </slot>
    </template>

    <template #body>
      <slot name="body">
        <p class="text-sm text-muted">
          {{ description }}
        </p>
      </slot>
    </template>

    <template #footer>
      <slot name="footer">
        <div class="flex justify-end gap-2 w-full">
          <UButton
            v-if="showCancel"
            variant="ghost"
            color="neutral"
            @click="handleCancel"
          >
            {{ cancelLabel }}
          </UButton>
          <UButton color="error" @click="handleConfirm">
            {{ confirmLabel }}
          </UButton>
        </div>
      </slot>
    </template>
  </UModal>
</template>

<script setup lang="ts">
import { computed } from 'vue'

const props = withDefaults(
  defineProps<{
    open: boolean
    title?: string
    description?: string
    confirmLabel?: string
    cancelLabel?: string
    showCancel?: boolean
  }>(),
  {
    title: '',
    description: '',
    confirmLabel: 'Confirm',
    cancelLabel: 'Cancel',
    showCancel: true
  }
)

const emit = defineEmits<{
  (e: 'update:open', value: boolean): void
  (e: 'confirm'): void
  (e: 'cancel'): void
}>()

const internalOpen = computed({
  get: () => props.open,
  set: (value: boolean) => emit('update:open', value)
})

const handleConfirm = () => {
  emit('confirm')
}

const handleCancel = () => {
  emit('cancel')
}
</script>

