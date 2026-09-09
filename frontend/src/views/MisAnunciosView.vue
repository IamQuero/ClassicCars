<script setup>
import { onMounted, reactive, ref } from 'vue'

import { anuncios as apiAnuncios } from '@/api/recursos'
import CargandoOVacio from '@/components/CargandoOVacio.vue'
import PaginacionSimple from '@/components/PaginacionSimple.vue'
import TarjetaAnuncio from '@/components/TarjetaAnuncio.vue'
import { ESTADOS } from '@/utilidades/formato'

const listado = ref([])
const meta = ref(null)
const filtroEstado = ref('')
const estado = reactive({ cargando: true, error: null })

async function cargar(pagina = 1) {
  estado.cargando = true
  estado.error = null

  try {
    const respuesta = await apiAnuncios.mios({ page: pagina, status: filtroEstado.value })
    listado.value = respuesta.data
    meta.value = respuesta.meta
  } catch (error) {
    estado.error = error.message
  } finally {
    estado.cargando = false
  }
}

async function retirar(anuncio) {
  if (!confirm(`¿Retirar el anuncio de ${anuncio.car.brand} ${anuncio.car.model}?`)) return

  try {
    await apiAnuncios.retirar(anuncio.id)
    anuncio.status = 'expired'
  } catch (error) {
    estado.error = error.message
  }
}

onMounted(() => cargar())
</script>

<template>
  <div class="fila cabecera">
    <h1 class="sin-margen">Mis anuncios</h1>
    <RouterLink class="boton fila-fin" :to="{ name: 'anuncio-nuevo' }">Publicar anuncio</RouterLink>
  </div>

  <div class="campo filtro">
    <label for="estado">Estado</label>
    <select id="estado" v-model="filtroEstado" @change="cargar()">
      <option value="">Todos</option>
      <option v-for="(texto, clave) in ESTADOS" :key="clave" :value="clave">{{ texto }}</option>
    </select>
  </div>

  <CargandoOVacio
    :cargando="estado.cargando"
    :error="estado.error"
    :vacio="listado.length === 0"
    mensaje-vacio="Todavía no has publicado ningún anuncio."
  />

  <div v-if="!estado.cargando && listado.length" class="rejilla">
    <div v-for="anuncio in listado" :key="anuncio.id" class="pila">
      <TarjetaAnuncio :anuncio="anuncio" mostrar-estado />

      <div class="fila acciones">
        <RouterLink
          class="boton boton-secundario boton-pequeno"
          :to="{ name: 'anuncio-editar', params: { id: anuncio.id } }"
        >
          Editar
        </RouterLink>
        <button
          v-if="anuncio.status !== 'expired'"
          class="boton boton-peligro boton-pequeno"
          type="button"
          @click="retirar(anuncio)"
        >
          Retirar
        </button>
      </div>
    </div>
  </div>

  <PaginacionSimple :meta="meta" @ir="cargar" />
</template>

<style scoped>
.cabecera { margin-bottom: var(--espacio-5); }
.sin-margen { margin: 0; }
.filtro { max-width: 220px; margin-bottom: var(--espacio-5); }

.rejilla {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
  gap: var(--espacio-5);
}

.acciones { justify-content: flex-end; }
</style>
