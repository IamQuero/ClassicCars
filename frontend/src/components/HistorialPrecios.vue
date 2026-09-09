<script setup>
import { computed } from 'vue'

import { enEuros, enFecha, ESTADOS } from '@/utilidades/formato'

const props = defineProps({
  historial: { type: Array, default: () => [] },
  resumen: { type: Object, default: null },
})

/** Altura relativa de cada barra respecto al precio más alto del historial. */
const barras = computed(() => {
  const maximo = props.resumen?.max ?? 0
  if (!maximo) return []

  return props.historial.map((punto) => ({
    ...punto,
    altura: Math.max(8, Math.round((punto.price / maximo) * 100)),
  }))
})

const variacion = computed(() => {
  if (!props.resumen?.first || !props.resumen?.last) return null
  return Math.round(((props.resumen.last - props.resumen.first) / props.resumen.first) * 100)
})
</script>

<template>
  <section v-if="historial.length > 1" class="tarjeta">
    <div class="tarjeta-cuerpo pila">
      <h3 class="sin-margen">Historial de precios</h3>

      <p class="texto-pequeno texto-suave sin-margen">
        Este coche se ha anunciado {{ historial.length }} veces.
        <span v-if="variacion !== null">
          Del primero al último, {{ variacion >= 0 ? '+' : '' }}{{ variacion }} %.
        </span>
      </p>

      <div class="grafico">
        <div v-for="punto in barras" :key="punto.listing_id" class="columna">
          <span class="valor texto-pequeno">{{ enEuros(punto.price) }}</span>

          <!-- La barra mide su porcentaje contra esta zona, que sí tiene altura. -->
          <div class="zona-barra">
            <div class="barra" :style="{ height: `${punto.altura}%` }" />
          </div>

          <span class="fecha texto-pequeno texto-suave">{{ enFecha(punto.published_at) }}</span>
          <span class="etiqueta">{{ ESTADOS[punto.status] ?? punto.status }}</span>
        </div>
      </div>
    </div>
  </section>
</template>

<style scoped>
.sin-margen { margin: 0; }

.grafico {
  display: flex;
  align-items: stretch;
  gap: var(--espacio-4);
  height: 240px;
  overflow-x: auto;
  padding-top: var(--espacio-4);
}

.columna {
  flex: 1 0 90px;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: var(--espacio-2);
}

.zona-barra {
  flex: 1;
  width: 100%;
  max-width: 56px;
  display: flex;
  align-items: flex-end;
}

.barra {
  width: 100%;
  background: var(--color-primario);
  border-radius: var(--radio-sm) var(--radio-sm) 0 0;
  min-height: 8px;
}

.valor { font-weight: var(--peso-medio); }
.fecha { text-align: center; }
</style>
