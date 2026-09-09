<script setup>
import { computed, onMounted, reactive, ref } from 'vue'

import { anuncios as apiAnuncios, mensajes as apiMensajes } from '@/api/recursos'
import CargandoOVacio from '@/components/CargandoOVacio.vue'
import { useAuth } from '@/stores/auth'
import { enEuros, enFechaYHora } from '@/utilidades/formato'

const props = defineProps({
  anuncioId: { type: [String, Number], required: true },
  usuarioId: { type: [String, Number], required: true },
})

const auth = useAuth()

const hilo = ref([])
const anuncio = ref(null)
const estado = reactive({ cargando: true, error: null })
const texto = ref('')
const envio = reactive({ enviando: false, error: null })

// El vendedor tiene que decir a quién responde; el comprador no.
const soyElVendedor = computed(() => auth.usuario?.id === anuncio.value?.seller?.id)

async function cargar() {
  estado.cargando = true
  estado.error = null

  try {
    const [respuestaHilo, respuestaAnuncio] = await Promise.all([
      apiMensajes.hilo(props.anuncioId, props.usuarioId),
      apiAnuncios.ver(props.anuncioId),
    ])

    hilo.value = respuestaHilo.data
    anuncio.value = respuestaAnuncio.data
  } catch (error) {
    estado.error = error.message
  } finally {
    estado.cargando = false
  }
}

async function enviar() {
  envio.enviando = true
  envio.error = null

  try {
    const nuevo = (
      await apiMensajes.enviar(
        props.anuncioId,
        texto.value,
        soyElVendedor.value ? props.usuarioId : null,
      )
    ).data

    hilo.value.push(nuevo)
    texto.value = ''
  } catch (error) {
    envio.error = error.errorDe('message') ?? error.errorDe('receiver_id') ?? error.message
  } finally {
    envio.enviando = false
  }
}

onMounted(cargar)
</script>

<template>
  <RouterLink class="texto-pequeno" :to="{ name: 'mensajes' }">← Todos los mensajes</RouterLink>

  <h1 v-if="anuncio" class="titulo">
    {{ anuncio.car.brand }} {{ anuncio.car.model }}
    <span class="precio">{{ enEuros(anuncio.price) }}</span>
  </h1>

  <CargandoOVacio :cargando="estado.cargando" :error="estado.error" />

  <div v-if="!estado.cargando" class="pila">
    <ul class="hilo">
      <li v-for="mensaje in hilo" :key="mensaje.id" :class="['burbuja', { mia: mensaje.is_mine }]">
        <p class="sin-margen">{{ mensaje.message }}</p>
        <span class="texto-pequeno texto-suave">{{ enFechaYHora(mensaje.created_at) }}</span>
      </li>
    </ul>

    <form class="tarjeta" @submit.prevent="enviar">
      <div class="tarjeta-cuerpo pila">
        <div class="campo">
          <label for="respuesta">Responder</label>
          <textarea id="respuesta" v-model="texto" />
          <p v-if="envio.error" class="campo-error">{{ envio.error }}</p>
        </div>

        <button class="boton" type="submit" :disabled="envio.enviando || texto.trim().length < 2">
          {{ envio.enviando ? 'Enviando…' : 'Enviar' }}
        </button>
      </div>
    </form>
  </div>
</template>

<style scoped>
.titulo { font-size: var(--texto-2xl); display: flex; gap: var(--espacio-4); align-items: baseline; flex-wrap: wrap; }

.hilo { list-style: none; margin: 0; padding: 0; display: grid; gap: var(--espacio-3); }

.burbuja {
  max-width: 70%;
  padding: var(--espacio-3) var(--espacio-4);
  border: 1px solid var(--color-borde);
  border-radius: var(--radio-lg);
  background: var(--color-superficie);
}

.burbuja.mia {
  margin-left: auto;
  background: var(--color-primario-suave);
  border-color: var(--color-primario-suave);
}

.sin-margen { margin: 0; }

@media (max-width: 640px) {
  .burbuja { max-width: 90%; }
}
</style>
