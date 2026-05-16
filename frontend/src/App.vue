<template>
  <div id="app">
    <NavBar v-if="!isAuthPage" />
    <main class="main-content">
      <RouterView />
    </main>
    <FooterBar v-if="!isAuthPage" />
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { useRoute } from 'vue-router'
import NavBar from '@/components/NavBar.vue'
import FooterBar from '@/components/FooterBar.vue'

const route = useRoute()
const isAuthPage = computed(() => route.meta.guestOnly)
</script>
