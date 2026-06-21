<template xmlns="http://www.w3.org/1999/html">
  <div class="m-4 mt-0 mb-0 p-4 text-2xl font-bold">
    Browse items here
  </div>

  <div class="flex flex-row justify-center">
    <input type="text" placeholder="Search" v-model="searchTerm"
           class="bg-[#e5e5e5] placeholder-[#64748b] rounded-2xl p-3 focus:bg-white focus:outline-2 focus:outline-[#18181b]"
    >
  </div>

  <div v-if="isLoading || !data">
    <div class="grid grid-cols-3 gap-4 m-4 mt-0">
      <SkeletonCard
          v-for="n in 3"
          :key="n"
          :title="`Test Card ${n}`"
      ></SkeletonCard>
    </div>
  </div>

  <div v-else-if="error">
    <p>Uh oh</p>
  </div>

  <div v-else-if="filteredItems.length === 0 && searchTerm">
    <p>Can't find item</p>
  </div>

  <div v-else-if="data.length === 0">
    <p>Currently empty...</p>
  </div>

  <div v-else>
    <div class="grid grid-cols-3 gap-2 m-4 mt-0">
      <Card
          v-for="item in filteredItems"
          :key="item.item_id"
          :title="`Item id ${item.item_id}`"
          :item="item"
      >
      </Card>
    </div>
  </div>
</template>

<script setup>
import SkeletonCard from "../components/SkeletonCard.vue";
import Card from "../components/Card.vue";
import {useItems} from "@/queries/items.js";
import {computed, ref} from "vue";

const {data, isLoading, error} = useItems();
let searchTerm = ref("");

const filteredItems = computed(() => {
  if (!data.value) return []
  const clean = searchTerm.value.trim().toLowerCase()
  if (!clean) return data.value;
  return data.value.filter(item => item.title.toLowerCase().includes(clean))
})

</script>
