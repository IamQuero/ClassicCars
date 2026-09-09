<script setup>
import { ref, watch } from 'vue'

const props = defineProps({
  fotos: { type: Array, default: () => [] },
  titulo: { type: String, default: '' },
})

const activa = ref(0)
const rotas = ref(new Set())

watch(() => props.fotos, () => {
  activa.value = 0
  rotas.value = new Set()
})
</script>

<template>
  <div v-if="fotos.length" class="galeria">
    <img
      v-if="!rotas.has(fotos[activa].id)"
      class="principal"
      :src="fotos[activa].url"
      :alt="titulo"
      @error="rotas = new Set([...rotas, fotos[activa].id])"
    />
    <div v-else class="principal sin-imagen vacio">Esta foto ya no está disponible.</div>

    <div v-if="fotos.length > 1" class="tiras">
      <button
        v-for="(foto, indice) in fotos"
        :key="foto.id"
        class="tira"
        :class="{ activa: indice === activa }"
        type="button"
        :aria-label="`Ver foto ${indice + 1}`"
        @click="activa = indice"
      >
        <img :src="foto.thumbnail_url" :alt="`${titulo} ${indice + 1}`" loading="lazy" />
      </button>
    </div>
  </div>

  <div v-else class="sin-fotos vacio">Este anuncio todavía no tiene fotos.</div>
</template>

<style scoped>
.galeria { display: flex; flex-direction: column; gap: var(--espacio-3); }

.principal {
  width: 100%;
  aspect-ratio: 4 / 3;
  object-fit: cover;
  border-radius: var(--radio-lg);
  background: var(--color-superficie-suave);
}

.tiras { display: flex; gap: var(--espacio-2); overflow-x: auto; padding-bottom: var(--espacio-1); }

.tira {
  flex: 0 0 84px;
  padding: 0;
  border: 2px solid transparent;
  border-radius: var(--radio-md);
  overflow: hidden;
  background: none;
  cursor: pointer;
}

.tira.activa { border-color: var(--color-primario); }
.tira img { width: 84px; height: 63px; object-fit: cover; }

.sin-fotos,
.sin-imagen {
  border: 1px dashed var(--color-borde-fuerte);
  border-radius: var(--radio-lg);
  display: grid;
  place-items: center;
}
</style>
