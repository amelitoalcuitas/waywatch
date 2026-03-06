<template>
  <UPopover>
    <UButton
      color="neutral"
      variant="subtle"
      icon="i-lucide-calendar"
    >
      {{ formattedDate }}
    </UButton>
    <template #content>
      <UCalendar v-model="calendarDate" class="p-2" />
    </template>
  </UPopover>
</template>

<script setup lang="ts">
import { CalendarDate, DateFormatter, getLocalTimeZone } from '@internationalized/date'

const props = defineProps<{
  modelValue: string
}>()

const emit = defineEmits<{
  'update:modelValue': [value: string]
}>()

const df = new DateFormatter('en-US', { dateStyle: 'medium' })

function parseToCalendarDate(s: string): CalendarDate {
  if (!s) {
    const today = new Date()
    return new CalendarDate(today.getFullYear(), today.getMonth() + 1, today.getDate())
  }
  const [y, m, d] = s.split('-').map(Number)
  return new CalendarDate(y, m, d)
}

function formatToStore(c: CalendarDate): string {
  return `${c.year}-${String(c.month).padStart(2, '0')}-${String(c.day).padStart(2, '0')}`
}

const calendarDate = computed({
  get: () => parseToCalendarDate(props.modelValue),
  set: (v: CalendarDate) => emit('update:modelValue', formatToStore(v))
})

const formattedDate = computed(() =>
  props.modelValue
    ? df.format(parseToCalendarDate(props.modelValue).toDate(getLocalTimeZone()))
    : 'Select date'
)
</script>
