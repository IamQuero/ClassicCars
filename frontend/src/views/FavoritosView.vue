<script setup>
import { onMounted, reactive, ref } from 'vue'

import { favoritos as apiFavoritos } from '@/api/recursos'
import CargandoOVacio from '@/components/CargandoOVacio.vue'
import PaginacionSimple from '@/components/PaginacionSimple.vue'
import TarjetaAnuncio from '@/components/TarjetaAnuncio.vue'

const listado = ref([])
const meta = ref(null)
const estado = reactive({ cargando: true, error: null })

async function cargar(pagina = 1) {
  estado.cargando = true
  estado.error = null

  try {
    const respuesta = await apiFavoritos.listar({ page: pagina })
    listado.value = respuesta.data
    meta.value = respuesta.meta
  } catch (error) {
    estado.error = error.message
  } finally {
    estado.cargando = false
  }
}

async function quitar(anuncio) {
  const copia = [...listado.value]
  listado.value = listado.value.filter((a) => a.id !== anuncio.id)

  try {
    await apiFavoritos.quitar(anuncio.id)
  } catch {
    listado.value = copia
  }
}

onMounted(() => cargar())
</script>

<template>
  <h1>Favoritos</h1>

  <CargandoOVacio
    :cargando="estado.cargando"
    :error="estado.error"
    :vacio="listado.length === 0"
    mensaje-vacio="Todavía no has guardado ningún anuncio."
  />

  <div v-if="!estado.cargando && listado.length" class="rejilla">
    <TarjetaAnuncio
      v-for="anuncio in listado"
      :key="anuncio.id"
      :anuncio="anuncio"
      @alternar-favorito="quitar"
    />
  </div>

  <PaginacionSimple :meta="meta" @ir="cargar" />
</template>

<style scoped>
.rejilla {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
  gap: var(--espacio-5);
}
</style>
