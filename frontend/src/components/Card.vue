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
  'id/cards': IDCardBadge,
  books: BooksBadge,
  clothing: ClothingBadge
}

// Compute which component to show dynamically
const activeBadge = computed(() => {
  const cleanCategory = props.item.category?.trim().toLowerCase()
  return badgeMap[cleanCategory] || OthersBadge
})

</script>

<template>
  <div class="shadow-lg m-4 outline-black/5 hover:shadow-xl hover:-translate-y-2">
    <div>
      <img :src="item.image_path ? item.image_path : placeholder" class="w-full h-56 object-cover object-top" alt="Posted image">
    </div>
    <div class="flex flex-col p-4 gap-3">
      <div class="flex flex-row justify-between">
        <span class="text-xl font-medium text-black">{{ item.title }}</span>
        <div class="flex flex-row">
          <component :is="activeBadge" />
        </div>
      </div>
      <p class="truncate">{{ item.description }}</p>
      <div class="flex flex-row justify-between">
        <p class="text-gray-600 opacity-60">Posted X hours ago</p>
        <a class="self-end underline" href="#">Link here</a>
      </div>
    </div>
  </div>
</template>
