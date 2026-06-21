<script setup>
import placeholder from '../assets/placeholder.png';
import ElectronicsBadge from "@/components/badges/ElectronicsBadge.vue";
import AccessoriesBadge from "@/components/badges/AccessoriesBadge.vue";
import BooksBadge from "@/components/badges/BooksBadge.vue";
import ClothingBadge from "@/components/badges/ClothingBadge.vue";
import IDCardBadge from "@/components/badges/IDCardBadge.vue";
import OthersBadge from "@/components/badges/OthersBadge.vue";
import {computed} from "vue";
const props = defineProps({
  /** @type Item */
  item: Object
})

const badgeMap = {
  electronics: ElectronicsBadge,
  accessories: AccessoriesBadge,
  'id/card': IDCardBadge,
  books: BooksBadge,
  clothing: ClothingBadge
}

// Compute which component to show dynamically
const activeBadge = computed(() => {
  const cleanCategory = props.item.category?.trim().toLowerCase()
  return badgeMap[cleanCategory] || OthersBadge
})

const isLost = computed(() => props.item.report_type === 'lost')

const postedAgo = computed(() => {
  const diffMs = Date.now() - new Date(props.item.date).getTime()
  const minutes = Math.floor(diffMs / 60000)
  if (minutes < 1) return 'Just now'
  if (minutes < 60) return `${minutes}m ago`
  const hours = Math.floor(minutes / 60)
  if (hours < 24) return `${hours}h ago`
  const days = Math.floor(hours / 24)
  if (days < 30) return `${days}d ago`
  const months = Math.floor(days / 30)
  if (months < 12) return `${months}mo ago`
  return `${Math.floor(months / 12)}y ago`
})
</script>

<template>
  <RouterLink
      :to="`/items/${item.item_id}`"
      class="group flex flex-col bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden hover:shadow-xl hover:-translate-y-1 transition-all duration-200"
  >
    <div class="relative">
      <img :src="item.image_path ? `/api${item.image_path}` : placeholder" class="w-full h-48 object-cover object-center" alt="Posted image">
      <span
          class="absolute top-3 left-3 px-2.5 py-1 rounded-full text-xs font-bold uppercase tracking-wide shadow-sm"
          :class="isLost ? 'bg-[var(--primary-light)] text-[var(--primary)]' : 'bg-blue-50 text-blue-700'"
      >
        {{ isLost ? 'Lost' : 'Found' }}
      </span>
    </div>
    <div class="flex flex-col p-4 gap-2 flex-1">
      <div class="flex flex-row justify-between items-start gap-2">
        <span class="text-lg font-semibold text-gray-900 leading-snug min-w-0">{{ item.title }}</span>
        <component :is="activeBadge" class="shrink-0" />
      </div>
      <p class="text-sm text-gray-600 line-clamp-2 flex-1">{{ item.description }}</p>
      <div class="flex flex-row justify-between items-center gap-2 pt-2 mt-1 border-t border-gray-100 text-sm">
        <span class="text-gray-500 truncate min-w-0">📍 {{ item.location }} · {{ postedAgo }}</span>
        <span class="font-semibold text-[var(--primary)] group-hover:underline shrink-0 whitespace-nowrap">View details →</span>
      </div>
    </div>
  </RouterLink>
</template>
