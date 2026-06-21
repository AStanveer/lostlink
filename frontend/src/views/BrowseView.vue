<template>
  <div class="max-w-6xl mx-auto px-4 py-8">

    <div class="mb-6">
      <h1 class="text-3xl font-extrabold text-gray-900">Browse Items</h1>
      <p class="text-gray-500 mt-1">Search lost and found reports from across campus</p>
    </div>

    <div class="flex flex-col sm:flex-row gap-3 mb-6">
      <input
          type="text"
          placeholder="Search by title, description or location..."
          v-model="searchTerm"
          class="flex-1 bg-gray-100 placeholder-gray-500 rounded-xl px-4 py-2.5 focus:bg-white focus:outline-2 focus:outline-[var(--primary)]"
      >

      <div class="flex gap-1 bg-gray-100 rounded-xl p-1 self-start">
        <button
            v-for="option in typeOptions"
            :key="option.value"
            @click="typeFilter = option.value"
            class="px-3 py-1.5 rounded-lg text-sm font-semibold transition-colors"
            :class="typeFilter === option.value ? 'bg-white text-[var(--primary)] shadow-sm' : 'text-gray-500 hover:text-gray-700'"
        >
          {{ option.label }}
        </button>
      </div>

      <select
          v-model="categoryFilter"
          class="bg-gray-100 rounded-xl px-4 py-2.5 focus:bg-white focus:outline-2 focus:outline-[var(--primary)]"
      >
        <option value="">All categories</option>
        <option v-for="cat in categories" :key="cat" :value="cat">{{ cat }}</option>
      </select>

      <select
          v-model="statusFilter"
          class="bg-gray-100 rounded-xl px-4 py-2.5 focus:bg-white focus:outline-2 focus:outline-[var(--primary)]"
      >
        <option value="">All statuses</option>
        <option value="active">Unclaimed</option>
        <option value="claimed">Claimed</option>
      </select>
    </div>

    <p v-if="data && !isLoading" class="text-sm text-gray-500 mb-4">
      {{ filteredItems.length }} item{{ filteredItems.length === 1 ? '' : 's' }} found
    </p>

    <div v-if="isLoading || !data">
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
        <SkeletonCard v-for="n in 6" :key="n" />
      </div>
    </div>

    <div v-else-if="error" class="flex flex-col items-center text-center py-20 text-gray-500">
      <span class="text-4xl mb-3">⚠️</span>
      <p class="font-semibold text-gray-700">Couldn't load items</p>
      <p class="text-sm mt-1">Please check your connection and try again.</p>
    </div>

    <div v-else-if="filteredItems.length === 0" class="flex flex-col items-center text-center py-20 text-gray-500">
      <span class="text-4xl mb-3">🔍</span>
      <p class="font-semibold text-gray-700">{{ data.length === 0 ? 'No items have been reported yet' : 'No items match your filters' }}</p>
      <p class="text-sm mt-1">{{ data.length === 0 ? 'Be the first to report a lost or found item.' : 'Try a different search term or clear your filters.' }}</p>
    </div>

    <div v-else class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
      <Card v-for="item in filteredItems" :key="item.item_id" :item="item" />
    </div>

  </div>
</template>

<script setup>
import SkeletonCard from "../components/SkeletonCard.vue";
import Card from "../components/Card.vue";
import {useItems} from "@/queries/items.js";
import {computed, ref} from "vue";

const {data, isLoading, error} = useItems();
const searchTerm = ref("");
const typeFilter = ref("");
const categoryFilter = ref("");
const statusFilter = ref("");

const typeOptions = [
  {label: 'All', value: ''},
  {label: 'Lost', value: 'lost'},
  {label: 'Found', value: 'found'},
]

const categories = computed(() => {
  if (!data.value) return []
  return [...new Set(data.value.map(item => item.category).filter(Boolean))].sort()
})

const filteredItems = computed(() => {
  if (!data.value) return []
  const clean = searchTerm.value.trim().toLowerCase()

  return data.value.filter(item => {
    if (typeFilter.value && item.report_type !== typeFilter.value) return false
    if (categoryFilter.value && item.category !== categoryFilter.value) return false
    if (statusFilter.value && item.status !== statusFilter.value) return false
    if (!clean) return true
    return [item.title, item.description, item.location]
        .some(field => field?.toLowerCase().includes(clean))
  })
})
</script>
