<script setup>
import { onMounted } from 'vue'

import CabeceraApp from '@/components/CabeceraApp.vue'
import { useAuth } from '@/stores/auth'

const auth = useAuth()

onMounted(() => auth.recuperarSesion())
</script>

<template>
  <CabeceraApp />

  <main class="contenedor contenido">
    <RouterView />
  </main>

  <footer class="pie">
    <div class="contenedor texto-pequeno texto-suave">
      ClassicCars — proyecto de aprendizaje.
    </div>
  </footer>
</template>

<style scoped>
.contenido {
  padding-top: var(--espacio-6);
  padding-bottom: var(--espacio-8);
  min-height: 60vh;
}

.pie {
  border-top: 1px solid var(--color-borde);
  padding: var(--espacio-5) 0;
}
</style>
