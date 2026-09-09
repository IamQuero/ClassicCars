<script setup>
/** Los tres estados de cualquier listado: cargando, error o vacío. */
defineProps({
  cargando: Boolean,
  error: { type: String, default: null },
  vacio: Boolean,
  mensajeVacio: { type: String, default: 'No hay nada que mostrar.' },
})
</script>

<template>
  <p v-if="cargando" class="vacio">Cargando…</p>
  <p v-else-if="error" class="aviso aviso-error">{{ error }}</p>
  <p v-else-if="vacio" class="vacio">{{ mensajeVacio }}</p>
</template>
