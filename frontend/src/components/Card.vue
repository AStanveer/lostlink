<script setup>
defineProps({
  /** @type Item */
  item: Object
})

const PLACEHOLDER_IMAGE_URL = "https://developers.elementor.com/docs/assets/img/elementor-placeholder-image.png"
</script>

<template>
  <div class="shadow-lg m-4 outline-black/5 hover:shadow-xl hover:-translate-y-2">
    <div>
      <img :src="item.image_path ? item.image_path : PLACEHOLDER_IMAGE_URL" class="w-full h-56 object-cover object-top" alt="Posted image">
    </div>
    <div class="flex flex-col p-4 gap-3">
      <span class="text-xl font-medium text-black">{{ item.title }}</span>
      <p class="truncate">{{ item.description }}</p>
      <a class="self-end underline" href="#">Link here</a>
    </div>
  </div>
</template>
