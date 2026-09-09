<script setup>
import { onMounted, reactive, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'

import { anuncios as apiAnuncios, favoritos as apiFavoritos } from '@/api/recursos'
import CargandoOVacio from '@/components/CargandoOVacio.vue'
import PaginacionSimple from '@/components/PaginacionSimple.vue'
import PanelFiltros from '@/components/PanelFiltros.vue'
import TarjetaAnuncio from '@/components/TarjetaAnuncio.vue'
import { useAuth } from '@/stores/auth'

const route = useRoute()
const router = useRouter()
const auth = useAuth()

const FILTROS_VACIOS = {
  brand: '',
  model: '',
  fuel: '',
  transmission: '',
  price_min: '',
  price_max: '',
  year_min: '',
  year_max: '',
  mileage_max: '',
  sort: 'recent',
}

// Los filtros viven en la URL: así un enlace a una búsqueda se puede compartir.
const filtros = ref({ ...FILTROS_VACIOS, ...route.query })
const pagina = ref(Number(route.query.page ?? 1))

const estado = reactive({ cargando: true, error: null })
const listado = ref([])
const meta = ref(null)
const disponibles = ref(null)

async function cargar() {
  estado.cargando = true
  estado.error = null

  try {
    const respuesta = await apiAnuncios.listar({ ...filtros.value, page: pagina.value })
    listado.value = respuesta.data
    meta.value = respuesta.meta
  } catch (error) {
    estado.error = error.message
  } finally {
    estado.cargando = false
  }
}

async function cargarFiltrosDisponibles() {
  try {
    disponibles.value = (await apiAnuncios.filtrosDisponibles()).data
  } catch {
    // Sin esto los desplegables quedan vacíos, pero el catálogo sigue usable.
  }
}

/** Refleja filtros y página en la URL; el watch de la ruta dispara la carga. */
function sincronizarUrl() {
  const query = Object.fromEntries(
    Object.entries(filtros.value).filter(([, valor]) => valor !== '' && valor !== null),
  )

  if (pagina.value > 1) query.page = pagina.value

  router.push({ name: 'catalogo', query })
}

function alCambiarFiltros(nuevos) {
  filtros.value = nuevos
  pagina.value = 1
  sincronizarUrl()
}

function limpiar() {
  filtros.value = { ...FILTROS_VACIOS }
  pagina.value = 1
  sincronizarUrl()
}

function irAPagina(numero) {
  pagina.value = numero
  sincronizarUrl()
}

async function alternarFavorito(anuncio) {
  if (!auth.autenticado) {
    router.push({ name: 'acceso', query: { volverA: route.fullPath } })
    return
  }

  const guardado = anuncio.is_favorite
  anuncio.is_favorite = !guardado // respuesta inmediata; se revierte si falla

  try {
    if (guardado) await apiFavoritos.quitar(anuncio.id)
    else await apiFavoritos.guardar(anuncio.id)
  } catch {
    anuncio.is_favorite = guardado
  }
}

watch(
  () => route.query,
  (query) => {
    filtros.value = { ...FILTROS_VACIOS, ...query }
    pagina.value = Number(query.page ?? 1)
    cargar()
  },
)

onMounted(() => {
  cargar()
  cargarFiltrosDisponibles()
})
</script>

<template>
  <div class="catalogo">
    <PanelFiltros
      :model-value="filtros"
      :disponibles="disponibles"
      @update:model-value="alCambiarFiltros"
      @limpiar="limpiar"
    />

    <section>
      <h1>Coches clásicos en venta</h1>

      <CargandoOVacio
        :cargando="estado.cargando"
        :error="estado.error"
        :vacio="listado.length === 0"
        mensaje-vacio="Ningún anuncio coincide con esos filtros."
      />

      <div v-if="!estado.cargando && listado.length" class="rejilla">
        <TarjetaAnuncio
          v-for="anuncio in listado"
          :key="anuncio.id"
          :anuncio="anuncio"
          @alternar-favorito="alternarFavorito"
        />
      </div>

      <PaginacionSimple :meta="meta" @ir="irAPagina" />
    </section>
  </div>
</template>

<style scoped>
.catalogo {
  display: grid;
  grid-template-columns: 280px 1fr;
  gap: var(--espacio-6);
  align-items: start;
}

.rejilla {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
  gap: var(--espacio-5);
}

@media (max-width: 880px) {
  .catalogo { grid-template-columns: 1fr; }
}
</style>
