<script setup>
import { computed } from 'vue'

import { CAMBIOS, COMBUSTIBLES, ORDENES } from '@/utilidades/formato'

const props = defineProps({
  // v-model con los filtros activos.
  modelValue: { type: Object, required: true },
  // Lo que responde GET /listings/filters.
  disponibles: { type: Object, default: null },
})

const emit = defineEmits(['update:modelValue', 'limpiar'])

const filtros = computed({
  get: () => props.modelValue,
  set: (valor) => emit('update:modelValue', valor),
})

function actualizar(campo, valor) {
  const siguiente = { ...props.modelValue, [campo]: valor }

  // Cambiar de marca invalida el modelo elegido de la anterior.
  if (campo === 'brand') siguiente.model = ''

  emit('update:modelValue', siguiente)
}

const modelosDeLaMarca = computed(() => {
  if (!filtros.value.brand) return []
  return props.disponibles?.brands?.find((m) => m.brand === filtros.value.brand)?.models ?? []
})
</script>

<template>
  <aside class="tarjeta">
    <div class="tarjeta-cuerpo pila">
      <div class="fila">
        <h3 class="sin-margen">Filtros</h3>
        <button class="boton boton-secundario boton-pequeno fila-fin" type="button" @click="emit('limpiar')">
          Limpiar
        </button>
      </div>

      <div class="campo">
        <label for="f-marca">Marca</label>
        <select id="f-marca" :value="filtros.brand" @change="actualizar('brand', $event.target.value)">
          <option value="">Todas</option>
          <option v-for="marca in disponibles?.brands ?? []" :key="marca.brand" :value="marca.brand">
            {{ marca.brand }}
          </option>
        </select>
      </div>

      <div class="campo">
        <label for="f-modelo">Modelo</label>
        <select
          id="f-modelo"
          :value="filtros.model"
          :disabled="!filtros.brand"
          @change="actualizar('model', $event.target.value)"
        >
          <option value="">{{ filtros.brand ? 'Todos' : 'Elige antes una marca' }}</option>
          <option v-for="modelo in modelosDeLaMarca" :key="modelo" :value="modelo">
            {{ modelo }}
          </option>
        </select>
      </div>

      <div class="campo">
        <label for="f-combustible">Combustible</label>
        <select id="f-combustible" :value="filtros.fuel" @change="actualizar('fuel', $event.target.value)">
          <option value="">Cualquiera</option>
          <option v-for="clave in disponibles?.fuels ?? []" :key="clave" :value="clave">
            {{ COMBUSTIBLES[clave] ?? clave }}
          </option>
        </select>
      </div>

      <div class="campo">
        <label for="f-cambio">Cambio</label>
        <select
          id="f-cambio"
          :value="filtros.transmission"
          @change="actualizar('transmission', $event.target.value)"
        >
          <option value="">Cualquiera</option>
          <option v-for="clave in disponibles?.transmissions ?? []" :key="clave" :value="clave">
            {{ CAMBIOS[clave] ?? clave }}
          </option>
        </select>
      </div>

      <div class="rejilla-campos">
        <div class="campo">
          <label for="f-precio-min">Precio desde</label>
          <input
            id="f-precio-min"
            type="number"
            min="0"
            :placeholder="disponibles?.ranges?.price?.min ?? ''"
            :value="filtros.price_min"
            @change="actualizar('price_min', $event.target.value)"
          />
        </div>
        <div class="campo">
          <label for="f-precio-max">hasta</label>
          <input
            id="f-precio-max"
            type="number"
            min="0"
            :placeholder="disponibles?.ranges?.price?.max ?? ''"
            :value="filtros.price_max"
            @change="actualizar('price_max', $event.target.value)"
          />
        </div>
      </div>

      <div class="rejilla-campos">
        <div class="campo">
          <label for="f-year-min">Año desde</label>
          <input
            id="f-year-min"
            type="number"
            :placeholder="disponibles?.ranges?.year?.min ?? ''"
            :value="filtros.year_min"
            @change="actualizar('year_min', $event.target.value)"
          />
        </div>
        <div class="campo">
          <label for="f-year-max">hasta</label>
          <input
            id="f-year-max"
            type="number"
            :placeholder="disponibles?.ranges?.year?.max ?? ''"
            :value="filtros.year_max"
            @change="actualizar('year_max', $event.target.value)"
          />
        </div>
      </div>

      <div class="campo">
        <label for="f-km">Kilómetros máximos</label>
        <input
          id="f-km"
          type="number"
          min="0"
          :placeholder="disponibles?.ranges?.mileage?.max ?? ''"
          :value="filtros.mileage_max"
          @change="actualizar('mileage_max', $event.target.value)"
        />
      </div>

      <div class="campo">
        <label for="f-orden">Ordenar por</label>
        <select id="f-orden" :value="filtros.sort" @change="actualizar('sort', $event.target.value)">
          <option v-for="clave in disponibles?.sorts ?? Object.keys(ORDENES)" :key="clave" :value="clave">
            {{ ORDENES[clave] ?? clave }}
          </option>
        </select>
      </div>
    </div>
  </aside>
</template>

<style scoped>
.sin-margen { margin: 0; }
</style>
