<script setup>
import { onMounted, reactive, ref } from 'vue'

import { mensajes as apiMensajes } from '@/api/recursos'
import CargandoOVacio from '@/components/CargandoOVacio.vue'
import { enFechaYHora } from '@/utilidades/formato'

const conversaciones = ref([])
const estado = reactive({ cargando: true, error: null })

async function cargar() {
  estado.cargando = true
  estado.error = null

  try {
    conversaciones.value = (await apiMensajes.conversaciones()).data
  } catch (error) {
    estado.error = error.message
  } finally {
    estado.cargando = false
  }
}

onMounted(cargar)
</script>

<template>
  <h1>Mensajes</h1>

  <CargandoOVacio
    :cargando="estado.cargando"
    :error="estado.error"
    :vacio="conversaciones.length === 0"
    mensaje-vacio="No tienes ninguna conversación todavía."
  />

  <ul v-if="!estado.cargando && conversaciones.length" class="lista">
    <li v-for="conversacion in conversaciones" :key="`${conversacion.listing.id}-${conversacion.with.id}`">
      <RouterLink
        class="tarjeta conversacion"
        :to="{
          name: 'hilo',
          params: { anuncioId: conversacion.listing.id, usuarioId: conversacion.with.id },
        }"
      >
        <div class="tarjeta-cuerpo">
          <div class="fila">
            <strong>{{ conversacion.with.name }}</strong>
            <span class="texto-pequeno texto-suave">sobre {{ conversacion.listing.title }}</span>
            <span v-if="conversacion.unread" class="etiqueta sin-leer fila-fin">
              {{ conversacion.unread }} sin leer
            </span>
          </div>

          <p class="ultimo texto-pequeno">
            <span v-if="conversacion.last_message.is_mine" class="texto-suave">Tú: </span>
            {{ conversacion.last_message.message }}
          </p>

          <p class="texto-pequeno texto-suave sin-margen">
            {{ enFechaYHora(conversacion.last_message.created_at) }}
          </p>
        </div>
      </RouterLink>
    </li>
  </ul>
</template>

<style scoped>
.lista { list-style: none; margin: 0; padding: 0; display: grid; gap: var(--espacio-3); }

.conversacion { display: block; color: inherit; }
.conversacion:hover { text-decoration: none; border-color: var(--color-primario); }

.ultimo {
  margin: var(--espacio-2) 0;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.sin-margen { margin: 0; }
.sin-leer { background: var(--color-primario); color: var(--color-texto-inverso); }
</style>
