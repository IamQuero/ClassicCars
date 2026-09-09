<script setup>
import { onMounted, reactive, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'

import {
  anuncios as apiAnuncios,
  coches as apiCoches,
  favoritos as apiFavoritos,
  mensajes as apiMensajes,
} from '@/api/recursos'
import CargandoOVacio from '@/components/CargandoOVacio.vue'
import GaleriaFotos from '@/components/GaleriaFotos.vue'
import HistorialPrecios from '@/components/HistorialPrecios.vue'
import { useAuth } from '@/stores/auth'
import { CAMBIOS, COMBUSTIBLES, enEuros, enFecha, enMiles } from '@/utilidades/formato'

const props = defineProps({ id: { type: [String, Number], required: true } })

const route = useRoute()
const router = useRouter()
const auth = useAuth()

const anuncio = ref(null)
const historial = ref(null)
const estado = reactive({ cargando: true, error: null })

const mensaje = ref('')
const envio = reactive({ enviando: false, error: null, enviado: false })

async function cargar() {
  estado.cargando = true
  estado.error = null
  historial.value = null

  try {
    anuncio.value = (await apiAnuncios.ver(props.id)).data
    // El historial es secundario: si falla, el anuncio se ve igual.
    historial.value = (await apiCoches.historialDePrecios(anuncio.value.car.id)).data
  } catch (error) {
    if (!anuncio.value) estado.error = error.status === 404 ? 'Este anuncio no existe.' : error.message
  } finally {
    estado.cargando = false
  }
}

async function alternarFavorito() {
  if (!auth.autenticado) {
    router.push({ name: 'acceso', query: { volverA: route.fullPath } })
    return
  }

  const guardado = anuncio.value.is_favorite
  anuncio.value.is_favorite = !guardado

  try {
    if (guardado) await apiFavoritos.quitar(anuncio.value.id)
    else await apiFavoritos.guardar(anuncio.value.id)
  } catch {
    anuncio.value.is_favorite = guardado
  }
}

async function enviarMensaje() {
  envio.enviando = true
  envio.error = null

  try {
    await apiMensajes.enviar(anuncio.value.id, mensaje.value)
    mensaje.value = ''
    envio.enviado = true
  } catch (error) {
    envio.error = error.errorDe('message') ?? error.message
  } finally {
    envio.enviando = false
  }
}

watch(() => props.id, cargar)
onMounted(cargar)
</script>

<template>
  <CargandoOVacio :cargando="estado.cargando" :error="estado.error" />

  <article v-if="anuncio" class="detalle">
    <div class="pila">
      <GaleriaFotos :fotos="anuncio.photos" :titulo="`${anuncio.car.brand} ${anuncio.car.model}`" />

      <section v-if="anuncio.car.description" class="tarjeta">
        <div class="tarjeta-cuerpo">
          <h3>Descripción</h3>
          <p class="descripcion">{{ anuncio.car.description }}</p>
        </div>
      </section>

      <HistorialPrecios
        v-if="historial"
        :historial="historial.history"
        :resumen="historial.summary"
      />
    </div>

    <aside class="pila lateral">
      <div class="tarjeta">
        <div class="tarjeta-cuerpo pila">
          <div>
            <h1 class="titulo">{{ anuncio.car.brand }} {{ anuncio.car.model }}</h1>
            <p v-if="anuncio.car.generation" class="texto-suave sin-margen">
              {{ anuncio.car.generation }}
            </p>
          </div>

          <p class="precio sin-margen">{{ enEuros(anuncio.price) }}</p>

          <button
            v-if="anuncio.is_favorite !== undefined || !auth.autenticado"
            class="boton boton-secundario"
            type="button"
            @click="alternarFavorito"
          >
            {{ anuncio.is_favorite ? '★ Guardado' : '☆ Guardar en favoritos' }}
          </button>

          <dl class="fichas">
            <div><dt>Año</dt><dd>{{ anuncio.car.year }}</dd></div>
            <div><dt>Kilómetros</dt><dd>{{ enMiles(anuncio.car.mileage) }} km</dd></div>
            <div><dt>Combustible</dt><dd>{{ COMBUSTIBLES[anuncio.car.fuel] ?? anuncio.car.fuel }}</dd></div>
            <div><dt>Cambio</dt><dd>{{ CAMBIOS[anuncio.car.transmission] ?? anuncio.car.transmission }}</dd></div>
            <div v-if="anuncio.car.engine"><dt>Motor</dt><dd>{{ anuncio.car.engine }}</dd></div>
            <div v-if="anuncio.car.horsepower"><dt>Potencia</dt><dd>{{ anuncio.car.horsepower }} CV</dd></div>
            <div><dt>Publicado</dt><dd>{{ enFecha(anuncio.published_at) }}</dd></div>
          </dl>

          <p class="texto-pequeno texto-suave sin-margen">
            Vendedor: {{ anuncio.seller?.name }}
          </p>
        </div>
      </div>

      <div class="tarjeta">
        <div class="tarjeta-cuerpo pila">
          <h3 class="sin-margen">Contactar</h3>

          <template v-if="!auth.autenticado">
            <p class="texto-pequeno texto-suave sin-margen">
              Necesitas una cuenta para escribir al vendedor.
            </p>
            <RouterLink class="boton" :to="{ name: 'acceso', query: { volverA: route.fullPath } }">
              Entrar
            </RouterLink>
          </template>

          <template v-else-if="auth.usuario.id === anuncio.seller?.id">
            <p class="texto-pequeno texto-suave sin-margen">Este anuncio es tuyo.</p>
            <RouterLink class="boton boton-secundario" :to="{ name: 'anuncio-editar', params: { id: anuncio.id } }">
              Editarlo
            </RouterLink>
          </template>

          <template v-else>
            <p v-if="envio.enviado" class="aviso aviso-exito">
              Mensaje enviado. Lo tienes en <RouterLink :to="{ name: 'mensajes' }">tus mensajes</RouterLink>.
            </p>

            <div class="campo">
              <label for="mensaje">Tu mensaje</label>
              <textarea
                id="mensaje"
                v-model="mensaje"
                placeholder="¿Sigue disponible? ¿Se puede ver en persona?"
              />
              <p v-if="envio.error" class="campo-error">{{ envio.error }}</p>
            </div>

            <button class="boton" type="button" :disabled="envio.enviando || mensaje.trim().length < 2" @click="enviarMensaje">
              {{ envio.enviando ? 'Enviando…' : 'Enviar mensaje' }}
            </button>
          </template>
        </div>
      </div>
    </aside>
  </article>
</template>

<style scoped>
.detalle {
  display: grid;
  grid-template-columns: 1fr 360px;
  gap: var(--espacio-6);
  align-items: start;
}

.titulo { font-size: var(--texto-2xl); margin: 0; }
.sin-margen { margin: 0; }
.descripcion { white-space: pre-line; margin: 0; }

.fichas { display: grid; gap: var(--espacio-2); margin: 0; }
.fichas > div { display: flex; justify-content: space-between; gap: var(--espacio-3); }
.fichas dt { color: var(--color-texto-suave); font-size: var(--texto-sm); }
.fichas dd { margin: 0; font-weight: var(--peso-medio); }

@media (max-width: 880px) {
  .detalle { grid-template-columns: 1fr; }
  .lateral { order: -1; }
}
</style>
