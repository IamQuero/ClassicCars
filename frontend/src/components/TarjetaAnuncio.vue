<script setup>
import { computed, ref } from 'vue'

import { CAMBIOS, COMBUSTIBLES, enEuros, enMiles } from '@/utilidades/formato'

const props = defineProps({
  anuncio: { type: Object, required: true },
  mostrarEstado: Boolean,
})

defineEmits(['alternar-favorito'])

const portada = computed(() => props.anuncio.photos?.[0] ?? null)
const fotoRota = ref(false)
const coche = computed(() => props.anuncio.car ?? {})
</script>

<template>
  <article class="tarjeta anuncio">
    <RouterLink :to="{ name: 'anuncio', params: { id: anuncio.id } }" class="imagen">
      <img
        v-if="portada && !fotoRota"
        :src="portada.thumbnail_url"
        :alt="`${coche.brand} ${coche.model}`"
        loading="lazy"
        @error="fotoRota = true"
      />
      <div v-else class="sin-foto texto-suave texto-pequeno">Sin fotos</div>
    </RouterLink>

    <div class="tarjeta-cuerpo pila">
      <div class="fila">
        <h3 class="titulo">
          <RouterLink :to="{ name: 'anuncio', params: { id: anuncio.id } }">
            {{ coche.brand }} {{ coche.model }}
          </RouterLink>
        </h3>

        <button
          v-if="anuncio.is_favorite !== undefined"
          class="favorito fila-fin"
          type="button"
          :aria-pressed="anuncio.is_favorite"
          :title="anuncio.is_favorite ? 'Quitar de favoritos' : 'Guardar en favoritos'"
          @click="$emit('alternar-favorito', anuncio)"
        >
          {{ anuncio.is_favorite ? '★' : '☆' }}
        </button>
      </div>

      <p class="texto-pequeno texto-suave datos">
        {{ coche.year }} · {{ enMiles(coche.mileage) }} km ·
        {{ COMBUSTIBLES[coche.fuel] ?? coche.fuel }} ·
        {{ CAMBIOS[coche.transmission] ?? coche.transmission }}
      </p>

      <div class="fila">
        <span class="precio">{{ enEuros(anuncio.price) }}</span>
        <span
          v-if="mostrarEstado"
          class="etiqueta fila-fin"
          :class="`etiqueta-${anuncio.status}`"
        >
          {{ anuncio.status }}
        </span>
      </div>
    </div>
  </article>
</template>

<style scoped>
.anuncio { display: flex; flex-direction: column; height: 100%; }

.imagen { display: block; aspect-ratio: 4 / 3; background: var(--color-superficie-suave); }
.imagen img { width: 100%; height: 100%; object-fit: cover; }

.sin-foto {
  width: 100%;
  height: 100%;
  display: grid;
  place-items: center;
}

.tarjeta-cuerpo { gap: var(--espacio-2); }

.titulo { font-size: var(--texto-lg); margin: 0; }
.titulo a { color: var(--color-texto); }
.titulo a:hover { color: var(--color-primario); text-decoration: none; }

.datos { margin: 0; }

.favorito {
  border: none;
  background: none;
  cursor: pointer;
  font-size: var(--texto-xl);
  line-height: 1;
  color: var(--color-acento);
  padding: 0;
}
</style>
