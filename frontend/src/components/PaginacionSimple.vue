<script setup>
defineProps({
  meta: { type: Object, default: null },
})

defineEmits(['ir'])
</script>

<template>
  <nav v-if="meta && meta.last_page > 1" class="paginacion fila">
    <button
      class="boton boton-secundario boton-pequeno"
      type="button"
      :disabled="meta.current_page <= 1"
      @click="$emit('ir', meta.current_page - 1)"
    >
      Anterior
    </button>

    <span class="texto-pequeno texto-suave">
      Página {{ meta.current_page }} de {{ meta.last_page }} · {{ meta.total }} anuncios
    </span>

    <button
      class="boton boton-secundario boton-pequeno"
      type="button"
      :disabled="meta.current_page >= meta.last_page"
      @click="$emit('ir', meta.current_page + 1)"
    >
      Siguiente
    </button>
  </nav>
</template>

<style scoped>
.paginacion { justify-content: center; margin-top: var(--espacio-6); }
</style>
