<template>
  <div class="m-4 mt-0 mb-0 p-4 text-2xl font-bold">
    Browse items here
  </div>

  <div v-if="store.loading">
    <div class="grid grid-cols-3 gap-4 m-4 mt-0">
      <SkeletonCard
          v-for="n in 3"
          :key="n"
          :title="`Test Card ${n}`"
      ></SkeletonCard>
    </div>
  </div>

  <div v-else-if="store.error">
    <p>Uh oh</p>
  </div>

  <div v-else>
    <div class="grid grid-cols-3 gap-2 m-4 mt-0">
      <Card
          v-for="item in store.items"
          :key="item.item_id"
          :title="`Item id ${item.item_id}`"
          :item="item"
      >
      </Card>
    </div>
  </div>
</template>

<script setup>
import {useItemStore} from "../store/itemStore.js";
import SkeletonCard from "../components/SkeletonCard.vue";
import Card from "../components/Card.vue";
import {onMounted} from "vue";

const store = useItemStore();

onMounted(async () => {
  await store.fetchItems();
})
</script>
