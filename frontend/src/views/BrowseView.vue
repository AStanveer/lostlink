<template>
  <div class="m-4 mt-0 mb-0 p-4 text-2xl font-bold =">
    Browse items here
  </div>
  <div class="grid grid-cols-3 gap-4 m-4 mt-0">
    <Card
      v-for="n in 3"
      :key="n"
      :title="`Test Card ${n}`"
      ></Card>
  </div>
</template>

<script setup>
import Card from "../components/Card.vue";
</script>
